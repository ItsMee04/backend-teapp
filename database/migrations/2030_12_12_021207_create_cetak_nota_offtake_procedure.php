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
            DROP PROCEDURE IF EXISTS CetakNotaOfftake;
        ");

        DB::unprepared("
            CREATE PROCEDURE CetakNotaOfftake(IN KODETRANSAKSI_INPUT INT)
            BEGIN
                SELECT
                    o.kodetransaksi,
                    o.tanggal,
                    s.nama AS supplier_nama,
                    s.kontak,
                    s.alamat,

                    pr.kodeproduk,
                    pr.nama AS produk_nama,
                    pr.image_produk,
                    ko.berat,
                    ko.karat,
                    ko.harga_jual,
                    ko.total,

                    GREATEST(
                        (SELECT SUM(ko2.total)
                            FROM keranjang_offtake ko2
                            WHERE ko2.kodetransaksi = o.kodetransaksi) - o.hargatotal,
                            0
                        )
                    AS potongan,

                    o.total as subtotal,
                    o.terbilang,
                    o.hargatotal,
                    pg.nip,
                    pg.nama AS pegawai_nama

                FROM offtake o
                JOIN keranjang_offtake ko ON o.kodetransaksi = ko.kodetransaksi
                JOIN produk pr ON ko.produk_id = pr.id
                JOIN suplier s ON o.suplier_id = s.id
                JOIN users u ON o.oleh = u.id
                JOIN pegawai pg ON u.pegawai_id = pg.id
                WHERE o.id = KODETRANSAKSI_INPUT;
            END
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared("DROP PROCEDURE IF EXISTS CetakNotaOfftake;");
    }
};
