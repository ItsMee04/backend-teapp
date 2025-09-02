<?php

namespace App\Http\Controllers\Produk;

use Carbon\Carbon;
use App\Models\Nampan;
use App\Models\Produk;
use App\Models\NampanProduk;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class NampanProdukController extends Controller
{
    public function getNampanProduk($id)
    {
        $query = NampanProduk::with(['nampan', 'produk', 'user'])->where('status', 1);

        if ($id !== 'all') {
            $query->where('nampan_id', $id);
        }

        $nampanProduk = $query->get();

        // Tambahkan hargatotal ke setiap produk
        $nampanProduk->each(function ($item) {
            if ($item->produk) {
                $item->produk->hargatotal = number_format(
                    (float) $item->produk->harga_jual * (float) $item->produk->berat,
                    2,
                    '.',
                    ''
                );
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Data Nampan Produk Berhasil Ditemukan',
            'Data' => $nampanProduk
        ]);
    }

    public function getProdukByJenis($id)
    {
        // Cari semua produk dengan jenisproduk_id yang sesuai
        // dan pastikan statusnya aktif (misal, status = 1).
        $produk = Produk::with('jenisproduk')
            ->where('jenisproduk_id', $id)
            ->where('status', 1) // Asumsi 1 adalah status aktif
            ->get();

        // Periksa apakah ada produk yang ditemukan
        if ($produk->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada produk yang ditemukan untuk jenis ini.',
                'Data' => []
            ], 404);
        }

        // Kembalikan data produk dalam format JSON
        return response()->json([
            'success' => true,
            'message' => 'Data Produk Berhasil Ditemukan',
            'Data' => $produk
        ]);
    }

    public function getProdukNampan($id)
    {
        $nampan = Nampan::where('id', $id)->first();

        if (!$nampan) {
            return response()->json(['success' => false, 'message' => 'Data Nampan Tidak Ditemukan'], 404);
        }

        $produk = Produk::with('jenisproduk')->where('jenisproduk_id', $nampan->jenisproduk_id)->where('status', 1)->get();

        return response()->json([
            'success' => true,
            'message' => 'Data Nampan Produk Berhasil Ditemukan',
            'Data' => $produk
        ]);
    }

    public function storeProdukNampan(Request $request, $id)
    {
        $request->validate([
            'items' => 'required|array',
        ]);


        $nampan = Nampan::findOrFail($id);

        // ✅ PERBAIKAN: Cek produk yang masih aktif di nampan lain
        // Eager load relasi 'nampan' untuk mendapatkan nama nampan yang bermasalah
        $conflictingProducts = NampanProduk::with('nampan')
            ->where('status', 1)
            ->where('nampan_id', '!=', $id) // Cari di nampan yang berbeda
            ->whereIn('produk_id', $request->items)
            ->get();

        if ($conflictingProducts->isNotEmpty()) {
            $errorMessages = [];
            foreach ($conflictingProducts as $product) {
                $nampanName = $product->nampan ? $product->nampan->nampan : 'nampan lain';
                $errorMessages[] = "Produk masih aktif di nampan **" . $nampanName . "**.";
            }
            return response()->json(['success' => false, 'message' => implode(' ', $errorMessages)]);
        }

        $nampanProducts = [];
        foreach ($request->items as $produk_id) {
            $nampanProducts[] = NampanProduk::create([
                'nampan_id' => $id,
                'produk_id' => $produk_id,
                'jenis' => "masuk", // Sesuaikan dengan jenis yang benar
                'tanggal' => Carbon::today()->format('Y-m-d'),
                'status' => 1,
                'oleh' => Auth::user()->id,
            ]);
        }

        return response()->json(['success' => true, 'message' => 'Produk berhasil ditambahkan']);
    }

    public function pindahProduk(Request $request)
    {
        $request->validate([
            'produkId' => [
                'required',
                'integer',
                // Cek apakah produk_id ada DAN statusnya aktif
                Rule::exists('nampan_produk', 'produk_id')->where(function ($query) {
                    return $query->where('status', 1);
                }),
            ],
            'nampanAsalId' => 'required|integer|exists:nampan,id',
            'tujuanNampanId' => 'required|integer|exists:nampan,id',
        ]);

        $produkId = $request->produkId;
        $nampanAsalId = $request->nampanAsalId;
        $tujuanNampanId = $request->tujuanNampanId;

        try {
            DB::beginTransaction();

            // Cari entri produk yang aktif di nampan asal yang spesifik
            $produkDiNampanAsal = NampanProduk::where('produk_id', $produkId)
                ->where('nampan_id', $nampanAsalId)
                ->where('status', 1)
                ->first();

            if (!$produkDiNampanAsal) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'Produk tidak ditemukan di nampan asal yang spesifik.'], 404);
            }

            // Cek jika nampan asal dan tujuan sama
            if ($produkDiNampanAsal->nampan_id == $tujuanNampanId) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'Produk sudah berada di nampan tujuan.'], 422);
            }

            // Update entri produk di nampan asal (jadi pindah)
            $produkDiNampanAsal->update([
                'jenis' => 'pindah',
                'status' => 0,
            ]);

            // Buat entri baru untuk produk yang masuk ke nampan tujuan
            NampanProduk::create([
                'nampan_id' => $tujuanNampanId,
                'produk_id' => $produkId,
                'jenis' => 'masuk',
                'tanggal' => now(),
                'oleh' => auth()->user()->id,
                'status' => 1,
            ]);

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Produk berhasil dipindahkan.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Gagal memindahkan produk. Silakan coba lagi. ' . $e->getMessage()], 500);
        }
    }

    // Contoh fungsi getBeratProduk (bisa sesuaikan dengan model produkmu)
    private function getBeratProduk($produk_id)
    {
        $produk = Produk::find($produk_id);
        return $produk ? $produk->berat : 0;
    }

    public function deleteNampanProduk($id)
    {
        // Cari data produk berdasarkan ID
        $nampanProduk = NampanProduk::find($id);

        // Periksa apakah data ditemukan
        if (!$nampanProduk) {
            return response()->json(['success' => false, 'message' => 'Produk tidak ditemukan.'], 404);
        }

        // Update status menjadi 0 (soft delete manual)
        $nampanProduk->update([
            'status' => 0,
        ]);

        return response()->json(['success' => true, 'message' => 'Produk Berhasil Dihapus.']);
    }
}
