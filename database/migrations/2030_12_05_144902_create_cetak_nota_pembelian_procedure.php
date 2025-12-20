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
            DROP PROCEDURE IF EXISTS CetakNotaPembelian;
        ");

        DB::unprepared("
            CREATE PROCEDURE CetakNotaPembelian(IN KODEPEMBELIAN_INPUT INT)
            BEGIN
                SELECT
                    tr.kodepembelian,
                    pl.nama AS namapelanggan,
                    pl.alamat,
                    pl.kontak,
                    pg.nip,
                    pg.nama AS namapegawai,
                    pr.kodeproduk,
                    pr.nama AS namaproduk,
                    kr.berat,
                    kr.karat,
                    kr.harga_beli,
                    pr.image_produk,
                    tr.total,
                    kr.terbilang,
                    kr.total AS keranjangtotal
                FROM produk pr
                JOIN keranjang_pembelian kr ON pr.id = kr.produk_id
                JOIN pembelian tr ON kr.kodepembelian = tr.kodepembelian
                JOIN pelanggan pl ON tr.pelanggan_id = pl.id
                JOIN users us ON tr.oleh = us.id
                JOIN pegawai pg ON us.pegawai_id = pg.id
                WHERE tr.id = KODEPEMBELIAN_INPUT;
            END
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared("DROP PROCEDURE IF EXISTS CetakNotaPembelian;");
    }
};
