<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('routine_exercises', function (Blueprint $table) {
            $table->id();
            $table->foreignId('routine_day_id')->constrained()->cascadeOnDelete();
            $table->foreignId('exercise_id')->constrained()->cascadeOnDelete();
            $table->tinyInteger('sets')->default(3);
            $table->string('reps')->default('10');
            $table->integer('duration_seconds')->nullable();
            $table->integer('rest_seconds')->default(60);
            $table->tinyInteger('rir')->nullable();
            $table->tinyInteger('order')->default(1);
            $table->timestamps();

            $table->index('routine_day_id');
            $table->index('exercise_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('routine_exercises');
    }
};
