<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\{AuthorizeNetService, RefundQueueService};

class RefundQueueCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'process:refund:queue';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process refund queue';

    protected $authorizeNetService;
    protected $refundQueueService;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->refundQueueService = new RefundQueueService();
        $this->authorizeNetService = new AuthorizeNetService();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $refundsQueue = $this->refundQueueService->getAll();

        foreach ($refundsQueue as $refundQueue) {
            $refund = $this->authorizeNetService->refundCardPayment(
                $refundQueue->customer_profile,
                $refundQueue->payment_profile,
                $refundQueue->transaction_id,
                $refundQueue->amount
            );

            if ($refund['messages']['resultCode'] == "Error") {
                if (isset($refund['transactionResponse']['errors'][0]['errorText'])) {
                    logger()->error("Refund transId={$refundQueue->transaction_id}: {$refund['transactionResponse']['errors'][0]['errorText']}");
                }

                if (isset($refund['messages']['message'][0]['text'])) {
                    logger()->error("Refund transId={$refundQueue->transaction_id}: {$refund['messages']['message'][0]['text']}");
                }
            } else {
                //Remove from queue if refund was successful
                $refundQueue->delete();
            }
        }
    }
}
