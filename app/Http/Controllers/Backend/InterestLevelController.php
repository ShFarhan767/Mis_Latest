<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\InterestLevel;

class InterestLevelController extends Controller
{
    // Get all interest levels
    public function index()
    {
        return response()->json(InterestLevel::all());
    }

    // Store a new interest level
    public function store(Request $request)
    {
        $request->validate([
            'level_name' => 'required|string|unique:interest_levels,level_name',
            'status' => 'required|in:Running,Disabled'
        ]);

        $interest = InterestLevel::create([
            'level_name' => $request->level_name,
            'status' => $request->status
        ]);

        return response()->json($interest, 201);
    }

    // Update an existing interest level
    public function update(Request $request, $id)
    {
        $interest = InterestLevel::findOrFail($id);

        $request->validate([
            'level_name' => 'required|string|unique:interest_levels,level_name,' . $id,
            'status' => 'required|in:Running,Disabled'
        ]);

        $interest->update([
            'level_name' => $request->level_name,
            'status' => $request->status
        ]);

        return response()->json($interest);
    }

    // Delete an interest level
    public function destroy($id)
    {
        $interest = InterestLevel::findOrFail($id);
        $interest->delete();

        return response()->json(['message' => 'Interest level deleted']);
    }

    // Optional: show single interest level
    public function show($id)
    {
        $interest = InterestLevel::findOrFail($id);
        return response()->json($interest);
    }
}