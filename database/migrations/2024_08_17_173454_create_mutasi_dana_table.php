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
        Schema::create('mutasi_dana', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('user');
            $table->integer('tahun');
            $table->tinyInteger('bulan');
            $table->bigInteger('modal');
            $table->bigInteger('harga_unit_tahun');
            $table->bigInteger('alur_dana')->nullable();
            $table->integer('harga_unit_bulan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mutasi_dana');
    }
};
