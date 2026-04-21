<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'shop_id',
        'shop_name',
        'title',
        'details',
        'status',
        'start_date',
        'image_path',
        'reissue_comment',
        'decline_note',
        'approve_note',
        'declined_trash_note',
        'complete_note', // ✅ added
        'cancelled_note', // ✅ added
        'approved_note', // ✅ added
        'created_by',
    ];

    protected $appends = ['image_url'];

    public function taskAssignments()
    {
        return $this->hasMany(TaskAssignment::class);
    }

    public function getImageUrlAttribute()
    {
        return $this->image_path ? asset($this->image_path) : null;
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function shop()
    {
        return $this->belongsTo(Client::class, 'shop_id');
    }

    public function notes()
    {
        return $this->hasMany(TaskNote::class, 'task_id', 'id');
    }
}
