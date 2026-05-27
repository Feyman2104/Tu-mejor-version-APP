<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Food extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'foods';

    protected $fillable = [
        'name', 'slug', 'category', 'kcal', 'protein_g', 'fat_g',
        'carbs_g', 'fiber_g', 'portion_g', 'tcac_code',
    ];

    protected function casts(): array
    {
        return [
            'kcal'      => 'float',
            'protein_g' => 'float',
            'fat_g'     => 'float',
            'carbs_g'   => 'float',
            'fiber_g'   => 'float',
            'portion_g' => 'float',
        ];
    }

    public function dietMealItems(): HasMany
    {
        return $this->hasMany(DietMealItem::class);
    }

    public static function findBySlug(string $slug): ?self
    {
        return static::where('slug', $slug)->first();
    }
}

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

class DietMeal extends Model
{
    use HasFactory;

    protected $fillable = [
        'diet_plan_id', 'meal_number', 'name', 'time',
        'target_kcal', 'target_protein_g',
    ];

    protected function casts(): array
    {
        return [
            'meal_number'    => 'integer',
            'target_kcal'     => 'float',
            'target_protein_g' => 'float',
        ];
    }

    public function dietPlan(): BelongsTo  { return $this->belongsTo(DietPlan::class); }
    public function items(): HasMany        { return $this->hasMany(DietMealItem::class); }
}

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