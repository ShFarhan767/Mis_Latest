<?php

namespace App\Http\Middleware;

use App\Models\Logo;
use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        [$message, $author] = str(Inspiring::quotes()->random())->explode('-');

        // Determine which guard is authenticated
        $guard = Auth::guard('employee')->check()
            ? 'employee'
            : (Auth::guard('web')->check()
                ? 'web'
                : null);

        // Get the authenticated user from the active guard
        $user = $guard ? Auth::guard($guard)->user() : null;

        $branding = Logo::latest()->first();

        return [
            ...parent::share($request),

            'name' => config('app.name'),
            'quote' => [
                'message' => trim($message),
                'author' => trim($author),
            ],

            // ✅ Share authenticated user + active guard
            'auth' => [
                'user' => $user,
                'guard' => $guard,
            ],

            'branding' => $branding, // ✅ now actually shared

            // ✅ Include Ziggy for route() support in Vue
            'ziggy' => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],

            // ✅ Sidebar state persistence
            'sidebarOpen' => !$request->hasCookie('sidebar_state')
                || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
