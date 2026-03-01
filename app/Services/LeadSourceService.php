<?php

namespace App\Services;

use App\Repositories\LeadSourceRepository;

class LeadSourceService
{
    protected $repo;

    public function __construct(LeadSourceRepository $repo)
    {
        $this->repo = $repo;
    }

    public function listLeadSources()
    {
        return $this->repo->all();
    }

    public function createLeadSource(array $data)
    {
        return $this->repo->create($data);
    }

    public function updateLeadSource(int $id, array $data)
    {
        return $this->repo->update($id, $data);
    }

    public function deleteLeadSource(int $id)
    {
        return $this->repo->delete($id);
    }
}
