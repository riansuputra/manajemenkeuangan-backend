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
        Schema::create('kategori_pemasukan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('user');
            $table->string('nama_kategori_pemasukan');
            $table->string('nama_kategori_pemasukan_en')->nullable();
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
        Schema::dropIfExists('kategori_pemasukan');
    }
};

