<?php

namespace App\Http\Controllers\Stok;

use App\Models\StokNampan;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class StokBulananController extends Controller
{
    public function getStokPeriodeBulanan()
    {
        $stoknampan = StokNampan::with('user')
            ->orderByRaw('YEAR(tanggal) DESC')
            ->orderByRaw('MONTH(tanggal) DESC')
            ->get()
            ->map(function ($item) {
                // Tambah format periode (YYYY-MM)
                $item->periode = date('Y-m', strtotime($item->tanggal));
                return $item;
            })
            ->groupBy('periode') // Group by bulan-tahun
            ->map(function ($group, $key) {
                return [
                    'periode' => $key,
                    'items'   => $group->values()
                ];
            })
            ->values(); // reset index biar array-nya rapi

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

    public function storeStokOpnameByPeriodeBulanan(Request $request)
    {
        $request->validate([
            'tanggal' => 'required|string', // karena dari input type="month"
        ]);

        // Ambil tahun dan bulan
        [$tahun, $bulan] = explode('-', $request->tanggal);

        // Misalnya diset ke tanggal 1 tiap bulan (opsional, bisa juga akhir bulan)
        $tanggalFix = date('Y-m-d', strtotime("$tahun-$bulan-01"));

        // Cek biar tidak duplikat di bulan yang sama
        $isExist = StokNampan::whereYear('tanggal', $tahun)
            ->whereMonth('tanggal', $bulan)
            ->exists();

        if ($isExist) {
            return response()->json([
                'success' => false,
                'message' => 'Stok opname untuk bulan ini sudah ada!'
            ], 422);
        }

        $stokNampan = StokNampan::create([
            'tanggal'       => $tanggalFix,
            'tanggal_input' => now(),
            'keterangan'    => "Create Stok Opname Bulanan",
            'oleh'          => Auth::user()->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Stok opname bulanan berhasil disimpan',
            'data' => $stokNampan
        ]);
    }
}
