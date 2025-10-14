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
            CREATE PROCEDURE CetakBarcodeProduk(IN produkId INT)
            BEGIN
                SELECT
                    p.nama,
                    p.kodeproduk,
                    p.berat,
                    n.nampan
                FROM
                    nampan_produk np
                JOIN
                    produk p ON np.produk_id = p.id
                JOIN
                    nampan n ON np.nampan_id = n.id
                WHERE
                    p.id = produkId
                    AND np.status = 1;
            END
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared('DROP PROCEDURE IF EXISTS CetakBarcodeProduk');
    }
};
