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
        Schema::create('transaksi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('user');
            $table->foreignId('aset_id')->constrained('aset');
            $table->foreignId('sekuritas_id')->nullable()->constrained('sekuritas');
            $table->enum('jenis_transaksi', ['beli', 'jual', 'deposit', 'tarik', 'dividen', 'kas']);
            $table->date('tanggal');
            $table->bigInteger('volume')->nullable();
            $table->bigInteger('harga')->nullable();
            $table->string('deskripsi')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaksi');
    }
};
