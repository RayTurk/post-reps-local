<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OfficeEmailSettings extends Model
{
    use HasFactory;

    protected $fillable = [
        'office_id',
        'email',
        'order',
        'accounting',
    ];
}
