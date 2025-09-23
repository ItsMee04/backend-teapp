<?php

namespace App\Http\Controllers\Transaksi;

use Carbon\Carbon;
use App\Models\Produk;
use App\Models\Offtake;
use Illuminate\Http\Request;
use App\Models\KeranjangOfftake;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\Keranjang;
use Illuminate\Support\Facades\Auth;

class KeranjangOfftakeController extends Controller
{
    /**
     * Menghasilkan kode transaksi baru.
     */
    private function generateKodeTransaksi()
    {
        $last = DB::table('offtake')
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

        return 'OFT-' . $now . '-' . $random . '-' . $formattedNumber;
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
            $activeTransaksi = Offtake::where('oleh', Auth::user()->id)
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

            Offtake::create([
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

    public function getKeranjangOfftake()
    {
        $offtake = Offtake::with(['suplier', 'user', 'user.pegawai'])
            ->where('status', 1)
            ->first();

        return response()->json([
            'status' => 'success',
            'message' => 'Data keranjang offtake berhasil diambil',
            'data' => $offtake
        ]);
    }

    public function getKeranjangOfftakeAktif()
    {
        $offtake = KeranjangOfftake::with(['produk','user.pegawai'])
            ->where('oleh', Auth::user()->id)
            ->where('status', 1)
            ->first();

        return response()->json([
            'status' => 'success',
            'message' => 'Data keranjang offtake berhasil diambil',
            'data' => $offtake
        ]);
    }

    public function storeKeranjangOfftake(Request $request)
    {
        // 🔹 Validasi: harus ada produk_ids
        if (!$request->has('produk_ids') || !is_array($request->produk_ids) || count($request->produk_ids) === 0) {
            return response()->json([
                'success' => false,
                'message' => 'Silakan pilih minimal 1 produk.'
            ], 422);
        }

        // 🔹 Ambil semua produk berdasarkan produk_ids
        $produkList = Produk::whereIn('id', $request->produk_ids)->get();

        if ($produkList->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Produk tidak ditemukan.'
            ], 404);
        }

        // 🔹 Cari transaksi aktif untuk user
        $activeOfftake = Offtake::where('oleh', Auth::user()->id)
            ->where('status', 1)
            ->first();

        if (!$activeOfftake) {
            $kodeOfftake = $this->generateKodeTransaksi();
            $activeOfftake = Offtake::create([
                'kodetransaksi' => $kodeOfftake,
                'tanggal'       => Carbon::now(),
                'oleh'          => Auth::user()->id,
                'status'        => 1,
            ]);
        }

        $kodeOfftake = $activeOfftake->kodetransaksi;
        $keranjangBaru = [];

        // 🔹 Loop semua produk yang dipilih
        foreach ($produkList as $produk) {
            // Cek apakah produk sudah ada di keranjang
            $existingProductInCart = KeranjangOfftake::where('produk_id', $produk->id)
                ->where('kodetransaksi', $kodeOfftake)
                ->where('status', 1)
                ->first();

            if ($existingProductInCart) {
                // Lewati produk ini biar nggak dobel
                continue;
            }

            // Hitung subtotal
            $subtotalHargaBaru = $produk->harga_jual * $produk->berat;
            $angka = abs($subtotalHargaBaru);
            $terbilang = ucwords(trim($this->terbilang($angka))) . ' Rupiah';

            $keranjangBaru[] = KeranjangOfftake::create([
                'kodetransaksi' => $kodeOfftake,
                'produk_id'     => $produk->id,
                'harga_jual'    => $produk->harga_jual,
                'berat'         => $produk->berat,
                'karat'         => $produk->karat,
                'lingkar'       => $produk->lingkar,
                'panjang'       => $produk->panjang,
                'total'         => $subtotalHargaBaru,
                'terbilang'     => $terbilang,
                'oleh'          => Auth::user()->id,
                'status'        => 1,
            ]);
        }

        return response()->json([
            'success'       => true,
            'message'       => 'Produk berhasil ditambahkan ke keranjang offtake',
            'kodetransaksi' => $kodeOfftake,
            'data'          => $keranjangBaru
        ]);
    }
}
