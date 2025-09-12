<?php

namespace App\Http\Controllers\Transaksi;

use Carbon\Carbon;
use App\Models\Produk;
use App\Models\Pembelian;
use Illuminate\Http\Request;
use App\Models\KeranjangPembelian;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

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
    private function generateKodePembelian()
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
            ->where('status', 1)
            ->first();

        $kodePembelian = null;

        // 2. Jika tidak ada transaksi aktif, buat transaksi baru
        if (!$activePembelian) {
            $kodePembelian = $this->generateKodePembelian();

            // Masukkan hanya kode transaksi ke tabel transaksi
            Pembelian::create([
                'kodepembelian' => $kodePembelian,
                'pelanggan_id'  => $request->pelanggan_id,
                'tanggal'       => Carbon::now(),
                'oleh'          => Auth::user()->id,
                'status'        => 1, // Status 1 menandakan transaksi sedang aktif
            ]);
        } else {
            // Jika ada transaksi aktif, gunakan kode yang sudah ada
            $kodePembelian = $activePembelian->kodepembelian;
        }

        // Ambil data produk dari database berdasarkan ID yang dikirim
        $produk = Produk::where('id', $request->id)->first();
        if (!$produk) {
            return response()->json([
                'success' => false,
                'message' => 'Produk tidak ditemukan.'
            ], 404);
        }

        // Cek apakah produk sudah ada di keranjang transaksi yang aktif
        $existingProductInCart = KeranjangPembelian::where('kodepembelian', $kodePembelian)
            ->where('produk_id', $request->id)
            ->where('status', 1)
            ->first();

        if ($existingProductInCart) {
            return response()->json([
                'success' => false,
                'message' => 'Produk ini sudah ada di keranjang'
            ]);
        }

        // Siapkan data untuk dimasukkan ke tabel keranjang
        $dataKeranjang = [
            'kodepembelian' => $kodePembelian,
            'produk_id' => $request->id,
            'harga_jual' => $produk->harga_jual,
            'berat' => $produk->berat,
            'karat' => $produk->karat,
            'lingkar' => $produk->lingkar,
            'panjang' => $produk->panjang,
            'oleh' => Auth::user()->id,
            'status' => 1, // Status 1 menandakan item ini berada di keranjang aktif
        ];

        // Simpan data ke database
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
            'kondisi'   => 'required|integer'
        ], $messages);

        // Hitung subtotalharga baru (harga_beli * berat produk yang ada di pembelian_produk)
        $subtotalHargaBaru = $request->hargabeli * $request->berat;

        // Update data pembelian produk sekaligus subtotalharga
        $produk->update([
            'harga_beli'     => $request->hargabeli,
            'kondisi_id'     => $request->kondisi,
            'berat'          => $request->berat,
            'subtotalharga'  => $subtotalHargaBaru,
        ]);

        // Ambil ID produk master dari kodeproduk
        $produkMaster = Produk::where('kodeproduk', $produk->kodeproduk)->first();

        // // Jika ditemukan, update juga perbaikannya
        // if ($produkMaster) {
        //     if (in_array($request->kondisi, [2, 3])) {
        //         // Jika kondisi rusak atau kusam, update kondisi di perbaikan
        //         Perbaikan::where('produk_id', $produkMaster->id)->update([
        //             'kondisi_id' => $request->kondisi,
        //             'status'     => 1
        //         ]);
        //     } elseif ($request->kondisi == 1) {
        //         // Jika kondisi bagus, update status perbaikan menjadi selesai
        //         Perbaikan::where('produk_id', $produkMaster->id)->update([
        //             'kondisi_id' => 1,
        //             'status'     => 0
        //         ]);
        //     }
        // }

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

        return response()->json([
            'success' => true,
            'message' => 'Item keranjang berhasil dikeluarkan.'
        ]);
    }
}
