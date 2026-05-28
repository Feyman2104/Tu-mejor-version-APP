<?php

namespace App\Services;

use App\Models\Food;
use App\Models\User;
use Illuminate\Support\Collection;

class NutritionService
{
    public function __construct(private readonly ?User $user = null) {}

    public function calculateBMR(float $weightKg, float $heightCm, int $age, string $sex = 'male'): float
    {
        if ($sex === 'female') {
            return (10 * $weightKg) + (6.25 * $heightCm) - (5 * $age) - 161;
        }
        return (10 * $weightKg) + (6.25 * $heightCm) - (5 * $age) + 5;
    }

    public function calculateTDEE(float $bmr, string $activityLevel): float
    {
        $factor = match ($activityLevel) {
            'sedentary'      => 1.2,
            'lightly_active' => 1.375,
            'active'         => 1.55,
            'very_active'    => 1.725,
            default          => 1.375,
        };
        return $bmr * $factor;
    }

    public function adjustForGoal(float $tdee, string $goal): float
    {
        return match ($goal) {
            'fat_loss' => $tdee * 0.80,
            'muscle_gain', 'body_recomposition' => $tdee * 1.15,
            default => $tdee,
        };
    }

    public function calculateMacros(float $tdee, float $weightKg, string $goal): array
    {
        $proteinGPerKg = match ($goal) {
            'fat_loss', 'body_recomposition' => 2.0,
            'muscle_gain' => 1.8,
            default => 1.6,
        };
        $proteinG = $weightKg * $proteinGPerKg;
        $fatG = $weightKg * 0.8;
        $proteinKcal = $proteinG * 4;
        $fatKcal = $fatG * 9;
        $carbsG = ($tdee - $proteinKcal - $fatKcal) / 4;
        $carbsG = max(0, $carbsG);

        return [
            'kcal'       => round($tdee),
            'protein_g'  => round($proteinG),
            'fat_g'      => round($fatG),
            'carbs_g'    => round($carbsG),
        ];
    }

    public function generateDietPlan(?User $user = null): array
    {
        $user = $user ?? $this->user;
        if (!$user) {
            return [];
        }

        $weight = $user->weight_kg ?? 70;
        $height = $user->height_cm ?? 170;
        $age = $user->age ?? 30;
        $sex = $user->sex ?? 'male';
        $activity = $user->activity_level ?? 'lightly_active';
        $goal = $user->goal ?? 'muscle_gain';

        $bmr = $this->calculateBMR($weight, $height, $age, $sex);
        $tdee = $this->calculateTDEE($bmr, $activity);
        $adjustedTdee = $this->adjustForGoal($tdee, $goal);
        $macros = $this->calculateMacros($adjustedTdee, $weight, $goal);

        $mealDistribution = $this->getMealDistribution($adjustedTdee, $macros);

        return [
            'bmr'   => round($bmr),
            'tdee'  => round($tdee),
            'target_kcal' => $macros['kcal'],
            'macros' => $macros,
            'meals' => $mealDistribution,
        ];
    }

    private function getMealDistribution(float $targetKcal, array $macros): array
    {
        $meals = [
            ['name' => 'Desayuno',     'time' => '07:00', 'pct' => 0.25],
            ['name' => 'Merienda AM',  'time' => '10:00', 'pct' => 0.10],
            ['name' => 'Almuerzo',    'time' => '13:00', 'pct' => 0.35],
            ['name' => 'Merienda PM',  'time' => '17:00', 'pct' => 0.10],
            ['name' => 'Cena',         'time' => '20:00', 'pct' => 0.20],
        ];

        $foodsByCategory = Food::all()->groupBy('category');

        foreach ($meals as $index => &$meal) {
            $meal['meal_number'] = $index + 1;
            $meal['target_kcal'] = round($targetKcal * $meal['pct']);
            $meal['target_protein_g'] = round($macros['protein_g'] * $meal['pct']);
            $meal['items'] = $this->selectFoodsForMeal($meal, $foodsByCategory);
        }
        unset($meal);

        return $meals;
    }

    /**
     * Selecciona alimentos del catálogo para una comida según una plantilla de
     * categorías y reparte las kcal objetivo entre ellos. Determinista.
     *
     * @return array<int, array<string, mixed>>
     */
    private function selectFoodsForMeal(array $meal, Collection $foodsByCategory): array
    {
        $items = [];
        foreach ($this->mealTemplate($meal['meal_number']) as $position => [$categories, $share]) {
            $food = $this->pickFood($categories, $foodsByCategory, $meal['meal_number'] + $position);
            if (!$food) {
                continue;
            }
            $items[] = $this->buildItem($food, $meal['target_kcal'] * $share);
        }

        return $items;
    }

    /**
     * Plantilla de roles por comida: [categorías candidatas, fracción de kcal].
     * Las fracciones de cada comida suman ~1.0.
     *
     * @return array<int, array{0: array<int, string>, 1: float}>
     */
    private function mealTemplate(int $mealNumber): array
    {
        return match ($mealNumber) {
            1 => [ // Desayuno
                [['proteínas', 'lácteos'], 0.35],
                [['cereales'], 0.40],
                [['frutas'], 0.25],
            ],
            2 => [ // Merienda AM
                [['lácteos', 'frutas'], 0.55],
                [['frutas', 'snacks'], 0.45],
            ],
            3 => [ // Almuerzo
                [['proteínas'], 0.35],
                [['cereales', 'tubérculos'], 0.35],
                [['leguminosas', 'vegetales'], 0.18],
                [['vegetales'], 0.12],
            ],
            4 => [ // Merienda PM
                [['frutas'], 0.55],
                [['grasas', 'snacks'], 0.45],
            ],
            5 => [ // Cena
                [['proteínas'], 0.45],
                [['tubérculos', 'cereales'], 0.30],
                [['vegetales'], 0.25],
            ],
            default => [
                [['proteínas'], 0.5],
                [['vegetales', 'frutas'], 0.5],
            ],
        };
    }

    /**
     * Elige un alimento de la primera categoría disponible, rotando por índice
     * para dar variedad entre comidas sin perder determinismo.
     */
    private function pickFood(array $categories, Collection $foodsByCategory, int $offset): ?Food
    {
        foreach ($categories as $category) {
            $pool = $foodsByCategory->get($category);
            if ($pool && $pool->isNotEmpty()) {
                return $pool->values()->get($offset % $pool->count());
            }
        }

        return null;
    }

    /**
     * Construye un item con la porción escalada para acercarse a las kcal asignadas.
     * Los macros de Food son por 100 g; se escalan a la porción elegida.
     *
     * @return array<string, mixed>
     */
    private function buildItem(Food $food, float $shareKcal): array
    {
        $kcalPer100 = max(1.0, (float) $food->kcal);
        $portion = ($shareKcal / $kcalPer100) * 100;
        $portion = round($portion / 5) * 5;
        $portion = (float) max(20, min($portion, 400));

        return array_merge(
            ['food_id' => $food->id, 'quantity' => $portion, 'unit' => 'g'],
            $this->macrosForGrams($food, $portion),
        );
    }

    /**
     * Convierte una cantidad en una unidad cualquiera a gramos, usando densidad
     * (volúmenes) o gramos por pieza (unidades) del alimento.
     */
    public function toGrams(float $quantity, string $unit, Food $food): float
    {
        $density = $food->density ?: 1.0;

        return match ($unit) {
            'g'      => $quantity,
            'kg'     => $quantity * 1000,
            'oz'     => $quantity * 28.35,
            'ml'     => $quantity * $density,
            'l'      => $quantity * 1000 * $density,
            'unidad' => $quantity * ($food->grams_per_unit ?: $food->portion_g),
            default  => $quantity,
        };
    }

    /**
     * Escala los macros por-100 g del alimento a una cantidad de gramos dada.
     *
     * @return array<string, float>
     */
    public function macrosForGrams(Food $food, float $grams): array
    {
        $factor = $grams / 100;

        return [
            'portion_g' => round($grams, 1),
            'kcal'      => round($food->kcal * $factor),
            'protein_g' => round($food->protein_g * $factor, 1),
            'fat_g'     => round($food->fat_g * $factor, 1),
            'carbs_g'   => round($food->carbs_g * $factor, 1),
        ];
    }

    /**
     * Construye el payload de un DietMealItem desde una entrada de usuario
     * (alimento + cantidad + unidad).
     *
     * @return array<string, mixed>
     */
    public function buildItemFromInput(Food $food, float $quantity, string $unit): array
    {
        $grams = $this->toGrams($quantity, $unit, $food);

        return array_merge(
            ['food_id' => $food->id, 'quantity' => $quantity, 'unit' => $unit],
            $this->macrosForGrams($food, $grams),
        );
    }
}