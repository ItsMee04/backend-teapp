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
        Schema::create('keranjang_offtake', function (Blueprint $table) {
            $table->id();
            $table->string('kodetransaksi', 100);
            $table->unsignedBigInteger('produk_id');
            $table->integer('harga_jual')->default(0);
            $table->decimal('berat', 8, 3)->default(0.000);
            $table->integer('karat')->default(0);
            $table->integer('lingkar')->default(0);
            $table->integer('panjang')->default(0);
            $table->integer('total');
            $table->string('terbilang', 100);
            $table->unsignedBigInteger('oleh');
            $table->integer('status');
            $table->timestamps();

            $table->foreign('kodetransaksi')->references('kodetransaksi')->on('offtake')->onDelete('cascade');
            $table->foreign('produk_id')->references('id')->on('produk')->onDelete('cascade');
            $table->foreign('oleh')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('keranjang_offtake');
    }
};
