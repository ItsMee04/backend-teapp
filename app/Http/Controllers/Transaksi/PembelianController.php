<?php

namespace App\Http\Controllers\Transaksi;

use App\Models\Produk;
use App\Models\Pembelian;
use App\Models\NampanProduk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class PembelianController extends Controller
{
    public function getPembelian()
    {
        $pembelian = Pembelian::with(['pelanggan', 'suplier', 'keranjangPembelian', 'user', 'user.pegawai'])->get();
        return response()->json([
            'status' => 'success',
            'message' => 'Data pembelian berhasil diambil',
            'data' => $pembelian
        ]);
    }

    public function batalTransaksi(Request $request)
    {
        DB::beginTransaction();

        try {
            $credentials = $request->validate([
                'kodepembelian'   => 'required|string',
            ]);

            $kodepembelian = $request->kodepembelian;

            $transaksi = Pembelian::with(['keranjangPembelian'])
                ->where('kodepembelian', $kodepembelian)
                ->first();

            if (!$transaksi) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Transaksi tidak ditemukan'
                ], 404);
            }

            // update status transaksi jadi batal
            $transaksi->update(['status' => 0]);

            foreach ($transaksi->keranjangPembelian as $keranjangItem) {
                if (!is_null($keranjangItem->kodetransaksi)) {
                    // ✅ dari toko → batalin keranjang + aktifkan produk lagi
                    $keranjangItem->update(['status' => 0]);
                    Produk::where('id', $keranjangItem->produk_id)->update(['status' => 2]);
                } else {
                    // ✅ dari luar toko → hapus produk + keranjang
                    Produk::where('id', $keranjangItem->produk_id)->delete();
                    $keranjangItem->update(['status' => 0 ]);
                }
            }

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Transaksi berhasil dibatalkan',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal membatalkan transaksi: ' . $e->getMessage(),
            ]);
        }
    }
}
