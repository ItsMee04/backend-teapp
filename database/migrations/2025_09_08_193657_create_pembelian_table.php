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
        Schema::create('pembelian', function (Blueprint $table) {
            $table->id();
            $table->string('kodepembelian', 100)->unique(); // kode transaksi pembelian utama
            $table->unsignedBigInteger('suplier_id')->nullable();   // jika dari suplier
            $table->unsignedBigInteger('pelanggan_id')->nullable(); // jika dari pelanggan
            $table->date('tanggal')->nullable();
            $table->integer('total')->default(0);
            $table->string('terbilang')->nullable();
            $table->unsignedBigInteger('oleh'); // user yang menginput
            $table->text('catatan')->nullable(); // opsional
            $table->enum('jenispembelian', ['daritoko', 'luartoko']);
            $table->tinyInteger('status')->default(0);
            $table->timestamps();

            // Relasi
            $table->foreign('suplier_id')->references('id')->on('suplier')->nullOnDelete();
            $table->foreign('pelanggan_id')->references('id')->on('pelanggan')->nullOnDelete();
            $table->foreign('oleh')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pembelian');
    }
};
