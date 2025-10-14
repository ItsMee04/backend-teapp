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
        Schema::create('stok_nampan', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal');
            $table->date('tanggal_input');
            $table->text('keterangan')->nullable();
            $table->enum('status', ['Batal','Proses','Final'])->default('proses');
            $table->unsignedBigInteger('oleh');
            $table->timestamps();

            $table->foreign('oleh')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stok_nampan');
    }
};
