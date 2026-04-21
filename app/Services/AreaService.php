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
        return $this->repo->all();
    }

    public function store(array $data, $userId)
    {
        return $this->repo->store($data, $userId);
    }

    public function update($id, $data)
    {
        return $this->repo->update($id, $data);
    }

    public function delete($id)
    {
        return $this->repo->delete($id);
    }
}
