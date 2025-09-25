<?php

namespace App\Http\Controllers\Transaksi;

use App\Http\Controllers\Controller;
use App\Models\Offtake;
use Illuminate\Http\Request;

class OfftakeController extends Controller
{
    public function getTransaksiOfftake()
    {
        $transaksi = Offtake::with(['keranjangOfftake', 'keranjangOfftake.produk', 'suplier', 'user.pegawai'])->get();

        return response()->json([
            'success'   => true,
            'message'   => 'Data transaksi berhasil ditemukan',
            'data'      => $transaksi
        ]);
    }
}
