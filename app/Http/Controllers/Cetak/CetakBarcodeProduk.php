<?php

namespace App\Http\Controllers\Cetak;

use App\Models\Produk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use App\Http\Controllers\Controller;
use PHPJasper\PHPJasper; // Gunakan class ini, bukan yang lain

class CetakBarcodeProduk extends Controller
{
    public function getSignedPrintUrl(Request $request, $id)
    {
        // Nama route yang baru, sesuai dengan yang ada di api.php
        $route_name = 'produk.cetak_barcode';
        $expiration = now()->addMinutes(5); // URL akan kadaluarsa dalam 5 menit

        $signedUrl = URL::temporarySignedRoute(
            $route_name,
            $expiration,
            ['id' => $id]
        );

        return response()->json(['url' => $signedUrl]);
    }

    public function PrintBarcodeProduk(Request $request, $id)
    {
        if (!$request->hasValidSignature()) {
            abort(401, 'Invalid signature.');
        }

        $produk = Produk::find($id);

        if (!$produk) {
            return response()->json(['error' => 'Produk tidak ditemukan.'], 404);
        }

        $jasper_file = resource_path('reports/CetakBarcodeProduk.jasper');

        $db_connection = config('database.connections.mysql');
        $database_options = [
            'driver'    => 'mysql',
            'host'      => $db_connection['host'],
            'port'      => $db_connection['port'],
            'database'  => $db_connection['database'],
            'username'  => $db_connection['username'],
            'password'  => $db_connection['password'],
        ];

        $parameters = [
            'ProdukID' => $produk->id,
        ];

        $output_pdf_file = public_path('temp/barcode-' . $produk->id);

        $report = new PHPJasper();

        try {
            $report->process(
                $jasper_file,
                $output_pdf_file,
                [
                    'format' => ['pdf'],
                    'params' => $parameters,
                    'db_connection' => $database_options,
                ]
            )->execute();

            $headers = [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="barcode-' . $produk->id . '.pdf"'
            ];

            return response()->file($output_pdf_file . '.pdf', $headers);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Gagal membuat laporan: ' . $e->getMessage()], 500);
        }
    }
}
