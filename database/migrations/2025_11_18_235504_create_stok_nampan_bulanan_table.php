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
        Schema::create('stok_nampan_bulanan', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal');
            $table->date('tanggal_input');
            $table->string('keterangan', 100);
            $table->enum('status_final', ['PROSES', 'FINAL','BATAL']);
            $table->unsignedBigInteger('oleh');
            $table->integer('status');
            $table->timestamps();

            $table->foreign('oleh')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stok_nampan_bulanan');
    }
};
