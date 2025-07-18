<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderAttacment extends Model
{
    use HasFactory;

    protected $fillable = [
        "order_id",
        "name"
    ];
}
