<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateRoutineJob;
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
            'name'                     => ['required', 'string', 'max:255'],
            'age'                      => ['nullable', 'integer', 'min:10', 'max:100'],
            'sex'                      => ['nullable', 'in:male,female,other'],
            'weight_kg'                => ['nullable', 'numeric', 'min:20', 'max:300'],
            'height_cm'                => ['nullable', 'integer', 'min:100', 'max:250'],
            'mobility'                 => ['nullable', 'in:good,average,limited'],
            'activity_level'           => ['nullable', 'in:sedentary,lightly_active,active,very_active'],
            'level'                    => ['required', 'in:beginner,intermediate,advanced'],
            'goal'                     => ['required', 'in:fat_loss,muscle_gain,strength,maintain,flexibility,cardio,body_recomposition'],
            'place'                    => ['required', 'in:home,gym,both'],
            'equipment'                => ['required', 'array', 'min:1'],
            'equipment.*'              => ['in:none,dumbbells,barbell,pull_up_bar,cables,machines,kettlebell,bands'],
            'injuries'                 => ['nullable', 'array'],
            'injuries.*.zone'          => ['required', 'string', 'in:knee,back,shoulder,wrist,ankle,neck,hip'],
            'injuries.*.notes'         => ['nullable', 'string', 'max:300'],
            'days_per_week'            => ['required', 'integer', 'min:1', 'max:7'],
            'session_duration_minutes' => ['required', 'integer', 'min:15', 'max:180'],
            'preferred_muscles'        => ['nullable', 'array'],
            'preferred_muscles.*'      => ['string'],
            'split_type'               => ['nullable', 'in:auto,full_body,upper_lower,ppl,weider'],
            'has_trained_before'       => ['nullable', 'boolean'],
            'last_trained'             => ['nullable', 'in:never,currently,lt_1m,1_3m,3_6m,gt_6m'],
        ]);

        $user = $request->user();
        $user->update([
            'name'                     => $validated['name'],
            'age'                      => $validated['age'] ?? null,
            'sex'                      => $validated['sex'] ?? null,
            'weight_kg'                => $validated['weight_kg'] ?? null,
            'height_cm'                => $validated['height_cm'] ?? null,
            'mobility'                 => $validated['mobility'] ?? null,
            'activity_level'           => $validated['activity_level'] ?? null,
            'level'                    => $validated['level'],
            'goal'                     => $validated['goal'],
            'place'                    => $validated['place'],
            'equipment'                => $validated['equipment'],
            'injuries'                 => $validated['injuries'] ?? [],
            'days_per_week'            => $validated['days_per_week'],
            'session_duration_minutes' => $validated['session_duration_minutes'],
            'preferred_muscles'        => $validated['preferred_muscles'] ?? [],
            'split_type'               => $validated['split_type'] ?? 'auto',
            'has_trained_before'       => $validated['has_trained_before'] ?? null,
            'last_trained'             => $validated['last_trained'] ?? null,
            'onboarding_completed_at'  => now(),
        ]);

        // Ejecutar sincrónicamente: la animación frontend cubre el tiempo de espera.
        // set_time_limit(0) evita que XAMPP/PHP corte la petición si la IA tarda >30s.
        set_time_limit(0);

        try {
            GenerateRoutineJob::dispatchSync($user);
        } catch (\Throwable $e) {
            // Si la generación falla (timeout de IA, error de red, etc.), el job
            // ya intentó crear una rutina de fallback internamente. Registramos el
            // error y redirigimos igualmente — el usuario llega al dashboard con
            // la rutina básica o con un aviso para generarla manualmente.
            \Illuminate\Support\Facades\Log::error('Onboarding routine generation failed', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);

            return redirect()->route('dashboard')
                ->with('info', 'Tu perfil está listo. La generación de rutina tardó más de lo esperado — pulsa "Generar nueva rutina" desde el dashboard para crearla.');
        }

        return redirect()->route('dashboard');
    }
}
