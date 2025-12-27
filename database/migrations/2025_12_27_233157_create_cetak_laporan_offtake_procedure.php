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
        // Menghapus procedure jika sudah ada sebelumnya
        DB::unprepared("DROP PROCEDURE IF EXISTS CetakLaporanOfftake;");

        DB::unprepared("
            CREATE PROCEDURE CetakLaporanOfftake(
                IN TANGGAL_AWAL DATE,
                IN TANGGAL_AKHIR DATE
            )
            BEGIN
                SELECT
                    o.kodetransaksi,
                    p.kodeproduk,
                    p.nama,
                    p.berat,
                    k.karat,
                    ko.harga_jual,
                    ko.total AS totalkeranjang,
                    o.total,
                    pg.nama as pegawai,

                    /* Menghitung TOTAL menggunakan Window Function */
                    SUM(ko.total) OVER() AS totaltransaksi,
                    SUM(p.berat) OVER() AS totalberat,
                    COUNT(*) OVER() AS totalpotong

                FROM keranjang_offtake ko
                JOIN offtake o ON ko.kodetransaksi = o.kodetransaksi
                JOIN produk p ON ko.produk_id = p.id
                JOIN karat k ON p.karat_id = k.id
                JOIN users u ON ko.oleh = u.id
                JOIN pegawai pg ON u.pegawai_id = pg.id

                /* Filter berdasarkan rentang tanggal yang diinput user */
                WHERE DATE(o.tanggal) BETWEEN TANGGAL_AWAL AND TANGGAL_AKHIR

                /* Diurutkan berdasarkan kode transaksi agar rapi di laporan */
                ORDER BY o.kodetransaksi ASC;
            END
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared('DROP PROCEDURE IF EXISTS CetakLaporanOfftake');
    }
};
