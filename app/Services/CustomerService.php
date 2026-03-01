<?php

namespace App\Services;

use App\Repositories\CustomerRepository;
use App\Models\ServiceType; // ✅ ADD THIS

class CustomerService
{
    public $repo;

    public function __construct(CustomerRepository $repo)
    {
        $this->repo = $repo;
    }

    public function createCustomer(array $data)
    {
        // Convert arrays to JSON strings
        if(!empty($data['service_type'])) {
            $data['service_type'] = json_encode($data['service_type']);
        }

        if(!empty($data['feature_need']) && is_array($data['feature_need'])) {
            $data['feature_need'] = json_encode($data['feature_need']);
        }

        if(!empty($data['our_commitment']) && is_array($data['our_commitment'])) {
            $data['our_commitment'] = json_encode($data['our_commitment']);
        }

        // ✅ existing logic
        $numbers = $data['numbers'];
        unset($data['numbers']);

        $customer = $this->repo->create($data);

        foreach ($numbers as $num) {
            $customer->numbers()->create([
                'number' => $num['number'],
                'full_number' => $num['full_number'],
                'type' => $num['type'] ?? 'call',
                'country_code' => $num['country_code'] ?? null,
            ]);
        }

        return $customer->load('numbers');
    }
}
