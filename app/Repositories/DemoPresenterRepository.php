<?php

namespace App\Repositories;

use App\Models\User;

class DemoPresenterRepository
{
    public function all()
    {
        return User::where('role', 'demo_presenter')->get();
    }

    public function find($id)
    {
        return User::where('role', 'demo_presenter')->findOrFail($id);
    }

    public function create(array $data)
    {
        return User::create($data);
    }

    public function update($id, array $data)
    {
        $presenter = $this->find($id);
        $presenter->update($data);
        return $presenter;
    }

    public function delete($id)
    {
        $presenter = $this->find($id);
        return $presenter->delete();
    }
}
