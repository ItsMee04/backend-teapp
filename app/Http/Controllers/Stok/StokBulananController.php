<?php

namespace App\Http\Controllers\Stok;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class StokBulananController extends Controller
{
    public function getStokPeriode(Request $request)
    {
        // Validasi input
        $request->validate([
            'bulan' => 'required|integer|min:1|max:12',
            'tahun' => 'required|integer|min:2000|max:' . date('Y'),
        ]);

        $bulan = $request->input('bulan');
        $tahun = $request->input('tahun');

        // Logika untuk mengambil data stok bulanan berdasarkan bulan dan tahun
        // Misalnya, menggunakan model Stok untuk query database
        // $stokBulanan = Stok::whereMonth('tanggal', $bulan)
        //                     ->whereYear('tanggal', $tahun)
        //                     ->get();

        // Untuk contoh ini, kita akan mengembalikan data dummy
        $stokBulanan = [
            'bulan' => $bulan,
            'tahun' => $tahun,
            'data_stok' => [
                ['item' => 'Produk A', 'jumlah' => 100],
                ['item' => 'Produk B', 'jumlah' => 150],
            ],
        ];

        return response()->json($stokBulanan);
    }
}
