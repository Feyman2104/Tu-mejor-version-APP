<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trainer_feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trainer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('routine_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('workout_log_id')->nullable()->constrained()->nullOnDelete();
            $table->text('content');
            $table->enum('type', ['suggestion', 'correction', 'encouragement']);
            $table->boolean('is_read')->default(false);
            $table->timestamp('created_at')->useCurrent();

            $table->index('student_id');
            $table->index('trainer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trainer_feedback');
    }
};
