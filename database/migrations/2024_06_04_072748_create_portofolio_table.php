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
        Schema::create('portofolio', function (Blueprint $table) {
            $table->id();
            $table->uuid('kode_grup_transaksi')->nullable()->index();
            $table->foreignId('user_id')->constrained('user');
            $table->foreignId('aset_id')->constrained('aset');
            $table->date('tanggal');
            $table->foreignId('kinerja_portofolio_id')
                  ->constrained('kinerja_portofolio')
                  ->onDelete('cascade');
            $table->decimal('volume', 20, 4);
            $table->decimal('avg_price', 20, 4)->nullable();
            $table->decimal('cur_price', 20, 4)->nullable();
            $table->decimal('dividen', 20, 4)->nullable();
            $table->timestamps(); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('portofolio');
    }
};
