<?php

namespace App\Services;

use App\Repositories\StaffRepository;
use Illuminate\Support\Facades\Hash;

class StaffService
{
    public function __construct(protected StaffRepository $repository) {}

    public function all()
    {
        return $this->repository->all();
    }

    public function find($id)
    {
        return $this->repository->find($id);
    }

    public function create(array $data)
    {
        if (!empty($data['password'])) {
            $data['plain_password'] = $data['password'];
            $data['password'] = Hash::make($data['password']);
        }

        $data['role'] = 'staff'; // staff role

        return $this->repository->create($data);
    }

    public function update($id, array $data)
    {
        if (!empty($data['password'])) {
            $data['plain_password'] = $data['password'];
            $data['password'] = Hash::make($data['password']);
        }

        return $this->repository->update($id, $data);
    }

    public function delete($id)
    {
        return $this->repository->delete($id);
    }
}
