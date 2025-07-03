<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateInventoryInFieldInstallJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $orderService;
    protected $items;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($items, $orderService)
    {
        $this->orderService = $orderService;
        $this->items = $items;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //Need to update field Qty for post
        if (isset($this->items['postId'])) {
            $postId = (int) $this->items['postId'];
            $this->orderService->recalculatePostInFieldInstall($postId);
        }

        //Need to update field Qty for panel
        if (isset($this->items['panelId'])) {
            $panelId = (int) $this->items['panelId'];
            if ($panelId) {
                $this->orderService->recalculatePanelInFieldInstall($panelId);
            }
        }

        //Need to update field Qty for accessories
        if (isset($this->items['accessories'])) {
            $accessories = $this->items['accessories'];
            if (count($accessories)) {
                foreach ($accessories as $accessoryId) {
                    $this->orderService->recalculateAccessoryInFieldInstall(
                        (int) $accessoryId
                    );
                }
            }
        }
    }
}
