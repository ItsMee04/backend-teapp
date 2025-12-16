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
                    /* ================= ANTING ================= */
                    COUNT(CASE WHEN jp.jenis_produk = 'ANTING' THEN 1 END) AS anting_qty,
                    SUM(CASE WHEN jp.jenis_produk = 'ANTING' THEN k.berat ELSE 0 END) AS anting_gr,
                    SUM(CASE WHEN jp.jenis_produk = 'ANTING' THEN k.harga_beli ELSE 0 END) AS anting_rp,

                    /* ================= CINCIN ================= */
                    COUNT(CASE WHEN jp.jenis_produk = 'CINCIN' THEN 1 END) AS cincin_qty,
                    SUM(CASE WHEN jp.jenis_produk = 'CINCIN' THEN k.berat ELSE 0 END) AS cincin_gr,
                    SUM(CASE WHEN jp.jenis_produk = 'CINCIN' THEN k.harga_beli ELSE 0 END) AS cincin_rp,

                    /* ================= GELANG ================= */
                    COUNT(CASE WHEN jp.jenis_produk = 'GELANG' THEN 1 END) AS gelang_qty,
                    SUM(CASE WHEN jp.jenis_produk = 'GELANG' THEN k.berat ELSE 0 END) AS gelang_gr,
                    SUM(CASE WHEN jp.jenis_produk = 'GELANG' THEN k.harga_beli ELSE 0 END) AS gelang_rp,

                    /* ================= KALUNG ================= */
                    COUNT(CASE WHEN jp.jenis_produk = 'KALUNG' THEN 1 END) AS kalung_qty,
                    SUM(CASE WHEN jp.jenis_produk = 'KALUNG' THEN k.berat ELSE 0 END) AS kalung_gr,
                    SUM(CASE WHEN jp.jenis_produk = 'KALUNG' THEN k.harga_beli ELSE 0 END) AS kalung_rp,

                    /* ================= LIONTIN ================= */
                    COUNT(CASE WHEN jp.jenis_produk = 'LIONTIN' THEN 1 END) AS liontin_qty,
                    SUM(CASE WHEN jp.jenis_produk = 'LIONTIN' THEN k.berat ELSE 0 END) AS liontin_gr,
                    SUM(CASE WHEN jp.jenis_produk = 'LIONTIN' THEN k.harga_beli ELSE 0 END) AS liontin_rp,

                    /* ================= SUBENG ================= */
                    COUNT(CASE WHEN jp.jenis_produk = 'SUBENG' THEN 1 END) AS subeng_qty,
                    SUM(CASE WHEN jp.jenis_produk = 'SUBENG' THEN k.berat ELSE 0 END) AS subeng_gr,
                    SUM(CASE WHEN jp.jenis_produk = 'SUBENG' THEN k.harga_beli ELSE 0 END) AS subeng_rp,

                    /* ================= TOTAL KESELURUHAN ================= */
                    COUNT(*) AS total_qty,
                    SUM(k.berat) AS total_gr,
                    SUM(k.harga_beli) AS total_rp

                FROM pembelian t
                JOIN keranjang_pembelian k ON t.kodepembelian = k.kodepembelian
                JOIN produk p ON k.produk_id = p.id
                JOIN jenis_produk jp ON p.jenisproduk_id = jp.id
                WHERE DATE(t.tanggal) = TANGGAL_INPUT;
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
