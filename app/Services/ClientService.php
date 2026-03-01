<?php

namespace App\Services;

use App\Models\Client;
use App\Models\ClientOperatorHistory;
use App\Repositories\ClientRepository;

class ClientService
{
    public function __construct(
        protected ClientRepository $repository
    ) {}

    public function getAllClients()
    {
        return $this->repository->all();
    }

    public function getClientById(int $id)
    {
        return $this->repository->find($id);
    }

    public function createClient(array $data)
    {
        // ✅ extract only business type name
        if (isset($data['business_type']['name'])) {
            $data['business_type'] = $data['business_type']['name'];
        }

        return $this->repository->create($data);
    }

    public function updateClient(int $id, array $data)
    {
        $client = $this->repository->find($id);
        if (!$client) abort(404);

        // extract business type
        if (isset($data['business_type']['name'])) {
            $data['business_type'] = $data['business_type']['name'];
        }

        // ✅ detect operator change
        if (
            ($data['operator_name'] ?? null) != $client->operator_name ||
            ($data['oparetor_number'] ?? null) != $client->oparetor_number
        ) {
            ClientOperatorHistory::create([
                'client_id' => $client->id,
                'old_operator_name' => $client->operator_name,
                'old_operator_number' => $client->oparetor_number,
                'new_operator_name' => $data['operator_name'] ?? $client->operator_name,
                'new_operator_number' => $data['oparetor_number'] ?? $client->oparetor_number,
            ]);
        }

        return $this->repository->update($client, $data);
    }

    public function deleteClient(int $id)
    {
        $client = $this->repository->find($id);
        if (!$client) {
            abort(404, 'Client not found');
        }
        return $this->repository->delete($client);
    }

    public function searchRunningClients(string $query)
    {
        return \App\Models\Client::where(function($q) use ($query) {
            $q->where('name', 'like', "%$query%")
                ->orWhere('company_name', 'like', "%$query%")  // ✅ Add this line
                ->orWhere('number', 'like', "%$query%")
                ->orWhere('operator_name', 'like', "%$query%")
                ->orWhere('oparetor_number', 'like', "%$query%")
                ->orWhere('area_name', 'like', "%$query%")
                ->orWhere('project_name', 'like', "%$query%");
        })->with('tasks')->get();
    }
}
