<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RemovalOrderAdjustment extends Model
{
    use HasFactory;

    protected $fillable = [
        'removal_order_id',
        'description',
        'charge',
        'discount'
    ];

    protected $dates = [
        'created_at',
        'updated_at'
    ];

    public function removal_order()
    {
        return $this->belongsTo(RemovalOrder::class);
    }
}
