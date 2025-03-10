<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('historis_bulanan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('user');
            $table->foreignId('historis_tahunan_id')->constrained('historis_tahunan');
            $table->tinyInteger('bulan');
            $table->decimal('yield', 10, 2)->nullable();
            $table->decimal('ihsg', 10, 2)->nullable();
            $table->decimal('lq45', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('historis_bulanan');
    }
};
