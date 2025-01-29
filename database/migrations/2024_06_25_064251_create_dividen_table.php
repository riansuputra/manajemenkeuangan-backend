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
        Schema::create('dividen', function (Blueprint $table) {
            $table->id();
            $table->string('emiten');
            $table->decimal('dividen_per_saham', 10, 2);
            $table->decimal('dividen_yield', 5, 2);
            $table->date('cum_date');
            $table->date('payment_date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dividen');
    }
};
