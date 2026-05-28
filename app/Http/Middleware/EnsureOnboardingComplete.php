<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOnboardingComplete
{
    /**
     * Redirige al onboarding si el usuario no lo ha completado.
     * Permite continuar si la ruta actual ya es onboarding o logout.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && is_null($user->onboarding_completed_at)) {
            // Evitar redirecciones infinitas
            if (!$request->routeIs('onboarding', 'onboarding.store', 'logout')) {
                return redirect()->route('onboarding');
            }
        }

        return $next($request);
    }
}
