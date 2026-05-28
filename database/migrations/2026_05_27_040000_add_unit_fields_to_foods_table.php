<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('foods', function (Blueprint $table) {
            // Densidad en g/ml para convertir volúmenes (ml/l) a gramos. Agua ≈ 1.0.
            $table->decimal('density', 6, 3)->default(1.0)->after('portion_g');
            // Gramos por unidad/pieza (ej. 1 huevo ≈ 50 g). Null → no se ofrece "unidad".
            $table->decimal('grams_per_unit', 8, 2)->nullable()->after('density');
        });
    }

    public function down(): void
    {
        Schema::table('foods', function (Blueprint $table) {
            $table->dropColumn(['density', 'grams_per_unit']);
        });
    }
};
