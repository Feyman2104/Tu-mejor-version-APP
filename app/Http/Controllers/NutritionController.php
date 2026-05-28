<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddMealItemRequest;
use App\Http\Requests\UpdateMealItemRequest;
use App\Models\DietMeal;
use App\Models\DietMealItem;
use App\Models\DietPlan;
use App\Models\Food;
use App\Models\User;
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

        // Regenera si no hay plan o si el plan activo quedó sin alimentos (p.ej. se
        // creó antes de sembrar el catálogo de alimentos).
        if (!$activePlan || !$activePlan->meals()->has('items')->exists()) {
            $activePlan = $this->freshPlan($user);
        }

        $meals = $activePlan->meals()->with('items.food')->get();

        return Inertia::render('Nutrition/Index', [
            'plan'  => $activePlan,
            'meals' => $meals,
            'foods' => Food::orderBy('category')->orderBy('name')->get(['id', 'name', 'category', 'kcal', 'protein_g', 'fat_g', 'carbs_g', 'fiber_g', 'portion_g', 'density', 'grams_per_unit']),
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
        $this->freshPlan(auth()->user());

        return redirect()->route('nutrition.index');
    }

    /** POST /nutrition/meals/{meal}/items */
    public function addItem(AddMealItemRequest $request, DietMeal $meal): \Illuminate\Http\RedirectResponse
    {
        abort_if($meal->dietPlan->user_id !== auth()->id(), 403);

        $food = Food::findOrFail($request->food_id);
        $item = $this->nutritionService->buildItemFromInput($food, $request->quantity, $request->unit);

        $meal->items()->create($item);

        return redirect()->route('nutrition.index');
    }

    /** PATCH /nutrition/meal-items/{item} */
    public function updateItem(UpdateMealItemRequest $request, DietMealItem $item): \Illuminate\Http\RedirectResponse
    {
        abort_if($item->dietMeal->dietPlan->user_id !== auth()->id(), 403);

        $food    = $item->food;
        $updated = $this->nutritionService->buildItemFromInput($food, $request->quantity, $request->unit);

        $item->update($updated);

        return redirect()->route('nutrition.index');
    }

    /** DELETE /nutrition/meal-items/{item} */
    public function destroyItem(DietMealItem $item): \Illuminate\Http\RedirectResponse
    {
        abort_if($item->dietMeal->dietPlan->user_id !== auth()->id(), 403);

        $item->delete();

        return redirect()->route('nutrition.index');
    }

    private function freshPlan(User $user): DietPlan
    {
        $user->dietPlans()->where('is_active', true)->update(['is_active' => false]);

        return $this->createDietPlan($user, $this->nutritionService->generateDietPlan($user));
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
                'target_protein_g' => $mealData['target_protein_g'] ?? 0,
            ]);

            foreach ($mealData['items'] ?? [] as $item) {
                $meal->items()->create($item);
            }
        }

        return $plan;
    }
}