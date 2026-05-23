<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainerFeedback extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'trainer_id', 'student_id', 'routine_id',
        'workout_log_id', 'content', 'type', 'is_read',
    ];

    protected function casts(): array
    {
        return [
            'is_read'    => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function trainer(): BelongsTo    { return $this->belongsTo(User::class, 'trainer_id'); }
    public function student(): BelongsTo    { return $this->belongsTo(User::class, 'student_id'); }
    public function routine(): BelongsTo    { return $this->belongsTo(Routine::class); }
    public function workoutLog(): BelongsTo { return $this->belongsTo(WorkoutLog::class); }
}
