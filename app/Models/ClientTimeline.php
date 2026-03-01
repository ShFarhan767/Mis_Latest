<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientTimeline extends Model
{
    use HasFactory;

    protected $table = 'client_timelines';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'client_id',
        'type',         // e.g., 'note', 'task_pending', 'task_complete', 'client_update'
        'description',  // description of the event
    ];

    /**
     * Get the client associated with this timeline entry.
     */
    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
