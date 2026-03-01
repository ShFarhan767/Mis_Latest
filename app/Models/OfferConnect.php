<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OfferConnect extends Model
{
    use HasFactory;

    protected $table = 'offer_connects'; // explicitly set table name
    protected $fillable = ['name', 'status'];
}
