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
        Schema::create('jual_saham', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('user');
            $table->foreignId('saham_id')->constrained('saham');
            $table->foreignId('sekuritas_id')->constrained('sekuritas');
            $table->date('tanggal_jual');
            $table->bigInteger('volume_jual');
            $table->bigInteger('harga_jual');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jual_saham');
    }
};
