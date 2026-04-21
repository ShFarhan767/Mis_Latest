<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerDemoNote;
use App\Models\CustomerDemoNoteRead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerDemoNoteController extends Controller
{
    private function canAccess(Customer $customer): bool
    {
        $user = Auth::user();
        if (!$user) return false;

        if ($user->role === 'admin') return true;

        if ($user->role === 'staff') {
            return (int)$customer->assigned_staff_id === (int)$user->id;
        }

        if ($user->role === 'demo_presenter') {
            return (int)$customer->demo_presenter_id === (int)$user->id;
        }

        return false;
    }

    public function index(Customer $customer)
    {
        if (!$this->canAccess($customer)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $notes = CustomerDemoNote::query()
            ->where('customer_id', $customer->id)
            ->with('user:id,name,role')
            ->orderBy('id', 'asc')
            ->get();

        return response()->json(['notes' => $notes]);
    }

    public function store(Request $request, Customer $customer)
    {
        if (!$this->canAccess($customer)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = $request->validate([
            'message' => 'required|string|max:5000',
        ]);

        $note = CustomerDemoNote::create([
            'customer_id' => $customer->id,
            'user_id' => Auth::id(),
            'message' => $data['message'],
        ]);

        return response()->json([
            'message' => 'Note added',
            'note' => $note->load('user:id,name,role'),
        ], 201);
    }

    public function markRead(Customer $customer)
    {
        if (!$this->canAccess($customer)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $latestId = (int)CustomerDemoNote::query()
            ->where('customer_id', $customer->id)
            ->max('id');

        CustomerDemoNoteRead::updateOrCreate(
            ['customer_id' => $customer->id, 'user_id' => Auth::id()],
            ['last_read_note_id' => $latestId]
        );

        return response()->json(['message' => 'Marked as read', 'last_read_note_id' => $latestId]);
    }
}
