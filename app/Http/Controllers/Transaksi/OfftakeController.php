<?php

namespace App\Http\Controllers\Transaksi;

use App\Http\Controllers\Controller;
use App\Models\Offtake;
use Illuminate\Http\Request;

class OfftakeController extends Controller
{
    public function getTransaksiOfftake()
    {
        $transaksi = Offtake::with(['keranjangOfftake', 'keranjangOfftake.produk', 'suplier', 'user.pegawai'])
            ->whereIn('status', [1, 2])
            ->get();

        return response()->json([
            'success'   => true,
            'message'   => 'Data transaksi berhasil ditemukan',
            'data'      => $transaksi
        ]);
    }
}
