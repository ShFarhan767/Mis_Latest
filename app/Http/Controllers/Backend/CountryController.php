<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Country;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CountryController extends Controller
{
    // GET all countries
    public function index(): JsonResponse
    {
        return response()->json(Country::orderBy('country_name')->get());
    }

    // STORE new country
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'country_name' => 'required|string|max:255|unique:countries,country_name',
            'status' => 'required|in:Running,Disabled',
        ]);

        $country = Country::create([
            'country_name' => $request->country_name,
            'status' => $request->status,
        ]);

        return response()->json($country, 201);
    }

    // UPDATE country
    public function update(Request $request, $id): JsonResponse
    {
        $country = Country::findOrFail($id);

        $request->validate([
            'country_name' => 'required|string|max:255|unique:countries,country_name,' . $country->id,
            'status' => 'required|in:Running,Disabled',
        ]);

        $country->update([
            'country_name' => $request->country_name,
            'status' => $request->status,
        ]);

        return response()->json($country);
    }

    // DELETE country
    public function destroy($id): JsonResponse
    {
        $country = Country::findOrFail($id);
        $country->delete();

        return response()->json(['message' => 'Country deleted successfully']);
    }
}