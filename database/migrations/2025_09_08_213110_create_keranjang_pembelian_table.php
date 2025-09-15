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
        Schema::create('keranjang_pembelian', function (Blueprint $table) {
            $table->id();
            $table->string('kodetransaksi', 100)->nullable(); // relasi ke pembelian (kode, bukan id)
            $table->string('kodepembelian', 100); // relasi ke pembelian (kode, bukan id)
            $table->unsignedBigInteger('produk_id')->nullable(); // bisa null kalau produk luar toko
            $table->unsignedBigInteger('harga_beli')->default(0);
            $table->decimal('berat', 8, 3)->nullable()->default(0.000);
            $table->unsignedBigInteger('karat')->nullable();
            $table->unsignedBigInteger('lingkar')->nullable();
            $table->unsignedBigInteger('panjang')->nullable();
            $table->unsignedBigInteger('kondisi_id')->nullable();
            $table->enum('jenis_pembelian', ['daritoko', 'luartoko']);
            $table->unsignedBigInteger('total')->default(0);
            $table->string('terbilang')->nullable();
            $table->text('keterangan')->nullable();
            $table->unsignedBigInteger('oleh');
            $table->tinyInteger('status')->default(1);
            $table->timestamps();

            // Relasi
            $table->foreign('kodepembelian')->references('kodepembelian')->on('pembelian')->cascadeOnDelete();
            $table->foreign('produk_id')->references('id')->on('produk')->nullOnDelete();
            $table->foreign('kondisi_id')->references('id')->on('kondisi')->nullOnDelete();
            $table->foreign('oleh')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('keranjang_pembelian');
    }
};
