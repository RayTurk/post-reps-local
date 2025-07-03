<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DateTimeInterface;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'office_id',
        'agent_id',
        'invoice_number',
        'due_date',
        'fully_paid',
        'void',
        'visible',
        'amount',
        'missing_items',
        'invoice_type'
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'due_date'
    ];

    protected $appends = ['office_name', 'agent_name'];

    const FULLY_PAID = 1;
    const UNPAID = 0;
    const VOID = 1;

    const INVOICE_TYPE_SINGLE_ORDER = 1;

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function repair_order()
    {
        return $this->belongsTo(RepairOrder::class);
    }

    public function removal_order()
    {
        return $this->belongsTo(RemovalOrder::class);
    }

    public function delivery_order()
    {
        return $this->belongsTo(DeliveryOrder::class);
    }

    public function invoice_lines()
    {
        return $this->hasMany(InvoiceLine::class);
    }

    public function payments()
    {
        return $this->hasMany(InvoicePayments::class);
    }

    public function adjustments()
    {
        return $this->hasMany(InvoiceAdjustments::class);
    }

    public function office()
    {
        return $this->belongsTo(Office::class);
    }

    public function agent()
    {
        return $this->belongsTo(Agent::class);
    }

    public function total()
    {
        $charges = $this->invoice_lines->sum('amount');
        $adjustments = $this->adjustments->sum('amount');
        $payments = $this->payments->sum('total');

        $amountDue = $charges + $adjustments - $payments;
        if ($amountDue == 0) {
            $this->fully_paid = true;
            $this->save();
        }

        return $amountDue;
    }

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function invoice_email_histories()
    {
        return $this->hasMany(InvoiceEmailHistory::class)->orderByDesc('created_at');
    }

    public function getOfficeNameAttribute()
    {
        return $this->office->user->name;
    }

    public function getAgentNameAttribute()
    {
        $name = '';

        if ($this->agent !== null) {
            $name = $this->agent->user->name;
        }

        return $name;
    }
}
