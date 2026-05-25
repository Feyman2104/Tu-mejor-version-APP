<?php

namespace App\Http\Controllers;

use App\Models\RoutineExercise;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoutineExerciseController extends Controller
{
    /**
     * Update the display order of routine exercises.
     * Expects: { order: [id1, id2, ...] } — array of RoutineExercise IDs in desired order.
     * Only updates exercises that belong to the authenticated user's routines.
     */
    public function reorder(Request $request): JsonResponse
    {
        $order = $request->validate([
            'order'   => 'required|array',
            'order.*' => 'integer',
        ])['order'];

        foreach ($order as $position => $id) {
            RoutineExercise::where('id', $id)
                ->whereHas('routineDay.routine', fn ($q) => $q->where('user_id', $request->user()->id))
                ->update(['order' => $position]);
        }

        return response()->json(['ok' => true]);
    }
}
