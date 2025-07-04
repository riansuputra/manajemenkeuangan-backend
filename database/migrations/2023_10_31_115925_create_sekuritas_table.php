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
        Schema::create('sekuritas', function (Blueprint $table) {
            $table->id();
            $table->string('nama_sekuritas');
            $table->decimal('fee_beli', 5, 4)->nullable();
            $table->decimal('fee_jual', 5, 4)->nullable();
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
        Schema::dropIfExists('sekuritas');
    }
};
