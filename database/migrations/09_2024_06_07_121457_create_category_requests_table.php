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
        Schema::create('category_requests', function (Blueprint $table) {
            $table->id('id_category_request');
            $table->string('category_type'); // 'pengeluaran' or 'pemasukan'
            $table->string('nama_kategori', 100);
            $table->unsignedBigInteger('user_id'); // Assuming you have a users table
            $table->unsignedBigInteger('admin_id')->nullable(); // To store the admin who approved it
            $table->enum('status', ['Pending', 'Approved', 'Rejected'])->default('Pending');
            $table->text('message')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('user_id')->on('users')->onDelete('cascade');
            $table->foreign('admin_id')->references('admin_id')->on('admins')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('category_requests');
    }
};
