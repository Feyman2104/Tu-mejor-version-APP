<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('routines', function (Blueprint $table) {
            $table->text('description')->nullable()->after('name');
        });

        Schema::table('routine_exercises', function (Blueprint $table) {
            $table->text('notes')->nullable()->after('rir');
        });
    }

    public function down(): void
    {
        Schema::table('routines', function (Blueprint $table) {
            $table->dropColumn('description');
        });

        Schema::table('routine_exercises', function (Blueprint $table) {
            $table->dropColumn('notes');
        });
    }
};
