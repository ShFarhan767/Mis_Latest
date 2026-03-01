<?php

namespace App\Repositories;

use App\Models\ServiceType;

class ServiceTypeRepository
{
    public function all()
    {
        return ServiceType::orderBy('id', 'desc')->get();
    }

    public function create(array $data)
    {
        return ServiceType::create($data);
    }

    public function update(ServiceType $serviceType, array $data)
    {
        return tap($serviceType)->update($data);
    }

    public function delete(ServiceType $serviceType)
    {
        return $serviceType->delete();
    }
}