<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerHistory extends Model
{
    use HasFactory;

    protected $fillable = ['customer_id', 'staff_id', 'note', 'old_data'];
    protected $casts = ['old_data' => 'array'];

    public function staff()
    {
        return $this->belongsTo(User::class, 'staff_id')
                    ->where('role', 'staff'); // optional
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
