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
        Schema::create('kinerja_portofolio', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('user');
            $table->foreignId('transaksi_id')->constrained('transaksi');
            $table->bigInteger('valuasi_saat_ini')->nullable();
            $table->decimal('yield', 10, 2)->nullable();
            $table->integer('ihsg_start')->nullable(); 
            $table->integer('ihsg_end')->nullable();
            $table->decimal('yield_ihsg', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kinerja_portofolio');
    }
};
