<?php

namespace App\Http\Controllers;

use App\Models\Exercise;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ExerciseController extends Controller
{
    public function index(Request $request): Response
    {
        $exercises = Exercise::query()
            ->when($request->search, fn ($q, $s) =>
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('muscle_group', 'like', "%{$s}%")
            )
            ->when($request->environment, fn ($q, $e) =>
                $q->where('environment', $e)
            )
            ->when($request->level, fn ($q, $l) =>
                $q->where('level', $l)
            )
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Exercises/Index', [
            'exercises' => $exercises,
            'filters'   => $request->only('search', 'environment', 'level'),
        ]);
    }

    public function show(Exercise $exercise): Response
    {
        $exercise->load('contraindications');

        return Inertia::render('Exercises/Show', [
            'exercise' => $exercise,
        ]);
    }
}
