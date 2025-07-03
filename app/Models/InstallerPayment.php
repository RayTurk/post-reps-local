<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstallerPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'check_number',
        'amount',
        'comments',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
