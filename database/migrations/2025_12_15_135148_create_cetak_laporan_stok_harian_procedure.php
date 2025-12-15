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
        // Hapus SP lama jika ada
        DB::unprepared("
            DROP PROCEDURE IF EXISTS CetakLaporanStokHarian;
        ");

        DB::unprepared("
        CREATE PROCEDURE CetakLaporanStokHarian(IN TARGET_DATE DATE)
        BEGIN

            WITH CombinedData AS (
                -- A. STOK AWAL (semua 'masuk' sebelum tanggal target)
                SELECT
                    'Stok Awal' as KETERANGAN,
                    p.jenisproduk_id,
                    SUM(p.berat) as total_berat,
                    -- SAFE: Cukup hitung COUNT/SUM(1) karena np.jenis sudah difilter 'masuk'
                    COUNT(np.id) as total_potong
                FROM nampan_produk np
                JOIN produk p ON np.produk_id = p.id
                WHERE np.tanggal < TARGET_DATE
                  AND np.status != 0
                  AND np.jenis = 'masuk' -- <-- PERBAIKAN LOGIKA: Hanya hitung 'masuk' untuk stok awal
                GROUP BY p.jenisproduk_id

                UNION ALL

                -- B. PERGERAKAN HARIAN (Masuk dan Keluar pada tanggal target)
                SELECT
                    np.jenis as KETERANGAN, -- <-- PERBAIKAN 1: Gunakan kolom dasar ('masuk'/'keluar')
                    p.jenisproduk_id,
                    SUM(CASE WHEN np.jenis = 'masuk' THEN p.berat ELSE -p.berat END) as total_berat,
                    SUM(CASE WHEN np.jenis = 'masuk' THEN 1 ELSE -1 END) as total_potong
                FROM nampan_produk np
                JOIN produk p ON np.produk_id = p.id
                WHERE np.tanggal = TARGET_DATE
                  AND np.status != 0
                  AND np.jenis IN ('masuk', 'keluar')
                GROUP BY np.jenis, p.jenisproduk_id -- <-- PERBAIKAN 2: Grouping berdasarkan kolom dasar
            )

            -- 2. Lakukan PIVOT pada data yang digabungkan
            SELECT
                -- Re-labeling (pengubahan nama) KETERANGAN dilakukan di SELECT terluar (lebih aman)
                CASE T.KETERANGAN
                    WHEN 'masuk' THEN 'Masuk'
                    WHEN 'keluar' THEN 'Keluar'
                    ELSE T.KETERANGAN
                END AS KETERANGAN,

                -- PIVOT UNTUK ANTING (Asumsi jenisproduk_id = 1)
                SUM(CASE WHEN T.jenisproduk_id = 1 THEN T.total_berat ELSE 0 END) AS ANTING_GRAM,
                SUM(CASE WHEN T.jenisproduk_id = 1 THEN T.total_potong ELSE 0 END) AS ANTING_POTONG,

                -- PIVOT UNTUK CINCIN (Asumsi jenisproduk_id = 2)
                SUM(CASE WHEN T.jenisproduk_id = 2 THEN T.total_berat ELSE 0 END) AS CINCIN_GRAM,
                SUM(CASE WHEN T.jenisproduk_id = 2 THEN T.total_potong ELSE 0 END) AS CINCIN_POTONG,

                -- PIVOT UNTUK GELANG (Asumsi jenisproduk_id = 3)
                SUM(CASE WHEN T.jenisproduk_id = 3 THEN T.total_berat ELSE 0 END) AS GELANG_GRAM,
                SUM(CASE WHEN T.jenisproduk_id = 3 THEN T.total_potong ELSE 0 END) AS GELANG_POTONG,

                -- PIVOT UNTUK KALUNG (Asumsi jenisproduk_id = 4)
                SUM(CASE WHEN T.jenisproduk_id = 4 THEN T.total_berat ELSE 0 END) AS KALUNG_GRAM,
                SUM(CASE WHEN T.jenisproduk_id = 4 THEN T.total_potong ELSE 0 END) AS KALUNG_POTONG

            FROM CombinedData T
            -- Grouping berdasarkan kolom T.KETERANGAN ('Stok Awal', 'masuk', 'keluar') yang menjadi baris final
            GROUP BY T.KETERANGAN
            ORDER BY
                -- Mengurutkan berdasarkan KETERANGAN asli di CTE T
                CASE T.KETERANGAN
                    WHEN 'Stok Awal' THEN 1
                    WHEN 'masuk' THEN 2
                    WHEN 'keluar' THEN 3
                END;
        END
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared('DROP PROCEDURE IF EXISTS CetakLaporanStokHarian');
    }
};
