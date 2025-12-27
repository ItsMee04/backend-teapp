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
        // 1. Drop procedure jika sudah ada
        DB::unprepared("DROP PROCEDURE IF EXISTS CetakLaporanStok;");

        // 2. Create procedure dengan query utuh
        DB::unprepared("
            CREATE PROCEDURE CetakLaporanStok(
                IN TANGGAL_AWAL DATE,
                IN TANGGAL_AKHIR DATE
            )
            BEGIN
                SELECT
                    tgl,
                    -- 1. STOK AWAL PER JENIS
                    anting_awal_pt, anting_awal_gr, cincin_awal_pt, cincin_awal_gr,
                    gelang_awal_pt, gelang_awal_gr, kalung_awal_pt, kalung_awal_gr,
                    liontin_awal_pt, liontin_awal_gr, subeng_awal_pt, subeng_awal_gr,

                    -- TOTAL STOK AWAL GABUNGAN
                    (anting_awal_pt + cincin_awal_pt + gelang_awal_pt + kalung_awal_pt + liontin_awal_pt + subeng_awal_pt) AS total_awal_pt,
                    (anting_awal_gr + cincin_awal_gr + gelang_awal_gr + kalung_awal_gr + liontin_awal_gr + subeng_awal_gr) AS total_awal_gr,

                    -- 2. PERGERAKAN MASUK PER JENIS
                    anting_pt_in, anting_gr_in, cincin_pt_in, cincin_gr_in,
                    gelang_pt_in, gelang_gr_in, kalung_pt_in, kalung_gr_in,
                    liontin_pt_in, liontin_gr_in, subeng_pt_in, subeng_gr_in,

                    -- TOTAL MASUK GABUNGAN
                    (anting_pt_in + cincin_pt_in + gelang_pt_in + kalung_pt_in + liontin_pt_in + subeng_pt_in) AS total_pt_in,
                    (anting_gr_in + cincin_gr_in + gelang_gr_in + kalung_gr_in + liontin_gr_in + subeng_gr_in) AS total_gr_in,

                    -- 3. PERGERAKAN KELUAR PER JENIS
                    anting_pt_out, anting_gr_out, cincin_pt_out, cincin_gr_out,
                    gelang_pt_out, gelang_gr_out, kalung_pt_out, kalung_gr_out,
                    liontin_pt_out, liontin_gr_out, subeng_pt_out, subeng_gr_out,

                    -- TOTAL KELUAR GABUNGAN
                    (anting_pt_out + cincin_pt_out + gelang_pt_out + kalung_pt_out + liontin_pt_out + subeng_pt_out) AS total_pt_out,
                    (anting_gr_out + cincin_gr_out + gelang_gr_out + kalung_gr_out + liontin_gr_out + subeng_gr_out) AS total_gr_out,

                    -- 4. STOK AKHIR PER JENIS
                    (anting_awal_pt - anting_pt_out) AS anting_pt_akhir, (anting_awal_gr - anting_gr_out) AS anting_gr_akhir,
                    (cincin_awal_pt - cincin_pt_out) AS cincin_pt_akhir, (cincin_awal_gr - cincin_gr_out) AS cincin_gr_akhir,
                    (gelang_awal_pt - gelang_pt_out) AS gelang_pt_akhir, (gelang_awal_gr - gelang_gr_out) AS gelang_gr_akhir,
                    (kalung_awal_pt - kalung_pt_out) AS kalung_pt_akhir, (kalung_awal_gr - kalung_gr_out) AS kalung_gr_akhir,
                    (liontin_awal_pt - liontin_pt_out) AS liontin_pt_akhir, (liontin_awal_gr - liontin_gr_out) AS liontin_gr_akhir,
                    (subeng_awal_pt - subeng_pt_out) AS subeng_pt_akhir, (subeng_awal_gr - subeng_gr_out) AS subeng_gr_akhir,

                    -- 5. TOTAL AKHIR GABUNGAN
                    ((anting_awal_pt + cincin_awal_pt + gelang_awal_pt + kalung_awal_pt + liontin_awal_pt + subeng_awal_pt) -
                    (anting_pt_out + cincin_pt_out + gelang_pt_out + kalung_pt_out + liontin_pt_out + subeng_pt_out)) AS total_pt_akhir,

                    ((anting_awal_gr + cincin_awal_gr + gelang_awal_gr + kalung_awal_gr + liontin_awal_gr + subeng_awal_gr) -
                    (anting_gr_out + cincin_gr_out + gelang_gr_out + kalung_gr_out + liontin_gr_out + subeng_gr_out)) AS total_gr_akhir

                FROM (
                    SELECT
                        DATE(np.tanggal) AS tgl,
                        S.anting_awal_pt, S.anting_awal_gr, S.cincin_awal_pt, S.cincin_awal_gr,
                        S.gelang_awal_pt, S.gelang_awal_gr, S.kalung_awal_pt, S.kalung_awal_gr,
                        S.liontin_awal_pt, S.liontin_awal_gr, S.subeng_awal_pt, S.subeng_awal_gr,

                        -- Masuk
                        SUM(CASE WHEN jp.jenis_produk = 'ANTING' AND np.jenis = 'masuk' THEN 1 ELSE 0 END) AS anting_pt_in,
                        SUM(CASE WHEN jp.jenis_produk = 'ANTING' AND np.jenis = 'masuk' THEN p.berat ELSE 0 END) AS anting_gr_in,
                        SUM(CASE WHEN jp.jenis_produk = 'CINCIN' AND np.jenis = 'masuk' THEN 1 ELSE 0 END) AS cincin_pt_in,
                        SUM(CASE WHEN jp.jenis_produk = 'CINCIN' AND np.jenis = 'masuk' THEN p.berat ELSE 0 END) AS cincin_gr_in,
                        SUM(CASE WHEN jp.jenis_produk = 'GELANG' AND np.jenis = 'masuk' THEN 1 ELSE 0 END) AS gelang_pt_in,
                        SUM(CASE WHEN jp.jenis_produk = 'GELANG' AND np.jenis = 'masuk' THEN p.berat ELSE 0 END) AS gelang_gr_in,
                        SUM(CASE WHEN jp.jenis_produk = 'KALUNG' AND np.jenis = 'masuk' THEN 1 ELSE 0 END) AS kalung_pt_in,
                        SUM(CASE WHEN jp.jenis_produk = 'KALUNG' AND np.jenis = 'masuk' THEN p.berat ELSE 0 END) AS kalung_gr_in,
                        SUM(CASE WHEN jp.jenis_produk = 'LIONTIN' AND np.jenis = 'masuk' THEN 1 ELSE 0 END) AS liontin_pt_in,
                        SUM(CASE WHEN jp.jenis_produk = 'LIONTIN' AND np.jenis = 'masuk' THEN p.berat ELSE 0 END) AS liontin_gr_in,
                        SUM(CASE WHEN jp.jenis_produk = 'SUBENG' AND np.jenis = 'masuk' THEN 1 ELSE 0 END) AS subeng_pt_in,
                        SUM(CASE WHEN jp.jenis_produk = 'SUBENG' AND np.jenis = 'masuk' THEN p.berat ELSE 0 END) AS subeng_gr_in,

                        -- Keluar
                        SUM(CASE WHEN jp.jenis_produk = 'ANTING' AND np.jenis = 'keluar' THEN 1 ELSE 0 END) AS anting_pt_out,
                        SUM(CASE WHEN jp.jenis_produk = 'ANTING' AND np.jenis = 'keluar' THEN p.berat ELSE 0 END) AS anting_gr_out,
                        SUM(CASE WHEN jp.jenis_produk = 'CINCIN' AND np.jenis = 'keluar' THEN 1 ELSE 0 END) AS cincin_pt_out,
                        SUM(CASE WHEN jp.jenis_produk = 'CINCIN' AND np.jenis = 'keluar' THEN p.berat ELSE 0 END) AS cincin_gr_out,
                        SUM(CASE WHEN jp.jenis_produk = 'GELANG' AND np.jenis = 'keluar' THEN 1 ELSE 0 END) AS gelang_pt_out,
                        SUM(CASE WHEN jp.jenis_produk = 'GELANG' AND np.jenis = 'keluar' THEN p.berat ELSE 0 END) AS gelang_gr_out,
                        SUM(CASE WHEN jp.jenis_produk = 'KALUNG' AND np.jenis = 'keluar' THEN 1 ELSE 0 END) AS kalung_pt_out,
                        SUM(CASE WHEN jp.jenis_produk = 'KALUNG' AND np.jenis = 'keluar' THEN p.berat ELSE 0 END) AS kalung_gr_out,
                        SUM(CASE WHEN jp.jenis_produk = 'LIONTIN' AND np.jenis = 'keluar' THEN 1 ELSE 0 END) AS liontin_pt_out,
                        SUM(CASE WHEN jp.jenis_produk = 'LIONTIN' AND np.jenis = 'keluar' THEN p.berat ELSE 0 END) AS liontin_gr_out,
                        SUM(CASE WHEN jp.jenis_produk = 'SUBENG' AND np.jenis = 'keluar' THEN 1 ELSE 0 END) AS subeng_pt_out,
                        SUM(CASE WHEN jp.jenis_produk = 'SUBENG' AND np.jenis = 'keluar' THEN p.berat ELSE 0 END) AS subeng_gr_out

                    FROM nampan_produk np
                    JOIN nampan n ON np.nampan_id = n.id
                    JOIN jenis_produk jp ON n.jenisproduk_id = jp.id
                    JOIN produk p ON np.produk_id = p.id
                    CROSS JOIN (
                        SELECT
                            COUNT(CASE WHEN jp2.jenis_produk = 'ANTING' THEN p2.id END) as anting_awal_pt,
                            COALESCE(SUM(CASE WHEN jp2.jenis_produk = 'ANTING' THEN p2.berat END), 0) as anting_awal_gr,
                            COUNT(CASE WHEN jp2.jenis_produk = 'CINCIN' THEN p2.id END) as cincin_awal_pt,
                            COALESCE(SUM(CASE WHEN jp2.jenis_produk = 'CINCIN' THEN p2.berat END), 0) as cincin_awal_gr,
                            COUNT(CASE WHEN jp2.jenis_produk = 'GELANG' THEN p2.id END) as gelang_awal_pt,
                            COALESCE(SUM(CASE WHEN jp2.jenis_produk = 'GELANG' THEN p2.berat END), 0) as gelang_awal_gr,
                            COUNT(CASE WHEN jp2.jenis_produk = 'KALUNG' THEN p2.id END) as kalung_awal_pt,
                            COALESCE(SUM(CASE WHEN jp2.jenis_produk = 'KALUNG' THEN p2.berat END), 0) as kalung_awal_gr,
                            COUNT(CASE WHEN jp2.jenis_produk = 'LIONTIN' THEN p2.id END) as liontin_awal_pt,
                            COALESCE(SUM(CASE WHEN jp2.jenis_produk = 'LIONTIN' THEN p2.berat END), 0) as liontin_awal_gr,
                            COUNT(CASE WHEN jp2.jenis_produk = 'SUBENG' THEN p2.id END) as subeng_awal_pt,
                            COALESCE(SUM(CASE WHEN jp2.jenis_produk = 'SUBENG' THEN p2.berat END), 0) as subeng_awal_gr
                        FROM jenis_produk jp2
                        LEFT JOIN produk p2 ON jp2.id = p2.jenisproduk_id AND p2.status != 0
                    ) AS S

                    /* PERUBAHAN DI SINI: Menggunakan Range Tanggal */
                    WHERE DATE(np.tanggal) BETWEEN TANGGAL_AWAL AND TANGGAL_AKHIR

                    GROUP BY
                        DATE(np.tanggal),
                        S.anting_awal_pt, S.anting_awal_gr, S.cincin_awal_pt, S.cincin_awal_gr,
                        S.gelang_awal_pt, S.gelang_awal_gr, S.kalung_awal_pt, S.kalung_awal_gr,
                        S.liontin_awal_pt, S.liontin_awal_gr, S.subeng_awal_pt, S.subeng_awal_gr
                ) AS SubQuery
                ORDER BY tgl ASC;
            END;
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared('DROP PROCEDURE IF EXISTS CetakLaporanStok');
    }
};
