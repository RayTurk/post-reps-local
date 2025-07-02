<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionSummary extends Model
{
    use HasFactory;

    protected $fillable = [
        'settlement_date',
        'amount',
        'type',
        'transaction_id'
    ];

    protected $dates = [
        'settlement_date'
    ];

    const RECENT = 0;
    const FUTURE = 1;
}
