<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trainer_student', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trainer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->enum('status', ['pending', 'active', 'rejected'])->default('pending');
            $table->enum('invited_by', ['trainer', 'student']);
            $table->text('notes')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();

            $table->index('trainer_id');
            $table->index('student_id');
            $table->unique(['trainer_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trainer_student');
    }
};
