<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\{AuthorizeNetService, RefundQueueService};

class RefundJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $authorizeNetService;
    protected $refundQueueService;
    protected $order;
    protected $orderType;
    protected $orderService;
    protected $refundAmount;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($order, $orderType, $orderService, $refundAmount = 0)
    {
        $this->authorizeNetService = new AuthorizeNetService();
        $this->refundQueueService = new RefundQueueService();
        $this->orderService = $orderService;
        $this->order = $order;
        $this->orderType = $orderType;
        $this->refundAmount = $refundAmount;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $order = $this->order;
        $orderType = $this->orderType;

        //Get payer
        $payer = $this->orderService->getOrderPayer($order, $orderType);
        //info($payer);

        //Try to refund
        $paymentProfileId = $this->orderService->getPaymentProfile($order->id, $orderType);
        //info($paymentProfileId); die;
        $authorizeNetCustomerId = $payer->authorizenet_profile_id;
        //Get transaction Id from order
        $transId = $order->auth_transaction_id;

        //If refundAmount is specified use it, otherwise refund order total
        $refundAmount = $this->refundAmount;
        if ($refundAmount === 0) {
            $refundAmount = $order->total;
        }

        $refund = $this->authorizeNetService->refundCardPayment(
            $authorizeNetCustomerId,
            $paymentProfileId,
            $transId,
            $refundAmount
        );

        if ($refund['messages']['resultCode'] == "Error") {
            //dd($payment['messages']);
            //info($refund['messages']);
            if (isset($refund['transactionResponse']['errors'][0]['errorText'])) {
                logger()->error("Refund order {$order->id}: {$refund['transactionResponse']['errors'][0]['errorText']}");
            }

            if (isset($refund['messages']['message'][0]['text'])) {
                logger()->error("Refund order {$order->id}: {$refund['messages']['message'][0]['text']}");
            }

            //if it gets an error then add to refund queue to be processed by refund cron*/
            $this->refundQueueService->create([
                'customer_profile' => $authorizeNetCustomerId,
                'payment_profile' => $paymentProfileId,
                'transaction_id' => $transId,
                'amount' =>  $refundAmount
            ]);
        }
    }
}
