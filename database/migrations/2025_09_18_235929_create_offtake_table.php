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
        Schema::create('offtake', function (Blueprint $table) {
            $table->id();
            $table->string('kodetransaksi', 100)->unique();
            $table->date('tanggal');
            $table->unsignedBigInteger('suplier_id');
            $table->integer('total')->default(0);
            $table->enum('pembayaran', ['lunas', 'hutang'])->default('hutang');
            $table->text('keterangan')->nullable();
            $table->unsignedBigInteger('oleh');
            $table->integer('status');
            $table->timestamps();

            $table->foreign('suplier_id')->references('id')->on('suplier')->onDelete('cascade');
            $table->foreign('oleh')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('offtake');
    }
};
