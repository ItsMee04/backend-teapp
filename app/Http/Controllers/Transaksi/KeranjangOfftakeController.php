<?php

namespace App\Http\Controllers\Transaksi;

use Carbon\Carbon;
use App\Models\Produk;
use App\Models\Offtake;
use App\Models\Keranjang;
use App\Models\NampanProduk;
use Illuminate\Http\Request;
use App\Models\KeranjangOfftake;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
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
        $offtake = KeranjangOfftake::with(['produk', 'user.pegawai', 'produk.karat','produk.harga'])
            ->where('oleh', Auth::user()->id)
            ->where('status', 1)
            ->get();

        // Total berat (sum dari field berat)
        $totalBerat = round(
            $offtake->sum(function ($item) {
                return (float) $item->berat;
            }),
            3 // jumlah digit belakang koma
        );

        // Total harga (sum dari field total)
        $totalHarga = $offtake->sum(function ($item) {
            return (float) $item->total; // total = 1936000
        });

        $totalPotong = $offtake->count();

        return response()->json([
            'success' => true,
            'message' => 'Data keranjang offtake berhasil diambil',
            'data' => $offtake,
            'total_berat' => $totalBerat,
            'total_harga' => $totalHarga,
            'total_potong' => $totalPotong
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
        $produkList = Produk::with(['karat', 'harga'])->whereIn('id', $request->produk_ids)->get();

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

            // 1. Ambil Harga dari relasi (Gunakan fallback ke harga_jual jika relasi kosong)
            $hargaSatuan = $produk->harga ? $produk->harga->harga : $produk->harga_jual;

            // 2. Ambil Nilai Karat dari relasi
            $nilaiKarat = $produk->karat ? $produk->karat->karat : $produk->karat_id;

            // 3. Hitung subtotal dengan harga dari relasi
            $subtotalHargaBaru = $hargaSatuan * (float)$produk->berat;

            $terbilang = ucwords(trim($this->terbilang(abs($subtotalHargaBaru)))) . ' Rupiah';

            $keranjangBaru[] = KeranjangOfftake::create([
                'kodetransaksi' => $kodeOfftake,
                'produk_id'     => $produk->id,
                'harga_jual'    => $hargaSatuan,
                'berat'         => $produk->berat,
                'karat'         => $nilaiKarat,
                'lingkar'       => $produk->lingkar,
                'panjang'       => $produk->panjang,
                'total'         => $subtotalHargaBaru,
                'terbilang'     => $terbilang,
                'oleh'          => Auth::user()->id,
                'status'        => 1,
            ]);

            // Loop melalui setiap item keranjang yang ditemukan
            foreach ($keranjangBaru as $keranjangItem) {
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
        }

        return response()->json([
            'success'       => true,
            'message'       => 'Produk berhasil ditambahkan ke keranjang offtake',
            'kodetransaksi' => $kodeOfftake,
            'data'          => $keranjangBaru
        ]);
    }

    public function deleteProduk($id)
    {
        $keranjangItem = KeranjangOfftake::where('id', $id)
            ->where('oleh', Auth::user()->id)
            ->where('status', 1)
            ->first();

        if (!$keranjangItem) {
            return response()->json([
                'success' => false,
                'message' => 'Item keranjang tidak ditemukan atau sudah diproses.'
            ], 404);
        }

        // ubah status jadi 0, bukan delete
        $keranjangItem->status = 0;
        $keranjangItem->update();

        // --- AKTIFKAN PRODUK DI MASTER ---
        if ($keranjangItem->produk_id) {
            Produk::where('id', $keranjangItem->produk_id)
                ->update(['status' => 1]);

            // --- AMBIL NAMPAN_ID DARI NAMPAN_PRODUK ---
            $nampanId = NampanProduk::where('produk_id', $keranjangItem->produk_id)
                ->orderBy('id', 'desc') // ambil history terakhir
                ->value('nampan_id');

            if ($nampanId) {
                // --- BUAT HISTORY BARU DI NAMPAN_PRODUK ---
                NampanProduk::create([
                    'nampan_id' => $nampanId,
                    'produk_id' => $keranjangItem->produk_id,
                    'jenis'     => 'masuk', // karena balik ke nampan
                    'tanggal'   => Carbon::today()->format('Y-m-d'),
                    'status'    => 1,
                    'oleh'      => Auth::id(),
                ]);
            }
        }

        // --- CEK SISA PRODUK AKTIF DI TRANSAKSI ---
        $activeItems = KeranjangOfftake::where('kodetransaksi', $keranjangItem->kodetransaksi)
            ->where('status', 1)
            ->count();

        // Kalau sudah tidak ada produk aktif, nonaktifkan transaksi
        if ($activeItems == 0) {
            Offtake::where('kodetransaksi', $keranjangItem->kodetransaksi)
                ->update(['status' => 0]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Item keranjang berhasil dikeluarkan dan produk dikembalikan ke nampan.'
        ]);
    }

    public function submitTransaksi(Request $request)
    {
        $request->validate([
            'kodetransaksi' => 'required|string',
            'suplier'       => 'required',
            'keterangan'    => 'nullable|string'
        ]);

        $kodetransaksi = $request->kodetransaksi;

        // Ambil semua produk di keranjang yang aktif
        $keranjang = KeranjangOfftake::where('kodetransaksi', $kodetransaksi)
            ->where('status', 1)
            ->get();

        if ($keranjang->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada produk di keranjang'
            ]);
        }

        $total = $keranjang->sum('total');
        $totalHarga = $request->hargatotal; // pakai kolom total langsung
        $angka = abs($totalHarga);
        $terbilang = ucwords(trim($this->terbilang($angka))) . ' Rupiah';

        $offtake = Offtake::where('kodetransaksi', $kodetransaksi)
            ->where('status', 1)
            ->update([
                "suplier_id"    => $request->suplier,
                "total"         => $total,
                "hargatotal"    => $request->hargatotal,
                "terbilang"     => $terbilang,
                "pembayaran"    => $request->pembayaran,
                "keterangan"    => toUpper($request->keterangan),
                "status"        => 2, // status 2 artinya sudah selesai / tidak aktif
            ]);

        // Ambil data pembelian yang baru diupdate
        $offtake = Offtake::where('kodetransaksi', $kodetransaksi)
            ->where('status', 2)
            ->first();

        // Update semua keranjang jadi status 2
        KeranjangOfftake::where('kodetransaksi', $kodetransaksi)
            ->where('status', 1)
            ->update(['status' => 2]);

        return response()->json([
            'success' => true,
            'message' => 'Pembelian berhasil disimpan',
            'data' => $offtake
        ]);
    }
}
