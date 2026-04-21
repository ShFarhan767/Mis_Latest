<?php

namespace App\Repositories;

use App\Models\Customer;

class CustomerRepository
{
    private function baseWith(): array
    {
        return [
            'numbers',
            'assignedStaff:id,name,designation',
            'demoPresenter:id,name,mobile',
        ];
    }

    public function create(array $data)
    {
        return Customer::create($data);
    }

    public function find($id)
    {
        return Customer::with($this->baseWith())->findOrFail($id);
    }

    public function update($id, array $data, $staffId = null)
    {
        $customer = $this->find($id);

        // Track old data only for fields that are changing
        $oldData = [];
        foreach ($data as $key => $value) {
            if ($customer->$key != $value) {
                $oldData[$key] = $customer->$key;
            }
        }

        // Update customer
        $customer->update($data);

        // Store history if there is any change
        if (!empty($oldData)) {
            \App\Models\CustomerHistory::create([
                'customer_id' => $customer->id,
                'staff_id' => $staffId ?? auth()->id(),
                'old_data' => $oldData
            ]);
        }

        return $customer->load($this->baseWith());
    }

    public function delete($id)
    {
        $customer = $this->find($id);
        return $customer->delete();
    }

    public function all()
    {
        return Customer::with($this->baseWith())->latest()->get();
    }
}
