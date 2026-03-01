<?php

namespace App\Models;

use App\Models\OfferConnect;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'designation',
        'email',
        'shop_type',
        'country_name',
        'locations',
        'lead_source',
        'interest_level',
        'service_type',
        'feature_need',
        'our_commitment',
        'offer_connect',
        'client_behaviour',
        'status',
        'staff_status',
        'last_contact_date',
        'next_follow_up_date',
        'last_discuss_note',
        'created_by',
        // ✅ ADD THIS
        'assigned_staff_id',
    ];

    protected $casts = [
        'service_type' => 'array',
    ];

    public function numbers()
    {
        return $this->hasMany(CustomerNumber::class);
    }

    public function designation()
    {
        return $this->belongsTo(Designation::class);
    }

    public function shopType()
    {
        return $this->belongsTo(ShopType::class);
    }

    public function leadSource()
    {
        return $this->belongsTo(LeadSource::class);
    }

    public function interestLevel()
    {
        return $this->belongsTo(InterestLevel::class);
    }

    public function serviceTypes()
    {
        return $this->belongsToMany(ServiceType::class);
    }

    public function offerConnect()
    {
        return $this->belongsTo(OfferConnect::class, 'offer_connect_id');
    }

    public function staffStatusHistories()
    {
        return $this->hasMany(CustomerStaffStatusHistory::class);
    }

    public function assignedStaff()
    {
        return $this->belongsTo(User::class, 'assigned_staff_id');
    }

    public function assignHistories()
    {
        return $this->hasMany(CustomerAssignHistory::class);
    }

    public function histories()
    {
        return $this->hasMany(CustomerHistory::class, 'customer_id');
    }
}
