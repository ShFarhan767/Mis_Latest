<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'company_name',
        'operator_name',
        'number',
        'oparetor_number',
        'project_name',
        'country_name',
        'area_name',
        'address',
        'referred_by_name',       // ✅ added
        'referred_by_number',     // ✅ added
        'business_type',          // ✅ added (foreign key)
        'status',
    ];

    // tasks relation
    public function tasks()
    {
        return $this->hasMany(Task::class, 'shop_id');
    }

    // business type relation
    public function businessType()
    {
        return $this->belongsTo(BusinessType::class, 'business_type');
    }

    public function notes()
    {
        return $this->hasMany(ClientNote::class, 'client_id'); // assuming 'client_id' is the foreign key in notes table
    }

    public function statusHistories()
    {
        return $this->hasMany(ClientStatusHistory::class);
    }

    public function operatorHistories()
    {
        return $this->hasMany(ClientOperatorHistory::class);
    }
}
