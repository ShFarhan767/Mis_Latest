<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\ServiceTypeRequest;
use App\Models\ServiceType;
use App\Services\ServiceTypeService;

class ServiceTypeController extends Controller
{
    protected $service;

    public function __construct(ServiceTypeService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        return response()->json($this->service->list());
    }

    public function store(ServiceTypeRequest $request)
    {
        $serviceType = $this->service->store($request->validated());
        return response()->json($serviceType, 201);
    }

    public function update(ServiceTypeRequest $request, ServiceType $serviceType)
    {
        $updated = $this->service->update($serviceType, $request->validated());
        return response()->json($updated);
    }

    public function destroy(ServiceType $serviceType)
    {
        $this->service->delete($serviceType);
        return response()->json(['message' => 'Deleted successfully']);
    }
}
