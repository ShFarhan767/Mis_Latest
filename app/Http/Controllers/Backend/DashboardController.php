<?php

namespace App\Http\Controllers\Backend;

use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user(); // get currently logged-in user
        return Inertia::render('Dashboard', [
            'userRole' => $user->role, // 'admin' or 'employee'
            "userId"   => auth()->id(),
        ]);
    }
}
