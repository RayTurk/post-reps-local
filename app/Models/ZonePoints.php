<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ZonePoints extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'zone_id',
        'points'
    ];

    protected $dates = [
        'created_at',
        'updated_at'
    ];
}
