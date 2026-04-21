<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Models\Shop;
use App\Models\Task;
use App\Services\TaskService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TaskController extends Controller
{
    protected $service;

    public function __construct(TaskService $service)
    {
        $this->service = $service;
    }

    // Store a task
    public function store(StoreTaskRequest $request)
    {
        $data = $request->validated();
        $data['created_by'] = Auth::id(); // store logged-in user

        // Ensure shop_id is included
        if ($request->has('shop_id')) {
            $data['shop_id'] = $request->shop_id;
        }

        $file = $request->file('image');

        $task = $this->service->store($data, $file);

        return response()->json([
            'message' => 'Task created successfully',
            'task' => $task
        ]);
    }

    // Update a task
    public function update(UpdateTaskRequest $request, int $id)
    {
        $data = $request->validated();
        $file = $request->file('image');

        try {
            $task = $this->service->update($id, $data, $file);

            return response()->json([
                'message' => 'Task updated successfully',
                'task' => $task
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Task not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    public function staffTaskDecision(Request $request, $id)
    {
        $request->validate([
            'decision' => 'required|in:Approved,Declined,Declined Trash',
            'note' => 'required'
        ]);

        $task = Task::findOrFail($id);

        $task->staff_decision = $request->decision;

        if ($request->decision === 'Declined') {
            $task->decline_note = $request->note;
        }

        if ($request->decision === 'Approved') {
            $task->approve_note = $request->note;
        }

        if ($request->decision === 'Declined Trash') {
            $task->declined_trash_note = $request->note;
        }

        $task->save();

        return response()->json([
            'message' => 'Decision saved successfully'
        ]);
    }

    // Delete a task
    public function destroy(int $id)
    {
        try {
            $this->service->delete($id);

            return response()->json([
                'message' => 'Task deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Task not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    // List tasks
    public function index()
    {
        $tasks = $this->service->all(); // use service for consistent logic
        return response()->json($tasks);
    }

    // Search shops for VueMultiselect (q param)
    public function searchShops(Request $request)
    {
        $q = $request->get('q', '');

        $query = Shop::query();
        if (strlen($q) >= 1) {
            $query->where('name', 'like', "%$q%");
        }

        $results = $query->limit(20)->get(['id', 'name']);

        return response()->json($results);
    }
}

