<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exercises', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('muscle_group');
            $table->enum('movement_pattern', ['push', 'pull', 'hinge', 'squat', 'carry', 'rotation', 'core']);
            $table->enum('level', ['beginner', 'intermediate', 'advanced']);
            $table->enum('environment', ['gym', 'home', 'both']);
            $table->json('goal_tags')->nullable();
            $table->text('description');
            $table->json('instructions');
            $table->json('common_errors')->nullable();
            $table->string('thumbnail')->nullable();
            $table->string('video_url')->nullable();
            $table->decimal('met_value', 4, 2)->default(4.00);
            $table->string('knowledge_key')->nullable()->index();
            $table->timestamps();

            $table->index('muscle_group');
            $table->index('level');
            $table->index('environment');
            $table->index('movement_pattern');
            $table->fullText(['name', 'description', 'muscle_group']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exercises');
    }
};
