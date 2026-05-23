<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user() ? [
                    'id'                     => $request->user()->id,
                    'name'                   => $request->user()->name,
                    'email'                  => $request->user()->email,
                    'role'                   => $request->user()->role,
                    'level'                  => $request->user()->level,
                    'goal'                   => $request->user()->goal,
                    'equipment'              => $request->user()->equipment,
                    'injuries'               => $request->user()->injuries,
                    'avatar'                 => $request->user()->avatar,
                    'onboarding_completed_at'=> $request->user()->onboarding_completed_at,
                ] : null,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error'   => fn () => $request->session()->get('error'),
            ],
        ];
    }
}
