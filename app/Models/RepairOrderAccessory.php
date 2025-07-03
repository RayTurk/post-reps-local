<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RepairOrderAccessory extends Model
{
    use HasFactory;

    protected $fillable = [
        'repair_order_id',
        'accessory_id',
        'action'
    ];

    const ACTION_REMOVE = 1;
    const ACTION_ADD_REPLACE = 0;

    public function accessory()
    {
        return $this->belongsTo(Accessory::class);
    }

    public function repair_order()
    {
        return $this->belongsTo(RepairOrder::class);
    }
}
