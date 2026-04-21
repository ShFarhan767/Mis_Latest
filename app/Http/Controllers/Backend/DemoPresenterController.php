<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\DemoPresenterStoreRequest;
use App\Http\Requests\DemoPresenterUpdateRequest;
use App\Services\DemoPresenterService;
use Illuminate\Http\JsonResponse;

class DemoPresenterController extends Controller
{
    public function __construct(protected DemoPresenterService $service) {}

    // Get all demo presenters
    public function index(): JsonResponse
    {
        $presenters = $this->service->all();
        return response()->json($presenters);
    }

    // Store new demo presenter
    public function store(DemoPresenterStoreRequest $request): JsonResponse
    {
        $presenter = $this->service->create($request->validated());
        return response()->json(['message' => 'Demo Presenter created successfully', 'presenter' => $presenter], 201);
    }

    // Show demo presenter detail
    public function show($id): JsonResponse
    {
        $presenter = $this->service->find($id);
        return response()->json($presenter);
    }

    // Update demo presenter
    public function update(DemoPresenterUpdateRequest $request, $id): JsonResponse
    {
        $presenter = $this->service->update($id, $request->validated());
        return response()->json(['message' => 'Demo Presenter updated successfully', 'presenter' => $presenter]);
    }

    // Delete demo presenter
    public function destroy($id): JsonResponse
    {
        $this->service->delete($id);
        return response()->json(['message' => 'Demo Presenter deleted successfully']);
    }
}
