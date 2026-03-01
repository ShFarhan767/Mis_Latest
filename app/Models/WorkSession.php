<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkSession extends Model
{
    use HasFactory;

    protected $fillable = ['task_assignment_id', 'start_time', 'stop_time', 'duration_minutes', 'duration_display' , 'status'];

    public function taskAssignment()
    {
        return $this->belongsTo(TaskAssignment::class);
    }
}
