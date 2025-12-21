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
        Schema::create('mutasi_saldo', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('saldo_id');
            $table->date('tanggal');
            $table->text('keterangan')->nullable();
            $table->enum('jenis', ['masuk', 'keluar']);
            $table->integer('jumlah')->default(0);
            $table->unsignedBigInteger('oleh');
            $table->integer('status')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mutasi_saldo');
    }
};
