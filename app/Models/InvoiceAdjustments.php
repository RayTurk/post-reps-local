<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceAdjustments extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'description',
        'amount',
        'type'
    ];

    protected $dates = [
        'created_at',
        'updated_at'
    ];

    const TYPE_REGULAR = 1;
    const TYPE_LATE_FEE = 2;
    const DEFAULT_TYPE = self::TYPE_REGULAR;

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}
