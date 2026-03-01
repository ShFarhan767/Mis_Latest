<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\LeadSourceRequest;
use App\Services\LeadSourceService;
use Illuminate\Http\JsonResponse;

class LeadSourceController extends Controller
{
    protected $service;

    public function __construct(LeadSourceService $service)
    {
        $this->service = $service;
    }

    public function index(): JsonResponse
    {
        return response()->json($this->service->listLeadSources());
    }

    public function store(LeadSourceRequest $request): JsonResponse
    {
        return response()->json($this->service->createLeadSource($request->validated()));
    }

    public function update(LeadSourceRequest $request, $id): JsonResponse
    {
        return response()->json($this->service->updateLeadSource($id, $request->validated()));
    }

    public function destroy($id): JsonResponse
    {
        $this->service->deleteLeadSource($id);
        return response()->json(['message' => 'Lead Source deleted successfully']);
    }
}
