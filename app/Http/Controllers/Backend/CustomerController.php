<?php

namespace App\Http\Controllers\Backend;

use App\Models\Customer;
use App\Models\ServiceType;
use Illuminate\Http\Request;
use App\Services\CustomerService;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\CustomerAssignHistory;
use App\Http\Requests\CustomerRequest;

class CustomerController extends Controller
{
    protected $service;

    public function __construct(CustomerService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        $query = Customer::with(['numbers', 'assignedStaff']);

        // STAFF → only his assigned customers
        if (auth()->user()->role === 'staff') {
            $query->where('assigned_staff_id', auth()->id());
        }

        // ADMIN → all customers (assigned + unassigned)
        return response()->json([
            'customers' => $query->latest()->get()
        ]);
    }

    public function store(CustomerRequest $request)
    {
        $customer = $this->service->createCustomer($request->validated());

        return response()->json([
            'message' => 'Customer created successfully',
            'customer' => $customer
        ], 201);
    }

    public function show($id)
    {
        return response()->json([
            'customer' => $this->service->repo->find($id)
        ]);
    }

    public function update(CustomerRequest $request, $id)
    {
        $data = $request->validated();
        $numbers = $data['numbers'];
        unset($data['numbers']);

        // Pass staff id for history tracking
        $customer = $this->service->repo->update($id, $data, auth()->id());

        // Reset numbers
        $customer->numbers()->delete();
        foreach ($numbers as $num) {
            $customer->numbers()->create([
                'number' => $num['number'],
                'full_number' => $num['full_number'] ?? ($num['country_code'] . $num['number']),
                'type' => $num['type'] ?? 'call',
                'country_code' => $num['country_code'] ?? null,
            ]);
        }

        return response()->json([
            'message' => 'Customer updated successfully',
            'customer' => $customer->load('numbers')
        ]);
    }

    public function updateServiceType(Request $request, $id)
    {
        $request->validate([
            'service_type' => 'required|array',
            'service_type.*' => 'string|max:255'
        ]);

        $customer = $this->service->repo->find($id);

        // ✅ AUTO-CREATE service types
        foreach ($request->service_type as $type) {
            ServiceType::firstOrCreate(
                ['service_type_name' => $type],
                ['status' => 'Running']
            );
        }

        // ✅ Save JSON to customer
        $customer->update([
            'service_type' => $request->service_type
        ]);

        return response()->json([
            'message' => 'Service types updated successfully',
            'customer' => $customer
        ]);
    }

    public function destroy($id)
    {
        $this->service->repo->delete($id);

        return response()->json([
            'message' => 'Customer deleted successfully'
        ]);
    }

    public function staffStatusHistory($id)
    {
        $customer = $this->service->repo->find($id);

        return response()->json([
            'history' => $customer->staffStatusHistories()
                ->with('changedBy:id,name')
                ->latest('changed_at')
                ->get()
        ]);
    }

    public function assignToStaff(Request $request)
    {
        $data = $request->validate([
            'customer_ids' => 'required|array|min:1',
            'customer_ids.*' => 'exists:customers,id',
            'staff_id' => 'required|exists:users,id',
        ]);

        foreach ($data['customer_ids'] as $customerId) {
            $customer = Customer::find($customerId);

            // Assign staff
            $customer->update([
                'assigned_staff_id' => $data['staff_id']
            ]);

            // Log history
            CustomerAssignHistory::create([
                'customer_id' => $customerId,
                'staff_id' => $data['staff_id'],
                'assigned_by' => Auth::id(),
                'assigned_at' => now(),
            ]);
        }

        return response()->json([
            'message' => 'Customer(s) assigned successfully'
        ]);
    }

    // Update only staff_status
    public function updateStaffStatus(Request $request, $id)
    {
        $request->validate(['staff_status' => 'required|string']);
        $customer = $this->service->repo->find($id);

        // Check permission
        if (auth()->user()->role === 'staff' && $customer->assigned_staff_id !== auth()->id()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $customer = $this->service->repo->update($id, [
            'staff_status' => $request->staff_status
        ], auth()->id());

        return response()->json([
            'message' => 'Staff status updated successfully',
            'customer' => $customer
        ]);
    }

    public function search(Request $request)
    {
        $q = trim($request->query('q', ''));

        // 🛑 Prevent empty search
        if ($q === '') {
            return response()->json([]);
        }

        $customers = Customer::query()
            ->with('numbers')
            ->where(function ($query) use ($q) {

                // Search by customer name
                $query->where('name', 'like', "%{$q}%")

                    // Search by customer number table
                    ->orWhereHas('numbers', function ($q2) use ($q) {
                        $q2->where('number', 'like', "%{$q}%")
                        ->orWhere('full_number', 'like', "%{$q}%");
                    });
            })
            ->orderBy('id', 'desc')
            ->limit(10)
            ->get();

        return response()->json($customers);
    }

}
