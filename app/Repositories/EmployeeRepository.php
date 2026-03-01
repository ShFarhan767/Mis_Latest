<?php

namespace App\Repositories;

use App\Models\User;

class EmployeeRepository
{
    public function all()
    {
        return User::where('role', 'employee')->get();
    }

    public function find($id)
    {
        return User::where('role', 'employee')->findOrFail($id);
    }

    public function create(array $data)
    {
        return User::create($data);
    }

    public function update($id, array $data)
    {
        $employee = $this->find($id);
        $employee->update($data);
        return $employee;
    }

    public function delete($id)
    {
        $employee = $this->find($id);
        return $employee->delete();
    }
}
