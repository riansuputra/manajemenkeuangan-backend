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
        Schema::create('anggarans', function (Blueprint $table) {
            $table->id('id_anggaran');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->enum('periode', ['Mingguan', 'Bulanan', 'Tahunan']);
            $table->string('anggaran', 100)->nullable();
            $table->date('tanggal_mulai')->nullable();
            $table->date('tanggal_selesai')->nullable();
            $table->unsignedBigInteger('id_kategori_pengeluaran')->nullable();
            $table->foreign('id_kategori_pengeluaran')->references('id_kategori_pengeluaran')->on('kategori_pengeluarans');
            $table->foreign('user_id')->references('user_id')->on('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('anggarans');
    }
};
