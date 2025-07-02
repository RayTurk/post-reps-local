<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\{ServiceSetting, Invoice, InvoiceAdjustments};
use App\Services\InvoiceService;

class ProcessLateFees extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'process:late:fees';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Apply late fee to invoices';

    protected $invoiceService;
    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(InvoiceService $invoiceService)
    {
        parent::__construct();

        $this->invoiceService = $invoiceService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $settings = ServiceSetting::first();

        $defaultDueDate = $settings->default_invoice_due_date_days;
        $gracePeriod = $settings->grace_period_days;
        $daysUntilLate = $defaultDueDate + $gracePeriod;
        $lateFeeAmount = $settings->late_fee_amount;
        $lateFeePercent = $settings->late_fee_percent;

        $invoices = Invoice::where('fully_paid', false)
            ->where('visible', true)
            ->where('void', false)
            ->get();

        $adjustment = [];
        foreach($invoices as $invoice) {
            //If latestFee found then use that date, otherwise use invoice due date
            $latestFee = $this->invoiceService->latestFee((int) $invoice->id);
            $daysPassed = $invoice->created_at->diffInDays(now(), false);
            if ($latestFee) {
                $daysPassed = $latestFee->created_at->diffInDays(now(), false);
            }

            //dd($invoice->invoice_number.' - '.$daysUntilLate.' - '.$daysPassed);

            if ($daysPassed > $daysUntilLate) {
                //Apply either amount or percentage, whichever is greater
                $lateFee = $lateFeeAmount;
                $feePercent = $invoice->amount * $lateFeePercent / 100;
                if ($feePercent > $lateFeeAmount) {
                    $lateFee = $feePercent;
                }

                $adjustment['invoice_id'] = $invoice->id;
                $adjustment['description'] = 'Late Charge';
                $adjustment['amount'] = $lateFee;
                $adjustment['type'] = InvoiceAdjustments::TYPE_LATE_FEE;

                $this->invoiceService->createInvoiceAdjustment($adjustment);
                //die;
            }
        }
    }
}
