<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkoutLog extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id', 'routine_day_id', 'date',
        'duration_minutes', 'notes', 'completed',
    ];

    protected function casts(): array
    {
        return [
            'date'      => 'date',
            'completed' => 'boolean',
        ];
    }

    public function user(): BelongsTo       { return $this->belongsTo(User::class); }
    public function routineDay(): BelongsTo { return $this->belongsTo(RoutineDay::class); }
    public function sets(): HasMany         { return $this->hasMany(WorkoutSet::class); }
}
