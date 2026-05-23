<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exercise_contraindications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exercise_id')->constrained()->cascadeOnDelete();
            $table->enum('body_zone', ['lumbar', 'rodilla', 'hombro', 'muneca', 'cadera']);
            $table->enum('injury_phase', ['aguda', 'subaguda', 'cronica', 'retorno']);
            $table->text('recommendation');
            $table->timestamps();

            $table->index('exercise_id');
            $table->index('body_zone');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exercise_contraindications');
    }
};
