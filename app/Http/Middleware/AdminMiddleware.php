<?php

namespace App\Http\Middleware;

use Closure;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        // User must be logged in
        if (!Auth::check()) {
            return redirect()->route('employee.login');
        }

        // Only allow if user role = 'admin'
        if (Auth::user()->role !== 'admin') {
            return Inertia::render('Errors/Forbidden', [
                'message' => 'You are not authorized to access this page.'
            ]);
        }

        Inertia::share('authUser', function () {
            return Auth::user();
        });

        return $next($request);
    }
}
