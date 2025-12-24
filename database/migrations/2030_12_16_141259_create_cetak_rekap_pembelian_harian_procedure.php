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
        DROP PROCEDURE IF EXISTS CetakRekapPembelianHarian;
    ");

        DB::unprepared("
        CREATE PROCEDURE CetakRekapPembelianHarian(IN TANGGAL_INPUT DATE)
        BEGIN
            SELECT
                pm.tanggal,
                pm.kodepembelian,
                jp.jenis_produk,
                pr.kodeproduk,
                pr.berat,
                k.karat,
                kp.harga_beli AS harga,

                /* Menghitung TOTAL menggunakan Window Function */
                SUM(pr.berat) OVER() AS TOTALBERAT,
                SUM(kp.harga_beli) OVER() AS TOTALHARGA,
                COUNT(*) OVER() AS TOTALPOTONG

            FROM pembelian pm
            JOIN keranjang_pembelian kp ON pm.kodepembelian = kp.kodepembelian
            JOIN produk pr ON kp.produk_id = pr.id
            JOIN jenis_produk jp ON pr.jenisproduk_id = jp.id
            JOIN karat k ON pr.karat_id = k.id
            WHERE pm.status = 2
            AND DATE(pm.tanggal) = TANGGAL_INPUT;
        END;
    ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared('DROP PROCEDURE IF EXISTS CetakRekapPembelianHarian');
    }
};
