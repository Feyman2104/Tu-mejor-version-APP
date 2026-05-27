<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('foods', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('category', 50);
            $table->decimal('kcal', 8, 2);
            $table->decimal('protein_g', 8, 2);
            $table->decimal('fat_g', 8, 2);
            $table->decimal('carbs_g', 8, 2);
            $table->decimal('fiber_g', 8, 2)->default(0);
            $table->decimal('portion_g', 8, 2);
            $table->string('tcac_code', 50)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('category');
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('foods');
    }
};