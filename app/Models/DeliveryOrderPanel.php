<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryOrderPanel extends Model
{
    use HasFactory;

    protected $fillable = [
        'delivery_order_id',
        'panel_id',
        'quantity',
        'pickup_delivery',
        'existing_new'
    ];

    protected $dates = [
        'created_at',
        'updated_at'
    ];

    const PICKUP = 0;
    const DROPOFF = 1;

    const EXISTING_PANEL = 0;
    const NEW_PANEL = 1;

    public function delivery_order()
    {
        return $this->belongsTo(DeliveryOrder::class);
    }

    public function panel()
    {
        return $this->belongsTo(Panel::class);
    }
}
