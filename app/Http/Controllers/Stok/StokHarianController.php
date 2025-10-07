<?php

namespace App\Http\Controllers\Stok;

use App\Models\Nampan;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class StokHarianController extends Controller
{
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
            $totalProduk = $nampan->nampanProduk->where('jenis','masuk')->where('status',1)->count();

            $totalBerat = $nampan->nampanProduk->where('jenis','masuk')->where('status',1)->sum(function ($item) {
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
}
