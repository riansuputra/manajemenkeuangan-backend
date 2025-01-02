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
        Schema::create('historis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('user');
            $table->integer('bulan');
            $table->integer('tahun');    
            $table->decimal('yield', 10, 2)->nullable();
            $table->decimal('ihsg_start', 10, 2)->nullable();
            $table->decimal('ihsg_end', 10, 2)->nullable();
            $table->decimal('yield_ihsg', 10, 2)->nullable();
            $table->unique(['user_id', 'bulan', 'tahun']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('historis');
    }
};
