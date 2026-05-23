<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Align goal ENUM with what the onboarding form and controller use
        DB::statement("ALTER TABLE users MODIFY COLUMN goal ENUM('fat_loss','muscle_gain','strength','maintain','flexibility','cardio') NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE users MODIFY COLUMN goal ENUM('fat_loss','hypertrophy','strength','endurance','mobility') NULL");
    }
};
