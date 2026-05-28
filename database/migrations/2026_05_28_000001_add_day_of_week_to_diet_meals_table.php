<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('diet_meals', function (Blueprint $table) {
            // 1 = Lunes … 7 = Domingo (ISO). Null = plan de día único (legado).
            $table->tinyInteger('day_of_week')->nullable()->after('diet_plan_id');
            $table->index(['diet_plan_id', 'day_of_week', 'meal_number'],
                          'diet_meals_plan_day_meal_idx');
        });
    }

    public function down(): void
    {
        Schema::table('diet_meals', function (Blueprint $table) {
            $table->dropIndex('diet_meals_plan_day_meal_idx');
            $table->dropColumn('day_of_week');
        });
    }
};
