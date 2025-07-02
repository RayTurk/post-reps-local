<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateInventoryInFieldRepairJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $orderService;
    protected $items;
    protected $repairOrder;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($items, $repairOrder, $orderService)
    {
        $this->orderService = $orderService;
        $this->items = $items;
        $this->repairOrder = $repairOrder;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //Need to update field Qty for panel
        if (isset($this->items['panelId'])) {
            $panelId = $this->items['panelId'];
            if ($panelId) {
                $this->orderService->recalculatePanelInFieldRepair(
                    (int) $panelId,
                    $this->repairOrder
                );
            }
        }

        //Need to update field Qty for accessories
        if (isset($this->items['accessories'])) {
            $accessories = $this->items['accessories'];
            if (count($accessories)) {
                foreach ($accessories as $accessoryId) {
                    $this->orderService->recalculateAccessoryInFieldRepair(
                        (int) $accessoryId,
                        $this->repairOrder
                    );
                }
            }
        }
    }
}
