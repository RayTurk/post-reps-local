<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderAdjustment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'description',
        'charge',
        'discount'
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
