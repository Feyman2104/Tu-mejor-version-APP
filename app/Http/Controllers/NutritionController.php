<?php

namespace App\Http\Controllers;

use App\Models\DietPlan;
use App\Models\Food;
use App\Services\NutritionService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class NutritionController extends Controller
{
    public function __construct(private readonly NutritionService $nutritionService) {}

    public function index(): \Inertia\Response
    {
        $user = auth()->user();

        $activePlan = $user->dietPlans()->where('is_active', true)->first();

        if (!$activePlan) {
            $planData = $this->nutritionService->generateDietPlan($user);
            $activePlan = $this->createDietPlan($user, $planData);
        }

        $meals = $activePlan->meals()->with('items.food')->get();

        return Inertia::render('Nutrition/Index', [
            'plan'  => $activePlan,
            'meals' => $meals,
            'foods' => Food::orderBy('category')->orderBy('name')->get(['id', 'name', 'category', 'kcal', 'protein_g', 'fat_g', 'carbs_g', 'portion_g']),
        ]);
    }

    public function show(DietPlan $plan): \Inertia\Response
    {
        abort_if($plan->user_id !== auth()->id(), 403);

        $meals = $plan->meals()->with('items.food')->get();
        $totals = $plan->calculateMacros();

        return Inertia::render('Nutrition/Show', [
            'plan'    => $plan,
            'meals'   => $meals,
            'totals'  => $totals,
        ]);
    }

    public function regenerate(Request $request): \Illuminate\Http\RedirectResponse
    {
        $user = auth()->user();

        $user->dietPlans()->where('is_active', true)->update(['is_active' => false]);

        $planData = $this->nutritionService->generateDietPlan($user);
        $plan = $this->createDietPlan($user, $planData);

        return redirect()->route('nutrition.show', $plan);
    }

    private function createDietPlan($user, array $planData): DietPlan
    {
        $dietGoal = match ($user->goal) {
            'fat_loss'           => 'fat_loss',
            'muscle_gain'        => 'muscle_gain',
            'body_recomposition' => 'body_recomposition',
            default              => 'maintenance',
        };

        $plan = $user->dietPlans()->create([
            'name'             => 'Plan nutricional personalizado',
            'goal'             => $dietGoal,
            'daily_kcal_target' => $planData['target_kcal'],
            'protein_g_target'  => $planData['macros']['protein_g'],
            'fat_g_target'      => $planData['macros']['fat_g'],
            'carbs_g_target'    => $planData['macros']['carbs_g'],
            'is_active'        => true,
        ]);

        foreach ($planData['meals'] as $mealData) {
            $meal = $plan->meals()->create([
                'meal_number'      => $mealData['meal_number'] ?? 1,
                'name'             => $mealData['name'],
                'time'             => $mealData['time'],
                'target_kcal'      => $mealData['target_kcal'],
                'target_protein_g' => 0,
            ]);
        }

        return $plan;
    }
}