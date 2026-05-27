<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** Align routines.goal ENUM with the expanded users.goal ENUM. */
    public function up(): void
    {
        DB::statement("ALTER TABLE routines MODIFY COLUMN goal ENUM(
            'fat_loss','muscle_gain','strength','maintain',
            'flexibility','cardio','body_recomposition',
            'hypertrophy','endurance','mobility'
        ) NOT NULL DEFAULT 'maintain'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE routines MODIFY COLUMN goal ENUM(
            'fat_loss','hypertrophy','strength','endurance','mobility'
        ) NOT NULL DEFAULT 'strength'");
    }
};
