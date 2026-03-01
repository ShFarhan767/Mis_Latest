<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\HeaderLogoRequest;
use App\Models\HeaderLogo;
use App\Services\HeaderLogoService;
use Illuminate\Http\JsonResponse;

class HeaderLogoController extends Controller
{
    public function __construct(protected HeaderLogoService $service) {}

    public function index(): JsonResponse
    {
        return response()->json($this->service->getAll());
    }

    public function show(HeaderLogo $headerLogo): JsonResponse
    {
        return response()->json($headerLogo);
    }

    public function store(HeaderLogoRequest $request): JsonResponse
    {
        $logo = $this->service->create($request->file('image'));
        return response()->json($logo, 201);
    }

    public function update(HeaderLogoRequest $request, HeaderLogo $headerLogo): JsonResponse
    {
        $logo = $this->service->update($headerLogo, $request->file('image'));
        return response()->json($logo);
    }

    public function destroy(HeaderLogo $headerLogo): JsonResponse
    {
        $this->service->delete($headerLogo);
        return response()->json(['message' => 'Deleted successfully']);
    }
}
