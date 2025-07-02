<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Traits\HelperTrait;
use App\Services\InvoiceService;
use App\Services\OfficeService;
use App\Models\ServiceSetting;
use App\Models\User;
use App\Services\PanelService;
use App\Services\PostService;
use App\Services\AccessoryService;
use App\Services\AuthorizeNetService;
use App\Services\RefundQueueService;

class AccountingPaymentsController extends Controller
{

    use HelperTrait;

    protected $invoiceService;
    protected $officeService;
    protected $postService;
    protected $panelService;
    protected $accessoryService;
    protected $authorizeNetService;
    protected $refundQueueService;

    public function __construct(
        InvoiceService $invoiceService,
        OfficeService $officeService,
        PostService $postService,
        PanelService $panelService,
        AuthorizeNetService $authorizeNetService,
        AccessoryService $accessoryService,
        RefundQueueService $refundQueueService
    ) {
        $this->invoiceService = $invoiceService;
        $this->officeService = $officeService;
        $this->postService = $postService;
        $this->panelService = $panelService;
        $this->accessoryService = $accessoryService;
        $this->authorizeNetService = $authorizeNetService;
        $this->refundQueueService = $refundQueueService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $authUser = auth()->user();

        if ($authUser->role == User::ROLE_SUPER_ADMIN) {
            $offices = $this->officeService->getAll();
            $posts = $this->postService->getOrderByListingOrderAndName();
            $panels = $this->panelService->getOrderByListingOrderAndName();
            $accessories = $this->accessoryService->getOrderByListingOrderAndName();

            $serviceSettings = $service_settings = ServiceSetting::first();

            $data = compact('offices', 'accessories', 'panels', 'posts', 'service_settings', 'serviceSettings');

            return view('accounting.payments.index', $data);
        }

        if ($authUser->role == User::ROLE_OFFICE) {
            $agents = $this->officeService->getAgents($authUser->office->id);
            $posts = $this->postService->getOrderByListingOrderAndName();
            $panels = $this->panelService->getOrderByListingOrderAndName();
            $accessories = $this->accessoryService->getOrderByListingOrderAndName();

            $serviceSettings = $service_settings = ServiceSetting::first();

            $data = compact('agents', 'accessories', 'panels', 'posts', 'service_settings', 'serviceSettings');

            return view('accounting.payments.office.index', $data);
        }

        if ($authUser->role == User::ROLE_AGENT) {
            $agents = $this->officeService->getAgents($authUser->agent->office->id);
            $posts = $this->postService->getOrderByListingOrderAndName();
            $panels = $this->panelService->getOrderByListingOrderAndName();
            $accessories = $this->accessoryService->getOrderByListingOrderAndName();

            $serviceSettings = $service_settings = ServiceSetting::first();

            $data = compact('agents', 'accessories', 'panels', 'posts', 'service_settings', 'serviceSettings');

            return view('accounting.payments.agent.index', $data);
        }
    }

    public function datatable()
    {
        return $this->invoiceService->paymentsDatatable();
    }

    public function reverseBalancePayment($paymentId)
    {
        $this->invoiceService->reverseBalancePayment($paymentId);

        $url = '/accounting/payments';
        return $this->backWithUrlSuccess($url, 'Payment reversed successfully.');
    }

    public function reverseCheckPayment($paymentId)
    {
        $this->invoiceService->reverseCheckPayment($paymentId);

        $url = '/accounting/payments';
        return $this->backWithUrlSuccess($url, 'Payment reversed successfully.');
    }

    public function reverseCardPayment($paymentId)
    {
        $invoicePayment = $this->invoiceService->findInvoicePaymentById($paymentId);
        $payer = $this->invoiceService->getInvoicePayer($invoicePayment->invoice_id);

        $customerPaymentProfileId = $invoicePayment->payment_profile;
        $authorizeNetCustomerId = $payer->authorizenet_profile_id;
        $transId = $invoicePayment->transaction_id;

        $refund = $this->authorizeNetService->refundCardPayment(
            $authorizeNetCustomerId,
            $customerPaymentProfileId,
            $transId,
            $invoicePayment->total
        );
        //dd($refund);

        if ($refund['messages']['resultCode'] == "Error") {
            if (isset($refund['transactionResponse']['errors'][0]['errorText'])) {
                logger()->error("Refund transId={$transId}: {$refund['transactionResponse']['errors'][0]['errorText']}");
                //return $this->backWithError($refund['transactionResponse']['errors'][0]['errorText']);
            }

            if (isset($refund['messages']['message'][0]['text'])) {
                logger()->error("Refund transId={$transId}: {$refund['messages']['message'][0]['text']}");
                //return $this->backWithError($refund['messages']['message'][0]['text']);
            }

            //if it gets an error then add to refund queue to be processed by refund cron
            $this->refundQueueService->create([
                'customer_profile' => $authorizeNetCustomerId,
                'payment_profile' => $customerPaymentProfileId,
                'transaction_id' => $transId,
                'amount' =>  $invoicePayment->total
            ]);
        }

        $this->invoiceService->reverseCardPayment($invoicePayment);

        $url = '/accounting/payments';
        return $this->backWithUrlSuccess($url, 'Payment reversed successfully.');
    }

    public function reverseCardPaymentPartial(Request $request, $paymentId)
    {
        //dd($request->all());
        $message = 'Payment reversed successfully.';

        $refundAmount = $request->card_refund_amount;
        if ($refundAmount) {
            $invoicePayment = $this->invoiceService->findInvoicePaymentById($paymentId);
            $payer = $this->invoiceService->getInvoicePayer($invoicePayment->invoice_id);

            if ($refundAmount > $invoicePayment->total) {
                return $this->backWithError('Refund amount cannot be more than amount paid.');
            }

            $customerPaymentProfileId = $invoicePayment->payment_profile;
            $authorizeNetCustomerId = $payer->authorizenet_profile_id;
            $transId = $invoicePayment->transaction_id;

            $refund = $this->authorizeNetService->refundCardPayment(
                $authorizeNetCustomerId,
                $customerPaymentProfileId,
                $transId,
                $refundAmount
            );
            //dd($refund);

            if ($refund['messages']['resultCode'] == "Error") {
                if (isset($refund['transactionResponse']['errors'][0]['errorText'])) {
                    logger()->error("Refund transId={$transId}: {$refund['transactionResponse']['errors'][0]['errorText']}");
                    //return $this->backWithError($refund['transactionResponse']['errors'][0]['errorText']);
                }

                if (isset($refund['messages']['message'][0]['text'])) {
                    logger()->error("Refund transId={$transId}: {$refund['messages']['message'][0]['text']}");
                    //return $this->backWithError($refund['messages']['message'][0]['text']);
                }

                //if it gets an error then add to refund queue to be processed by refund cron
                $this->refundQueueService->create([
                    'customer_profile' => $authorizeNetCustomerId,
                    'payment_profile' => $customerPaymentProfileId,
                    'transaction_id' => $transId,
                    'amount' =>  $refundAmount
                ]);

                $message = "Unable to process the refund instantly. Transaction was added to refund queue to be processed later.";
            }

            $this->invoiceService->reverseCardPaymentPartial($invoicePayment,  (float) $refundAmount);

            $url = '/accounting/payments';
            return $this->backWithUrlSuccess($url, $message);
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
