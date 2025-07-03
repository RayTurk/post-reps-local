<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\{
    InvoiceService,
    AuthorizeNetService,
    PostService,
    OrderService,
    NotificationService
};

class ProcessRenewalFee extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'process:renewal:fee';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Charge for post renewal fee';

    protected $invoiceService;
    protected $authorizeNetService;
    protected $postService;
    protected $orderService;
    protected $notificationService;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(
        InvoiceService $invoiceService,
        AuthorizeNetService $authorizeNetService,
        PostService $postService,
        OrderService $orderService,
        NotificationService $notificationService
    ) {
        parent::__construct();

        $this->invoiceService = $invoiceService;
        $this->authorizeNetService = $authorizeNetService;
        $this->postService = $postService;
        $this->orderService = $orderService;
        $this->notificationService = $notificationService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        //Get all completed install orders not removed
        $renewalData = $this->orderService->getDataForRenewalFee();

        foreach ($renewalData as $data) {
            //info($data);
            //Renewal fee is either saved under orders table or under posts table
            $renewalFee = !empty($data->post_renewal_fee) ? $data->post_renewal_fee : $data->renewal_fee;
            $frequency = $data->time_days;

            if ($renewalFee > 0 && $frequency > 0) {
                //Check the last renewal transaction
                $latestRenewal = $this->postService->latestRenewal(
                    (int) $data->post_id,
                    (int) $data->order_id
                );

                //If latestRenewal found then use that date, otherwise use date order was completed
                $daysPassed = $data->date_completed->diffInDays(now(), false);
                if ($latestRenewal) {
                    $daysPassed = $latestRenewal->created_at->diffInDays(now(), false);
                }

                $order = $this->orderService->findById((int) $data->order_id);
                $payer = $this->orderService->getOrderPayer($order, 'install');

                if ($daysPassed > $frequency) {
                    $invoiceData['invoice_number'] = $this->invoiceService->incrementInvoiceNumber($order->invoice_number);
                    //dd($invoiceData['invoice_number']);
                    $invoiceData['office_id'] = $order->office->id;
                    $invoiceData['agent_id'] = $order->agent->id ?? null;
                    $invoiceData['due_date'] = now()->format('Y-m-d');
                    $invoiceData['invoice_type'] = $this->invoiceService->invoiceTypeSingleOrder();
                    $invoiceData['line_items'][0]['description'] = "Post renewal fee: $data->post_name";
                    $invoiceData['line_items'][0]['amount'] = $renewalFee;
                    $invoiceData['line_items'][0]['order_id'] = $order->id;
                    $invoiceData['line_items'][0]['order_type'] = $order::INSTALL_ORDER;
                    $invoiceData['line_items'][0]['missing_items'] = true;

                    //info($daysPassed);
                    //Try to charge saved card if there is any
                    if ($payer->authorizenet_profile_id) {
                        $customerPaymentProfileId = $this->orderService->getPaymentProfile(
                            $data->order_id,
                            'install'
                        );

                        $capture = $this->authorizeNetService->chargeCustomerProfile(
                            $payer->authorizenet_profile_id,
                            $customerPaymentProfileId,
                            $order,
                            $renewalFee
                        );

                        if ($capture['messages']['resultCode'] == "Ok") {
                            //Create invoice and mark as paid
                            $invoice = $this->invoiceService->generateInvoice($invoiceData);
                            $paymentData = [
                                'invoice_id' => $invoice->id,
                                'total' => $renewalFee,
                                'payment_method' =>  $this->invoiceService->invoicePaymentTypeCard(),
                                'card_type' =>  $order->card_type,
                                'card_last_four' => $order->card_last_four,
                                'transaction_id' => $order->auth_transaction_id,
                                'payment_profile' => $customerPaymentProfileId
                            ];
                            $this->invoiceService->createInvoicePayment($paymentData);
                        } else {
                            //Create HIDDEN invoice if charge fails
                            $invoiceData['visible'] = false;
                            $invoiceData['line_items'][0]['visible'] = false;
                            $invoice = $this->invoiceService->generateInvoice($invoiceData);
                        }
                    } else {
                        //Create HIDDEN invoice if charge fails
                        $invoiceData['visible'] = false;
                        $invoiceData['line_items'][0]['visible'] = false;
                        $invoice = $this->invoiceService->generateInvoice($invoiceData);
                    }

                    //Record renewal transaction
                    $this->postService->createRenewalTransaction([
                        'post_id' => $data->post_id,
                        'order_id' => $data->order_id,
                        'amount' => $renewalFee,
                    ]);
                }

                /********************** SEND EMAIL REMINDER FOR RENEWALS *********************/
                //Get next renewal date
                $lastDate = $data->date_completed;
                if ($latestRenewal) {
                    $lastDate = $latestRenewal->created_at;
                }
                $nextRenewalDate = $lastDate->addDays($frequency);

                $daysUntilRenewal = now()->diffInDays($nextRenewalDate, false);

                if ($daysUntilRenewal == 7) {
                    $this->notificationService->sendPostRenewalReminder(
                        $order,
                        $payer,
                        $renewalFee,
                        $nextRenewalDate
                    );
                }
            }
        }
    }
}
