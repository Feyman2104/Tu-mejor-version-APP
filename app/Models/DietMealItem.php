<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DietMealItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'diet_meal_id', 'food_id', 'portion_g',
        'kcal', 'protein_g', 'fat_g', 'carbs_g',
    ];

    protected function casts(): array
    {
        return [
            'portion_g'  => 'float',
            'kcal'       => 'float',
            'protein_g'  => 'float',
            'fat_g'      => 'float',
            'carbs_g'    => 'float',
        ];
    }

    public function dietMeal(): BelongsTo { return $this->belongsTo(DietMeal::class); }
    public function food(): BelongsTo      { return $this->belongsTo(Food::class); }
}
