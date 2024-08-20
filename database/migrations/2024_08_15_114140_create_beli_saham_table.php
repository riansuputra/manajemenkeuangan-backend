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
        Schema::create('beli_saham', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('user');
            $table->foreignId('saham_id')->constrained('saham');
            $table->foreignId('sekuritas_id')->constrained('sekuritas');
            $table->date('tanggal_beli');
            $table->bigInteger('volume_beli');
            $table->bigInteger('harga_beli');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('beli_saham');
    }
};
