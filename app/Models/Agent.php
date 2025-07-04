<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Agent extends Model
{
    use HasFactory;

    protected $fillable = [
        're_license',
        'agent_office',
        'inactive',
        'user_id',
        'payment_method',
        'note',
    ];

    // Payment methods
    const PAYMENT_METHOD_PAY_AT_TIME_OF_ORDER = 1;
    const PAYMENT_METHOD_INVOICE = 2;
    const PAYMENT_METHOD_OFFICE_PAY = 3;
    const PAYMENT_METHOD_DEFAULT = self::PAYMENT_METHOD_PAY_AT_TIME_OF_ORDER;

    const STATUS_ACTIVE = 0;
    const STATUS_INACTIVE = 1;

    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    public function office()
    {
        return $this->belongsTo(Office::class,'agent_office','id');
    }

    public function agent_access()
    {
        return $this->hasMany(PostAgent::class, 'agent_id', 'id')->where('access', 1);
    }

    public function accessory_agents()
    {
        return $this->hasMany(AccessoryAgent::class, 'agent_id', 'id')
        ->with('accessory')
        ->where('access', 1);
    }

    public function inventory_lists()
    {
        return $this->hasMany(InventoryList::class);
    }

    public function panel_agents()
    {
        return $this->hasMany(PanelAgent::class)
            ->join('panels', 'panel_agents.panel_id', 'panels.id')
            ->where('panels.status', Panel::STATUS_ACTIVE);
    }

    public function getPanels()
    {

    }
}
