<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientOperatorHistory extends Model
{
    protected $fillable = [
        'client_id',
        'old_operator_name',
        'old_operator_number',
        'new_operator_name',
        'new_operator_number',
    ];
}
