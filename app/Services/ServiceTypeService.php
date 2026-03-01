<?php

namespace App\Services;

use App\Repositories\ServiceTypeRepository;

class ServiceTypeService
{
    protected $repo;

    public function __construct(ServiceTypeRepository $repo)
    {
        $this->repo = $repo;
    }

    public function list()
    {
        return $this->repo->all();
    }

    public function store(array $data)
    {
        return $this->repo->create($data);
    }

    public function update($serviceType, array $data)
    {
        return $this->repo->update($serviceType, $data);
    }

    public function delete($serviceType)
    {
        return $this->repo->delete($serviceType);
    }
}
