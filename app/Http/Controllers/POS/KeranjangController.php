<?php

namespace App\Http\Controllers\POS;

use Carbon\Carbon;
use App\Models\Produk;
use App\Models\Keranjang;
use App\Models\Transaksi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class KeranjangController extends Controller
{
    /**
     * Menampilkan daftar produk yang ada di keranjang untuk user yang sedang aktif.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getKeranjang()
    {
        try {
            // 1. Cari transaksi yang sedang aktif (status = 1) untuk user ini
            $activeTransaksi = Transaksi::where('oleh', Auth::user()->id)
                ->where('status', 1)
                ->first();

            // Jika tidak ada transaksi aktif, kembalikan array kosong
            if (!$activeTransaksi) {
                return response()->json([
                    'success' => true,
                    'Data' => []
                ]);
            }

            // 2. Ambil semua produk di keranjang yang terkait dengan transaksi aktif
            $keranjang = Keranjang::where('kodetransaksi', $activeTransaksi->kodetransaksi)
                ->where('status', 1) // Hanya ambil item yang aktif
                ->with('produk') // Eager loading untuk mendapatkan detail produk
                ->get();

            return response()->json([
                'success' => true,
                'Data' => $keranjang
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat keranjang. ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mengambil atau membuat kode transaksi yang sedang aktif untuk user.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getKodeTransaksi()
    {
        try {
            // Cari transaksi aktif untuk user yang login
            $activeTransaksi = Transaksi::where('oleh', Auth::user()->id)
                ->where('status', 1)
                ->first();

            // Jika transaksi aktif ditemukan, kembalikan kode-nya
            if ($activeTransaksi) {
                return response()->json([
                    'success' => true,
                    'kode' => $activeTransaksi->kodetransaksi,
                    'message' => 'Kode transaksi aktif ditemukan.'
                ]);
            }

            // Jika tidak ada transaksi aktif, buat transaksi baru
            $newKode = $this->generateKodeTransaksi();

            Transaksi::create([
                'kodetransaksi' => $newKode,
                'oleh' => Auth::user()->id,
                'status' => 1, // Menandakan transaksi masih berjalan
            ]);

            return response()->json([
                'success' => true,
                'kode' => $newKode,
                'message' => 'Transaksi baru berhasil dibuat.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil kode transaksi: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Menambahkan produk ke keranjang untuk transaksi aktif.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function addToCart(Request $request)
    {
        try {
            // Ambil transaksi aktif dari user
            $activeTransaksi = Transaksi::where('oleh', Auth::user()->id)
                ->where('status', 1)
                ->first();

            if (!$activeTransaksi) {
                return response()->json([
                    'success' => false,
                    'message' => 'Transaksi belum dibuat. Silakan refresh halaman POS.',
                ], 400);
            }

            $kodeTransaksi = $activeTransaksi->kodetransaksi;

            // Cari produk berdasarkan ID
            $produk = Produk::with(['karat','harga'])->where('id',$request->id)->first();

            if (!$produk) {
                return response()->json([
                    'success' => false,
                    'message' => 'Produk tidak ditemukan.',
                ], 404);
            }

            // Cek apakah produk sudah ada di keranjang
            $existing = Keranjang::where('kodetransaksi', $kodeTransaksi)
                ->where('produk_id', $produk->id)
                ->where('status', 1)
                ->first();

            if ($existing) {
                return response()->json([
                    'success' => false,
                    'message' => 'Produk sudah ada di keranjang.',
                ]);
            }

            $karat      = $produk->karat->karat;
            $hargajual  = $produk->harga->harga;
            $berat      = $produk->berat;

            // Hitung total harga
            $total = $hargajual * $berat;
            $terbilang = ucwords(trim($this->terbilang(abs($total)))) . ' Rupiah';

            // Simpan ke keranjang
            $keranjang = Keranjang::create([
                'kodetransaksi' => $kodeTransaksi,
                'produk_id'     => $produk->id,
                'harga_jual'    => $hargajual,
                'berat'         => $produk->berat,
                'karat'         => $karat,
                'lingkar'       => $produk->lingkar,
                'panjang'       => $produk->panjang,
                'total'         => $total,
                'terbilang'     => $terbilang,
                'oleh'          => Auth::user()->id,
                'status'        => 1,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Produk berhasil ditambahkan ke keranjang.',
                'data'    => $keranjang,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menambahkan ke keranjang: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Menghapus satu item dari keranjang dengan mengubah statusnya.
     *
     * @param int $id ID item di tabel keranjang
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteKeranjangApi($id)
    {
        try {
            $keranjang = Keranjang::find($id);

            if (!$keranjang) {
                return response()->json([
                    'success' => false,
                    'message' => 'Produk di keranjang tidak ditemukan.'
                ], 404);
            }

            // Memastikan item yang akan dihapus dimiliki oleh user yang sedang login
            if ($keranjang->oleh !== Auth::user()->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki hak untuk menghapus item ini.'
                ], 403);
            }

            // Ubah status item menjadi 0 (dihapus/tidak aktif)
            $keranjang->status = 0;
            $keranjang->save();

            return response()->json([
                'success' => true,
                'message' => 'Produk berhasil dihapus dari keranjang.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus produk. ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mengosongkan seluruh keranjang belanja dengan mengubah status semua item menjadi 0.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function clearAllKeranjangApi()
    {
        try {
            // Cari transaksi yang sedang aktif untuk user ini
            $activeTransaksi = Transaksi::where('oleh', Auth::user()->id)
                ->where('status', 1)
                ->first();

            // Jika tidak ada transaksi aktif
            if (!$activeTransaksi) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada transaksi aktif untuk dikosongkan.'
                ]);
            }

            // Cek apakah ada item keranjang aktif dengan kode transaksi ini
            $activeKeranjangCount = Keranjang::where('kodetransaksi', $activeTransaksi->kodetransaksi)
                ->where('status', 1)
                ->count();

            // Jika keranjang sudah kosong, jangan lakukan apapun
            if ($activeKeranjangCount === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Keranjang sudah kosong.'
                ]);
            }

            // Jika masih ada, ubah status semua item keranjang menjadi 0 (nonaktif)
            Keranjang::where('kodetransaksi', $activeTransaksi->kodetransaksi)
                ->where('status', 1)
                ->update(['status' => 0]);

            // Ubah juga status transaksi menjadi "dibatalkan" (status = 2)
            $activeTransaksi->status = 2;
            $activeTransaksi->save();

            return response()->json([
                'success' => true,
                'message' => 'Keranjang berhasil dikosongkan.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengosongkan keranjang. ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Fungsi helper untuk mengubah angka menjadi terbilang.
     * Ini bisa dipindahkan ke helper atau service terpisah di aplikasi nyata.
     */
    private function terbilang($angka)
    {
        // Implementasi fungsi terbilang
        $angka = abs($angka);
        $baca = array('', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan', 'sepuluh', 'sebelas');
        $temp = '';
        if ($angka < 12) {
            $temp = ' ' . $baca[$angka];
        } else if ($angka < 20) {
            $temp = $this->terbilang($angka - 10) . ' belas';
        } else if ($angka < 100) {
            $temp = $this->terbilang($angka / 10) . ' puluh' . $this->terbilang($angka % 10);
        } else if ($angka < 200) {
            $temp = ' seratus' . $this->terbilang($angka - 100);
        } else if ($angka < 1000) {
            $temp = $this->terbilang($angka / 100) . ' ratus' . $this->terbilang($angka % 100);
        } else if ($angka < 2000) {
            $temp = ' seribu' . $this->terbilang($angka - 1000);
        } else if ($angka < 1000000) {
            $temp = $this->terbilang($angka / 1000) . ' ribu' . $this->terbilang($angka % 1000);
        } else if ($angka < 1000000000) {
            $temp = $this->terbilang($angka / 1000000) . ' juta' . $this->terbilang($angka % 1000000);
        } else if ($angka < 1000000000000) {
            $temp = $this->terbilang($angka / 1000000000) . ' milyar' . $this->terbilang(fmod($angka, 1000000000));
        } else if ($angka < 1000000000000000) {
            $temp = $this->terbilang($angka / 1000000000000) . ' triliun' . $this->terbilang(fmod($angka, 1000000000000));
        }
        return $temp;
    }

    /**
     * Menghasilkan kode transaksi baru.
     */
    private function generateKodeTransaksi()
    {
        $last = DB::table('transaksi')
            ->orderBy('id', 'desc')
            ->first();

        $lastNumber = 0;
        if ($last) {
            $lastKode = $last->kodetransaksi;
            $lastNumber = (int) substr($lastKode, -4);
        }

        $newNumber = $lastNumber + 1;
        $formattedNumber = str_pad($newNumber, 4, '0', STR_PAD_LEFT);

        $now = Carbon::now()->format('YmdHis');
        $random = rand(100, 999);

        return 'TRX-' . $now . '-' . $random . '-' . $formattedNumber;
    }
}
