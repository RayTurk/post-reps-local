<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        "address",
        "service_date_type",
        "service_date",
        "office_id",
        "comment",
        "delivery_fee",
        "zone_fee",
        "rush_fee",
        "total",
        "order_number",
        "latitude",
        "longitude",
        "user_id",
        "agent_id",
        'zone_id',
        'status',
        'assigned_to',
        'stop_number',
        'rating',
        'feedback',
        'feedback_date',
        'feedback_published',
        'invoiced',
        'fully_paid',
        'auth_transaction_id',
        'date_completed',
        'card_last_four',
        'card_type',
        'invoice_number',
        'authorized_amount',
        'to_be_invoiced',
        'action_needed'
    ];

    protected $dates = [
        'created_at',
        'service_date',
        'date_completed'
    ];

    // status
    const STATUS_RECEIVED    = 0;
    const STATUS_INCOMPLETE  = 1;
    const STATUS_SCHEDULED   = 2;
    const STATUS_COMPLETED   = 3;
    const STATUS_CANCELLED   = 4;

    const PICKUP = 0;
    const DELIVERY = 1;

    public function agent()
    {
        return $this->hasOne(Agent::class, 'id', 'agent_id')->with('user');
    }
    public function office()
    {
        return $this->hasOne(Office::class, 'id', 'office_id')->with('user');
    }

    public function getStatus()
    {
        switch ($this->status) {
            case self::STATUS_RECEIVED:
                return "Received";
                break;
            case self::STATUS_INCOMPLETE:
                return "Incomplete";
                break;
            case self::STATUS_SCHEDULED:
                return "Scheduled";
                break;
            case self::STATUS_COMPLETED:
                return "Completed";
                break;
            case self::STATUS_CANCELLED:
                return "Cancelled";
                break;
            default:
                return "Received";
                break;
        }
    }

    public function panels()
    {
        return $this->hasMany(DeliveryOrderPanel::class)
            ->join('panels', 'delivery_order_panels.panel_id', 'panels.id')
            //->where('panels.status', Panel::STATUS_ACTIVE)
            ->select('delivery_order_panels.*');
    }

    public function payments()
    {
        return $this->hasMany(DeliveryOrderPayment::class);
    }

    public function adjustments()
    {
        return $this->hasMany(DeliveryOrderAdjustment::class);
    }

    public function pickups()
    {
        return $this->hasMany(DeliveryOrderPanel::class)
        ->where('pickup_delivery', self::PICKUP)
        ->with('panel');
    }

    public function dropoffs()
    {
        return $this->hasMany(DeliveryOrderPanel::class)
        ->where('pickup_delivery', self::DELIVERY)
        ->with('panel');
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function installer()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
