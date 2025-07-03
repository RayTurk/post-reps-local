<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notice extends Model
{
    use HasFactory;

    protected $fillable = [
        "start_date",
        "end_date",
        "subject",
        "details",
    ];

    protected $dates = [
        "start_date",
        "end_date"
    ];
}
