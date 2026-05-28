<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DietPlan extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id', 'name', 'goal', 'daily_kcal_target',
        'protein_g_target', 'fat_g_target', 'carbs_g_target', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'daily_kcal_target'  => 'float',
            'protein_g_target'   => 'float',
            'fat_g_target'       => 'float',
            'carbs_g_target'     => 'float',
            'is_active'          => 'boolean',
        ];
    }

    public function user(): BelongsTo    { return $this->belongsTo(User::class); }
    public function meals(): HasMany     { return $this->hasMany(DietMeal::class)->orderBy('meal_number'); }

    public function calculateMacros(): array
    {
        $totals = ['kcal' => 0, 'protein_g' => 0, 'fat_g' => 0, 'carbs_g' => 0];
        foreach ($this->meals as $meal) {
            foreach ($meal->items as $item) {
                $totals['kcal']      += $item->kcal;
                $totals['protein_g'] += $item->protein_g;
                $totals['fat_g']     += $item->fat_g;
                $totals['carbs_g']   += $item->carbs_g;
            }
        }
        return $totals;
    }
}
