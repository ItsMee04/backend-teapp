<?php

namespace App\Http\Controllers\Transaksi;

use Illuminate\Http\Request;
use App\Models\KeranjangPembelian;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class PembelianLuarTokoController extends Controller
{
    public function getPembelianProduk()
    {
        $pembelianProduk = KeranjangPembelian::with(['produk.jenisproduk', 'produk', 'kondisi'])
            ->where('status', 1)
            ->where('oleh', Auth::user()->id)
            ->where('jenis_pembelian', 2)
            ->get();

        return response()->json(['success' => true, 'message' => 'Data Pembelian Produk Berhasil Ditemukan', 'Data' => $pembelianProduk]);
    }
}
