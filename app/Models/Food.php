<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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