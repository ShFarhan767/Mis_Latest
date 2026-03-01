<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Services\DesignationService;
use Illuminate\Http\Request;
use App\Http\Requests\StoreDesignationRequest;

class DesignationController extends Controller
{
    protected $service;

    public function __construct(DesignationService $service)
    {
        $this->service = $service;
    }

    // List all designations
    public function index()
    {
        $designations = $this->service->list();
        return response()->json($designations);
    }

    // Store new designation
    public function store(StoreDesignationRequest $request)
    {
        $designation = $this->service->create($request->validated());
        return response()->json($designation, 201);
    }

    // Update existing designation
    public function update(StoreDesignationRequest $request, $id)
    {
        $designation = $this->service->update($id, $request->validated());
        return response()->json($designation);
    }

    // Delete designation
    public function destroy($id)
    {
        $this->service->delete($id);
        return response()->json(['message' => 'Designation deleted successfully']);
    }
}
