<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CustomerNumber extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'number',
        'full_number',
        'type',
        'country_code',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
