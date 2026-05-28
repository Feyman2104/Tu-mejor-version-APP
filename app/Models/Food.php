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
        'carbs_g', 'fiber_g', 'portion_g', 'density', 'grams_per_unit', 'tcac_code',
    ];

    protected function casts(): array
    {
        return [
            'kcal'           => 'float',
            'protein_g'      => 'float',
            'fat_g'          => 'float',
            'carbs_g'        => 'float',
            'fiber_g'        => 'float',
            'portion_g'      => 'float',
            'density'        => 'float',
            'grams_per_unit' => 'float',
        ];
    }

    /**
     * Unidades de medida ofrecidas para este alimento.
     * Siempre masa (g/kg/oz); volumen (ml/l) si es líquido; unidad si tiene grams_per_unit.
     *
     * @return array<int, string>
     */
    public function availableUnits(): array
    {
        $units = ['g', 'kg', 'oz'];

        if (in_array($this->category, ['bebidas', 'sopas'], true)) {
            array_push($units, 'ml', 'l');
        }

        if ($this->grams_per_unit !== null && $this->grams_per_unit > 0) {
            $units[] = 'unidad';
        }

        return $units;
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