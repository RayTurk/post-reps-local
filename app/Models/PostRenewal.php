<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PostRenewal extends Model
{
    use HasFactory;

    protected $fillable = [
        "post_id",
        "order_id",
        "amount"
    ];

    protected $dates = [
        'created_at',
        'updated_at'
    ];
}
