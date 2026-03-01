<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerAssignHistory extends Model
{
    protected $fillable = [
        'customer_id',
        'staff_id',
        'assigned_by',
        'assigned_at',
    ];

    public function staff()
    {
        return $this->belongsTo(User::class, 'staff_id');
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
