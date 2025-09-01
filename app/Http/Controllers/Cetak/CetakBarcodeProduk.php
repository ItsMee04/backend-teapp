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
        // if (!$request->hasValidSignature()) {
        //     abort(401, 'Invalid signature.');
        // }

        // $produk = Produk::find($id);

        // if (!$produk) {
        //     return response()->json(['message' => 'Produk tidak ditemukan.'], 404);
        // }

        // try {
        //     // Buat instance PHPJasper
        //     $jasper = new PHPJasper;

        //     // Path ke file jrxml yang lo buat di JasperStudio
        //     $input_file = resource_path('reports/CetakBarcodeProduk.jrxml');
        //     $output_file = public_path('temp/barcode-' . $produk->id);

        //     // Siapkan parameter yang akan dikirim ke laporan
        //     $parameters = [
        //         'ProdukID' => $produk->id,
        //     ];

        //     // Ambil konfigurasi koneksi database dari Laravel
        //     $db_connection = config('database.connections.mysql');

        //     // Buat array koneksi yang sesuai dengan format PHPJasper
        //     $database_options = [
        //         'driver'   => 'mysql',
        //         'host'     => $db_connection['host'],
        //         'port'     => $db_connection['port'],
        //         'database' => $db_connection['database'],
        //         'username' => $db_connection['username'],
        //         'password' => $db_connection['password'],
        //     ];

        //     // Buat array options untuk proses Jasper
        //     $options = [
        //         'format' => ['pdf'],
        //         'params' => $parameters,
        //         'db_connection' => $database_options,
        //     ];

        //     // Eksekusi JasperReports untuk membuat PDF
        //     $jasper->process(
        //         $input_file,
        //         $output_file,
        //         $options
        //     )->execute();

        //     // Kembalikan file PDF sebagai response
        //     return response()->file($output_file . '.pdf');
        // } catch (\Exception $e) {
        //     return response()->json(['message' => 'Gagal mencetak laporan: ' . $e->getMessage()], 500);
        // }

        // Validasi tanda tangan di URL
    if (!$request->hasValidSignature()) {
        abort(401, 'Invalid signature.');
    }

    $produk = Produk::find($id);

    if (!$produk) {
        return response()->json(['error' => 'Produk tidak ditemukan.'], 404);
    }

    $jrxml_file = resource_path('reports/CetakBarcodeProduk.jrxml');
    $jasper_file = resource_path('reports/CetakBarcodeProduk.jasper');
    
    // Periksa apakah file .jasper sudah ada. Jika belum, kompilasi.
    if (!file_exists($jasper_file)) {
        try {
            $report = new PHPJasper(); // Gunakan class yang benar
            $report->compile($jrxml_file)->execute();
        } catch (\Exception $e) {
            return response()->json(['error' => 'Gagal mengompilasi laporan: ' . $e->getMessage()], 500);
        }
    }

    $db_connection = config('database.connections.mysql');
    $database_options = [
        'driver'   => 'mysql',
        'host'     => $db_connection['host'],
        'port'     => $db_connection['port'],
        'database' => $db_connection['database'],
        'username' => $db_connection['username'],
        'password' => $db_connection['password'],
    ];

    $parameters = [
        'ProdukID' => $produk->id,
    ];

    $output_pdf_file = public_path('temp/barcode-' . $produk->id);
    
    $report = new PHPJasper(); // Gunakan class yang benar

    try {
        $report->process(
            $jasper_file, // Gunakan file .jasper yang sudah dikompilasi
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
