<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryOrderAdjustment extends Model
{
    use HasFactory;

    protected $fillable = [
        'delivery_order_id',
        'description',
        'charge',
        'discount'
    ];

    protected $dates = [
        'created_at',
        'updated_at'
    ];

    public function delivery_order()
    {
        return $this->belongsTo(DeliveryOrder::class);
    }
}
