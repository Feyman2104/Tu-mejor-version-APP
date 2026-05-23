<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('routine_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('routine_id')->constrained()->cascadeOnDelete();
            $table->tinyInteger('day_number');
            $table->string('name');
            $table->string('focus')->nullable();
            $table->timestamps();

            $table->index('routine_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('routine_days');
    }
};
