<?php

namespace App\Services;

use App\Models\Designation;

class DesignationService
{
    public function list()
    {
        return Designation::orderBy('id', 'DESC')->get();
    }

    public function create(array $data)
    {
        return Designation::create($data);
    }

    public function update(int $id, array $data)
    {
        $designation = Designation::findOrFail($id);
        $designation->update($data);
        return $designation;
    }

    public function delete(int $id)
    {
        $designation = Designation::findOrFail($id);
        $designation->delete();
        return true;
    }
}
