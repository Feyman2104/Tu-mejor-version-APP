<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Routine extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id', 'name', 'generated_by_ai',
        'goal', 'days_per_week', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'generated_by_ai' => 'boolean',
            'is_active'       => 'boolean',
        ];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function days(): HasMany   { return $this->hasMany(RoutineDay::class); }
}
