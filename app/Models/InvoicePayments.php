<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoicePayments extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'total',
        'payment_method',
        'reversed',
        'check_number',
        'comments',
        'card_type',
        'card_last_four',
        'invoice_balance',
        'transaction_id',
        'payment_profile'
    ];

    protected $dates = [
        'created_at',
        'updated_at'
    ];

    const CHECK = 0;
    const CREDIT_CARD = 1;
    const BALANCE = 2;

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}
