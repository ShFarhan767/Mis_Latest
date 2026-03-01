<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Task;
use App\Models\TaskNote;

class TaskNoteController extends Controller
{
    public function index(Task $task)
    {
        $notes = $task->notes()->with('user')->orderBy('created_at', 'desc')->get();
        $unreadCount = $notes->where('is_read', false)->count();

        return response()->json([
            'notes' => $notes,
            'unreadCount' => $unreadCount,
        ]);
    }

    public function store(Request $request, Task $task)
    {
        $request->validate(['note' => 'required|string']);

        $note = TaskNote::create([
            'task_id' => $task->id,
            'created_by' => $request->user()->id,
            'note' => $request->note,
        ]);

        return response()->json($note);
    }

    public function markAsRead(Request $request, Task $task)
    {
        $noteIds = $request->input('note_ids', []);

        TaskNote::where('task_id', $task->id)
                ->whereIn('id', $noteIds)
                ->update(['is_read' => true]);

        return response()->json(['success' => true]);
    }
}
