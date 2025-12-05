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
        set_time_limit(300);
        ini_set('memory_limit', '512M');

        if (!$request->hasValidSignature()) {
            abort(401, 'Invalid signature.');
        }

        $produk = Produk::find($id);

        if (!$produk) {
            return response()->json(['error' => 'Produk tidak ditemukan.'], 404);
        }

        $jasper_file = resource_path('reports/CetakBarcodeProduk.jasper');

        $db = config('database.connections.mysql');

        $parameters = [
            'ProdukID' => $produk->id,
        ];

        try {
            // ❗ Simpan file PDF SEMENTARA di storage/temp
            $tempDir = storage_path('app/temp');
            if (!file_exists($tempDir)) mkdir($tempDir, 0777, true);

            $outputFile = $tempDir . '/barcode-' . $produk->id;

            $jasper = new \PHPJasper\PHPJasper;
            $jasper->process(
                $jasper_file,
                $outputFile,
                [
                    'format' => ['pdf'],
                    'params' => $parameters,
                    'db_connection' => [
                        'driver'   => 'mysql',
                        'host'     => $db['host'],
                        'port'     => $db['port'],
                        'database' => $db['database'],
                        'username' => $db['username'],
                        'password' => $db['password'],
                    ],
                ]
            )->execute();

            $pdfPath = $outputFile . '.pdf';

            // ❗ Baca isi PDF
            $pdfContent = file_get_contents($pdfPath);

            // ❗ Hapus file setelah selesai digunakan
            unlink($pdfPath);

            // ❗ Kirim PDF langsung ke browser (inline)
            return response($pdfContent, 200, [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'inline; filename="BARCODE-' . $produk->id . '.pdf"',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Gagal membuat laporan: ' . $e->getMessage()
            ], 500);
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
        set_time_limit(300);
        ini_set('memory_limit', '512M');

        if (!$request->hasValidSignature()) {
            abort(401, 'Invalid signature.');
        }

        $transaksi = Transaksi::find($id);

        if (!$transaksi) {
            return response()->json(['error' => 'Transaksi tidak ditemukan.'], 404);
        }

        $jasper_file = resource_path('reports/CetakNotaTransaksi.jasper');

        $logo_path = public_path('assets/logo.jpg');
        $product_path = public_path('storage/produk/');
        $ttd_path = public_path('ttd/');

        $db = config('database.connections.mysql');

        $parameters = [
            'LOGO' => $logo_path,
            'PRODUK' => $product_path,
            'TTD' => $ttd_path,
            'KODETRANSAKSI_INPUT' => $transaksi->id,
        ];

        try {
            // ❗ Simpan ke folder temp Laravel (AMAN)
            $tempDir = storage_path('app/temp');
            if (!file_exists($tempDir)) mkdir($tempDir, 0777, true);

            $outputFile = $tempDir . '/nota-' . $transaksi->kodetransaksi;

            $jasper = new \PHPJasper\PHPJasper;
            $jasper->process(
                $jasper_file,
                $outputFile,
                [
                    'format' => ['pdf'],
                    'params' => $parameters,
                    'db_connection' => [
                        'driver' => 'mysql',
                        'host' => $db['host'],
                        'port' => $db['port'],
                        'database' => $db['database'],
                        'username' => $db['username'],
                        'password' => $db['password'],
                    ],
                ]
            )->execute();

            $pdfPath = $outputFile . '.pdf';

            // ❗ Baca isi PDF
            $pdfContent = file_get_contents($pdfPath);

            // ❗ Hapus file setelah dibaca
            unlink($pdfPath);

            return response($pdfContent, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="NOTA-' . $transaksi->kodetransaksi . '.pdf"',
            ]);
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
        set_time_limit(300);
        ini_set('memory_limit', '512M');

        if (!$request->hasValidSignature()) {
            abort(401, 'Invalid signature.');
        }

        $transaksi = Pembelian::find($id);

        if (!$transaksi) {
            return response()->json(['error' => 'Transaksi tidak ditemukan.'], 404);
        }

        $jasper_file = resource_path('reports/CetakNotaPembelian.jasper');

        $logo_path = public_path('assets/logo.jpg');
        $product_path = public_path('storage/produk/');
        $ttd_path = public_path('ttd/');

        $db = config('database.connections.mysql');

        $parameters = [
            'LOGO' => $logo_path,
            'PRODUK' => $product_path,
            'TTD' => $ttd_path,
            'KODEPEMBELIAN_INPUT' => $transaksi->id,
        ];

        try {
            // ❗ Simpan ke folder temp Laravel (AMAN)
            $tempDir = storage_path('app/temp');
            if (!file_exists($tempDir)) mkdir($tempDir, 0777, true);

            $outputFile = $tempDir . '/nota-' . $transaksi->kodepembelian;

            $jasper = new \PHPJasper\PHPJasper;
            $jasper->process(
                $jasper_file,
                $outputFile,
                [
                    'format' => ['pdf'],
                    'params' => $parameters,
                    'db_connection' => [
                        'driver' => 'mysql',
                        'host' => $db['host'],
                        'port' => $db['port'],
                        'database' => $db['database'],
                        'username' => $db['username'],
                        'password' => $db['password'],
                    ],
                ]
            )->execute();

            $pdfPath = $outputFile . '.pdf';

            // ❗ Baca isi PDF
            $pdfContent = file_get_contents($pdfPath);

            // ❗ Hapus file setelah dibaca
            unlink($pdfPath);

            return response($pdfContent, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="NOTA-' . $transaksi->kodepembelian . '.pdf"',
            ]);
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
