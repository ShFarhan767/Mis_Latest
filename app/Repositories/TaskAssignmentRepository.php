<?php

namespace App\Repositories;

use App\Models\TaskAssignment;

class TaskAssignmentRepository
{
    public function all()
    {
        return TaskAssignment::with(['task', 'employee' , 'assigner', 'workSessions'])->get();
    }

    public function create(array $data): TaskAssignment
    {
        return TaskAssignment::create($data);
    }

    public function update(TaskAssignment $assignment, array $data): TaskAssignment
    {
        $assignment->update($data);
        return $assignment;
    }

    public function delete(TaskAssignment $assignment): void
    {
        $assignment->delete();
    }
}
