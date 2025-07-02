<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CardRejectionCounter extends Model
{
    use HasFactory;

    protected $table = 'card_rejection_counter';

    protected $fillable = [
        'office_id',
        'agent_id',
        'card_last_four',
    ];
}
