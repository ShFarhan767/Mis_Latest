<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskNote extends Model
{
    use HasFactory;

    protected $fillable = ['task_id', 'created_by', 'note', 'is_read'];

    public function user() {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function task() {
        return $this->belongsTo(Task::class);
    }
}
