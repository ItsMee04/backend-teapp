<?php

namespace App\Http\Controllers\Stok;

use App\Models\Nampan;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\StokNampan;
use Illuminate\Support\Facades\Auth;

class StokHarianController extends Controller
{
    public function getPeriodeStokByNampan(Request $request)
    {
        $nampan = Nampan::find($request->id);

        if (!$nampan) {
            return response()->json([
                'success' => false,
                'message' => 'Data baki tidak ditemukan'
            ]);
        }

        $periodeStok = StokNampan::with(['nampan'])
            ->where('nampan_id', $nampan->id)
            ->orderBy('tanggal', 'desc')
            ->get();

        if ($periodeStok->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Data periode stok tidak ditemukan'
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Data periode stok ditemukan',
            'data' => $periodeStok
        ]);
    }

    public function getDetailStokByPeriode(Request $request)
    {
        $stokNampan = StokNampan::with(['nampan', 'nampan.nampanProduk', 'nampan.jenisProduk', 'nampan.nampanProduk.produk'])
            ->find($request->id);

        if (!$stokNampan) {
            return response()->json([
                'success' => false,
                'message' => 'Data stok nampan tidak ditemukan'
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Data stok nampan ditemukan',
            'data' => $stokNampan
        ]);
    }

    public function storeStokOpnameByPeriode(Request $request)
    {
        $request->validate([
            'nampan_id' => 'required|exists:nampan,id',
            'tanggal' => 'required|date'
        ]);

        $stokNampan = StokNampan::create([
            'nampan_id'     => $request->nampan_id,
            'tanggal'       => $request->tanggal,
            'tanggal_input' => now(),
            'keterangan'    => "Create Stok Opname",
            'oleh'          => Auth::user()->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Stok opname berhasil disimpan',
            'data' => $stokNampan
        ]);
    }

    public function detailStokOpname(Request $request)
    {
        // Pastikan Anda memvalidasi dulu
        $request->validate([
            'nampan_id' => 'required',
            'tanggal' => 'required|date'
        ]);

        // 1. Tentukan tanggal target dari request
        $targetDate = $request->tanggal;
        $targetNampanId = $request->nampan_id;

        $stok_nampan_collection = StokNampan::query()
            // ----------------------------------------------------------------------------------
            // ✨ PENTING: Terapkan filter tanggal di dalam CLOSURE relasi 'nampan.nampanProduk'
            // ----------------------------------------------------------------------------------
            ->with([
                'nampan.jenisProduk',
                'nampan.nampanProduk' => function ($query) use ($targetDate) {
                    // Ini akan memastikan hanya produk yang tanggalnya cocok yang dimuat
                    $query->where('tanggal', $targetDate)
                        // Jangan lupa memuat relasi 'produk' di sini jika diperlukan
                        ->with('produk');
                }
            ])

            // 2. Filter data StokNampan utama (opsional, tergantung struktur Anda)
            // Filter berdasarkan ID Nampan di tabel StokNampan (jika kolomnya ada)
            ->where('nampan_id', $targetNampanId)
            // Filter berdasarkan tanggal di tabel StokNampan (jika kolomnya ada dan relevan)
            ->where('tanggal', $targetDate)

            // 3. Hapus whereHas yang sebelumnya Anda gunakan.
            // whereHas hanya untuk filtering parent, bukan untuk membatasi data children yang di-load.

            ->get();


        // Cek apakah koleksi kosong
        if ($stok_nampan_collection->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Data stok nampan tidak ditemukan',
            ]);
        }

        // Untuk API detail, lebih baik menggunakan ->first() atau ->firstOrFail()
        // Jika Anda yakin hasilnya hanya 1, gunakan ->first() dan kembalikan 'data' => $stok_nampan_collection->first()
        return response()->json([
            'success' => true,
            'message' => 'Data stok nampan ditemukan',
            'data' => $stok_nampan_collection->first() ?? $stok_nampan_collection
        ]);
    }

    public function stokNampanHarian()
    {
        $nampanList = Nampan::with(['nampanProduk', 'jenisProduk', 'nampanProduk.produk'])->get();

        if ($nampanList->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Data baki tidak ditemukan'
            ]);
        }

        // Map hasilnya ke array baru, bukan model
        $data = $nampanList->map(function ($nampan) {
            $totalProduk = $nampan->nampanProduk->where('jenis', 'masuk')
                ->unique('produk_id')
                ->count();

            $totalBerat = $nampan->nampanProduk->where('jenis', 'masuk')->unique('produk_id')->sum(function ($item) {
                return (float) ($item->produk->berat ?? 0);
            });

            // ubah jadi array dan tambahkan field baru
            $nampanArray = $nampan->toArray();
            $nampanArray['totalproduk'] = $totalProduk;
            $nampanArray['totalberat'] = number_format($totalBerat, 3, '.', '');

            return $nampanArray;
        });

        return response()->json([
            'success' => true,
            'message' => 'Data baki ditemukan',
            'data' => $data
        ]);
    }

    public function getStokHarianByNampan(Request $request)
    {
        $nampan = Nampan::with(['nampanProduk', 'jenisProduk', 'nampanProduk.produk'])->find($request->id);

        if (!$nampan) {
            return response()->json([
                'success' => false,
                'message' => 'Data baki tidak ditemukan'
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Data baki ditemukan',
            'data' => $nampan
        ]);
    }
}
