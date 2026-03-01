<?php

namespace App\Providers;

use Inertia\Inertia;
use App\Models\Customer;
use App\Observers\CustomerObserver;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {

        Customer::observe(CustomerObserver::class);

        Inertia::share('auth.user', function () {
            // Check guards in priority order
            $guards = ['employee', 'web']; // employee first, fallback to web

            foreach ($guards as $guard) {
                if (Auth::guard($guard)->check()) {
                    $user = Auth::guard($guard)->user();

                    return [
                        'id' => $user->id,
                        'name' => $user->name ?? $user->full_name ?? 'No Name',
                        'email' => $user->email ?? null,
                        'mobile' => $user->mobile ?? null,
                        'designation' => $user->designation ?? null,
                        'role' => $user->role ?? null,
                        'code' => $user->code ?? null,
                    ];
                }
            }

            return null;
        });

        Inertia::share('guard', function () {
            if (Auth::guard('employee')->check()) return 'employee';
            if (Auth::guard('web')->check()) return 'admin';
            return null;
        });
    }
}
