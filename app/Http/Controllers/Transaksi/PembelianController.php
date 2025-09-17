<?php

namespace App\Http\Controllers\Transaksi;

use App\Http\Controllers\Controller;
use App\Models\Pembelian;
use Illuminate\Http\Request;

class PembelianController extends Controller
{
    public function getPembelian()
    {
        $pembelian = Pembelian::with(['pelanggan', 'suplier', 'keranjangPembelian'])->get();
        return response()->json([
            'status' => 'success',
            'message' => 'Data pembelian berhasil diambil',
            'data' => $pembelian
        ]);
    }
}
