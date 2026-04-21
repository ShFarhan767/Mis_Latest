<?php

namespace App\Http\Controllers\Backend;

use App\Models\Customer;
use App\Models\ServiceType;
use App\Models\User;
use Illuminate\Http\Request;
use App\Services\CustomerService;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\CustomerAssignHistory;
use App\Http\Requests\CustomerRequest;
use App\Models\CustomerDemoNote;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
    protected $service;

    public function __construct(CustomerService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        $query = Customer::with(['numbers', 'assignedStaff', 'demoPresenter'])
            ->select('customers.*');

        $userId = (int)auth()->id();
        if ($userId) {
            $query->addSelect(DB::raw("(
                select count(*) from customer_demo_notes n
                where n.customer_id = customers.id
                and n.user_id <> {$userId}
                and n.id > coalesce(
                    (select last_read_note_id from customer_demo_note_reads r
                     where r.customer_id = customers.id and r.user_id = {$userId}
                     limit 1),
                    0
                )
            ) as demo_notes_unread"));
        }

        if (auth()->user()->role === 'staff') {
            // STAFF → assigned to them OR created by them
            $query->where(function ($q) {
                $q->where('assigned_staff_id', auth()->id())
                ->orWhere('created_by', auth()->id());
            });
        }

        if (auth()->user()->role === 'demo_presenter') {
            // Demo presenter → only customers assigned to them for demo
            $query->where('demo_presenter_id', auth()->id())
                ->whereIn('staff_status', ['Need To Show Demo', 'Demo Done']);
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
        $request->validate([
            'staff_status' => 'required|string',
            'demo_presenter_id' => 'nullable|integer',
        ]);

        $customer = $this->service->repo->find($id);

        // Check permission
        if (auth()->user()->role === 'staff' && $customer->assigned_staff_id !== auth()->id()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = [
            'staff_status' => $request->staff_status,
        ];

        if ($request->staff_status === 'Need To Show Demo') {
            if (!$request->demo_presenter_id) {
                return response()->json([
                    'message' => 'Demo presenter is required when staff status is Need To Show Demo.'
                ], 422);
            }

            $presenter = User::query()
                ->where('id', $request->demo_presenter_id)
                ->where('role', 'demo_presenter')
                ->first();

            if (!$presenter) {
                return response()->json([
                    'message' => 'Invalid demo presenter.'
                ], 422);
            }

            $data['demo_presenter_id'] = $presenter->id;
            $data['demo_status'] = 'Pending';
            $data['demo_done_at'] = null;
        } else {
            // Clear demo presenter if moving away from demo status
            $data['demo_presenter_id'] = null;
            $data['demo_status'] = null;
            $data['demo_done_at'] = null;
        }

        $customer = $this->service->repo->update($id, $data, auth()->id());

        return response()->json([
            'message' => 'Staff status updated successfully',
            'customer' => $customer
        ]);
    }

    public function updateDemoStatus(Request $request, $id)
    {
        $data = $request->validate([
            'demo_status' => 'required|in:Pending,Done',
            'note' => 'nullable|string|max:5000',
        ]);

        $customer = $this->service->repo->find($id);

        if ($customer->staff_status !== 'Need To Show Demo') {
            return response()->json([
                'message' => 'Demo status can be updated only when staff status is Need To Show Demo.'
            ], 422);
        }

        $user = auth()->user();
        if ($user->role === 'staff' && (int)$customer->assigned_staff_id !== (int)$user->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        if ($user->role === 'demo_presenter' && (int)$customer->demo_presenter_id !== (int)$user->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if ($data['demo_status'] === 'Done' && empty(trim($data['note'] ?? ''))) {
            return response()->json(['message' => 'Note is required when marking demo as Done.'], 422);
        }

        $update = [
            'demo_status' => $data['demo_status'],
            'demo_done_at' => $data['demo_status'] === 'Done' ? now() : null,
        ];

        // Keep staff_status in sync for demo presenter flow
        $update['staff_status'] = $data['demo_status'] === 'Done' ? 'Demo Done' : 'Need To Show Demo';

        $updated = $this->service->repo->update($customer->id, $update, auth()->id());

        if (!empty(trim($data['note'] ?? ''))) {
            CustomerDemoNote::create([
                'customer_id' => $customer->id,
                'user_id' => auth()->id(),
                'message' => $data['note'],
            ]);
        }

        return response()->json([
            'message' => 'Demo status updated',
            'customer' => $updated,
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
