<?php

namespace App\Http\Controllers\Backend;

use App\Models\User;
use Inertia\Inertia;
use App\Services\EmployeeService;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\EmployeeLoginRequest;
use App\Http\Requests\EmployeeStoreRequest;
use App\Http\Requests\EmployeeUpdateRequest;

class EmployeeController extends Controller
{
    public function __construct(protected EmployeeService $service) {}

    // Get all employees
    public function index(): JsonResponse
    {
        $employees = $this->service->all();
        return response()->json($employees);
    }

    // Store new employee
    public function store(EmployeeStoreRequest $request): JsonResponse
    {
        $employee = $this->service->create($request->validated());
        return response()->json(['message' => 'Employee created successfully', 'employee' => $employee], 201);
    }

    // Show employee detail
    public function show($id): JsonResponse
    {
        $employee = $this->service->find($id);
        return response()->json($employee);
    }

    // Update employee
    public function update(EmployeeUpdateRequest $request, $id): JsonResponse
    {
        $employee = $this->service->update($id, $request->validated());
        return response()->json(['message' => 'Employee updated successfully', 'employee' => $employee]);
    }

    // Delete employee
    public function destroy($id): JsonResponse
    {
        $this->service->delete($id);
        return response()->json(['message' => 'Employee deleted successfully']);
    }

    // Employee login
    public function login(EmployeeLoginRequest $request)
    {
        $credentials = $request->only('mobile', 'password');

        $employee = User::where('mobile', $credentials['mobile'])
                                    ->where('status', 'Running') // only allow running employees
                                    ->first();

        if (!$employee || !Hash::check($credentials['password'], $employee->password)) {
            // You can customize the message if employee exists but status is not running
            $statusMessage = '';
            $existingEmployee = User::where('mobile', $credentials['mobile'])
                                    ->where('status', '!=', 'Running')
                                    ->first();
            if ($existingEmployee && $existingEmployee->status !== 'Running') {
                $statusMessage = 'Your account is ' . $existingEmployee->status . '. You cannot login.';
            }

            return back()->withErrors(['message' => $statusMessage ?: 'Invalid credentials']);
        }

        // Login with employee guard
        Auth::guard('web')->login($employee);

        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }
}

