<?php

namespace App\Http\Controllers\Transaksi;

use Carbon\Carbon;
use App\Models\Offtake;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class KeranjangOfftakeController extends Controller
{
    /**
     * Menghasilkan kode transaksi baru.
     */
    private function generateKodeTransaksi()
    {
        $last = DB::table('offtake')
            ->orderBy('id', 'desc')
            ->first();

        $lastNumber = 0;
        if ($last) {
            $lastKode = $last->kodetransaksi;
            $lastNumber = (int) substr($lastKode, -4);
        }

        $newNumber = $lastNumber + 1;
        $formattedNumber = str_pad($newNumber, 4, '0', STR_PAD_LEFT);

        $now = Carbon::now()->format('YmdHis');
        $random = rand(100, 999);

        return 'OFT-' . $now . '-' . $random . '-' . $formattedNumber;
    }

    /**
     * Mengambil atau membuat kode transaksi yang sedang aktif untuk user.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getKodeTransaksi()
    {
        try {
            // Cari transaksi aktif untuk user yang login
            $activeTransaksi = Offtake::where('oleh', Auth::user()->id)
                ->where('status', 1)
                ->first();

            // Jika transaksi aktif ditemukan, kembalikan kode-nya
            if ($activeTransaksi) {
                return response()->json([
                    'success' => true,
                    'kode' => $activeTransaksi->kodetransaksi,
                    'message' => 'Kode transaksi aktif ditemukan.'
                ]);
            }

            // Jika tidak ada transaksi aktif, buat transaksi baru
            $newKode = $this->generateKodeTransaksi();

            Offtake::create([
                'kodetransaksi' => $newKode,
                'oleh' => Auth::user()->id,
                'status' => 1, // Menandakan transaksi masih berjalan
            ]);

            return response()->json([
                'success' => true,
                'kode' => $newKode,
                'message' => 'Transaksi baru berhasil dibuat.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil kode transaksi: ' . $e->getMessage(),
            ], 500);
        }
    }
}
