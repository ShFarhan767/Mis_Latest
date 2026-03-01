<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'content',
        'created_by',
    ];

    // Relation to Client
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    // Relation to User (who added the note)
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
