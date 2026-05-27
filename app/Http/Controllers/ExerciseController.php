<?php

namespace App\Http\Controllers;

use App\Models\Exercise;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
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

    /**
     * Devuelve el primer gif_url disponible por cada knowledge_key solicitado.
     * Usado por el analizador de postura (briefing pre-ejercicio).
     * GET /exercises/gifs?keys[]=squat&keys[]=pushup...
     * Respuesta: { "squat": "https://...", "pushup": "https://..." }
     */
    public function gifsByKeys(Request $request): JsonResponse
    {
        $keys = array_filter((array) $request->input('keys', []), fn ($k) => is_string($k) && strlen($k) <= 60);

        if (empty($keys)) {
            return response()->json([]);
        }

        $exercises = Exercise::whereIn('knowledge_key', $keys)
            ->whereNotNull('gif_url')
            ->select('knowledge_key', 'gif_url')
            ->orderBy('id')
            ->get();

        // Un gif por key (el primero con imagen)
        $result = [];
        foreach ($exercises as $exercise) {
            $result[$exercise->knowledge_key] ??= $exercise->gif_url;
        }

        return response()->json($result);
    }

    /**
     * Endpoint JSON para el buscador en sesión de entrenamiento.
     * Devuelve hasta 30 ejercicios que coincidan con la query y/o grupo muscular.
     */
    public function apiSearch(Request $request): JsonResponse
    {
        $exercises = Exercise::query()
            ->when($request->q, fn ($q, $s) =>
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('muscle_group', 'like', "%{$s}%")
            )
            ->when($request->muscle_group, fn ($q, $mg) =>
                $q->where('muscle_group', $mg)
            )
            ->select('id', 'name', 'slug', 'muscle_group', 'level', 'thumbnail', 'gif_url', 'description')
            ->orderBy('name')
            ->limit(30)
            ->get();

        return response()->json($exercises);
    }

    /**
     * Grupos musculares que REALMENTE tienen ejercicios en el catálogo.
     * Evita mostrar categorías vacías en el buscador. Devuelve [{ value, label }].
     * GET /exercises/muscle-groups
     */
    public function muscleGroups(): JsonResponse
    {
        // Orden lógico de presentación; los que no estén listados van al final A-Z.
        $order = [
            'pecho', 'espalda', 'hombros', 'trapecios', 'bíceps', 'tríceps', 'antebrazos',
            'cuádriceps', 'isquiotibiales', 'glúteos', 'gemelos', 'cadera', 'core', 'cuerpo completo',
        ];

        $groups = Exercise::query()
            ->select('muscle_group')
            ->distinct()
            ->pluck('muscle_group')
            ->filter()
            ->sort(function ($a, $b) use ($order) {
                $ia = array_search($a, $order, true);
                $ib = array_search($b, $order, true);
                $ia = $ia === false ? PHP_INT_MAX : $ia;
                $ib = $ib === false ? PHP_INT_MAX : $ib;
                return $ia <=> $ib ?: strcmp($a, $b);
            })
            ->map(fn ($g) => ['value' => $g, 'label' => mb_convert_case($g, MB_CASE_TITLE, 'UTF-8')])
            ->values();

        return response()->json($groups);
    }

    public function show(Exercise $exercise): Response
    {
        $exercise->load('contraindications');

        return Inertia::render('Exercises/Show', [
            'exercise' => $exercise,
        ]);
    }
}
