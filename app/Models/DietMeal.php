<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DietMeal extends Model
{
    use HasFactory;

    protected $fillable = [
        'diet_plan_id', 'day_of_week', 'meal_number', 'name', 'time',
        'target_kcal', 'target_protein_g',
    ];

    protected function casts(): array
    {
        return [
            'day_of_week'      => 'integer',
            'meal_number'      => 'integer',
            'target_kcal'      => 'float',
            'target_protein_g' => 'float',
        ];
    }

    public function dietPlan(): BelongsTo  { return $this->belongsTo(DietPlan::class); }
    public function items(): HasMany        { return $this->hasMany(DietMealItem::class); }
}
