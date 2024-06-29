<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pemasukans', function (Blueprint $table) {
            $table->id('id_pemasukan');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->date('tanggal')->nullable();
            $table->string('jumlah', 100)->nullable();
            $table->text('catatan')->nullable();
            $table->unsignedBigInteger('id_kategori_pemasukan')->nullable();

            $table->foreign('id_kategori_pemasukan')->references('id_kategori_pemasukan')->on('kategori_pemasukans');
            $table->foreign('user_id')->references('user_id')->on('users');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pemasukans');
    }
};
