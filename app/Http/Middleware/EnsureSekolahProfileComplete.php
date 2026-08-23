<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSekolahProfileComplete
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('livewire/*')) {
            return $next($request);
        }

        $user = $request->user();

        if (
            $user && $user->role === 'sekolah' && is_null($user->sekolah_id)
            && ! $request->routeIs('lengkapi-profil')
            && ! $request->routeIs('logout')
        ) {
            return redirect()->route('lengkapi-profil');
        }

        return $next($request);
    }
}
