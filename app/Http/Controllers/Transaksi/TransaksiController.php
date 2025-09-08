<?php

namespace App\Http\Controllers\Transaksi;

use Carbon\Carbon;
use App\Models\Produk;
use App\Models\Keranjang;
use App\Models\Transaksi;
use App\Models\NampanProduk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class TransaksiController extends Controller
{
    private function terbilang($angka)
    {
        $angka = abs($angka);
        $huruf = ["", "satu", "dua", "tiga", "empat", "lima", "enam", "tujuh", "delapan", "sembilan", "sepuluh", "sebelas"];

        if ($angka < 12) {
            return $huruf[$angka];
        } elseif ($angka < 20) {
            return $this->terbilang($angka - 10) . " belas";
        } elseif ($angka < 100) {
            return $this->terbilang(floor($angka / 10)) . " puluh " . $this->terbilang($angka % 10);
        } elseif ($angka < 200) {
            return "seratus " . $this->terbilang($angka - 100);
        } elseif ($angka < 1000) {
            return $this->terbilang(floor($angka / 100)) . " ratus " . $this->terbilang($angka % 100);
        } elseif ($angka < 2000) {
            return "seribu " . $this->terbilang($angka - 1000);
        } elseif ($angka < 1000000) {
            return $this->terbilang(floor($angka / 1000)) . " ribu " . $this->terbilang($angka % 1000);
        } elseif ($angka < 1000000000) {
            return $this->terbilang(floor($angka / 1000000)) . " juta " . $this->terbilang($angka % 1000000);
        } elseif ($angka < 1000000000000) {
            return $this->terbilang(floor($angka / 1000000000)) . " miliar " . $this->terbilang($angka % 1000000000);
        } else {
            return "angka terlalu besar";
        }
    }

    /**
     * Memproses pembayaran transaksi.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function payment(Request $request)
    {
        DB::beginTransaction();

        try {
            // Ambil semua produk_id dari keranjang aktif yang terkait dengan kodetransaksi dan user ini
            $keranjangItems = Keranjang::where('kodetransaksi', $request->kodetransaksi)
                ->where('oleh', Auth::id())
                ->where('status', 1)
                ->get();

            // Validasi: pastikan keranjang tidak kosong
            if ($keranjangItems->isEmpty()) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada produk dalam keranjang aktif untuk diproses.'
                ], 404);
            }

            $angka = abs($request->grandTotal);
            $terbilang = ucwords(trim($this->terbilang($angka))) . ' Rupiah';

            // Simpan transaksi utama
            Transaksi::where('kodetransaksi', $request->kodetransaksi)->update([
                'pelanggan_id'  => $request->pelangganid,
                'diskon_id'     => $request->diskonid,
                'total'         => $request->grandTotal,
                'terbilang'     => $terbilang,
                'tanggal'       => Carbon::today()->format('Y-m-d')
            ]);

            // Loop melalui setiap item keranjang yang ditemukan
            foreach ($keranjangItems as $keranjangItem) {
                // Update produk menjadi tidak aktif (terjual)
                Produk::where('id', $keranjangItem->produk_id)->update(['status' => 2]);

                // Ambil entri nampan_produk asalnya (yang aktif)
                $nampanProdukAwal = NampanProduk::where('produk_id', $keranjangItem->produk_id)
                    ->where('status', 1)
                    ->latest('id')
                    ->first();

                if ($nampanProdukAwal) {
                    // Tandai yang awal sudah tidak aktif
                    $nampanProdukAwal->update(['status' => 2]);

                    // Buat histori keluar (entry baru)
                    NampanProduk::create([
                        'produk_id'     => $keranjangItem->produk_id,
                        'nampan_id'     => $nampanProdukAwal->nampan_id,
                        'jenis'         => 'keluar',
                        'tanggal'       => Carbon::now(),
                        'status'        => 2,
                        'oleh'          => Auth::user()->id,
                    ]);
                }
            }

            // Update status semua keranjang yang terkait dengan kodetransaksi menjadi 2
            Keranjang::where('kodetransaksi', $request->kodetransaksi)
                ->where('oleh', Auth::id())
                ->where('status', 1)
                ->update(['status' => 2]);

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Transaksi Berhasil',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Transaksi gagal: ' . $e->getMessage(),
            ]);
        }
    }

    public function konfirmasiPembayaran(Request $request)
    {
        // Mengambil kodetransaksi dari request
        $kodetransaksi = $request->kodetransaksi;

        // Mencari transaksi berdasarkan kodetransaksi, bukan ID
        $transaksi = Transaksi::where('kodetransaksi', $kodetransaksi)->first();

        if ($transaksi) {
            // Jika transaksi ditemukan, perbarui status
            $transaksi->status = 2;
            $transaksi->save();

            return response()->json(['success' => true, 'message' => 'Pembayaran Berhasil Dikonfirmasi']);
        } else {
            // Jika transaksi tidak ditemukan, kembalikan response error
            return response()->json(['success' => false, 'message' => 'Transaksi tidak ditemukan'], 404);
        }
    }
}
