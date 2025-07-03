<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\NotificationService;
use App\Models\{Order, RepairOrder, RemovalOrder, DeliveryOrder};

class RoutingScheduledEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'route:scheduled:email';

    protected $notificationService;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send email to office/agent when route is scheduled';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->notificationService = new NotificationService();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        //info('Cron worked');
        $scheduledInstalls = Order::where('status', Order::STATUS_SCHEDULED)
        ->where('route_schedule_email', false)
        ->get();
        foreach ($scheduledInstalls as $scheduledInstall) {
            $this->notificationService->orderCreated($scheduledInstall);

            sleep(1);

            $scheduledInstall->route_schedule_email = true;
            $scheduledInstall->save();
        }

        $scheduledRepairs = RepairOrder::where('status', RepairOrder::STATUS_SCHEDULED)
        ->where('route_schedule_email', false)
        ->get();
        foreach ($scheduledRepairs as $scheduledRepair) {
            $this->notificationService->repairOrderCreated($scheduledRepair);

            sleep(1);

            $scheduledRepair->route_schedule_email = true;
            $scheduledRepair->save();
        }

        $scheduledRemovals = RemovalOrder::where('status', RemovalOrder::STATUS_SCHEDULED)
        ->where('route_schedule_email', false)
        ->get();
        foreach ($scheduledRemovals as $scheduledRemoval) {
            $this->notificationService->removalOrderCreated($scheduledRemoval);

            sleep(1);

            $scheduledRemoval->route_schedule_email = true;
            $scheduledRemoval->save();
        }

        $scheduledDeliveries = DeliveryOrder::where('status', DeliveryOrder::STATUS_SCHEDULED)
        ->where('route_schedule_email', false)
        ->get();
        foreach ($scheduledDeliveries as $scheduledDelivery) {
            $this->notificationService->deliveryOrderCreated($scheduledDelivery);

            sleep(1);

            $scheduledDelivery->route_schedule_email = true;
            $scheduledDelivery->save();
        }
    }
}
