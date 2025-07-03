<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RepairOrderAdjustment extends Model
{
    use HasFactory;

    protected $fillable = [
        'repair_order_id',
        'description',
        'charge',
        'discount'
    ];

    protected $dates = [
        'created_at',
        'updated_at'
    ];

    public function repair_order()
    {
        return $this->belongsTo(RepairOrder::class);
    }
}
