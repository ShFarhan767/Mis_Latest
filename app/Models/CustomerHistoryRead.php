<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerHistoryRead extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'user_id',
        'last_read_history_id',
    ];
}
