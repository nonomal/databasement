<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class DemoModeMiddleware
{
    /**
     * Routes that demo users are not allowed to access.
     *
     * @var array<string>
     */
    protected array $restrictedRoutesForDemo = [
        'profile.edit',
        'user-password.edit',
        'two-factor.show',
    ];

    /**
     * Handle an incoming request.
     * In demo mode, ensure demo user exists and restrict demo users from certain routes.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('app.demo_mode')) {
            return $next($request);
        }

        // Ensure demo user exists when visiting login page
        if ($request->route()?->getName() === 'login') {
            $this->ensureDemoUserExists();
        }

        // Block demo users from restricted routes
        if (Auth::check() && Auth::user()->isDemo()) {
            if (in_array($request->route()?->getName(), $this->restrictedRoutesForDemo)) {
                abort(403);
            }
        }

        return $next($request);
    }

    /**
     * Create the demo user if it doesn't exist.
     */
    protected function ensureDemoUserExists(): void
    {
        User::firstOrCreate(
            ['email' => config('app.demo_user_email')],
            [
                'name' => 'Demo User',
                'password' => bcrypt(config('app.demo_user_password')),
                'role' => User::ROLE_DEMO,
                'invitation_accepted_at' => now(),
            ]
        );
    }
}
