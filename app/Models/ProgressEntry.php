<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProgressEntry extends Model
{
    protected $fillable = [
        'user_id', 'date', 'weight_kg', 'body_fat_pct',
        'muscle_mass_kg', 'notes', 'photo_url', 'recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'date'           => 'date',
            'recorded_at'    => 'datetime',
            'weight_kg'      => 'decimal:2',
            'body_fat_pct'   => 'decimal:2',
            'muscle_mass_kg' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
