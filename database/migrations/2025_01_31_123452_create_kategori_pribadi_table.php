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
        Schema::create('kategori_pribadi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('user'); // Kategori milik siapa
            $table->enum('tipe_kategori', ['pemasukan', 'pengeluaran']);
            $table->string('nama_kategori');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kategori_pribadi');
    }
};
