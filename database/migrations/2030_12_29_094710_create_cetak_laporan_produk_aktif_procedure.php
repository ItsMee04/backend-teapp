<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::unprepared("
            DROP PROCEDURE IF EXISTS CetakLaporanProdukAktif;
        ");

        DB::unprepared("
            CREATE PROCEDURE CetakLaporanProdukAktif(
                IN TANGGAL_AWAL DATE,
                IN TANGGAL_AKHIR DATE
            )
            BEGIN
                SELECT
                    p.kodeproduk,
                    p.nama,
                    p.berat,
                    p.created_at,
                    jp.jenis_produk,
                    k.karat,
                    jk.jenis,
                    h.harga AS harga_jual,
                    p.harga_beli,
                    kn.kondisi
                FROM produk p
                JOIN jenis_produk jp ON p.jenisproduk_id = jp.id
                JOIN karat k ON p.karat_id = k.id
                JOIN jenis_karat jk ON p.jenis_karat_id = jk.id
                JOIN harga h ON p.harga_jual = h.id
                JOIN kondisi kn ON p.kondisi_id = kn.id
                WHERE p.status = 1
                AND DATE(p.created_at) BETWEEN TANGGAL_AWAL AND TANGGAL_AKHIR
                ORDER BY p.created_at DESC;
            END
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared('DROP PROCEDURE IF EXISTS CetakLaporanProdukAktif');
    }
};
