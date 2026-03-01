<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerStaffStatusHistory extends Model
{
    protected $fillable = [
        'customer_id',
        'old_status',
        'new_status',
        'changed_by',
        'changed_at',
    ];

    protected $dates = ['changed_at'];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
