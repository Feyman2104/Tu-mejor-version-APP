<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('progress_entries', function (Blueprint $table) {
            $table->decimal('muscle_mass_kg', 5, 2)->nullable()->after('body_fat_pct');
            $table->timestamp('recorded_at')->nullable()->after('photo_url');
            $table->date('date')->nullable()->change();
        });

        // Backfill recorded_at from existing date values
        \DB::table('progress_entries')->whereNull('recorded_at')->update([
            'recorded_at' => \DB::raw('COALESCE(date, created_at)'),
        ]);
    }

    public function down(): void
    {
        Schema::table('progress_entries', function (Blueprint $table) {
            $table->dropColumn(['muscle_mass_kg', 'recorded_at']);
        });
    }
};
