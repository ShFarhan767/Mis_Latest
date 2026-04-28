<?php

namespace App\Services;

use App\Repositories\AreaRepository;

class AreaService
{
    protected $repo;

    public function __construct(AreaRepository $repo)
    {
        $this->repo = $repo;
    }

    public function getAll()
    {
        // Use CacheService for faster retrieval
        return CacheService::getAreas();
    }

    public function store(array $data, $userId)
    {
        $result = $this->repo->store($data, $userId);
        CacheService::invalidateAreas(); // Clear cache on create
        return $result;
    }

    public function update($id, $data)
    {
        $result = $this->repo->update($id, $data);
        CacheService::invalidateAreas(); // Clear cache on update
        return $result;
    }

    public function delete($id)
    {
        $result = $this->repo->delete($id);
        CacheService::invalidateAreas(); // Clear cache on delete
        return $result;
    }
}
