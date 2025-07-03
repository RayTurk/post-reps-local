<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\NotificationService;

class OrderEmails implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $notificationService;
    protected $order;
    protected $orderType;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($order, $orderType)
    {
       $this->notificationService = new NotificationService();
       $this->order = $order;
       $this->orderType = $orderType;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if ($this->orderType == 'install') {
            $this->notificationService->orderCreated($this->order);
        }

        if ($this->orderType == 'repair') {
            $this->notificationService->repairOrderCreated($this->order);
        }

        if ($this->orderType == 'removal') {
            $this->notificationService->removalOrderCreated($this->order);
        }

        if ($this->orderType == 'delivery') {
            $this->notificationService->deliveryOrderCreated($this->order);
        }
    }
}
