<?php

namespace App\Observers;

use App\Models\Customer;
use App\Models\CustomerStaffStatusHistory;
use Illuminate\Support\Facades\Auth;

class CustomerObserver
{
    /**
     * Handle the Customer "created" event.
     */
    public function created(Customer $customer): void
    {
        //
    }

    /**
     * Handle the Customer "updated" event.
     */
    public function updated(Customer $customer): void
    {
        if ($customer->isDirty('staff_status')) {
            CustomerStaffStatusHistory::create([
                'customer_id' => $customer->id,
                'old_status'  => $customer->getOriginal('staff_status'),
                'new_status'  => $customer->staff_status,
                'changed_by'  => Auth::id() ?? $customer->created_by,
                'changed_at'  => now(),
            ]);
        }
    }

    /**
     * Handle the Customer "deleted" event.
     */
    public function deleted(Customer $customer): void
    {
        //
    }

    /**
     * Handle the Customer "restored" event.
     */
    public function restored(Customer $customer): void
    {
        //
    }

    /**
     * Handle the Customer "force deleted" event.
     */
    public function forceDeleted(Customer $customer): void
    {
        //
    }
}
