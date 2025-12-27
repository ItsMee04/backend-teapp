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
        DB::unprepared("DROP PROCEDURE IF EXISTS CetakLaporanMutasi;");

        DB::unprepared("
            CREATE PROCEDURE CetakLaporanMutasi(
                IN TANGGAL_AWAL DATE,
                IN TANGGAL_AKHIR DATE
            )
            BEGIN
                SELECT
                    s.rekening AS sumberDana,
                    ms.tanggal,
                    ms.keterangan,
                    ms.jenis,
                    ms.jumlah,
                    pg.nama AS oleh
                FROM mutasi_saldo ms
                JOIN saldo s ON ms.saldo_id = s.id
                JOIN users u ON ms.oleh = u.id
                JOIN pegawai pg ON u.pegawai_id = pg.id

                /* Filter berdasarkan rentang tanggal */
                WHERE DATE(ms.tanggal) BETWEEN TANGGAL_AWAL AND TANGGAL_AKHIR

                /* Diurutkan berdasarkan tanggal terbaru agar rapi di laporan */
                ORDER BY ms.tanggal ASC;
            END
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared('DROP PROCEDURE IF EXISTS CetakLaporanMutasi');
    }
};
