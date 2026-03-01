<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OfferConnect;

class OfferConnectController extends Controller
{
    public function index()
    {
        return OfferConnect::all(); // or filter by status if needed
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'status' => 'nullable|string'
        ]);

        $offer = OfferConnect::create([
            'name' => $request->name,
            'status' => $request->status ?? 'Running'
        ]);

        return response()->json($offer);
    }

    public function update(Request $request, OfferConnect $offerConnect)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'status' => 'nullable|string'
        ]);

        $offerConnect->update([
            'name' => $request->name,
            'status' => $request->status ?? $offerConnect->status
        ]);

        return response()->json($offerConnect);
    }

    public function destroy(OfferConnect $offerConnect)
    {
        $offerConnect->delete();
        return response()->json(['message' => 'Offer deleted successfully']);
    }
}
