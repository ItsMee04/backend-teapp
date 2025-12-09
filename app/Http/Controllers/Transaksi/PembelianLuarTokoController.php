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
        // 1. Validasi Input
        $request->validate([
            'jenis'             => 'required|exists:jenis_produk,id',
            'nama'              => 'required|string',
            'hargabeli'         => 'required|numeric',
            'berat'             => 'required|numeric',
            'karat'             => 'nullable|string',
            'lingkar'           => 'nullable|string',
            'panjang'           => 'nullable|string',
            'keterangan'        => 'nullable|string',
            'kondisi'           => 'required|exists:kondisi,id',
        ]);

        return DB::transaction(function () use ($request) {
            $keranjang = null;
            $activePembelian = null;
            $perbaikan = null;
            $message = '';

            // --- Langkah A: Pembuatan Produk Master & Barcode (Wajib untuk semua kondisi) ---

            // A.1 Generate kode produk baru & Barcode
            $produkController = new ProdukController();
            $kodeProduk = $produkController->generateKodeProduk();

            $barcodeGenerator = new DNS1D();
            $barcodeGenerator->setStorPath(storage_path('app/public/barcode/'));
            $barcodeBase64 = $barcodeGenerator->getBarcodePNG($kodeProduk, 'C128');
            $barcodeImage = base64_decode($barcodeBase64);
            $barcodeFileName = 'barcode/' . $kodeProduk . '.jpg';
            Storage::disk('public')->put($barcodeFileName, $barcodeImage);

            // A.2 Simpan ke master produk (Status = 0: belum aktif)
            $produk = Produk::create([
                'kodeproduk'    => $kodeProduk,
                'jenisproduk_id' => $request->jenis,
                'nama'          => toUpper($request->nama),
                'harga_beli'    => $request->hargabeli,
                'berat'         => $request->berat,
                'karat'         => $request->karat,
                'lingkar'       => $request->lingkar ?? 0,
                'panjang'       => $request->panjang ?? 0,
                'keterangan'    => toUpper($request->keterangan),
                'kondisi_id'    => $request->kondisi,
                'status'        => 0,
            ]);

            // --- LOGIKA KERANJANG PEMBELIAN (Diambil dari Kondisi 1, dijadikan reusable) ---
            // Cari transaksi pembelian aktif (status = 1) untuk user ini
            $activePembelian = KeranjangPembelian::where('oleh', Auth::user()->id)
                ->where('status', 1)
                ->where('jenis_pembelian', 'luartoko')
                ->first();

            $kodePembelian = null;

            if (!$activePembelian) {
                // Jika belum ada transaksi aktif → buat baru
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

            // Hitung total dan terbilang
            $total = $produk->berat * $produk->harga_beli;
            $terbilang = ucwords($this->terbilang($total)) . ' Rupiah';

            // Simpan ke keranjang pembelian (SAMA UNTUK KONDISI 1 & 2)
            $keranjang = KeranjangPembelian::create([
                'kodepembelian'     => $kodePembelian,
                'produk_id'         => $produk->id,
                'kondisi_id'        => $produk->kondisi_id,
                'jenisproduk_id'    => $produk->jenisproduk_id,
                'nama'              => toUpper($produk->nama),
                'harga_beli'        => $produk->harga_beli,
                'berat'             => $produk->berat,
                'karat'             => $produk->karat,
                'lingkar'           => $produk->lingkar ?? 0,
                'panjang'           => $produk->panjang ?? 0,
                'total'             => $total,
                'terbilang'         => $terbilang,
                'keterangan'        => $produk->keterangan,
                'oleh'              => Auth::user()->id,
                'status'            => 1, // aktif di keranjang
                'jenis_pembelian'   => 'luartoko',
            ]);
            // --- END LOGIKA KERANJANG PEMBELIAN ---


            // --- Langkah B: Distribusi Aksi Berdasarkan Kondisi (Memproses Perbaikan/Pencucian) ---

            if ($request->kondisi == 1) {
                // B.1 KONDISI 1 (Baik): Masuk ke Perbaikan (Pencucian)

                $perbaikanController = new PerbaikanController();
                $kodePerbaikan = $perbaikanController->kodePerbaikan();

                $perbaikan = Perbaikan::create([
                    'kodeperbaikan' => $kodePerbaikan,
                    'produk_id'     => $produk->id,
                    'tanggalmasuk'  => Carbon::now(),
                    'kondisi_id'    => $produk->kondisi_id,
                    'keterangan'    => 'Produk masuk pencucian',
                    'status'        => 1, // aktif di perbaikan (sedang diproses)
                    'oleh'          => Auth::user()->id,
                ]);
                $message = 'Produk kondisi baik ditambahkan ke master, keranjang, dan masuk pencucian.';
            } elseif ($request->kondisi == 2) {
                // B.2 KONDISI 2 (Rusak/Lebur): Masuk ke Perbaikan (Status 2)

                $perbaikanController = new PerbaikanController();
                $kodePerbaikan = $perbaikanController->kodePerbaikan();

                $perbaikan = Perbaikan::create([
                    'kodeperbaikan' => $kodePerbaikan,
                    'produk_id'     => $produk->id,
                    'tanggalmasuk'  => Carbon::now(),
                    'kondisi_id'    => $produk->kondisi_id,
                    'keterangan'    => 'Produk di lebur', // Diubah menjadi 'Produk di lebur'
                    'status'        => 2, // Diubah menjadi status 2 (Dilebur/Selesai)
                    'oleh'          => Auth::user()->id,
                    'tanggalkeluar' => Carbon::now(), // Langsung keluar karena sudah dilebur
                ]);
                $message = 'Produk kondisi rusak berhasil ditambahkan ke master, keranjang, dan langsung dilebur (Perbaikan Status 2).';
            }

            // --- Langkah C: Response ---

            return response()->json([
                'success'   => true,
                'message'   => $message,
                'produk'    => $produk,
                'keranjang' => $keranjang,
                'pembelian' => $activePembelian,
                'perbaikan' => $perbaikan,
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
                ->where('jenis_pembelian', 'luartoko')
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

            $kondisiBaru = $request->kondisi;

            // Simpan kondisi baru dan data lainnya ke master produk
            $produk->update([
                'jenisproduk_id' => $request->jenis,
                'nama'           => toUpper($request->nama),
                'harga_beli'     => $request->hargabeli,
                'berat'          => $request->berat,
                'karat'          => $request->karat,
                'lingkar'        => $request->lingkar ?? 0,
                'panjang'        => $request->panjang ?? 0,
                'keterangan'     => toUpper($request->keterangan),
                'kondisi_id'     => $kondisiBaru, // Update kondisi produk master
            ]);

            // ----- LOGIKA PERBAIKAN DINAMIS -----

            // Cari record perbaikan yang masih aktif (status = 1) untuk produk ini
            $perbaikan = Perbaikan::where('produk_id', $produk->id)
                ->where('status', 1)
                ->first();

            // Kasus 1: Kondisi menjadi rusak/perlu perbaikan (bukan 1)
            if ($kondisiBaru != 1) {

                if ($perbaikan) {
                    // Perbaikan sudah aktif: Update jika ada perubahan kondisi
                    if ($perbaikan->kondisi_id != $kondisiBaru) {
                        $perbaikan->update([
                            'kondisi_id'  => $kondisiBaru,
                            'keterangan'  => $request->keterangan ?? 'Perubahan kondisi saat update produk',
                        ]);
                    }

                    // LOGIKA KHUSUS PELEBURAN (Kondisi 2)
                    if ($kondisiBaru == 2) {
                        $perbaikan->update([
                            'kondisi_id'    => 2,
                            'keterangan'    => 'Produk dilebur',
                            'status'        => 2, // Asumsi: Status 2 = Selesai / Dilebur (Non-Aktif)
                            'tanggalkeluar' => Carbon::now(),
                        ]);
                    }
                } else {
                    // Belum ada perbaikan aktif: Buat record perbaikan baru
                    $perbaikanController = new PerbaikanController();
                    $kodePerbaikan = $perbaikanController->kodePerbaikan();

                    $keteranganPerbaikan = ($kondisiBaru == 2) ? 'Produk dari luar toko, langsung dilebur' : ($request->keterangan ?? 'Produk dari luar toko kondisi tidak baik');
                    $statusPerbaikan = ($kondisiBaru == 2) ? 2 : 1;

                    $perbaikan = Perbaikan::create([
                        'kodeperbaikan' => $kodePerbaikan,
                        'produk_id'     => $produk->id,
                        'tanggalmasuk'  => Carbon::now(),
                        'kondisi_id'    => $kondisiBaru,
                        'keterangan'    => $keteranganPerbaikan,
                        'status'        => $statusPerbaikan,
                        'oleh'          => Auth::user()->id,
                        'tanggalkeluar' => ($statusPerbaikan == 2) ? Carbon::now() : null,
                    ]);
                }
            } else if ($kondisiBaru == 1) {
                // Kasus 2: Kondisi menjadi BAIK (1) → Masuk ke tahap Pencucian/QC (di dalam Perbaikan)

                if ($perbaikan) {
                    // Perbaikan sudah aktif: Update record yang sudah ada
                    $perbaikan->update([
                        'status'        => 1, // Diubah menjadi AKTIF
                        'kondisi_id'    => $kondisiBaru, // Diubah menjadi 1
                        'keterangan'    => 'Produk masuk pencucian',
                        'tanggalmasuk'  => Carbon::now(), // Reset tanggal masuk
                        'tanggalkeluar' => null, // Pastikan tanggal keluar null
                    ]);
                } else {
                    // Belum ada perbaikan aktif: Buat record perbaikan baru
                    $perbaikanController = new PerbaikanController();
                    $kodePerbaikan = $perbaikanController->kodePerbaikan();

                    $perbaikan = Perbaikan::create([
                        'kodeperbaikan' => $kodePerbaikan,
                        'produk_id'     => $produk->id,
                        'tanggalmasuk'  => Carbon::now(),
                        'kondisi_id'    => $kondisiBaru, // Yaitu Kondisi 1
                        'keterangan'    => 'Produk masuk pencucian',
                        'status'        => 1, // Aktif
                        'oleh'          => Auth::user()->id,
                        'tanggalkeluar' => null,
                    ]);
                }
            }
            // ----- END LOGIKA PERBAIKAN DINAMIS -----

            // Hitung ulang total & terbilang
            $total = $request->berat * $request->hargabeli;
            $terbilang = ucwords($this->terbilang($total)) . ' Rupiah';

            // Update data keranjang pembelian (disinkronkan dengan input baru)
            $keranjang->update([
                'kondisi_id'     => $request->kondisi,
                'jenisproduk_id' => $request->jenis,
                'nama'           => toUpper($request->nama),
                'harga_beli'     => $request->hargabeli,
                'berat'          => $request->berat,
                'karat'          => $request->karat,
                'lingkar'        => $request->lingkar ?? 0,
                'panjang'        => $request->panjang ?? 0,
                'total'          => $total,
                'terbilang'      => $terbilang,
                'keterangan'     => toUpper($request->keterangan),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Data keranjang pembelian berhasil diupdate, dan status perbaikan disesuaikan.',
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
