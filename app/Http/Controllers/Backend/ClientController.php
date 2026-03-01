<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\ClientRequest;
use App\Models\Client;
use App\Models\ClientStatusHistory;
use App\Models\ClientTimeline;
use App\Services\ClientService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function __construct(
        protected ClientService $service
    ) {}

    public function index(): JsonResponse
    {
        $clients = $this->service->getAllClients();
        return response()->json($clients);
    }

    public function store(ClientRequest $request): JsonResponse
    {
        $client = $this->service->createClient($request->validated());

        // ✅ Save operator history at creation time
        if ($client->operator_name && $client->oparetor_number) {
            $client->operatorHistories()->create([
                'old_operator_name' => null,
                'old_operator_number' => null,
                'new_operator_name' => $client->operator_name,
                'new_operator_number' => $client->oparetor_number,
            ]);
        }

        return response()->json($client, 201);
    }

    public function show(int $id): JsonResponse
    {
        $client = $this->service->getClientById($id);
        return $client
            ? response()->json($client)
            : response()->json(['message' => 'Client not found'], 404);
    }

    public function update(ClientRequest $request, int $id): JsonResponse
    {
        $client = $this->service->updateClient($id, $request->validated());
        return response()->json($client);
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'status' => 'required|string',
            'reason' => 'required|string|max:500',
        ]);

        $client = Client::findOrFail($id);

        $oldStatus = $client->status;
        $newStatus = $request->status;

        // Save history
        ClientStatusHistory::create([
            'client_id' => $client->id,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'reason' => $request->reason,
        ]);

        // Save timeline
        ClientTimeline::create([
            'client_id' => $client->id,
            'type' => 'status',
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'description' => $request->reason,
        ]);

        // Update client
        $client->update(['status' => $newStatus]);

        return response()->json($client);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->service->deleteClient($id);
        return response()->json(['message' => 'Client deleted successfully']);
    }

    public function search(Request $request): JsonResponse
    {
        $query = $request->query('query', '');
        if (strlen($query) < 1) {
            return response()->json([]);
        }

        $clients = $this->service->searchRunningClients($query);
        return response()->json($clients);
    }

    public function operatorHistory(Client $client)
    {
        $histories = $client->operatorHistories()
            ->whereNotNull('old_operator_name') // ❗ ignore first creation
            ->whereNotNull('old_operator_number') // ❗ ignore first creation
            ->orderBy('created_at', 'asc')
            ->get();

        // If no history but client has operator → still show first entry
        if ($histories->isEmpty() && $client->operator_name) {
            return response()->json(collect([[
                'id' => 'client-'.$client->id,
                'new_operator_name' => $client->operator_name,
                'new_operator_number' => $client->oparetor_number,
                'created_at' => $client->created_at,
            ]]));
        }

        // Force first history time = client created_at
        if ($histories->isNotEmpty()) {
            $first = $histories->first();

            if (is_array($first)) {
                $histories[0]['created_at'] = $client->created_at;
            } else {
                $first->created_at = $client->created_at;
            }
        }

        return response()->json($histories);
    }

    public function timeline(Client $client)
    {
        $notes = $client->notes()->get()->map(fn($n) => [
            'id' => $n->id,
            'type' => 'note',
            'description' => $n->content,
            'created_at' => $n->created_at,
        ]);

        $newTasks = $client->tasks()
            ->where('status', 'New')
            ->get()
            ->map(fn($t) => [
                'id' => $t->id,
                'type' => 'task_new',
                'title' => $t->title,
                'details' => $t->details,
                'image_path' => $t->image_path, // ✅ image
                'start_date' => $t->start_date,
                'created_at' => $t->created_at,
            ]);

        $assignedTasks = $client->tasks()
            ->where('status', 'Assigned')
            ->get()
            ->map(fn($t) => [
                'id' => $t->id,
                'type' => 'task_assigned',
                'title' => $t->title,
                'details' => $t->details,
                'image_path' => $t->image_path, // ✅ image
                'start_date' => $t->start_date,
                'created_at' => $t->created_at,
            ]);

        $reissueTasks = $client->tasks()
            ->where('status', 'Reissue')
            ->get()
            ->map(fn($t) => [
                'id' => $t->id,
                'type' => 'task_reissue',
                'title' => $t->title,
                'details' => $t->details,
                'image_path' => $t->image_path, // ✅ image
                'start_date' => $t->start_date,
                'created_at' => $t->created_at,
            ]);

        $completedTasks = $client->tasks()
            ->whereIn('status', ['Complete', 'Approved'])
            ->get()
            ->map(fn($t) => [
                'id' => $t->id,
                'type' => 'task_complete',
                'title' => $t->title,
                'details' => $t->details,
                'image_url' => $t->image_url, // ✅ image
                'start_date' => $t->start_date,
                'complete_note' => json_decode($t->complete_note, true),
                'created_at' => $t->created_at,
            ]);

        $statuses = $client->statusHistories()->get()->map(fn($s) => [
            'id' => $s->id,
            'type' => 'status',
            'old_status' => $s->old_status,
            'new_status' => $s->new_status,
            'reason' => $s->reason,
            'created_at' => $s->created_at,
        ]);

        $clientCreated = collect([[ 
            'id' => 'client-'.$client->id,
            'type' => 'client_created',
            'description' => 'Client was created',
            'created_at' => $client->created_at,
        ]]);

        $timeline = collect()
            ->merge($clientCreated)
            ->merge($notes)
            ->merge($newTasks)
            ->merge($assignedTasks)
            ->merge($reissueTasks)
            ->merge($completedTasks)
            ->merge($statuses)
            ->sortByDesc('created_at')
            ->values();

        return response()->json($timeline);
    }
}
