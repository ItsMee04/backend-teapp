<?php

namespace App\Http\Controllers\Dashboard;

use Carbon\Carbon;
use App\Models\Offtake;
use App\Models\Suplier;
use Carbon\CarbonPeriod;
use App\Models\Pelanggan;
use App\Models\Pembelian;
use App\Models\Transaksi;
use App\Models\MutasiSaldo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\Harga;

class DashboardController extends Controller
{
    public function getTotalSaldoMasuk()
    {
        $totalSaldoMasuk = MutasiSaldo::where('jenis', 'masuk')
            ->where('status', 1)
            ->sum('jumlah');

        return response()->json([
            'success' => true,
            'message' => 'Data total saldo berhasil ditemukan',
            'data'    => $totalSaldoMasuk
        ]);
    }

    public function getTotalSaldoKeluar()
    {
        $totalSaldoKeluar = MutasiSaldo::where('jenis', 'keluar')
            ->where('status', 1)
            ->sum('jumlah');

        return response()->json([
            'success' => true,
            'message' => 'Data total saldo berhasil ditemukan',
            'data'    => $totalSaldoKeluar
        ]);
    }

    public function getTotalPenjualanMasuk()
    {
        $totalTransaksi = Transaksi::where('status', 2)
            ->sum('total');

        $totalOfftake = Offtake::where('status', 2)
            ->sum('total');

        $totalPenjualanMasuk = $totalTransaksi + $totalOfftake;

        return response()->json([
            'success'   => true,
            'message'   => 'Data total penjualan berhasil ditemukan',
            'data'      => $totalPenjualanMasuk
        ]);
    }

    public function getTotalPenjualanKeluar()
    {
        $pembelian = Pembelian::where('status', 2)
            ->sum('total');

        return response()->json([
            'success'   => true,
            'message'   => 'Data total pembelian berhasil ditemukan',
            'data'      => $pembelian
        ]);
    }

    public function getTotalPelanggan()
    {
        $pelanggan = Pelanggan::where('status', 1)
            ->count();

        return response()->json([
            'success'   => true,
            'message'   => 'Data total pelanggan berhasil ditemukan',
            'data'      => $pelanggan
        ]);
    }

    public function getTotalSuplier()
    {
        $suplier = Suplier::where('status', 1)
            ->count();

        return response()->json([
            'success'   => true,
            'message'   => 'Data total suplier berhasil ditemukan',
            'data'      => $suplier
        ]);
    }

    public function getTotalPenjualan()
    {
        $penjualan = Transaksi::where('status', 2)
            ->count();

        return response()->json([
            'success'   => true,
            'message'   => 'Data total penjualan berhasil ditemukan',
            'data'      => $penjualan
        ]);
    }

    public function getTotalPembelian()
    {
        $pembelian = Pembelian::where('status', 2)
            ->count();

        return response()->json([
            'success'   => true,
            'message'   => 'Data total pembelian berhasil ditemukan',
            'data'      => $pembelian
        ]);
    }

    public function getSalesChart()
    {
        try {
            // 1. Tentukan rentang waktu (14 hari terakhir)
            $endDate = Carbon::now();
            $startDate = Carbon::now()->subDays(13);

            // 2. Ambil data dari database
            $salesData = Transaksi::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(total) as total')
            )
                ->where('created_at', '>=', $startDate->startOfDay())
                ->groupBy('date')
                ->get()
                ->pluck('total', 'date');

            // 3. Buat periode 14 hari
            $period = CarbonPeriod::create($startDate, $endDate);

            $labels = [];
            $data = [];

            foreach ($period as $date) {
                $formattedDate = $date->format('Y-m-d');
                $labels[] = $date->format('d M');

                // --- PERBAIKAN DI SINI ---
                // 1. Ambil nilai dari collection
                $val = $salesData->get($formattedDate, 0);

                // 2. Jika null, ubah ke 0. Jika ada nilai, cast ke (int) atau (float)
                // Ini akan mengubah "1700000" (string) menjadi 1700000 (number)
                $data[] = $val ? (float)$val : 0;
                // -------------------------
            }

            return response()->json([
                'success' => true,
                'message' => 'Data grafik 14 hari berhasil diambil',
                'data' => [
                    'labels' => $labels,
                    'sales' => $data
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getSalesChartPembelian()
    {
        try {
            // 1. Tentukan rentang waktu (14 hari terakhir)
            $endDate = Carbon::now();
            $startDate = Carbon::now()->subDays(13);

            // 2. Ambil data dari database
            $salesData = Pembelian::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(total) as total')
            )
                ->where('created_at', '>=', $startDate->startOfDay())
                ->groupBy('date')
                ->get()
                ->pluck('total', 'date');

            // 3. Buat periode 14 hari
            $period = CarbonPeriod::create($startDate, $endDate);

            $labels = [];
            $data = [];

            foreach ($period as $date) {
                $formattedDate = $date->format('Y-m-d');
                $labels[] = $date->format('d M');

                // --- PERBAIKAN DI SINI ---
                // 1. Ambil nilai dari collection
                $val = $salesData->get($formattedDate, 0);

                // 2. Jika null, ubah ke 0. Jika ada nilai, cast ke (int) atau (float)
                // Ini akan mengubah "1700000" (string) menjadi 1700000 (number)
                $data[] = $val ? (float)$val : 0;
                // -------------------------
            }

            return response()->json([
                'success' => true,
                'message' => 'Data grafik 14 hari berhasil diambil',
                'data' => [
                    'labels' => $labels,
                    'sales' => $data
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getProdukTerlaris()
    {
        $data = DB::table('jenis_produk')
            ->leftJoin('produk', 'produk.jenisproduk_id', '=', 'jenis_produk.id')
            ->leftJoin('keranjang', 'keranjang.produk_id', '=', 'produk.id')
            ->select(
                'jenis_produk.jenis_produk as jenis',
                // Menggunakan COALESCE agar jika data null (belum terjual) berubah jadi angka 0
                DB::raw('COALESCE(count(keranjang.id), 0) as terjual')
            )
            ->groupBy('jenis_produk.jenis_produk')
            ->orderBy('terjual', 'DESC')
            ->get();

        return response()->json([
            'success'   => true,
            'message'   => 'Data produk terlaris ditemukan',
            'data'      => $data
        ]);
    }

    public function getHargaEmas()
    {
        $data = Harga::with(['karat', 'jeniskarat'])->where('status', 1)->get();

        return response()->json([
            'success'   => true,
            'message'   => 'Data produk terlaris ditemukan',
            'data'      => $data
        ]);
    }
}
