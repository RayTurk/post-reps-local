<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AuthorizeNetService;
use App\Models\TransactionSummary;
use Carbon\Carbon;

class TransactionSummaryCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'transaction:summary';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get settled transactions from Authorize.net last 24 hours';

    protected $authorizeNetService;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->authorizeNetService = new AuthorizeNetService();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $startDate = '2022-10-27';

        //Recent transfers - all settled transactions
        $getSettledBatchList = $this->authorizeNetService->getSettledBatchList();
        if (isset($getSettledBatchList['batchList'])) {
            $settledBatchList = $getSettledBatchList['batchList'];
            foreach ($settledBatchList as $batchList) {
                $batchId = $batchList['batchId'];

                $transactionList = $this->authorizeNetService->getTransactionList($batchId)['transactions'];
                foreach ($transactionList as $transaction) {
                    TransactionSummary::where('transaction_id', $transaction['transId'])->delete();

                    $submitTimeLocal = Carbon::parse($transaction['submitTimeLocal'])->format('Y-m-d');
                    if ($submitTimeLocal >= $startDate) { //Ignore all transactions before startDate
                        if ($transaction['transactionStatus'] == 'settledSuccessfully') {
                            TransactionSummary::create([
                                'settlement_date' => $submitTimeLocal,
                                'amount' => $transaction['settleAmount'],
                                'type' => TransactionSummary::RECENT,
                                'transaction_id' => $transaction['transId']
                            ]);
                        }
                    }
                }
            }
        }

        //Future Transfers - all captured but not yet settled transaction
        $getUnsettledTransactionList = $this->authorizeNetService->getUnsettledTransactionList();
        if (isset($getUnsettledTransactionList['transactions'])) {
            $transactionList = $getUnsettledTransactionList['transactions'];
            foreach ($transactionList as $transaction) {
                TransactionSummary::where('transaction_id', $transaction['transId'])->delete();

                $submitTimeLocal = Carbon::parse($transaction['submitTimeLocal'])->format('Y-m-d');
                if ($submitTimeLocal >= $startDate) {//Ignore all transactions before startDate
                    if ($transaction['transactionStatus'] == 'capturedPendingSettlement') {
                        TransactionSummary::create([
                            'settlement_date' => $submitTimeLocal,
                            'amount' => $transaction['settleAmount'],
                            'type' => TransactionSummary::FUTURE,
                            'transaction_id' => $transaction['transId']
                        ]);
                    }
                }
            }
        }
    }
}
