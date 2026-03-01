<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\CustomerHistory;
use Illuminate\Support\Facades\Auth;

class CustomerHistoryController extends Controller
{
    // Add note (also tracks changes)
    public function addNote(Request $request, $customerId)
    {
        $customer = Customer::findOrFail($customerId);

        $trackFields = [
            'name', 'designation', 'email', 'shop_type', 'locations', 'lead_source',
            'interest_level', 'service_type', 'feature_need', 'our_commitment',
            'offer_connect', 'client_behaviour', 'status', 'staff_status',
            'last_contact_date', 'next_follow_up_date', 'last_discuss_note'
        ];

        $oldData = [];
        foreach ($trackFields as $field) {
            if ($request->has($field) && $customer->$field != $request->$field) {
                $oldData[$field] = $customer->$field;
                $customer->$field = $request->$field;
            }
        }

        // Update last_discuss_note if note is provided
        if ($request->has('note')) {
            $customer->last_discuss_note = $request->note;
        }

        $customer->save();

        CustomerHistory::create([
            'customer_id' => $customer->id,
            'staff_id' => Auth::id(),
            'note' => $request->note ?? null,
            'old_data' => $oldData,
        ]);

        return response()->json(['message' => 'Note added and history saved']);
    }

    public function updateServiceType(Request $request, $id)
    {
        $request->validate([
            'service_type' => 'nullable|array',
        ]);

        $customer = Customer::findOrFail($id);

        $oldServiceType = $customer->service_type ?? [];
        $newServiceType = $request->service_type ?? [];

        // ✅ If same, do nothing
        if ($oldServiceType == $newServiceType) {
            return response()->json(['message' => 'No change detected']);
        }

        $customer->service_type = $newServiceType;
        $customer->save();

        CustomerHistory::create([
            'customer_id' => $customer->id,
            'staff_id'    => auth()->id(),
            'note'        => 'Service type updated',
            'old_data'    => [
                'service_type'      => $oldServiceType,
                'new_service_type'  => $newServiceType,
            ],
        ]);

        return response()->json([
            'message' => 'Service type updated successfully'
        ]);
    }

    // Fetch all customers with merged history (one row per customer)
    public function getAllHistory()
    {
        // Fetch all customers with related numbers and histories (including staff)
        $customers = Customer::with(['numbers', 'histories.staff', 'assignedStaff'])->get();

        $result = $customers->map(function ($customer, $idx) {
            // Merge all service types (if stored as array or string)
            $serviceType = is_array($customer->service_type) ? implode(", ", $customer->service_type) : $customer->service_type;

            // Merge all numbers with type
            $numbers = $customer->numbers->map(function ($n) {
                return $n->full_number . " (" . $n->type . ")";
            })->implode(", ");

            // Merge old + new data from histories
            $historyData = $customer->histories->map(function ($h) {
                $old = $h->old_data ? json_encode($h->old_data) : null;
                $note = $h->note ?? null;
                $staffName = $h->staff ? $h->staff->name : '-';

                return [
                    'staff' => $staffName,
                    'old_data' => $old,
                    'note' => $note,
                ];
            });

            return [
                'sn' => $idx + 1,
                'assign_staff' => $customer->assignedStaff ? $customer->assignedStaff->name : "-",
                'customer_name' => $customer->name,
                'service_type' => $serviceType ?? "-",
                'numbers' => $numbers ?: "-",
                'history' => $historyData, // all old + new data merged
                'latest_update' => $customer->updated_at ? $customer->updated_at->format('d M Y') : "-",
                'last_update' => $customer->created_at ? $customer->created_at->format('d M Y') : "-",
            ];
        });

        return response()->json($result);
    }

    // Fetch all history for a customer
    public function getHistory($customerId)
    {
        $history = CustomerHistory::where('customer_id', $customerId)
                    ->with('staff')
                    ->orderBy('created_at', 'desc')
                    ->get();

        return response()->json($history);
    }

    // Fetch only notes for Add Note modal
    public function getNotes($customerId)
    {
        $notes = CustomerHistory::where('customer_id', $customerId)
                    ->whereNotNull('note')
                    ->with('staff')
                    ->orderBy('created_at', 'desc')
                    ->get();

        return response()->json($notes);
    }

    public function updateNote(Request $request, $historyId)
    {
        $request->validate([
            'note' => 'required|string'
        ]);

        $history = CustomerHistory::findOrFail($historyId);

        $history->update([
            'note' => $request->note
        ]);

        // Also update last_discuss_note on customer
        $history->customer()->update([
            'last_discuss_note' => $request->note
        ]);

        return response()->json(['message' => 'Note updated']);
    }
}
