<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workout_sets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workout_log_id')->constrained()->cascadeOnDelete();
            $table->foreignId('routine_exercise_id')->nullable()->constrained()->nullOnDelete();
            $table->tinyInteger('set_number');
            $table->integer('reps_done')->nullable();
            $table->decimal('weight_kg', 5, 2)->nullable();
            $table->tinyInteger('rpe')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->index('workout_log_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workout_sets');
    }
};
