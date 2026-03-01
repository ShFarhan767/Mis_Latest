<?php

namespace App\Http\Controllers\Backend;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\ShopTypeRequest;
use App\Models\ShopType;
use App\Services\ShopTypeService;

class ShopTypeController extends Controller
{
    protected $service;

    public function __construct(ShopTypeService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        return response()->json($this->service->getAll());
    }

    public function store(ShopTypeRequest $request)
    {
        $shopType = $this->service->store($request->validated());
        return response()->json($shopType, 201);
    }

    public function update(ShopTypeRequest $request, ShopType $shopType)
    {
        $updated = $this->service->update($shopType, $request->validated());
        return response()->json($updated);
    }

    public function destroy(ShopType $shopType)
    {
        $this->service->delete($shopType);
        return response()->json(['message' => 'Deleted successfully']);
    }
}