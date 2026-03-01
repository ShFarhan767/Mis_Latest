<?php

namespace App\Http\Controllers\Backend;

use App\Models\BusinessType;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class BusinessTypeController extends Controller
{
    // Fetch all business types
    public function index(): JsonResponse
    {
        $types = BusinessType::all();
        return response()->json($types);
    }

    // Store new business type
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'status' => 'required|in:Running,Disabled',
        ]);

        $type = BusinessType::create($request->only(['name', 'status']));

        return response()->json($type, 201);
    }

    // Optional: update business type
    public function update(Request $request, int $id): JsonResponse
    {
        $type = BusinessType::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'status' => 'required|in:Running,Disabled',
        ]);

        $type->update($request->only(['name', 'status']));

        return response()->json($type);
    }

    // Optional: delete business type
    public function destroy(int $id): JsonResponse
    {
        $type = BusinessType::findOrFail($id);
        $type->delete();

        return response()->json(['message' => 'Business type deleted successfully']);
    }
}
