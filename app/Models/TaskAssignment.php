<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_id',
        'employee_id',
        'start_date',
        'end_date',
        'committed_hours',
        'status',
        'reissue_comment',
        'complete_note', // ✅ added
        'cancelled_note', // ✅ added
        'approved_note', // ✅ added
        'assigned_by',
        'start_time', // ✅ add this
        'assigned_at',   // ✅
        'completed_at',  // ✅
    ];

    public function task() {
        return $this->belongsTo(Task::class);
    }

    public function employee() {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function assigner() {
        return $this->belongsTo(User::class, 'assigned_by');
    }
    public function workSessions()
    {
        return $this->hasMany(WorkSession::class, 'task_assignment_id');
    }
    // ✅ ONLY THIS ONE
    public function shop()
    {
        return $this->belongsTo(Client::class, 'shop_id');
    }

    public function notes()
    {
        return $this->hasMany(TaskNote::class, 'task_id', 'task_id');
    }
}
