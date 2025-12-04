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
            DROP PROCEDURE IF EXISTS CetakNotaTransaksi;
            CREATE PROCEDURE CetakNotaTransaksi(IN KODETRANSAKSI_INPUT INT)
            BEGIN
                SELECT
                    tr.kodetransaksi,
                    pl.nama AS namapelanggan,
                    pl.alamat,
                    pl.kontak,
                    pg.nip,
                    pg.nama AS namapegawai,
                    pr.kodeproduk,
                    pr.nama AS namaproduk,
                    pr.berat,
                    pr.karat,
                    ds.diskon,
                    ds.nilai,
                    pr.harga_jual,
                    pr.image_produk,
                    tr.total,
                    tr.terbilang,
                    kr.total AS keranjangtotal
                FROM produk pr
                JOIN keranjang kr ON pr.id = kr.produk_id
                JOIN transaksi tr ON kr.kodetransaksi = tr.kodetransaksi
                JOIN pelanggan pl ON tr.pelanggan_id = pl.id
                LEFT JOIN diskon ds ON tr.diskon_id = ds.id
                JOIN users us ON tr.oleh = us.id
                JOIN pegawai pg ON us.pegawai_id = pg.id
                WHERE tr.id = KODETRANSAKSI_INPUT;
            END;
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('DROP PROCEDURE IF EXISTS CetakNotaTransaksi;');
    }
};
