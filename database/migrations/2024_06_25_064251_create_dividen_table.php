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
            $table->foreignId('aset_id')->constrained('aset');
            $table->decimal('dividen', 10, 2);
            $table->date('cum_date');
            $table->date('ex_date');
            $table->date('recording_date');
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
