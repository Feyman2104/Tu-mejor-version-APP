<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkoutSet extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'workout_log_id', 'routine_exercise_id',
        'set_number', 'reps_done', 'weight_kg', 'rpe', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
            'weight_kg'    => 'decimal:2',
        ];
    }

    public function workoutLog(): BelongsTo      { return $this->belongsTo(WorkoutLog::class); }
    public function routineExercise(): BelongsTo { return $this->belongsTo(RoutineExercise::class); }
}
