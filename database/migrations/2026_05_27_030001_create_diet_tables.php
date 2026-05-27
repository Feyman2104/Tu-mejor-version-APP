<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('diet_plans')) {
            Schema::create('diet_plans', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->enum('goal', ['fat_loss', 'maintenance', 'muscle_gain', 'body_recomposition'])
                      ->default('maintenance');
                $table->decimal('daily_kcal_target', 8, 2);
                $table->decimal('protein_g_target', 8, 2);
                $table->decimal('fat_g_target', 8, 2);
                $table->decimal('carbs_g_target', 8, 2);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();

                $table->index('user_id');
                $table->index('is_active');
            });
        }

        if (!Schema::hasTable('diet_meals')) {
            Schema::create('diet_meals', function (Blueprint $table) {
                $table->id();
                $table->foreignId('diet_plan_id')->constrained()->cascadeOnDelete();
                $table->tinyInteger('meal_number');
                $table->string('name', 50);
                $table->time('time');
                $table->decimal('target_kcal', 8, 2)->default(0);
                $table->decimal('target_protein_g', 8, 2)->default(0);
                $table->timestamps();

                $table->index(['diet_plan_id', 'meal_number']);
            });
        }

        if (!Schema::hasTable('diet_meal_items')) {
            Schema::create('diet_meal_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('diet_meal_id')->constrained('diet_meals')->cascadeOnDelete();
                $table->foreignId('food_id')->constrained('foods')->cascadeOnDelete();
                $table->decimal('portion_g', 8, 2);
                $table->decimal('kcal', 8, 2);
                $table->decimal('protein_g', 8, 2);
                $table->decimal('fat_g', 8, 2);
                $table->decimal('carbs_g', 8, 2);
                $table->timestamps();

                $table->index('diet_meal_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('diet_meal_items');
        Schema::dropIfExists('diet_meals');
        Schema::dropIfExists('diet_plans');
    }
};