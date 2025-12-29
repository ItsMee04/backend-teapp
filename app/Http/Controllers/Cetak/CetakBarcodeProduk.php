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
use App\Models\Offtake;
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

    // Tambahkan fungsi baru ini di Controller Anda
    public function getSignedNotaOfftakeUrl(Request $request, $id)
    {
        // Gunakan nama route untuk cetak nota offtake
        $route_name = 'produk.cetak_notaofftake';
        $expiration = now()->addMinutes(5); // URL akan kadaluarsa dalam 5 menit

        $signedUrl = URL::temporarySignedRoute(
            $route_name,
            $expiration,
            ['id' => $id] // Parameter yang dibutuhkan oleh PrintNotaOfftake
        );

        return response()->json(['url' => $signedUrl]);
    }

    public function PrintNotaOfftake(Request $request, $id)
    {
        set_time_limit(300);
        ini_set('memory_limit', '512M');

        if (!$request->hasValidSignature()) {
            abort(401, 'Invalid signature.');
        }

        $transaksi = Offtake::find($id);

        if (!$transaksi) {
            return response()->json(['error' => 'Transaksi tidak ditemukan.'], 404);
        }

        $jasper_file = resource_path('reports/CetakNotaOfftake.jasper');

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
    public function getSignedRekapPenjualanUrl(Request $request)
    {
        // Gunakan nama route untuk cetak nota offtake
        $route_name = 'produk.cetak_rekappenjualan';
        $expiration = now()->addMinutes(5); // URL akan kadaluarsa dalam 5 menit

        $signedUrl = URL::temporarySignedRoute(
            $route_name,
            $expiration,
            [
                'TANGGAL_AWAL' => $request->TANGGAL_AWAL,
                'TANGGAL_AKHIR' => $request->TANGGAL_AKHIR
            ] // Parameter yang dibutuhkan oleh PrintRekapPenjualan
        );

        return response()->json(['url' => $signedUrl]);
    }

    public function PrintRekapPenjualan(Request $request)
    {
        set_time_limit(300);
        ini_set('memory_limit', '512M');

        if (!$request->hasValidSignature()) {
            abort(401, 'Invalid signature.');
        }

        $TANGGAL_AWAL = $request->get('TANGGAL_AWAL');
        $TANGGAL_AKHIR = $request->get('TANGGAL_AKHIR');

        if (!$TANGGAL_AWAL) {
            abort(400, 'Tanggal awal tidak ditemukan');
        }

        if (!$TANGGAL_AKHIR) {
            abort(400, 'Tanggal akhir tidak ditemukan');
        }

        $jasper_file = resource_path('reports/RekapPenjualanHarian.jasper');

        $db = config('database.connections.mysql');

        $parameters = [
            'TANGGAL_AWAL' => $TANGGAL_AWAL,
            'TANGGAL_AKHIR' => $TANGGAL_AKHIR
        ];

        try {
            // ❗ Simpan ke folder temp Laravel (AMAN)
            $tempDir = storage_path('app/temp');
            if (!file_exists($tempDir)) mkdir($tempDir, 0777, true);

           $outputFile = $tempDir . '/LaporanPenjualan-' . $TANGGAL_AWAL.' _sd_ '.$TANGGAL_AKHIR;

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
                'Content-Disposition' => 'inline; filename="LAPORAN-PENJUALAN-' . $TANGGAL_AWAL.' _sd_ '. $TANGGAL_AKHIR. '.pdf"',
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Gagal membuat laporan: ' . $e->getMessage()], 500);
        }
    }

    // Tambahkan fungsi baru ini di Controller Anda
    public function getSignedRekapPembelianUrl(Request $request)
    {
        // Gunakan nama route untuk cetak nota offtake
        $route_name = 'produk.cetak_rekappembelian';
        $expiration = now()->addMinutes(5); // URL akan kadaluarsa dalam 5 menit

        $signedUrl = URL::temporarySignedRoute(
            $route_name,
            $expiration,
            [
                'TANGGAL_AWAL' => $request->TANGGAL_AWAL,
                'TANGGAL_AKHIR' => $request->TANGGAL_AKHIR
            ] // Parameter yang dibutuhkan oleh PrintRekapPembelian
        );

        return response()->json(['url' => $signedUrl]);
    }

    public function PrintRekapPembelian(Request $request)
    {
        set_time_limit(300);
        ini_set('memory_limit', '512M');

        if (!$request->hasValidSignature()) {
            abort(401, 'Invalid signature.');
        }

        $TANGGAL_AWAL = $request->get('TANGGAL_AWAL');
        $TANGGAL_AKHIR = $request->get('TANGGAL_AKHIR');

        if (!$TANGGAL_AWAL) {
            abort(400, 'Tanggal tidak ditemukan');
        }

        if (!$TANGGAL_AKHIR) {
            abort(400, 'Tanggal tidak ditemukan');
        }

        $jasper_file = resource_path('reports/RekapPembelianHarian.jasper');

        $db = config('database.connections.mysql');

        $parameters = [
            'TANGGAL_AWAL' => $TANGGAL_AWAL,
            'TANGGAL_AKHIR' => $TANGGAL_AKHIR
        ];

        try {
            // ❗ Simpan ke folder temp Laravel (AMAN)
            $tempDir = storage_path('app/temp');
            if (!file_exists($tempDir)) mkdir($tempDir, 0777, true);

            $outputFile = $tempDir . '/LaporanPembelian-' . $TANGGAL_AWAL.' _sd_ '.$TANGGAL_AKHIR;

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
                'Content-Disposition' => 'inline; filename="LAPORAN-PEMBELIAN-' . $TANGGAL_AWAL.' _sd_ '. $TANGGAL_AKHIR. '.pdf"',
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Gagal membuat laporan: ' . $e->getMessage()], 500);
        }
    }

    public function getSignedLaporanStok(Request $request)
    {
        // Gunakan nama route untuk cetak nota offtake
        $route_name = 'produk.cetak_laporanstok';
        $expiration = now()->addMinutes(5); // URL akan kadaluarsa dalam 5 menit

        $signedUrl = URL::temporarySignedRoute(
            $route_name,
            $expiration,
            [
                'TANGGAL_AWAL' => $request->TANGGAL_AWAL,
                'TANGGAL_AKHIR' => $request->TANGGAL_AKHIR,
            ] // Parameter yang dibutuhkan oleh PrintRekapPembelian
        );

        return response()->json(['url' => $signedUrl]);
    }

    public function PrintLaporanStok(Request $request){
        set_time_limit(300);
        ini_set('memory_limit', '512M');

        if (!$request->hasValidSignature()) {
            abort(401, 'Invalid signature.');
        }

        $TANGGAL_AWAL = $request->TANGGAL_AWAL;
        $TANGGAL_AKHIR = $request->TANGGAL_AKHIR;

        if (!$TANGGAL_AWAL) {
            abort(400, 'Bulan tidak ditemukan');
        }

        if (!$TANGGAL_AKHIR) {
            abort(400, 'Tahun tidak ditemukan');
        }

        $jasper_file = resource_path('reports/CetakLaporanHarianStok.jasper');

        $db = config('database.connections.mysql');

        $parameters = [
            'TANGGAL_AWAL' => $TANGGAL_AWAL,
            'TANGGAL_AKHIR' => $TANGGAL_AKHIR,
        ];

        try {
            // ❗ Simpan ke folder temp Laravel (AMAN)
            $tempDir = storage_path('app/temp');
            if (!file_exists($tempDir)) mkdir($tempDir, 0777, true);

            $outputFile = $tempDir . '/LaporanStok-' . $TANGGAL_AWAL.' _sd_ '.$TANGGAL_AKHIR;

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
                'Content-Disposition' => 'inline; filename="LAPORAN-STOK-' . $TANGGAL_AWAL.' _sd_ '. $TANGGAL_AKHIR. '.pdf"',
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Gagal membuat laporan: ' . $e->getMessage()], 500);
        }
    }

    public function getSignedLaporanNampan(Request $request)
    {
        // Gunakan nama route untuk cetak nota offtake
        $route_name = 'produk.cetak_laporannampan';
        $expiration = now()->addMinutes(5); // URL akan kadaluarsa dalam 5 menit

        $signedUrl = URL::temporarySignedRoute(
            $route_name,
            $expiration,
            [
                'TANGGAL_AWAL' => $request->TANGGAL_AWAL,
                'TANGGAL_AKHIR' => $request->TANGGAL_AKHIR,
            ] // Parameter yang dibutuhkan oleh PrintRekapPembelian
        );

        return response()->json(['url' => $signedUrl]);
    }

    public function PrintLaporanNampan(Request $request){
        set_time_limit(300);
        ini_set('memory_limit', '512M');

        if (!$request->hasValidSignature()) {
            abort(401, 'Invalid signature.');
        }

        $TANGGAL_AWAL = $request->TANGGAL_AWAL;
        $TANGGAL_AKHIR = $request->TANGGAL_AKHIR;

        if (!$TANGGAL_AWAL) {
            abort(400, 'Bulan tidak ditemukan');
        }

        if (!$TANGGAL_AKHIR) {
            abort(400, 'Tahun tidak ditemukan');
        }

        $jasper_file = resource_path('reports/CetakLaporanNampan.jasper');

        $db = config('database.connections.mysql');

        $parameters = [
            'TANGGAL_AWAL' => $TANGGAL_AWAL,
            'TANGGAL_AKHIR' => $TANGGAL_AKHIR,
        ];

        try {
            // ❗ Simpan ke folder temp Laravel (AMAN)
            $tempDir = storage_path('app/temp');
            if (!file_exists($tempDir)) mkdir($tempDir, 0777, true);

            $outputFile = $tempDir . '/LaporanNampan-' . $TANGGAL_AWAL.' _sd_ '.$TANGGAL_AKHIR;
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
                'Content-Disposition' => 'inline; filename="LAPORAN-NAMPAN-' . $TANGGAL_AWAL.' _sd_ '. $TANGGAL_AKHIR. '.pdf"',
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Gagal membuat laporan: ' . $e->getMessage()], 500);
        }
    }

    public function getSignedLaporanMutasi(Request $request)
    {
        // Gunakan nama route untuk cetak nota offtake
        $route_name = 'produk.cetak_laporanmutasi';
        $expiration = now()->addMinutes(5); // URL akan kadaluarsa dalam 5 menit

        $signedUrl = URL::temporarySignedRoute(
            $route_name,
            $expiration,
            [
                'TANGGAL_AWAL' => $request->TANGGAL_AWAL,
                'TANGGAL_AKHIR' => $request->TANGGAL_AKHIR,
            ] // Parameter yang dibutuhkan oleh PrintRekapPembelian
        );

        return response()->json(['url' => $signedUrl]);
    }

    public function PrintLaporanMutasi(Request $request){
        set_time_limit(300);
        ini_set('memory_limit', '512M');

        if (!$request->hasValidSignature()) {
            abort(401, 'Invalid signature.');
        }

        $TANGGAL_AWAL = $request->TANGGAL_AWAL;
        $TANGGAL_AKHIR = $request->TANGGAL_AKHIR;

        if (!$TANGGAL_AWAL) {
            abort(400, 'Bulan tidak ditemukan');
        }

        if (!$TANGGAL_AKHIR) {
            abort(400, 'Tahun tidak ditemukan');
        }

        $jasper_file = resource_path('reports/CetakLaporanMutasi.jasper');

        $db = config('database.connections.mysql');

        $parameters = [
            'TANGGAL_AWAL' => $TANGGAL_AWAL,
            'TANGGAL_AKHIR' => $TANGGAL_AKHIR,
        ];

        try {
            // ❗ Simpan ke folder temp Laravel (AMAN)
            $tempDir = storage_path('app/temp');
            if (!file_exists($tempDir)) mkdir($tempDir, 0777, true);

            $outputFile = $tempDir . '/LaporanMutasi-' . $TANGGAL_AWAL.' _sd_ '.$TANGGAL_AKHIR;
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
                'Content-Disposition' => 'inline; filename="LAPORAN-MUTASI-' . $TANGGAL_AWAL.' _sd_ '. $TANGGAL_AKHIR. '.pdf"',
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Gagal membuat laporan: ' . $e->getMessage()], 500);
        }
    }

    public function getSignedLaporanOfftake(Request $request)
    {
        // Gunakan nama route untuk cetak nota offtake
        $route_name = 'produk.cetak_laporanofftake';
        $expiration = now()->addMinutes(5); // URL akan kadaluarsa dalam 5 menit

        $signedUrl = URL::temporarySignedRoute(
            $route_name,
            $expiration,
            [
                'TANGGAL_AWAL' => $request->TANGGAL_AWAL,
                'TANGGAL_AKHIR' => $request->TANGGAL_AKHIR,
            ] // Parameter yang dibutuhkan oleh PrintRekapPembelian
        );

        return response()->json(['url' => $signedUrl]);
    }

    public function PrintLaporanOfftake(Request $request){
        set_time_limit(300);
        ini_set('memory_limit', '512M');

        if (!$request->hasValidSignature()) {
            abort(401, 'Invalid signature.');
        }

        $TANGGAL_AWAL = $request->TANGGAL_AWAL;
        $TANGGAL_AKHIR = $request->TANGGAL_AKHIR;

        if (!$TANGGAL_AWAL) {
            abort(400, 'Bulan tidak ditemukan');
        }

        if (!$TANGGAL_AKHIR) {
            abort(400, 'Tahun tidak ditemukan');
        }

        $jasper_file = resource_path('reports/CetakLaporanOfftake.jasper');

        $db = config('database.connections.mysql');

        $parameters = [
            'TANGGAL_AWAL' => $TANGGAL_AWAL,
            'TANGGAL_AKHIR' => $TANGGAL_AKHIR,
        ];

        try {
            // ❗ Simpan ke folder temp Laravel (AMAN)
            $tempDir = storage_path('app/temp');
            if (!file_exists($tempDir)) mkdir($tempDir, 0777, true);

            $outputFile = $tempDir . '/LaporanOfftake-' . $TANGGAL_AWAL.' _sd_ '.$TANGGAL_AKHIR;
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
                'Content-Disposition' => 'inline; filename="LAPORAN-OFFTAKE-' . $TANGGAL_AWAL.' _sd_ '. $TANGGAL_AKHIR. '.pdf"',
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Gagal membuat laporan: ' . $e->getMessage()], 500);
        }
    }

    public function getSignedLaporanProduk(Request $request)
    {
        // Gunakan nama route untuk cetak produk
        $route_name = 'produk.cetak_laporanproduk';
        $expiration = now()->addMinutes(5); // URL akan kadaluarsa dalam 5 menit

        $signedUrl = URL::temporarySignedRoute(
            $route_name,
            $expiration,
            [
                'TANGGAL_AWAL' => $request->TANGGAL_AWAL,
                'TANGGAL_AKHIR' => $request->TANGGAL_AKHIR,
            ]
        );

        return response()->json(['url' => $signedUrl]);
    }

    public function PrintLaporanProduk(Request $request){
        set_time_limit(300);
        ini_set('memory_limit', '512M');

        if (!$request->hasValidSignature()) {
            abort(401, 'Invalid signature.');
        }

        $TANGGAL_AWAL = $request->TANGGAL_AWAL;
        $TANGGAL_AKHIR = $request->TANGGAL_AKHIR;

        if (!$TANGGAL_AWAL) {
            abort(400, 'Bulan tidak ditemukan');
        }

        if (!$TANGGAL_AKHIR) {
            abort(400, 'Tahun tidak ditemukan');
        }

        $jasper_file = resource_path('reports/CetakLaporanProduk.jasper');

        $db = config('database.connections.mysql');

        $parameters = [
            'TANGGAL_AWAL' => $TANGGAL_AWAL,
            'TANGGAL_AKHIR' => $TANGGAL_AKHIR,
        ];

        try {
            // ❗ Simpan ke folder temp Laravel (AMAN)
            $tempDir = storage_path('app/temp');
            if (!file_exists($tempDir)) mkdir($tempDir, 0777, true);

            $outputFile = $tempDir . '/LaporanProduk-' . $TANGGAL_AWAL.' _sd_ '.$TANGGAL_AKHIR;
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
                'Content-Disposition' => 'inline; filename="LAPORAN-PRODUK-' . $TANGGAL_AWAL.' _sd_ '. $TANGGAL_AKHIR. '.pdf"',
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Gagal membuat laporan: ' . $e->getMessage()], 500);
        }
    }

    public function CompileReports()
    {
        // Target file JRXML
        $input_jrxml = resource_path('reports/RekapPembelianHarian.jrxml');
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
                'message' => 'Kompilasi RekapPembelianHarian.jrxml berhasil!',
                'output_file' => $output_dir . '/RekapPembelianHarian.jasper'
            ]);
        } catch (\Exception $e) {
            // Jika ini gagal, cek kembali JRXML Anda di Jaspersoft Studio!
            return response()->json(['error' => 'Gagal Kompilasi (Error Java/XML): ' . $e->getMessage()], 500);
        }
    }
}
