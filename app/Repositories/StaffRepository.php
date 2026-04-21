<?php

namespace App\Repositories;

use App\Models\Customer;
use App\Models\User;

class StaffRepository
{
    public function all()
    {
        return User::where('role', 'staff')
            ->withCount('tasks')
            ->get()
            ->map(function ($staff) {
                // Count customers created by this staff
                $createdCustomersCount = Customer::where('created_by', $staff->id)->count();

                // Count customers assigned to this staff
                $assignedCustomersCount = Customer::where('assigned_staff_id', $staff->id)->count();

                return [
                    'id' => $staff->id,
                    'name' => $staff->name,
                    'mobile' => $staff->mobile,
                    'email' => $staff->email,
                    'designation' => $staff->designation,
                    'status' => $staff->status,
                    'plain_password' => $staff->plain_password,

                    // Check tasks + created + assigned customers
                    'can_delete' => $staff->tasks_count == 0
                                    && $createdCustomersCount == 0
                                    && $assignedCustomersCount == 0,
                ];
            });
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
