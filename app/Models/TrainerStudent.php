<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainerStudent extends Model
{
    protected $table = 'trainer_student';

    protected $fillable = [
        'trainer_id', 'student_id', 'status', 'invited_by', 'notes', 'accepted_at',
    ];

    protected function casts(): array
    {
        return ['accepted_at' => 'datetime'];
    }

    public function trainer(): BelongsTo { return $this->belongsTo(User::class, 'trainer_id'); }
    public function student(): BelongsTo { return $this->belongsTo(User::class, 'student_id'); }
}
