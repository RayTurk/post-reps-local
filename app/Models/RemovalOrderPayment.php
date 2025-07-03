<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RemovalOrderPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        "removal_order_id",
        "paid_by",
        "office_id",
        "agent_id",
        "amount"
    ];

    protected $dates = [
        'created_at',
        'updated_at'
    ];


    public function payer()
    {
        return $this->hasOne(User::class, 'paid_by', 'id');
    }
}
