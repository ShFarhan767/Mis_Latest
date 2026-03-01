<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens; // ✅ add this
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;

class Employee extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable; // ✅ include HasApiTokens

    protected $fillable = [
        'name',
        'mobile',
        'email',
        'designation',
        'password',
        'plain_password',
        // 'code',
        'status',
        'role', // add role if you want role-based redirection
    ];

    protected $hidden = [
        'password',
        'remember_token', // ✅ needed for Sanctum tokens
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($employee) {
            // $employee->code = rand(1000, 9999);
            $employee->password = Hash::make($employee->password);
        });
    }
}
