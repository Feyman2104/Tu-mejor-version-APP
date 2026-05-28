<?php

namespace App\Services;

use App\Models\User;

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

        $mealDistribution = $this->getMealDistribution($adjustedTdee);

        return [
            'bmr'   => round($bmr),
            'tdee'  => round($tdee),
            'target_kcal' => $macros['kcal'],
            'macros' => $macros,
            'meals' => $mealDistribution,
        ];
    }

    private function getMealDistribution(float $targetKcal): array
    {
        $meals = [
            ['name' => 'Desayuno',     'time' => '07:00', 'pct' => 0.25],
            ['name' => 'Merienda AM',  'time' => '10:00', 'pct' => 0.10],
            ['name' => 'Almuerzo',    'time' => '13:00', 'pct' => 0.35],
            ['name' => 'Merienda PM',  'time' => '17:00', 'pct' => 0.10],
            ['name' => 'Cena',         'time' => '20:00', 'pct' => 0.20],
        ];

        foreach ($meals as $index => &$meal) {
            $meal['meal_number'] = $index + 1;
            $meal['target_kcal'] = round($targetKcal * $meal['pct']);
        }
        unset($meal);

        return $meals;
    }
}