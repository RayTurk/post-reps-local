<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\NotificationService;

class OrderCompletedEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $notificationService;
    protected $order;
    protected $orderType;
    protected $orderStatus;
    protected $outOfInventory;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($order, $orderType, $orderStatus, $outOfInventory)
    {
       $this->notificationService = new NotificationService();
       $this->order = $order;
       $this->orderType = $orderType;
       $this->orderStatus = $orderStatus;
       $this->outOfInventory = $outOfInventory;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->notificationService->orderCompleted(
            $this->order,
            $this->orderType,
            $this->orderStatus,
            $this->outOfInventory
        );
    }
}
