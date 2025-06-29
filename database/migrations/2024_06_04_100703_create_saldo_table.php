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
        Schema::create('saldo', function (Blueprint $table) {
            $table->id();
            $table->uuid('kode_grup_transaksi')->nullable()->index();
            $table->foreignId('user_id')->constrained('user');
            $table->foreignId('transaksi_id')->constrained('transaksi');
            $table->date('tanggal');
            $table->enum('tipe_saldo', ['masuk', 'dividen', 'keluar']);
            $table->decimal('saldo', 30, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('saldo');
    }
};
