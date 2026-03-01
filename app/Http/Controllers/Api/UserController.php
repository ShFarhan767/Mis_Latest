<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $role = $request->query('role'); // e.g., ?role=employee

        $query = User::query();

        if ($role) {
            $query->where('role', $role);
        }

        $users = $query->get();

        return response()->json($users);
    }
}
