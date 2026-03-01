<?php

namespace App\Services;

use App\Models\TaskAssignment;
use App\Repositories\TaskAssignmentRepository;
use Carbon\Carbon;

class TaskAssignmentService
{
    protected TaskAssignmentRepository $repository;

    public function __construct(TaskAssignmentRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getAllAssignments()
    {
        // Ensure relationships are loaded
        return $this->repository->all();
    }

    public function createAssignment(array $data)
    {
        $data['start_date'] = $data['start_date'] ?? now()->toDateString();
        $data['end_date'] = $data['end_date'] ?? null;
        $data['reissue_comment'] = $data['reissue_comment'] ?? null; // always save if provided
        $data['committed_hours'] = $data['committed_hours'] ?? null;

        // Set assigned_by to current user (optional)
        $data['assigned_by'] = auth()->id();

        // Automatically set assigned_at if status is Assigned
        if (isset($data['status']) && $data['status'] === 'Assigned') {
            $data['assigned_at'] = now()->timezone('Asia/Dhaka');
        }

        $assignment = $this->repository->create($data);

        // Ensure relationships are loaded
        $assignment->load('task', 'employee');

        // Update task status
        $assignment->task->update(['status' => $data['status']]);

        return $assignment;
    }

    public function updateAssignment(TaskAssignment $assignment, array $data)
    {
        $data['reissue_comment'] = $data['reissue_comment'] ?? null;
        $data['committed_hours'] = $data['committed_hours'] ?? $assignment->committed_hours;
        $data['complete_note'] = $data['complete_note'] ?? $assignment->complete_note;
        $data['cancelled_note'] = $data['cancelled_note'] ?? $assignment->cancelled_note;
        $data['approved_note'] = $data['approved_note'] ?? $assignment->approved_note;

        // Set timezone to Bangladesh
        $now = Carbon::now('Asia/Dhaka');

        // When task is Assigned
        if (isset($data['status']) && $data['status'] === 'Assigned') {
            // store assigned_at only if it was not set before
            $data['assigned_at'] = $assignment->assigned_at ?? $now;
            $data['completed_at'] = null; // reset completed_at if re-assigned
        }

        // When task is Reissued
        if (isset($data['status']) && $data['status'] === 'Reissue') {
            // always reset assigned_at for Reissue
            $data['assigned_at'] = $now;
            $data['completed_at'] = null; // reset completed_at
            $data['start_time'] = null;   // reset start_time if needed
        }

        // When task is working
        if (isset($data['status']) && $data['status'] === 'Working') {
            $data['start_time'] = $assignment->start_time ?? $now; // set if not already set
        }

        // When task is completed
        if (isset($data['status']) && $data['status'] === 'Complete') {
            $data['completed_at'] = $now;
        }

        $assignment = $this->repository->update($assignment, $data);

        // Ensure relationships are loaded
        $assignment->load('task', 'employee');

        // Sync task status
        $assignment->task->update(['status' => $data['status']]);

        return $assignment;
    }

    public function deleteAssignment(TaskAssignment $assignment)
    {
        $this->repository->delete($assignment);
    }
}
