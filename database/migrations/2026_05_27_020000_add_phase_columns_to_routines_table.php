<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('routines', function (Blueprint $table) {
            $table->string('phase', 20)->default('main')->after('is_active');
            $table->tinyInteger('phase_weeks')->default(0)->after('phase');
        });
    }

    public function down(): void
    {
        Schema::table('routines', function (Blueprint $table) {
            $table->dropColumn(['phase', 'phase_weeks']);
        });
    }
};