<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RoutineExercise extends Model
{
    protected $fillable = [
        'routine_day_id', 'exercise_id', 'sets', 'reps',
        'duration_seconds', 'rest_seconds', 'rir', 'order', 'notes', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function routineDay(): BelongsTo { return $this->belongsTo(RoutineDay::class); }
    public function exercise(): BelongsTo   { return $this->belongsTo(Exercise::class); }
    public function workoutSets(): HasMany  { return $this->hasMany(WorkoutSet::class); }
}
