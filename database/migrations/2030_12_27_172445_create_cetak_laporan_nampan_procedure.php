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
        DB::unprepared("DROP PROCEDURE IF EXISTS CetakLaporanNampan;");

        DB::unprepared("
            CREATE PROCEDURE CetakLaporanNampan(
                IN TANGGAL_AWAL DATE,
                IN TANGGAL_AKHIR DATE
            )
            BEGIN
                SELECT
                    n.nampan,
                    p.kodeproduk,
                    p.nama,
                    p.berat,
                    k.karat,
                    h.harga,
                    np.jenis,
                    np.tanggal,
                    SUM(p.berat) OVER() AS totalBerat,
                    COUNT(*) OVER() AS totalPotong
                FROM nampan_produk np
                JOIN nampan n ON np.nampan_id = n.id
                JOIN produk p ON np.produk_id = p.id
                JOIN karat k ON p.karat_id = k.id
                JOIN harga h ON p.harga_jual = h.id
                WHERE DATE(np.tanggal) BETWEEN TANGGAL_AWAL AND TANGGAL_AKHIR
                ORDER BY np.id ASC;
            END
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared('DROP PROCEDURE IF EXISTS CetakLaporanNampan');
    }
};
