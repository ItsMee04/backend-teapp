<?php

namespace App\Http\Controllers\Transaksi;

use Log;
use Carbon\Carbon;
use App\Models\Produk;
use Milon\Barcode\DNS1D;
use App\Models\Pembelian;
use App\Models\Perbaikan;
use Illuminate\Http\Request;
use App\Models\KeranjangPembelian;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Produk\ProdukController;

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

    public function storeProduk(Request $request)
    {
        $request->validate([
            'jenis'          => 'required|exists:jenis_produk,id',
            'nama'           => 'required|string',
            'hargabeli'      => 'required|numeric',
            'berat'          => 'required|numeric',
            'karat'          => 'nullable|string',
            'lingkar'        => 'nullable|string',
            'panjang'        => 'nullable|string',
            'keterangan'     => 'nullable|string',
            'kondisi'        => 'required|exists:kondisi,id',
        ]);

        return DB::transaction(function () use ($request) {
            // 1. Generate kode produk baru
            $produkController = new ProdukController();
            $kodeProduk = $produkController->generateKodeProduk();

            /**
             * Generate BARCODE (Code128) pakai milon/barcode
             */
            $barcodeGenerator = new DNS1D();
            $barcodeGenerator->setStorPath(storage_path('app/public/barcode/'));

            // hasil barcode berupa base64 string
            $barcodeBase64 = $barcodeGenerator->getBarcodePNG($kodeProduk, 'C128');

            // ubah base64 ke binary PNG
            $barcodeImage = base64_decode($barcodeBase64);

            // nama file barcode
            $barcodeFileName = 'barcode/' . $kodeProduk . '.png';

            // simpan ke storage/app/public/barcode/
            Storage::disk('public')->put($barcodeFileName, $barcodeImage);

            // 2. Simpan ke master produk (status = 0)
            $produk = Produk::create([
                'kodeproduk'    => $kodeProduk,
                'jenisproduk_id' => $request->jenis,
                'nama'          => toUpper($request->nama),
                'harga_beli'    => $request->hargabeli,
                'berat'         => $request->berat,
                'karat'         => $request->karat,
                'lingkar'       => $request->lingkar??0,
                'panjang'       => $request->panjang??0,
                'keterangan'    => toUpper($request->keterangan),
                'kondisi_id'    => $request->kondisi,
                'status'        => 0, // produk baru dari luar toko = belum aktif
            ]);

            // 3. Cari transaksi pembelian aktif (status = 1) untuk user ini
            $activePembelian = KeranjangPembelian::where('oleh', Auth::user()->id)
                ->where('status', 1)
                ->where('jenis_pembelian', 'luartoko')
                ->first();

            $kodePembelian = null;

            if (!$activePembelian) {
                // kalau belum ada transaksi → buat baru
                $PembelianTokoController = new PembelianTokoController();
                $kodePembelian = $PembelianTokoController->generateKodePembelian();

                $activePembelian = Pembelian::create([
                    'kodepembelian' => $kodePembelian,
                    'tanggal'       => Carbon::now(),
                    'oleh'          => Auth::user()->id,
                    'status'        => 1, // aktif
                ]);
            } else {
                $kodePembelian = $activePembelian->kodepembelian;
            }

            $berat = $produk->berat;
            $harga = $produk->harga_beli;
            $total = $berat * $harga;

            $terbilang = ucwords($this->terbilang($total)) . ' Rupiah';
            // 4. Simpan ke keranjang pembelian
            $keranjang = KeranjangPembelian::create([
                'kodepembelian'     => $kodePembelian,
                'produk_id'         => $produk->id,
                'kondisi_id'        => $produk->kondisi_id,
                'jenisproduk_id'    => $produk->jenisproduk_id,
                'nama'              => toUpper($produk->nama),
                'harga_beli'        => $produk->harga_beli,
                'berat'             => $produk->berat,
                'karat'             => $produk->karat,
                'lingkar'           => $produk->lingkar??0,
                'panjang'           => $produk->panjang??0,
                'total'             => $total,
                'terbilang'         => $terbilang,
                'keterangan'        => $produk->keterangan,
                'oleh'              => Auth::user()->id,
                'status'            => 1, // aktif di keranjang
                'jenis_pembelian'   => 'luartoko',
            ]);

            // 5. Jika kondisi ≠ 1 → masuk ke perbaikan
            if ($request->kondisi != 1) {

                $perbaikanController = new PerbaikanController();
                $kodePerbaikan = $perbaikanController->kodePerbaikan();
                Perbaikan::create([
                    'kodeperbaikan' => $kodePerbaikan,
                    'produk_id'   => $produk->id,
                    'tanggalmasuk'  => Carbon::now(),
                    'kondisi_id'  => $produk->kondisi_id,
                    'keterangan'  => 'Produk dari luar toko kondisi tidak baik',
                    'status'      => 1, // aktif di perbaikan
                    'oleh'        => Auth::user()->id,
                ]);
            }

            return response()->json([
                'success'   => true,
                'message'   => 'Produk luar toko berhasil ditambahkan ke master & keranjang',
                'produk'    => $produk,
                'keranjang' => $keranjang,
                'pembelian' => $activePembelian,
            ]);
        });
    }

    public function updateProduk(Request $request, $id)
    {
        $request->validate([
            'jenis'      => 'required|exists:jenis_produk,id',
            'nama'       => 'required|string',
            'hargabeli'  => 'required|numeric',
            'berat'      => 'required|numeric',
            'karat'      => 'nullable|string',
            'lingkar'    => 'nullable|string',
            'panjang'    => 'nullable|string',
            'keterangan' => 'nullable|string',
            'kondisi'    => 'required|exists:kondisi,id',
        ]);

        return DB::transaction(function () use ($request, $id) {
            // Cari keranjang pembelian
            $keranjang = KeranjangPembelian::where('id', $id)
                ->where('status', 1)
                ->where('oleh', Auth::id())
                ->where('jenis_pembelian', 2) // luar toko
                ->first();

            if (!$keranjang) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data keranjang pembelian tidak ditemukan atau sudah tidak aktif'
                ], 404);
            }

            // Ambil produk terkait
            $produk = Produk::find($keranjang->produk_id);
            if (!$produk) {
                return response()->json([
                    'success' => false,
                    'message' => 'Produk terkait tidak ditemukan'
                ], 404);
            }

            // Simpan kondisi baru dulu ke produk
            $produk->update([
                'jenisproduk_id' => $request->jenis,
                'nama'           => toUpper($request->nama),
                'harga_beli'     => $request->hargabeli,
                'berat'          => $request->berat,
                'karat'          => $request->karat,
                'lingkar'        => $request->lingkar??0,
                'panjang'        => $request->panjang??0,
                'keterangan'     => toUpper($request->keterangan),
                'kondisi_id'     => $request->kondisi,
            ]);

            // ----- LOGIKA PERBAIKAN -----
            // Cek kondisi baru
            $kondisiBaru = $request->kondisi;

            // Cari record perbaikan dengan produk_id ini yang masih aktif (status = 1)
            $perbaikan = Perbaikan::where('produk_id', $produk->id)
                ->where('status', 1)
                ->first();

            if (in_array($kondisiBaru, [2, 3])) {
                // kalau kondisi baru 2 atau 3
                if ($perbaikan) {
                    // sudah ada perbaikan yg aktif, update tanggalmasuk jika ingin diperbaharui
                    // misal kalau kondisi berubah dari 2 ke 3 atau tetap 2 tapi ingin reset tanggal masuk
                    $perbaikan->update([
                        'tanggalmasuk' => Carbon::now(),
                        'kondisi_id'    => $kondisiBaru,
                        'keterangan'    => $request->keterangan ?? $perbaikan->keterangan,
                        // bisa update field lain kalau perlu
                    ]);
                } else {
                    // belum ada perbaikan yg aktif, buat baru
                    // misal kode perbaikan otomatis dibuat dengan fungsi tertentu
                    $kodePerbaikan = $this->generateKodePerbaikan();
                    Perbaikan::create([
                        'kodeperbaikan' => $kodePerbaikan,
                        'produk_id'     => $produk->id,
                        'tanggalmasuk'  => Carbon::now(),
                        'kondisi_id'    => $kondisiBaru,
                        'keterangan'    => $request->keterangan ?? 'Produk dari luar toko kondisi tidak baik',
                        'status'        => 1, // perbaikan aktif
                        'oleh'          => Auth::user()->id,
                    ]);
                }
            } else if ($kondisiBaru == 1) {
                // kondisi jadi baik (1), berarti keluar dari perbaikan
                if ($perbaikan) {
                    // ubah status perbaikan menjadi 0 (non aktif)
                    $perbaikan->update([
                        'status' => 0
                    ]);
                }
            }
            // ----- END LOGIKA PERBAIKAN -----

            // Hitung ulang total & terbilang
            $berat = $request->berat;
            $harga = $request->hargabeli;
            $total = $berat * $harga;
            $terbilang = ucwords($this->terbilang($total)) . ' Rupiah';

            // Update data keranjang pembelian
            $keranjang->update([
                'kondisi_id'     => $request->kondisi,
                'jenisproduk_id' => $request->jenis,
                'nama'           => toUpper($request->nama),
                'harga_beli'     => $request->hargabeli,
                'berat'          => $request->berat,
                'karat'          => $request->karat,
                'lingkar'        => $request->lingkar??0,
                'panjang'        => $request->panjang??0,
                'total'          => $total,
                'terbilang'      => $terbilang,
                'keterangan'     => toUpper($request->keterangan),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Data keranjang pembelian berhasil diupdate',
                'data'    => $keranjang
            ]);
        });
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

        // --- NONAKTIFKAN PRODUK DI MASTER ---
        if ($keranjangItem->produk_id) {
            Produk::where('id', $keranjangItem->produk_id)
                ->update(['status' => 0]);
        }

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
            'pelanggan_id'  => 'nullable|integer',
            'suplier_id'    => 'nullable|integer',
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
        $terbilang = ucwords(trim($this->terbilang(abs($totalHarga)))) . ' Rupiah';

        // Update pembelian (termasuk pelanggan_id & suplier_id)
        $updateData = [
            "total"        => $totalHarga,
            "terbilang"    => $terbilang,
            "catatan"      => toUpper($request->catatan),
            "status"       => 2, // sudah selesai
            "pelanggan_id" => $request->pelanggan_id ?? null,
            "suplier_id"   => $request->suplier_id ?? null,
        ];

        Pembelian::where('kodepembelian', $kodepembelian)
            ->where('status', 1)
            ->update($updateData);

        // Ambil data pembelian terbaru
        $pembelian = Pembelian::where('kodepembelian', $kodepembelian)
            ->where('status', 2)
            ->first();

        // Update status keranjang
        KeranjangPembelian::where('kodepembelian', $kodepembelian)
            ->where('status', 1)
            ->update(['status' => 2]);

        return response()->json([
            'success' => true,
            'message' => 'Pembelian berhasil disimpan',
            'data'    => $pembelian
        ]);
    }
}
