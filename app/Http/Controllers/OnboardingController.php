<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OnboardingController extends Controller
{
    public function show(): Response
    {
        return Inertia::render('Onboarding');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'level'     => ['required', 'in:beginner,intermediate,advanced'],
            'goal'      => ['required', 'in:fat_loss,muscle_gain,strength,maintain,flexibility,cardio'],
            'equipment' => ['required', 'array'],
            'equipment.*' => ['in:none,dumbbells,barbell,pull_up_bar,cables,machines'],
            'injuries'  => ['nullable', 'array'],
            'injuries.*' => ['in:knee,back,shoulder,wrist,ankle,neck,hip,none'],
        ]);

        $user = $request->user();
        $user->update([
            'level'                    => $validated['level'],
            'goal'                     => $validated['goal'],
            'equipment'                => $validated['equipment'],
            'injuries'                 => $validated['injuries'] ?? [],
            'onboarding_completed_at'  => now(),
        ]);

        return redirect()->route('dashboard');
    }
}
