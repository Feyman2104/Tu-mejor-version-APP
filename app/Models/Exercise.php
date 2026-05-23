<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Exercise extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'muscle_group', 'movement_pattern',
        'level', 'environment', 'goal_tags', 'description',
        'instructions', 'common_errors', 'thumbnail', 'video_url',
        'met_value', 'knowledge_key',
    ];

    protected function casts(): array
    {
        return [
            'goal_tags'     => 'array',
            'instructions'  => 'array',
            'common_errors' => 'array',
            'met_value'     => 'decimal:2',
        ];
    }

    public function contraindications(): HasMany
    {
        return $this->hasMany(ExerciseContraindication::class);
    }

    public function routineExercises(): HasMany
    {
        return $this->hasMany(RoutineExercise::class);
    }
}
