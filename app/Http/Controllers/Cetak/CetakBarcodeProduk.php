<?php

namespace App\Http\Controllers\Cetak;

use App\Models\Produk;
use App\Models\Keranjang;
use App\Models\Pembelian;
use App\Models\Transaksi;
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

    // Tambahkan fungsi baru ini di Controller Anda
    public function getSignedNotaUrl(Request $request, $id)
    {
        // Gunakan nama route untuk cetak nota transaksi
        $route_name = 'produk.cetak_notatransaksi';
        $expiration = now()->addMinutes(5); // URL akan kadaluarsa dalam 5 menit

        $signedUrl = URL::temporarySignedRoute(
            $route_name,
            $expiration,
            ['id' => $id] // Parameter yang dibutuhkan oleh PrintNotaTransaksi
        );

        return response()->json(['url' => $signedUrl]);
    }

    public function PrintNotaTransaksi(Request $request, $id)
    {
        // 🚨 SOLUSI 1: TAMBAHKAN ANTI-HANG DI AWAL FUNGSI
        set_time_limit(300); // Batas waktu 5 menit
        ini_set('memory_limit', '512M'); // Tingkatkan batas memori

        if (!$request->hasValidSignature()) {
            abort(401, 'Invalid signature.');
        }

        $transaksi = Transaksi::find($id);

        if (!$transaksi) {
            return response()->json(['error' => 'Transaksi tidak ditemukan.'], 404);
        }

        // 🚨 SOLUSI 2: PASTIKAN MENGGUNAKAN FILE .JASPER YANG SUDAH DIKOMPILASI MANUAL
        $jasper_file = resource_path('reports/CetakNotaTransaksi.jasper');

        // 🚨 SOLUSI 3: LOGO DIJADIKAN PATH ABSOLUT, BUKAN URL HTTP 127.0.0.1:8000
        $logo_path = public_path('assets/logo.jpg');
        // Catatan: Pastikan $F{image_produk} (gambar produk) juga diubah di JRXML Anda
        // agar tidak memanggil URL http://127.0.0.1:8000/...
        $product_path = public_path('storage/produk/');
        $ttd_path = public_path('ttd/');

        $db_connection = config('database.connections.mysql');
        $database_options = [
            'driver' => 'mysql',
            'host' => $db_connection['host'],
            'port' => $db_connection['port'],
            'database' => $db_connection['database'],
            'username' => $db_connection['username'],
            'password' => $db_connection['password'],
        ];

        $parameters = [
            // Pastikan Anda telah membuat parameter 'LOGO' bertipe java.lang.String di JRXML
            'LOGO'      => $logo_path,
            'PRODUK'    => $product_path,
            'TTD'       => $ttd_path,
            'KODETRANSAKSI_INPUT' => $transaksi->id,
        ];

        $output_pdf_file = public_path('temp/notatransaksi-' . $transaksi->kodetransaksi);

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
                'Content-Disposition' => 'inline; filename="NOTATRANSAKSI-' . $transaksi->kodetransaksi . '.pdf"'
            ];

            return response()->file($output_pdf_file . '.pdf', $headers);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Gagal membuat nota: ' . $e->getMessage()], 500);
        }
    }

    // Tambahkan fungsi baru ini di Controller Anda
    public function getSignedNotaPembelianUrl(Request $request, $id)
    {
        // Gunakan nama route untuk cetak nota pembelian
        $route_name = 'produk.cetak_notapembelian';
        $expiration = now()->addMinutes(5); // URL akan kadaluarsa dalam 5 menit

        $signedUrl = URL::temporarySignedRoute(
            $route_name,
            $expiration,
            ['id' => $id] // Parameter yang dibutuhkan oleh PrintNotaPembelian
        );

        return response()->json(['url' => $signedUrl]);
    }

    public function PrintNotaPembelian(Request $request, $id)
    {
        // 🚨 SOLUSI 1: TAMBAHKAN ANTI-HANG DI AWAL FUNGSI
        set_time_limit(300); // Batas waktu 5 menit
        ini_set('memory_limit', '512M'); // Tingkatkan batas memori

        if (!$request->hasValidSignature()) {
            abort(401, 'Invalid signature.');
        }

        $transaksi = Pembelian::find($id);

        if (!$transaksi) {
            return response()->json(['error' => 'Transaksi tidak ditemukan.'], 404);
        }

        // 🚨 SOLUSI 2: PASTIKAN MENGGUNAKAN FILE .JASPER YANG SUDAH DIKOMPILASI MANUAL
        $jasper_file = resource_path('reports/CetakNotaPembelian.jasper');

        // 🚨 SOLUSI 3: LOGO DIJADIKAN PATH ABSOLUT, BUKAN URL HTTP 127.0.0.1:8000
        $logo_path = public_path('assets/logo.jpg');
        // Catatan: Pastikan $F{image_produk} (gambar produk) juga diubah di JRXML Anda
        // agar tidak memanggil URL http://127.0.0.1:8000/...
        $product_path = public_path('storage/produk/');
        $ttd_path = public_path('ttd/');

        $db_connection = config('database.connections.mysql');
        $database_options = [
            'driver' => 'mysql',
            'host' => $db_connection['host'],
            'port' => $db_connection['port'],
            'database' => $db_connection['database'],
            'username' => $db_connection['username'],
            'password' => $db_connection['password'],
        ];

        $parameters = [
            // Pastikan Anda telah membuat parameter 'LOGO' bertipe java.lang.String di JRXML
            'LOGO'      => $logo_path,
            'PRODUK'    => $product_path,
            'TTD'       => $ttd_path,
            'KODEPEMBELIAN_INPUT' => $transaksi->id,
        ];

        $output_pdf_file = public_path('temp/notapembelian-' . $transaksi->kodepembelian);

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
                'Content-Disposition' => 'inline; filename="NOTATRANSAKSI-' . $transaksi->kodepembelian . '.pdf"'
            ];

            return response()->file($output_pdf_file . '.pdf', $headers);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Gagal membuat nota: ' . $e->getMessage()], 500);
        }
    }

    public function CompileReports()
    {
        // Target file JRXML
        $input_jrxml = resource_path('reports/CetakNotaPembelian.jrxml');
        $output_dir = resource_path('reports'); // Output .jasper di folder reports/

        if (!file_exists($input_jrxml)) {
            return response()->json(['error' => 'File JRXML tidak ditemukan. Silakan cek path: ' . $input_jrxml], 404);
        }

        $jasper = new PHPJasper();

        try {
            // Mengompilasi jrxml ke jasper
            $jasper->compile(
                $input_jrxml,
                false // Tidak ada opsi tambahan
            )->execute();

            return response()->json([
                'message' => 'Kompilasi CetakNotaPembelian.jrxml berhasil!',
                'output_file' => $output_dir . '/CetakNotaPembelian.jasper'
            ]);

        } catch (\Exception $e) {
            // Jika ini gagal, cek kembali JRXML Anda di Jaspersoft Studio!
            return response()->json(['error' => 'Gagal Kompilasi (Error Java/XML): ' . $e->getMessage()], 500);
        }
    }
}
