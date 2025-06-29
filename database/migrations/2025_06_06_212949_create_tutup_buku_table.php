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
        Schema::create('tutup_buku', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('user')
                  ->onDelete('cascade');
            $table->year('tahun');
            $table->decimal('harga_unit_akhir', 20, 4);
            $table->decimal('valuasi_akhir', 20, 4);
            $table->decimal('unit_penyertaan_awal', 20, 4);
            $table->timestamps();
            $table->unique(['user_id', 'tahun']); // 1 user hanya bisa tutup buku sekali per tahun
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tutup_buku');
    }
};
