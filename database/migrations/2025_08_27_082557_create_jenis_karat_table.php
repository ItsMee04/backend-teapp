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
        Schema::create('jenis_karat', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('karat_id');
            $table->string('jenis', 100);
            $table->integer('status')->default(1);
            $table->timestamps();

            $table->foreign('karat_id')->references('id')->on('karat')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jenis_karat');
    }
};
