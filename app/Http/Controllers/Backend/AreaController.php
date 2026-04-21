<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\AreaRequest;
use App\Services\AreaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AreaController extends Controller
{
    protected $service;

    public function __construct(AreaService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        return response()->json($this->service->getAll());
    }

    public function store(AreaRequest $request)
    {
        $userId = Auth::id(); // get logged-in user id

        return response()->json(
            $this->service->store($request->validated(), $userId)
        );
    }

    public function update(AreaRequest $request, $id)
    {
        return response()->json($this->service->update($id, $request->validated()));
    }

    public function destroy($id)
    {
        return response()->json($this->service->delete($id));
    }
}
