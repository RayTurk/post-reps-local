<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RepairOrderAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'repair_order_id',
        'file_name'
    ];
}
