<?php

namespace App\Http\Controllers;

use App\Models\Routine;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class RoutineController extends Controller
{
    /** Constructor de rutina nueva. */
    public function create(): Response
    {
        return Inertia::render('Workout/RoutineBuilder', [
            'routine' => null,
        ]);
    }

    /** Editar una rutina personalizada existente. */
    public function edit(Request $request, Routine $routine): Response
    {
        $this->authorizeCustom($request, $routine);

        $routine->load([
            'days' => fn ($q) => $q->orderBy('day_number'),
            'days.exercises' => fn ($q) => $q->orderBy('order'),
            'days.exercises.exercise',
        ]);

        $day = $routine->days->first();

        return Inertia::render('Workout/RoutineBuilder', [
            'routine' => [
                'id'        => $routine->id,
                'name'      => $routine->name,
                'exercises' => $day
                    ? $day->exercises->map(fn ($re) => [
                        'exercise'     => $re->exercise,
                        'sets'         => $re->sets,
                        'reps'         => $re->reps,
                        'rest_seconds' => $re->rest_seconds,
                    ])->values()
                    : [],
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);

        DB::transaction(function () use ($request, $data) {
            $routine = $request->user()->routines()->create([
                'name'            => $data['name'],
                'description'     => null,
                'generated_by_ai' => false,
                'goal'            => $request->user()->goal ?? 'maintain',
                'days_per_week'   => 1,
                'is_active'       => false,
            ]);

            $day = $routine->days()->create([
                'day_number' => 1,
                'name'       => $data['name'],
                'focus'      => 'custom',
            ]);

            foreach ($data['exercises'] as $i => $ex) {
                $day->exercises()->create([
                    'exercise_id'  => $ex['exercise_id'],
                    'sets'         => $ex['sets'],
                    'reps'         => $ex['reps'],
                    'rest_seconds' => $ex['rest_seconds'],
                    'order'        => $i,
                    'is_active'    => true,
                ]);
            }
        });

        return redirect()
            ->route('workout.index')
            ->with('success', 'Rutina "' . $data['name'] . '" creada.');
    }

    public function update(Request $request, Routine $routine): RedirectResponse
    {
        $this->authorizeCustom($request, $routine);
        $data = $this->validateData($request);

        DB::transaction(function () use ($routine, $data) {
            $routine->update(['name' => $data['name']]);

            $day = $routine->days()->first()
                ?? $routine->days()->create(['day_number' => 1, 'name' => $data['name'], 'focus' => 'custom']);

            $day->update(['name' => $data['name']]);
            $day->exercises()->delete(); // reemplaza el contenido

            foreach ($data['exercises'] as $i => $ex) {
                $day->exercises()->create([
                    'exercise_id'  => $ex['exercise_id'],
                    'sets'         => $ex['sets'],
                    'reps'         => $ex['reps'],
                    'rest_seconds' => $ex['rest_seconds'],
                    'order'        => $i,
                    'is_active'    => true,
                ]);
            }
        });

        return redirect()
            ->route('workout.index')
            ->with('success', 'Rutina actualizada.');
    }

    public function destroy(Request $request, Routine $routine): RedirectResponse
    {
        $this->authorizeCustom($request, $routine);

        $routine->delete();

        return redirect()
            ->route('workout.index')
            ->with('success', 'Rutina eliminada.');
    }

    /** Solo el dueño puede tocar, y solo rutinas NO generadas por IA. */
    private function authorizeCustom(Request $request, Routine $routine): void
    {
        abort_if($routine->user_id !== $request->user()->id, 403);
        abort_if($routine->generated_by_ai, 403, 'No se pueden modificar rutinas generadas por IA.');
    }

    /** @return array{name: string, exercises: array<int, array{exercise_id:int, sets:int, reps:string, rest_seconds:int}>} */
    private function validateData(Request $request): array
    {
        return $request->validate([
            'name'                       => ['required', 'string', 'max:80'],
            'exercises'                  => ['required', 'array', 'min:1'],
            'exercises.*.exercise_id'    => ['required', 'integer', 'exists:exercises,id'],
            'exercises.*.sets'           => ['required', 'integer', 'min:1', 'max:12'],
            'exercises.*.reps'           => ['required', 'string', 'max:20'],
            'exercises.*.rest_seconds'   => ['required', 'integer', 'min:0', 'max:600'],
        ]);
    }
}
