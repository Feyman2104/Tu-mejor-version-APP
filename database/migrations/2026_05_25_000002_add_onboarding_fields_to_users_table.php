<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->tinyInteger('age')->unsigned()->nullable()->after('name');
            $table->decimal('weight_kg', 5, 2)->nullable()->after('age');
            $table->smallInteger('height_cm')->unsigned()->nullable()->after('weight_kg');
            $table->enum('mobility', ['good', 'average', 'limited'])->nullable()->after('height_cm');
            $table->enum('place', ['home', 'gym', 'both'])->nullable()->after('injuries');
            $table->tinyInteger('days_per_week')->unsigned()->nullable()->after('place');
            $table->smallInteger('session_duration_minutes')->unsigned()->nullable()->after('days_per_week');
            $table->json('preferred_muscles')->nullable()->after('session_duration_minutes');
        });

        DB::statement("ALTER TABLE users MODIFY COLUMN goal ENUM('fat_loss','muscle_gain','strength','maintain','flexibility','cardio','body_recomposition') NULL");
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['age', 'weight_kg', 'height_cm', 'mobility', 'place', 'days_per_week', 'session_duration_minutes', 'preferred_muscles']);
        });

        DB::statement("ALTER TABLE users MODIFY COLUMN goal ENUM('fat_loss','muscle_gain','strength','maintain','flexibility','cardio') NULL");
    }
};
