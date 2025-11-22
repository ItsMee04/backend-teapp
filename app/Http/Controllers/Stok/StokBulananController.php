<?php

namespace App\Http\Controllers\Stok;

use App\Models\StokNampan;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\StokNampanBulanan;
use Illuminate\Support\Facades\Auth;

class StokBulananController extends Controller
{
    public function getStokPeriodeBulanan()
    {
        $stokNampanBulanan = StokNampanBulanan::with(['user'])
            ->orderBy('tanggal', 'desc')
            ->get();

        if ($stokNampanBulanan->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Data periode stok tidak ditemukan'
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Data periode stok ditemukan',
            'data' => $stokNampanBulanan
        ]);
    }

    public function storeStokOpnameByPeriodeBulanan(Request $request)
    {
        $request->validate([
            'tanggal' => 'required|date|unique:stok_nampan_bulanan,tanggal'
        ]);

        $stokNampanBulanan = StokNampanBulanan::create([
            'tanggal'       => $request->tanggal,
            'tanggal_input' => now(),
            'keterangan'    => $request->keterangan || "Create Stok Opname",
            'oleh'          => Auth::user()->id,
            'status'        => 1,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Periode stok berhasil disimpan'
        ]);
    }

    public function detailSokOpnamePeriodeBulanan(Request $request)
    {
        $request->validate([
            'tanggal' => 'required|date'
        ]);

        $targetDate = $request->tanggal;

        $stokNampanPerBulan = StokNampan::with(['user'])->whereRaw("DATE_FORMAT(tanggal, '%Y-%m') = ?", [$targetDate])
            ->where('status', 'Final')
            ->get();


        if ($stokNampanPerBulan->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Data periode stok tidak ditemukan'
            ]);
        }

        return response()->json([
            'success'   => true,
            'message'   => 'Periode stok berhasil ditemukan',
            'data'      => $stokNampanPerBulan
        ]);
    }
}
