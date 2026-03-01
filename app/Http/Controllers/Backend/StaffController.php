<?php

namespace App\Http\Controllers\Backend;

use App\Services\StaffService;
use App\Http\Controllers\Controller;
use App\Http\Requests\EmployeeStoreRequest;
use App\Http\Requests\EmployeeUpdateRequest;
use Illuminate\Http\JsonResponse;

class StaffController extends Controller
{
    public function __construct(protected StaffService $service) {}

    public function index(): JsonResponse
    {
        return response()->json($this->service->all());
    }

    public function store(EmployeeStoreRequest $request): JsonResponse
    {
        $staff = $this->service->create($request->validated());
        return response()->json(['message' => 'Staff created successfully', 'staff' => $staff], 201);
    }

    public function show($id): JsonResponse
    {
        return response()->json($this->service->find($id));
    }

    public function update(EmployeeUpdateRequest $request, $id): JsonResponse
    {
        $staff = $this->service->update($id, $request->validated());
        return response()->json(['message' => 'Staff updated successfully', 'staff' => $staff]);
    }

    public function destroy($id): JsonResponse
    {
        $this->service->delete($id);
        return response()->json(['message' => 'Staff deleted successfully']);
    }
}
