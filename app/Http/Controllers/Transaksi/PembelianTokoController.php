<?php

namespace App\Http\Controllers\Transaksi;

use Carbon\Carbon;
use App\Models\Produk;
use App\Models\Pembelian;
use App\Models\Perbaikan;
use Illuminate\Http\Request;
use App\Models\KeranjangPembelian;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Transaksi\PerbaikanController;

class PembelianTokoController extends Controller
{
    public function getPembelianProduk()
    {
        $pembelianProduk = KeranjangPembelian::with(['produk.jenisproduk', 'produk', 'kondisi'])
            ->where('status', 1)
            ->where('oleh', Auth::user()->id)
            ->where('jenis_pembelian', 1)
            ->get();

        return response()->json(['success' => true, 'message' => 'Data Pembelian Produk Berhasil Ditemukan', 'Data' => $pembelianProduk]);
    }

    /**
     * Menghasilkan kode pembelian baru.
     */
    public function generateKodePembelian()
    {
        $last = DB::table('pembelian')
            ->orderBy('id', 'desc')
            ->first();

        $lastNumber = 0;
        if ($last) {
            $lastKode = $last->kodepembelian;
            $lastNumber = (int) substr($lastKode, -4);
        }

        $newNumber = $lastNumber + 1;
        $formattedNumber = str_pad($newNumber, 4, '0', STR_PAD_LEFT);

        $now = Carbon::now()->format('YmdHis');
        $random = rand(100, 999);

        return 'PEM-' . $now . '-' . $random . '-' . $formattedNumber;
    }

    public function getKodePembelian()
    {
        try {
            // Cari pembelian yang sedang aktif (status = 1) untuk user ini
            $activePembelian = Pembelian::where('oleh', Auth::user()->id)
                ->where('status', 1)
                ->first();

            // Jika ada pembelian aktif, kembalikan kodenya
            if ($activePembelian) {
                return response()->json([
                    'success' => true,
                    'kode' => $activePembelian->kodepembelian,
                    'message' => 'Kode pembelian berhasil ditemukan.'
                ]);
            }

            $newKode = $this->generateKodePembelian();
            // Jika tidak ada pembelian aktif
            return response()->json([
                'success' => true,
                'kode' => $newKode,
                'message' => 'Tidak ada pembelian aktif.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mendapatkan kode pembelian. ' . $e->getMessage()
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

    public function getKodePembelianAktif()
    {
        $userId = Auth::id();

        $activePembelian = Pembelian::where('oleh', $userId)
            ->where('status', 1)
            ->first();

        if ($activePembelian) {
            return response()->json([
                'success' => true,
                'Data' => [
                    'kodepembelian' => $activePembelian->kodepembelian,
                    'pelanggan_id' => $activePembelian->pelanggan_id,
                    'pelanggan_nama' => $activePembelian->pelanggan->nama ?? null
                ]
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Tidak ada transaksi aktif'
        ]);
    }

    /**
     * Menambahkan produk ke keranjang belanja.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function pilihProduk(Request $request)
    {
        // Cek produk_id valid di master produk
        $produk = Produk::find($request->id);
        if (!$produk) {
            return response()->json([
                'success' => false,
                'message' => 'Produk tidak ditemukan.'
            ], 404);
        }

        // 1. Cari transaksi yang sedang aktif (status = 1) untuk user ini
        $activePembelian = Pembelian::where('oleh', Auth::user()->id)
            ->where('pelanggan_id', $request->pelanggan_id)
            ->where('status', 1)
            ->first();

        $kodePembelian = null;

        // 2. Jika tidak ada transaksi aktif, buat transaksi baru
        if (!$activePembelian) {
            $kodePembelian = $this->generateKodePembelian();

            Pembelian::create([
                'kodepembelian' => $kodePembelian,
                'pelanggan_id'  => $request->pelanggan_id,
                'tanggal'       => Carbon::now(),
                'oleh'          => Auth::user()->id,
                'status'        => 1, // Status 1 menandakan transaksi sedang aktif
            ]);
        } else {
            $kodePembelian = $activePembelian->kodepembelian;
        }

        // 🔹 Validasi produk di keranjang
        $existingProductInCart = KeranjangPembelian::where('produk_id', $request->id)
            ->where('kodetransaksi', $request->kodetransaksi) // cek transaksi asal
            ->orderByDesc('id')
            ->first();

        if ($existingProductInCart) {
            if ($existingProductInCart->status == 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Produk ini sudah ada di keranjang aktif untuk transaksi ini.'
                ]);
            }

            if ($existingProductInCart->status == 2) {
                return response()->json([
                    'success' => false,
                    'message' => 'Produk ini sudah masuk transaksi pembelian untuk transaksi ini dan tidak bisa dipilih lagi.'
                ]);
            }
        }


        // Siapkan data untuk dimasukkan ke tabel keranjang
        $dataKeranjang = [
            'kodetransaksi' => $request->kodetransaksi, // simpan transaksi asal
            'kodepembelian' => $kodePembelian,
            'produk_id' => $request->id,
            'berat' => $produk->berat,
            'karat' => $produk->karat,
            'lingkar' => $produk->lingkar,
            'panjang' => $produk->panjang,
            'oleh' => Auth::user()->id,
            'status' => 1, // Status 1 menandakan item ini berada di keranjang aktif
        ];

        $keranjang = KeranjangPembelian::create($dataKeranjang);

        return response()->json([
            'success' => true,
            'message' => 'Produk Berhasil Ditambahkan',
            'data' => $keranjang
        ]);
    }


    public function updatehargaPembelianProduk(Request $request, $id)
    {
        $produk = KeranjangPembelian::findOrFail($id);

        $messages = [
            'required' => ':attribute wajib di isi !!!',
            'integer'  => ':attribute format wajib menggunakan angka',
        ];

        $credentials = $request->validate([
            'hargabeli' => 'required|integer',
            'kondisi'   => 'required|integer',
            'berat'     => 'required|numeric|min:0.01', // ✅ wajib angka desimal positif
        ], $messages);

        // Hitung subtotalharga baru (harga_beli * berat produk yang ada di pembelian_produk)
        $subtotalHargaBaru = $request->hargabeli * $request->berat;
        $angka = abs($subtotalHargaBaru);
        $terbilang = ucwords(trim($this->terbilang($angka))) . ' Rupiah';

        // Update data pembelian produk sekaligus subtotalharga
        $produk->update([
            'harga_beli'     => $request->hargabeli,
            'kondisi_id'     => $request->kondisi,
            'berat'          => $request->berat,
            'total'          => $subtotalHargaBaru,
            'terbilang'      => $terbilang,
        ]);

        return response()->json(['success' => true, 'message' => 'Data Produk Berhasil Disimpan']);
    }

    public function deleteProduk($id)
    {
        $keranjangItem = KeranjangPembelian::where('id', $id)
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

        // --- CEK SISA PRODUK AKTIF DI TRANSAKSI ---
        $activeItems = KeranjangPembelian::where('kodepembelian', $keranjangItem->kodepembelian)
            ->where('status', 1)
            ->count();

        // Kalau sudah tidak ada produk aktif, nonaktifkan transaksi
        if ($activeItems == 0) {
            Pembelian::where('kodepembelian', $keranjangItem->kodepembelian)
                ->update(['status' => 0]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Item keranjang berhasil dikeluarkan.'
        ]);
    }

    public function storePembelian(Request $request)
    {
        $request->validate([
            'kodepembelian' => 'required|string',
            'catatan'       => 'nullable|string',
        ]);

        $kodepembelian = $request->kodepembelian;

        // Ambil semua produk di keranjang yang aktif
        $keranjang = KeranjangPembelian::where('kodepembelian', $kodepembelian)
            ->where('status', 1)
            ->get();

        if ($keranjang->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada produk di keranjang'
            ]);
        }

        $totalHarga = $keranjang->sum('total'); // pakai kolom total langsung

        $angka = abs($totalHarga);
        $terbilang = ucwords(trim($this->terbilang($angka))) . ' Rupiah';

        $pembelian = Pembelian::where('kodepembelian', $kodepembelian)
            ->where('status', 1)
            ->update([
                "total" => $totalHarga,
                "terbilang" => $terbilang,
                "catatan" => $request->catatan,
                "status" => 2, // status 2 artinya sudah selesai / tidak aktif
            ]);

        // Ambil data pembelian yang baru diupdate
        $pembelian = Pembelian::where('kodepembelian', $kodepembelian)
            ->where('status', 2)
            ->first();

        // Update semua keranjang jadi status 2
        KeranjangPembelian::where('kodepembelian', $kodepembelian)
            ->where('status', 1)
            ->update(['status' => 2]);

        $kodeperbaikan = (new PerbaikanController)->kodePerbaikan();
        // Insert ke perbaikan jika kondisi_id 2 atau 3
        foreach ($keranjang as $item) {
            if (in_array($item->kondisi_id, [2, 3])) {
                Perbaikan::create([
                    'kodeperbaikan' => $kodeperbaikan,
                    'produk_id'     => $item->produk_id,
                    'kondisi_id'    => $item->kondisi_id,
                    'tanggalmasuk'  => Carbon::now(),
                    'status'        => 1, // bisa disesuaikan
                    'oleh'          => Auth::id(),
                    'keterangan'    => 'Produk masuk perbaikan', // opsional
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Pembelian berhasil disimpan',
            'data' => $pembelian
        ]);
    }

    public function batalPembelian(Request $request)
    {
        $request->validate([
            'kodepembelian' => 'required|string',
        ]);

        $kodepembelian = $request->kodepembelian;

        // Cek apakah masih ada produk aktif di keranjang
        $keranjang = KeranjangPembelian::where('kodepembelian', $kodepembelian)
            ->where('status', 1)
            ->get();

        if ($keranjang->isNotEmpty()) {
            // Kalau masih ada produk, nonaktifkan semua
            KeranjangPembelian::where('kodepembelian', $kodepembelian)
                ->where('status', 1)
                ->update(['status' => 0]);

            return response()->json([
                'success' => true,
                'message' => 'Produk di keranjang berhasil dibatalkan'
            ]);
        } else {
            // Kalau sudah kosong, nonaktifkan transaksi pembelian
            $updated = Pembelian::where('kodepembelian', $kodepembelian)
                ->where('status', 1)
                ->update(['status' => 0]);

            if ($updated) {
                return response()->json([
                    'success' => true,
                    'message' => 'Transaksi pembelian berhasil dibatalkan'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Transaksi tidak ditemukan atau sudah dibatalkan'
                ], 404);
            }
        }
    }
}
