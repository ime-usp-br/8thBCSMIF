<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRegistration
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if ($user) {
            // Check directly in database for registrations to avoid cache issues
            $hasRegistration = \App\Models\Registration::where('user_id', $user->id)->exists();

            if (! $hasRegistration) {
                // If user is authenticated but has no registrations, redirect to registration form
                return redirect()->route('register-event');
            }
        }

        return $next($request);
    }
}
