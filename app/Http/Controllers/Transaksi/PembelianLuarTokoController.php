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

    public function storeProduk(Request $request)
    {
        $request->validate([
            'jenisproduk_id' => 'required|exists:jenisproduk,id',
            'nama'           => 'required|string',
            'harga_beli'     => 'required|numeric',
            'berat'          => 'required|numeric',
            'karat'          => 'nullable|string',
            'lingkar'        => 'nullable|string',
            'panjang'        => 'nullable|string',
            'keterangan'     => 'nullable|string',
            'kondisi_id'     => 'required|exists:kondisi,id',
            // ⚡ supplier_id dihapus, karena luar toko belum tentu ada supplier
        ]);

        return DB::transaction(function () use ($request) {
            // 1. Generate kode produk baru
            $kodeProduk = app(ProdukController::class)->generateKodeProduk();

            // 2. Simpan ke master produk (status = 0)
            $produk = Produk::create([
                'kodeproduk'    => $kodeProduk,
                'jenisproduk_id' => $request->jenisproduk_id,
                'nama'          => $request->nama,
                'harga_beli'    => $request->harga_beli,
                'berat'         => $request->berat,
                'karat'         => $request->karat,
                'lingkar'       => $request->lingkar,
                'panjang'       => $request->panjang,
                'keterangan'    => $request->keterangan,
                'kondisi_id'    => $request->kondisi_id,
                'status'        => 0, // produk baru dari luar toko = belum aktif
                'image_produk'  => $request->image_produk ?? null,
            ]);

            // 3. Cari transaksi pembelian aktif (status = 1) untuk user ini
            $activePembelian = KeranjangPembelian::where('oleh', Auth::user()->id)
                ->where('status', 1)
                ->where('jenis_pembelian', 'luartoko')
                ->first();

            $kodePembelian = null;

            if (!$activePembelian) {
                // kalau belum ada transaksi → buat baru
                $kodePembelian = $this->generateKodePembelian();

                $activePembelian = Pembelian::create([
                    'kodepembelian' => $kodePembelian,
                    'tanggal'       => Carbon::now(),
                    'oleh'          => Auth::user()->id,
                    'status'        => 1, // aktif
                ]);
            } else {
                $kodePembelian = $activePembelian->kodepembelian;
            }

            // 4. Simpan ke keranjang pembelian
            $keranjang = KeranjangPembelian::create([
                'kodepembelian' => $kodePembelian,
                'produk_id'     => $produk->id,
                'nama'          => $produk->nama,
                'harga_beli'    => $produk->harga_beli,
                'berat'         => $produk->berat,
                'karat'         => $produk->karat,
                'lingkar'       => $produk->lingkar,
                'panjang'       => $produk->panjang,
                'oleh'          => Auth::user()->id,
                'status'        => 1, // aktif di keranjang
                'jenis'         => 'luar_toko',
            ]);

            // 5. Jika kondisi ≠ 1 → masuk ke perbaikan
            if ($request->kondisi_id != 1) {

                $kodePerbaikan = app(PerbaikanController::class)->generateKodePerbaikan();
                Perbaikan::create([
                    'kodeperbaikan' => $kodePerbaikan,
                    'produk_id'   => $produk->id,
                    'kondisi_id'  => $produk->kondisi_id,
                    'keterangan'  => 'Produk dari luar toko kondisi tidak baik',
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
