<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'order_id',
        'order_type',
        'amount',
        'description',
        'missing_items'
    ];

    protected $dates = [
        'created_at',
        'updated_at'
    ];

    const ORDER_TYPE_INSTALL = 1;
    const ORDER_TYPE_REPAIR = 2;
    const ORDER_TYPE_REMOVAL = 3;
    const ORDER_TYPE_DELIVERY = 4;

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}
