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
            DROP PROCEDURE IF EXISTS CetakRekapTransaksiHarian;
        ");

        DB::unprepared("
        CREATE PROCEDURE CetakRekapTransaksiHarian(IN TANGGAL_INPUT DATE)
            BEGIN
                SELECT
                    tr.tanggal,
                    tr.kodetransaksi,
                    jp.jenis_produk,
                    pr.kodeproduk,
                    pr.berat,
                    k.karat,
                    kr.harga_jual AS harga,

                    /* Menghitung TOTAL menggunakan Window Function */
                    SUM(pr.berat) OVER() AS TOTALBERAT,
                    SUM(kr.harga_jual) OVER() AS TOTALHARGA,
                    COUNT(*) OVER() AS TOTALPOTONG

                FROM keranjang kr
                JOIN transaksi tr ON kr.kodetransaksi = tr.kodetransaksi
                JOIN produk pr ON kr.produk_id = pr.id
                JOIN jenis_produk jp ON pr.jenisproduk_id = jp.id
                JOIN karat k ON pr.karat_id = k.id
                WHERE tr.status = 2
                AND DATE(tr.tanggal) = TANGGAL_INPUT;
            END
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared('DROP PROCEDURE IF EXISTS CetakRekapTransaksiHarian');
    }
};
