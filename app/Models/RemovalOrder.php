<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RemovalOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'user_id',
        'service_date_type',
        'service_date',
        'sign_panel',
        'removal_fee',
        'zone_fee',
        'rush_fee',
        'total',
        "comment",
        'order_number',
        'status',
        'parent_removal_order',
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
        'action_needed',
        'pickup_address',
        'pickup_latitude',
        'pickup_longitude',
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

    //Sign panel action
    const ADD_TO_INVENTORY = 0;
    const AGENT_REMOVE_LEAVE_AT_PROPERTY = 1;
    const DFEAULT_SIGN_PANEL_ACTION = self::ADD_TO_INVENTORY;

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

    public function order()
    {
        return $this->belongsTo(Order::class)
            ->with('office')
            ->with('agent')
            ->with('accessories')
            ->with('repair', 'repair.accessories')
            ->with('removal')
            ->with('panel')
            ->with('post');
    }

    public function payments()
    {
        return $this->hasMany(RemovalOrderPayment::class);
    }

    public function adjustments()
    {
        return $this->hasMany(RemovalOrderAdjustment::class);
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
