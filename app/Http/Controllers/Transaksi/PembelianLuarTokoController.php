<?php

namespace App\Http\Controllers\Transaksi;

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
                'nama'          => $request->nama,
                'harga_beli'    => $request->hargabeli,
                'berat'         => $request->berat,
                'karat'         => $request->karat,
                'lingkar'       => $request->lingkar,
                'panjang'       => $request->panjang,
                'keterangan'    => $request->keterangan,
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
                'nama'              => $produk->nama,
                'harga_beli'        => $produk->harga_beli,
                'berat'             => $produk->berat,
                'karat'             => $produk->karat,
                'lingkar'           => $produk->lingkar,
                'panjang'           => $produk->panjang,
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
}
