<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DateTimeInterface;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        "address",
        "property_type",
        "desired_date_type",
        "desired_date",
        "office_id",
        "post_id",
        "panel_id",
        "comment",
        "signage_fee",
        "zone_fee",
        "total",
        "order_number",
        "latitude",
        "longitude",
        "agent_own_sign",
        "sign_at_property",
        "user_id",
        "agent_id",
        'zone_id',
        'rush_fee',
        'ignore_zone_fee',
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
        'post_renewal_fee',
        'removal_fee',
        'to_be_invoiced',
        'action_needed'
    ];

    protected $dates = [
        'created_at',
        'desired_date',
        'date_completed',
        'updated_at'
    ];

    // status
    const STATUS_RECEIVED    = 0;
    const STATUS_INCOMPLETE  = 1;
    const STATUS_SCHEDULED   = 2;
    const STATUS_COMPLETED   = 3;
    const STATUS_CANCELLED   = 4;

    // order type
    const INSTALL_ORDER      =  1;
    const REPAIR_ORDER       =  2;
    const REMOVAL_ORDER      =  3;
    const DELIVERY_ORDER     =  4;
    const ORDER_TYPE_DEFAULT = self::INSTALL_ORDER;

    //property type
    const EXISTING_HOME_CONDO   =  1;
    const NEW_CONSTRUCTION      =  2;
    const VACANT_LAND           =  3;
    const COMMERCIAL_INDUSTRIAL =  4;

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

    public function getOrderType()
    {
        switch ($this->order_type) {
            case self::INSTALL_ORDER:
                return "install";
                break;
            case self::REPAIR_ORDER:
                return "repair";
                break;
            case self::REMOVAL_ORDER:
                return "removal";
                break;
            case self::DELIVERY_ORDER:
                return "delivery";
                break;
            default:
                return "install";
                break;
        }
    }
    static $yearLatter =  [
        2021 => "A",
        2022 => "B",
        2023 => "C",
        2024 => "D",
        2025 => "E",
        2026 => "F",
        2027 => "G",
        2028 => "H",
        2029 => "I",
        2030 => "J",
        2031 => "K",
        2032 => "L",
        2034 => "M",
        2035 => "N",
        2036 => "O",
        2037 => "P",
        2038 => "Q",
        2039 => "R",
        2040 => "S",
        2041 => "T",
        2042 => "U",
        2043 => "V",
        2044 => "W",
        2045 => "X",
        2046 => "Y",
        2047 => "Z",
    ];

    static function getYearLatter($year)
    {
        return isset(self::$yearLatter[$year]) ? self::$yearLatter[$year] : null;
    }

    public function accessories()
    {
        return $this->hasMany(OrderAccessory::class, 'order_id', 'id')->with('accessory');
    }

    public function files()
    {
        return $this->hasMany(OrderAttachment::class, 'order_id', 'id');
    }

    public function attachments()
    {
        return $this->hasMany(OrderAttachment::class);
    }


    public function platMapFiles()
    {
        return $this->hasMany(OrderAttachment::class, 'order_id', 'id')->where('plat_map', 1);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    public function panel()
    {
        return $this->belongsTo(Panel::class);
    }

    public function zone()
    {
        return $this->belongsTo(Zone::class);
    }

    public function repair()
    {
        return $this->hasOne(RepairOrder::class)->latest()->with('panel')
        ->with('accessories')
        ->with('adjustments');
    }

    public function removal()
    {
        return $this->hasOne(RemovalOrder::class)->latest()->with('adjustments');
    }

    public function repair_completed()
    {
        return $this->hasOne(RepairOrder::class)->where('status', RepairOrder::STATUS_COMPLETED);
    }

    public function repair_cancelled()
    {
        return $this->hasOne(RepairOrder::class)->where('status', RepairOrder::STATUS_CANCELLED);
    }

    public function repair_incomplete()
    {
        return $this->hasOne(RepairOrder::class)->where('status', RepairOrder::STATUS_INCOMPLETE);
    }

    public function repair_scheduled()
    {
        return $this->hasOne(RepairOrder::class)->where('status', RepairOrder::STATUS_SCHEDULED);
    }

    public function repair_received()
    {
        return $this->hasOne(RepairOrder::class)->where('status', RepairOrder::STATUS_RECEIVED);
    }

    public function removal_completed()
    {
        return $this->hasOne(RemovalOrder::class)->where('status', RemovalOrder::STATUS_COMPLETED);
    }

    public function removal_cancelled()
    {
        return $this->hasOne(RemovalOrder::class)->where('status', RemovalOrder::STATUS_CANCELLED);
    }

    public function adjustments()
    {
        return $this->hasMany(OrderAdjustment::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function installer()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function latest_completed_repair()
    {
        return $this->hasOne(RepairOrder::class)
            ->where('status', RepairOrder::STATUS_COMPLETED)
            ->latest()
            ->first();
    }

    public function latest_repair()
    {
        return $this->hasOne(RepairOrder::class)->latest();
    }

    public function latest_removal()
    {
        return $this->hasOne(RemovalOrder::class)->latest();
    }

    public function attachmentsWithoutPlatMap()
    {
        return $this->hasMany(OrderAttachment::class)->where('plat_map', 0);
    }
}
