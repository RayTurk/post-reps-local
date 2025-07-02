<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\AuthorizeNetService;

class VoidCardHoldJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $authorizeNetService;
    protected $order;
    protected $transId;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($order, $transId = null)
    {
        $this->authorizeNetService = new AuthorizeNetService();
        $this->order = $order;
        $this->transId = $transId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $order = $this->order;
        $transId = $this->transId;

        //If not transId was passed then take from order
        if (empty($transId)) {
            $transId = $order->auth_transaction_id;
        }

        $void = $this->authorizeNetService->voidTransaction($transId);

        //info($void);
    }
}
