<?php

namespace App\Repositories;

use App\Models\Task;
use Illuminate\Support\Facades\Auth;

class TaskRepository
{
    public function create(array $data): Task
    {
        return Task::create($data);
    }

    public function find($id)
    {
        return Task::with([
            'shop',
            'creator',
            'taskAssignments.employee',
            'taskAssignments.assigner',
            'taskAssignments.workSessions'   // ⬅ LOAD WORK SESSIONS HERE
        ])->find($id);
    }

    public function all()
    {
        $user = Auth::user();

        // Admin can see everything
        if ($user->role === 'admin') {
            return Task::with('shop', 'creator')->latest()->get();
        }

        // Staff sees only tasks they created
        if ($user->role === 'staff') {
            return Task::with('shop', 'creator', 'taskAssignments', 'taskAssignments.workSessions')
                    ->where('created_by', $user->id)
                    ->latest()
                    ->get();
        }

        // Default (for safety)
        return collect([]);
    }

    public function update(int $id, array $data): Task
    {
        $task = Task::findOrFail($id);
        $task->update($data);
        return $task;
    }

    public function delete(int $id): bool
    {
        $task = Task::findOrFail($id);
        return $task->delete();
    }
}
