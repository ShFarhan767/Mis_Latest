<?php

namespace App\Services;

use App\Repositories\ShopTypeRepository;
use App\Models\ShopType;

class ShopTypeService
{
    protected $repo;

    public function __construct(ShopTypeRepository $repo)
    {
        $this->repo = $repo;
    }

    public function getAll()
    {
        return $repo = $this->repo->all();
    }

    public function store(array $data)
    {
        return $this->repo->create($data);
    }

    public function update(ShopType $shopType, array $data)
    {
        return $this->repo->update($shopType, $data);
    }

    public function delete(ShopType $shopType)
    {
        return $this->repo->delete($shopType);
    }
}
