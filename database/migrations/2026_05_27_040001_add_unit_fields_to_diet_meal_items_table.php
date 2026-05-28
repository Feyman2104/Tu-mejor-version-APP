<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('diet_meal_items', function (Blueprint $table) {
            // Cantidad y unidad elegidas por el usuario (g, kg, ml, l, oz, unidad).
            // portion_g sigue siendo los gramos canónicos para el cálculo de macros.
            $table->decimal('quantity', 8, 2)->default(0)->after('food_id');
            $table->string('unit', 10)->default('g')->after('quantity');
        });
    }

    public function down(): void
    {
        Schema::table('diet_meal_items', function (Blueprint $table) {
            $table->dropColumn(['quantity', 'unit']);
        });
    }
};
