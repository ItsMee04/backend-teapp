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
        Schema::create('harga', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('jenis_karat_id');
            $table->integer('harga');
            $table->integer('status')->default(1);
            $table->timestamps();

            $table->foreign('jenis_karat_id')->references('id')->on('jenis_karat')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('harga');
    }
};
