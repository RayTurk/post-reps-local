<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\OrderService;

class MoveUnfinishedOrdersToTodayCmd extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'move:unfinished:order:to:today';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Move unfinished orders to today';

    protected $orderService;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(OrderService $orderService)
    {
        parent::__construct();

        $this->orderService = $orderService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $today = now()->format('Y-m-d');

        //Get all unfinished order from previous day
        $unfinishedOrders = $this->orderService->getUnfinishedOrdersForPreviousDay();
        //dd($unfinishedOrders);

        foreach ($unfinishedOrders as $unfinishedOrder) {
            //Change order service date
            $this->orderService->changeUnfinishedOrderServiceDate(
                $today,
                $unfinishedOrder
            );

            //Push order to start of the route
            $stopNumber = 1;

            $this->orderService->increaseStopNumberInclusive(
                $unfinishedOrder->installer_id,
                $stopNumber,
                $today
            );

            $this->orderService->changeUnfinishedOrderStopNumber($stopNumber, $unfinishedOrder);
        }
    }
}
