<?php

namespace App\Repositories;

use App\Models\Designation;

class DesignationRepository
{
    public function all()
    {
        return Designation::orderBy('id', 'DESC')->get();
    }

    public function create(array $data)
    {
        return Designation::create($data);
    }
}
