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
        Schema::create('produk', function (Blueprint $table) {
            $table->id();
            $table->string('kodeproduk', 100)->unique();
            $table->string('nama', 100);
            $table->decimal('berat', 8, 3)->nullable()->default(0.000);
            $table->unsignedBigInteger('jenisproduk_id');
            $table->unsignedBigInteger('karat_id');
            $table->unsignedBigInteger('jenis_karat_id');
            $table->integer('lingkar')->default(0);
            $table->integer('panjang')->default(0);
            $table->unsignedBigInteger('harga_jual');
            $table->integer('harga_beli')->default(0);
            $table->text('keterangan')->nullable();
            $table->unsignedBigInteger('kondisi_id')->nullable()->default(1);
            $table->string('image_produk', 100)->nullable();
            $table->integer('status');
            $table->timestamps();

            $table->foreign('jenisproduk_id')->references('id')->on('jenis_produk')->onDelete('cascade');
            $table->foreign('karat_id')->references('id')->on('karat')->onDelete('cascade');
            $table->foreign('jenis_karat_id')->references('id')->on('jenis_karat')->onDelete('cascade');
            $table->foreign('harga_jual')->references('id')->on('harga')->onDelete('cascade');
            $table->foreign('kondisi_id')->references('id')->on('kondisi')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('produk');
    }
};
