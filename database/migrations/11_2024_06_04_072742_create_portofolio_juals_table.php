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
        Schema::create('portofolio_juals', function (Blueprint $table) {
            $table->id('id_portofolio_jual');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('id_saham')->nullable();
            $table->string('volume_jual', 100)->nullable();
            $table->date('tanggal_jual')->nullable();
            $table->bigInteger('harga_jual')->nullable();
            $table->bigInteger('harga_total')->nullable();
            $table->bigInteger('penjualan')->nullable();
            $table->unsignedBigInteger('id_sekuritas')->nullable();

            $table->foreign('id_saham')->references('id_saham')->on('sahams');
            $table->foreign('id_sekuritas')->references('id_sekuritas')->on('sekuritass');
            $table->foreign('user_id')->references('user_id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('portofolio_juals');
    }
};
