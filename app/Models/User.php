<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name', 'email', 'password',
        'role', 'level', 'goal',
        'equipment', 'injuries', 'avatar',
        'onboarding_completed_at',
        'age', 'sex', 'weight_kg', 'height_cm', 'mobility', 'activity_level',
        'place', 'days_per_week', 'session_duration_minutes', 'preferred_muscles',
        'split_type', 'has_trained_before', 'last_trained',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at'       => 'datetime',
            'onboarding_completed_at' => 'datetime',
            'password'                => 'hashed',
            'equipment'               => 'array',
            'injuries'                => 'array',
            'preferred_muscles'       => 'array',
            'has_trained_before'      => 'boolean',
        ];
    }

    public function isStudent(): bool   { return $this->role === 'student'; }
    public function isTrainer(): bool   { return $this->role === 'trainer'; }
    public function isAdmin(): bool     { return $this->role === 'admin'; }
    public function hasCompletedOnboarding(): bool { return $this->onboarding_completed_at !== null; }

    public function routines(): HasMany     { return $this->hasMany(Routine::class); }
    public function workoutLogs(): HasMany  { return $this->hasMany(WorkoutLog::class); }
    public function progressEntries(): HasMany { return $this->hasMany(ProgressEntry::class); }
    public function chatMessages(): HasMany { return $this->hasMany(ChatMessage::class); }
    public function dietPlans(): HasMany    { return $this->hasMany(DietPlan::class); }
}
