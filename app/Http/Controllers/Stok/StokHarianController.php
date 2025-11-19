<?php

namespace App\Http\Controllers\Stok;

use App\Models\Nampan;
use App\Models\StokNampan;
use App\Models\NampanProduk;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class StokHarianController extends Controller
{
    public function getPeriodeStok()
    {
        $stoknampan = StokNampan::with(['user'])
            ->orderBy('tanggal', 'desc')
            ->get();

        if ($stoknampan->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Data periode stok tidak ditemukan'
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Data periode stok ditemukan',
            'data' => $stoknampan
        ]);
    }

    public function storeStokOpnameByPeriode(Request $request)
    {
        $request->validate([
            'tanggal' => 'required|date|unique:stok_nampan,tanggal'
        ]);

        $stokNampan = StokNampan::create([
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
        // ... (Validasi tanggal sama)
        $request->validate([
            'tanggal' => 'required|date_format:Y-m-d'
        ]);

        $targetDate = $request->tanggal;

        // ==========================================================
        // A. QUERY UNTUK STOK AWAL (TOTAL SEMUA MASUK, TANPA TANGGAL)
        // ... (Logika ini tetap sama)
        // ==========================================================

        $stokAwalRaw = NampanProduk::query()
            ->where('jenis', 'masuk')
            ->where('nampan_produk.status', '!=', 0) // <-- DITAMBAHKAN
            ->join('produk', 'nampan_produk.produk_id', '=', 'produk.id')
            ->selectRaw('
            produk.jenisproduk_id,
            SUM(produk.berat) as total_berat,
            COUNT(nampan_produk.id) as total_potong
        ')
            ->groupBy('produk.jenisproduk_id')
            ->get();

        $stokAwal = [];
        foreach ($stokAwalRaw as $item) {
            $stokAwal[$item->jenisproduk_id] = [
                'total_berat' => $item->total_berat,
                'total_potong' => $item->total_potong,
            ];
        }

        // ==========================================================
        // B. QUERY UNTUK PERGERAKAN HARI INI (DIFILTER TANGGAL)
        //    ✨ Dikelompokkan berdasarkan jenis (masuk/keluar)
        // ==========================================================

        $pergerakanHarianRaw = NampanProduk::query()
            ->where('tanggal', $targetDate)
            ->where('nampan_produk.status', '!=', 0) // <-- DITAMBAHKAN
            ->join('produk', 'nampan_produk.produk_id', '=', 'produk.id')
            ->selectRaw('
            nampan_produk.jenis,
            produk.jenisproduk_id,
            SUM(produk.berat) as total_berat,
            COUNT(nampan_produk.id) as total_potong
        ')
            ->groupBy('nampan_produk.jenis', 'produk.jenisproduk_id')
            ->get();

        // Mengubah koleksi menjadi associative array
        // Struktur: ['masuk' => [jenis_id => {data}], 'keluar' => [jenis_id => {data}]]
        $pergerakanHarian = [
            'masuk' => [],
            'keluar' => [],
        ];

        foreach ($pergerakanHarianRaw as $item) {
            $pergerakanHarian[$item->jenis][$item->jenisproduk_id] = [
                'total_berat' => $item->total_berat,
                'total_potong' => $item->total_potong,
            ];
        }

        // 3. Mengembalikan ketiga data dalam satu response
        return response()->json([
            'success' => true,
            'message' => 'Detail pergerakan dan stok awal ditemukan',
            'data' => [
                'stok_awal_summary' => $stokAwal,
                'pergerakan_harian' => $pergerakanHarian, // Data baru yang sudah dipisahkan
            ]
        ]);
    }

    public function finalStokOpname(Request $request)
    {
        $request->validate([
            'periode' => 'required',
        ]);

        $stokNampan = StokNampan::where('id', $request->periode)->first();

        if (!$stokNampan) {
            return response()->json([
                'success' => false,
                'message' => 'Data periode stok tidak ditemukan'
            ]);
        }

        $stokNampan->update([
            'keterangan'    => "Final Stok Opname",
            'oleh'          => Auth::user()->id,
            'status'        => 'Final'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Stok opname berhasil difinal',
            'data' => $stokNampan
        ]);
    }

    public function cancelStokOpname(Request $request)
    {
        $request->validate([
            'periode' => 'required',
        ]);

        $stokNampan = StokNampan::where('id', $request->periode)->first();

        if (!$stokNampan) {
            return response()->json([
                'success' => false,
                'message' => 'Data periode stok tidak ditemukan'
            ]);
        }

        $stokNampan->update([
            'keterangan'    => "Batal Stok Opname",
            'oleh'          => Auth::user()->id,
            'status'        => 'Batal'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Stok opname berhasil dibatalkan',
            'data' => $stokNampan
        ]);
    }
}
