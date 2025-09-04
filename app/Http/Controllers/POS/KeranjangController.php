<?php

namespace App\Http\Controllers\POS;

use Carbon\Carbon;
use App\Models\Keranjang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class KeranjangController extends Controller
{
    private function generateKodeKeranjang()
    {
        // Cek apakah ada keranjang aktif (status = 1)
        $lastActive = DB::table('keranjang')
            ->where('status', 1)
            ->orderBy('kodekeranjang', 'desc')
            ->first();

        if ($lastActive) {
            return $lastActive->kodekeranjang;
        }

        // Timestamp sekarang
        $now = Carbon::now()->format('YmdHis');

        // Random 3 digit untuk menghindari duplikasi
        $random = rand(100, 999);

        // Ambil nomor urut terakhir
        $last = DB::table('keranjang')
            ->orderBy('id', 'desc')
            ->first();

        $lastNumber = $last ? $last->id + 1 : 1;

        // Format nomor urut 4 digit
        $formattedNumber = str_pad($lastNumber, 4, '0', STR_PAD_LEFT);

        // Gabungkan semua bagian
        $kode = 'KR-' . $now . '-' . $random . '-' . $formattedNumber;

        return $kode;
    }

    public function getKodeKeranjang()
    {
        // Ambil data keranjang pertama dengan status 1 dan user_id pengguna yang sedang login
        $keranjang = Keranjang::where('status', 1)
            ->where('oleh', Auth::user()->id)
            ->first();

        // Cek apakah keranjang ditemukan
        if ($keranjang) {
            // Ambil kode keranjang
            $kodeKeranjang = $keranjang->kodekeranjang;

            // Kembalikan response JSON dengan kode keranjang dan produk ID
            return response()->json(['success' => true, 'kode' => $kodeKeranjang]);
        } else {
            // Jika keranjang tidak ditemukan
            return response()->json([
                'success' => false,
                'message' => 'Belum ada barang dalam keranjang'
            ]);
        }
    }

    public function getKeranjang()
    {
        $keranjang = Keranjang::where('status', 1)
            ->where('oleh', Auth::user()->id)
            ->with(['produk', 'user'])
            ->get();

        $count = $keranjang->count();

        $totalKeranjang = Keranjang::where('status', 1)
            ->where('oleh', Auth::id())
            ->sum('total');

        return response()->json(['success' => true, 'message' => 'Data Keranjang Berhasil Ditemukan', 'Data' => $keranjang, 'TotalKeranjang' => $count, 'TotalHargaKeranjang' => $totalKeranjang]);
    }
}
