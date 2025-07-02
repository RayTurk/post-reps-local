<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        "install",
        "repair",
        "removal",
        "delivery",
        'rush_order',
        'repair_rush_order',
        'repair_trip_fee',
        'repair_replace_post',
        'relocate_post',
        'removal_rush_order',
        'removal_fee',
        'discount_extra_post_removal',
        'delivery_rush_order',
        'delivery_trip_fee',
        'install_points',
        'repair_points',
        'removal_points',
        'delivery_points',
        'late_fee_amount',
        'late_fee_percent',
        'default_invoice_due_date_days',
        'grace_period_days',
        'daily_order_cap',
        'convenience_fee',
        'additional_pickup_fee',
    ];



    const DEFAULT_SETTING = [
        "install" => 100,
        "repair" => 50,
        "removal" => 0,
        "delivery" => 50,
        "rush_order" => 0,
        "repair_rush_order" => 0,
        "repair_trip_fee" => 0
    ];
}
