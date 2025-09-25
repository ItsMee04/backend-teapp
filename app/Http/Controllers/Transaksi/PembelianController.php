<?php

namespace App\Http\Controllers\Transaksi;

use App\Http\Controllers\Controller;
use App\Models\Pembelian;
use Illuminate\Http\Request;

class PembelianController extends Controller
{
    public function getPembelian()
    {
        $pembelian = Pembelian::with(['pelanggan', 'suplier', 'keranjangPembelian','user','user.pegawai'])->get();
        return response()->json([
            'status' => 'success',
            'message' => 'Data pembelian berhasil diambil',
            'data' => $pembelian
        ]);
    }

    public function batalTransaksi(Request $request)
    {
        $kodepembelian = $request->kodepembelian;

        $transaksi = Pembelian::with(['keranjangPembelian'])->where('kodepembelian',$kodepembelian)->get();

        return response()->json(['success'=>true, 'message'=>'Transaksi berhasil dibatalkan', 'data'=>$transaksi]);
    }
}
