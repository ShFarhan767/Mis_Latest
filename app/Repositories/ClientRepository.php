<?php

namespace App\Repositories;

use App\Models\Client;
use Illuminate\Database\Eloquent\Collection;

class ClientRepository
{
    public function all(): Collection
    {
        return Client::orderByDesc('id')->get();
    }

    public function find(int $id): ?Client
    {
        return Client::find($id);
    }

    public function create(array $data): Client
    {
        return Client::create($data);
    }

    public function update(Client $client, array $data): Client
    {
        $client->update($data);
        return $client;
    }

    public function delete(Client $client): bool
    {
        return $client->delete();
    }

    public function searchByName(string $query)
    {
        return Client::where('status', 'Running')
            ->where('name', 'LIKE', "%{$query}%")
            ->select('id', 'name')
            ->orderBy('name')
            ->get();
    }
}
