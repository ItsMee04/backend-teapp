<?php

namespace App\Http\Controllers\Stok;

use App\Models\Nampan;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\StokNampan;

class StokHarianController extends Controller
{
    public function getPeriodeStokByNampan(Request $request)
    {
        $nampan = Nampan::find($request->id);

        if (!$nampan) {
            return response()->json([
                'success' => false,
                'message' => 'Data baki tidak ditemukan'
            ]);
        }

        $periodeStok = StokNampan::with(['nampan'])
            ->where('nampan_id', $nampan->id)
            ->orderBy('tanggal', 'desc')
            ->get();

        if ($periodeStok->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Data periode stok tidak ditemukan'
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Data periode stok ditemukan',
            'data' => $periodeStok
        ]);
    }

    public function getDetailStokByPeriode(Request $request)
    {
        $stokNampan = StokNampan::with(['nampan', 'nampan.nampanProduk', 'nampan.jenisProduk', 'nampan.nampanProduk.produk'])
            ->find($request->id);

        if (!$stokNampan) {
            return response()->json([
                'success' => false,
                'message' => 'Data stok nampan tidak ditemukan'
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Data stok nampan ditemukan',
            'data' => $stokNampan
        ]);
    }

    public function stokNampanHarian()
    {
        $nampanList = Nampan::with(['nampanProduk', 'jenisProduk', 'nampanProduk.produk'])->get();

        if ($nampanList->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Data baki tidak ditemukan'
            ]);
        }

        // Map hasilnya ke array baru, bukan model
        $data = $nampanList->map(function ($nampan) {
            $totalProduk = $nampan->nampanProduk->where('jenis', 'masuk')
                ->unique('produk_id')
                ->count();

            $totalBerat = $nampan->nampanProduk->where('jenis', 'masuk')->unique('produk_id')->sum(function ($item) {
                return (float) ($item->produk->berat ?? 0);
            });

            // ubah jadi array dan tambahkan field baru
            $nampanArray = $nampan->toArray();
            $nampanArray['totalproduk'] = $totalProduk;
            $nampanArray['totalberat'] = number_format($totalBerat, 3, '.', '');

            return $nampanArray;
        });

        return response()->json([
            'success' => true,
            'message' => 'Data baki ditemukan',
            'data' => $data
        ]);
    }

    public function getStokHarianByNampan(Request $request)
    {
        $nampan = Nampan::with(['nampanProduk', 'jenisProduk', 'nampanProduk.produk'])->find($request->id);

        if (!$nampan) {
            return response()->json([
                'success' => false,
                'message' => 'Data baki tidak ditemukan'
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Data baki ditemukan',
            'data' => $nampan
        ]);
    }
}
