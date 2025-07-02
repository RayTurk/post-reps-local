<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceEmailHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
    ];

    protected $dates = [
        'created_at'
    ];
}
