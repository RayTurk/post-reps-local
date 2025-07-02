<?php

namespace App\Services;

use App\Http\Traits\HelperTrait;
use App\Mail\UnpaidInvoice;
use Carbon\Carbon;
use DB;
use App\Models\RefundQueue;
use Illuminate\Support\Facades\Mail;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Schema;

class RefundQueueService
{
    use HelperTrait;

    public function create(array $attributes)
    {
        $refundQueue = RefundQueue::firstOrCreate(
            ['transaction_id' => $attributes['transaction_id']],
            [
                'customer_profile' => $attributes['customer_profile'],
                'payment_profile' => $attributes['payment_profile'],
                'amount' => $attributes['amount']
            ]
        );

        return $refundQueue;
    }

    public function getAll()
    {
        return RefundQueue::all();
    }

}
