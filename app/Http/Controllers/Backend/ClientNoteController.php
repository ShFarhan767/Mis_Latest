<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ClientNote;
use App\Models\ClientTimeline;
use Illuminate\Support\Facades\Auth;

class ClientNoteController extends Controller
{
    // Fetch all notes
    public function index()
    {
        return ClientNote::with('creator')->get();
    }

    // Store new note
    public function store(Request $request)
    {
        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'content' => 'required|string',
        ]);

        $note = ClientNote::create([
            'client_id' => $request->client_id,
            'content' => $request->content,
        ]);

        // Save in timeline
        ClientTimeline::create([
            'client_id' => $request->client_id,
            'type' => 'note',
            'description' => $request->content,
        ]);

        return response()->json(['success' => true, 'note' => $note]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'content' => 'required|string',
        ]);

        $note = ClientNote::findOrFail($id);
        $note->update([
            'content' => $request->content,
        ]);

        // Optional: update timeline note
        ClientTimeline::where('client_id', $note->client_id)
            ->where('type', 'note')
            ->where('description', $note->getOriginal('content'))
            ->latest()
            ->update([
                'description' => $request->content
            ]);

        return response()->json(['success' => true]);
    }

    public function timeline($clientId)
    {
        // Get all timeline events (notes, tasks, updates)
        $timeline = ClientTimeline::where('client_id', $clientId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($timeline);
    }

    // Fetch notes for a specific client
    public function clientNotes($clientId)
    {
        $notes = ClientNote::where('client_id', $clientId)
            ->with('creator')
            ->orderByDesc('created_at')
            ->get();

        return response()->json($notes);
    }

    // Optional: delete a note
    public function destroy($id)
    {
        $note = ClientNote::findOrFail($id);
        $note->delete();

        return response()->json(['message' => 'Note deleted successfully']);
    }
}
