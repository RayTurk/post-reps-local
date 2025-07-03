<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RefundQueue extends Model
{
    use HasFactory;

    protected $fillable =[
        'customer_profile',
        'payment_profile',
        'transaction_id',
        'amount'
    ];
}
