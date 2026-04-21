<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Area extends Model
{
    protected $fillable = ['area_name', 'status', 'country_name' , 'created_by'];

    public function country()
    {
        return $this->belongsTo(Country::class);
    }
}
