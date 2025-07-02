<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuthorizenetPaymentProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'payment_profile_id',
        'order_id',
        'order_type',
        'authorizenet_profile_id',
        'card_shared_with',
        'office_card_visible_agents'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
