<?php

namespace App\Repositories;

use App\Models\LeadSource;

class LeadSourceRepository
{
    public function all()
    {
        return LeadSource::orderBy('id', 'desc')->get();
    }

    public function find(int $id)
    {
        return LeadSource::findOrFail($id);
    }

    public function create(array $data)
    {
        return LeadSource::create($data);
    }

    public function update(int $id, array $data)
    {
        $leadSource = $this->find($id);
        $leadSource->update($data);
        return $leadSource;
    }

    public function delete(int $id)
    {
        $leadSource = $this->find($id);
        return $leadSource->delete();
    }
}
