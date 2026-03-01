<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessType extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'status'];

    // Optional: relation to clients
    public function clients()
    {
        return $this->hasMany(Client::class, 'business_type_id');
    }
}
