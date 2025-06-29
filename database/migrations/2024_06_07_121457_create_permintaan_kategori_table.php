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
        Schema::create('permintaan_kategori', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('user');
            $table->foreignId('admin_id')->constrained('admin')->nullable();
            $table->enum('tipe_kategori', ['pemasukan', 'pengeluaran']);
            $table->string('nama_kategori');
            $table->string('nama_kategori_en')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->enum('scope', ['global', 'personal'])->default('global');
            $table->text('message')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permintaan_kategori');
    }
};
