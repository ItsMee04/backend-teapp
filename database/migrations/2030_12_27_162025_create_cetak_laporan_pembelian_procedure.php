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
        DB::unprepared("DROP PROCEDURE IF EXISTS CetakLaporanPembelian;");

        DB::unprepared("
            CREATE PROCEDURE CetakLaporanPembelian(
                IN TANGGAL_AWAL DATE,
                IN TANGGAL_AKHIR DATE
            )
            BEGIN
                SELECT
                    pm.tanggal,
                    pm.kodepembelian,
                    jp.jenis_produk,
                    pr.kodeproduk,
                    pr.berat,
                    k.karat,
                    kp.harga_beli AS harga,
                    pm.total AS hargatotal,

                    /* Menghitung TOTAL menggunakan Window Function */
                    SUM(kp.berat) OVER() AS TOTALBERAT,
                    SUM(pm.total) OVER() AS TOTALTRANSAKSI,
                    COUNT(*) OVER() AS TOTALPOTONG

                FROM pembelian pm
                JOIN keranjang_pembelian kp ON pm.kodepembelian = kp.kodepembelian
                JOIN produk pr ON kp.produk_id = pr.id
                JOIN jenis_produk jp ON pr.jenisproduk_id = jp.id
                JOIN karat k ON pr.karat_id = k.id
                WHERE pm.status = 2
                AND DATE(pm.tanggal) BETWEEN TANGGAL_AWAL AND TANGGAL_AKHIR;
            END;
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared('DROP PROCEDURE IF EXISTS CetakLaporanPembelian');
    }
};
