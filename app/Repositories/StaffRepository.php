<?php

namespace App\Repositories;

use App\Models\User;

class StaffRepository
{
    public function all()
    {
        return User::where('role', 'staff')->get();
    }

    public function find($id)
    {
        return User::where('role', 'staff')->findOrFail($id);
    }

    public function create(array $data)
    {
        return User::create($data);
    }

    public function update($id, array $data)
    {
        $staff = $this->find($id);
        $staff->update($data);
        return $staff;
    }

    public function delete($id)
    {
        $staff = $this->find($id);
        return $staff->delete();
    }
}
