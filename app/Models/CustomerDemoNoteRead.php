<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CustomerDemoNoteRead extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'user_id',
        'last_read_note_id',
    ];
}

