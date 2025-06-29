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
            $table->uuid('kode_grup_transaksi')->nullable()->index();
            $table->foreignId('user_id')->constrained('user');
            $table->integer('tahun');
            $table->tinyInteger('bulan');
            $table->decimal('modal', 30, 2)->nullable();
            $table->decimal('harga_unit', 20, 4)->nullable();
            $table->decimal('harga_unit_saat_ini', 20, 4)->nullable();
            $table->decimal('jumlah_unit_penyertaan', 20, 6)->nullable();
            $table->decimal('alur_dana', 30, 2)->nullable();
            $table->boolean('dari_tutup_buku')->default(false);
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
