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

    public function getKeranjangOfftake()
    {
        $offtake = Offtake::with(['pelanggan', 'user', 'user.pegawai', 'detailOfftake', 'detailOfftake.produk'])
            ->where('status', 1)
            ->first();

        return response()->json([
            'status' => 'success',
            'message' => 'Data keranjang offtake berhasil diambil',
            'data' => $offtake
        ]);
    }

    public function storeKeranjangOfftake(Request $request, $id)
    {
        $request->validate([
            'pelanggan_id' => 'required|exists:pelanggan,id',
            'keterangan' => 'nullable|string',
        ]);

        $offtake = Offtake::where('status', 1)
            ->where('oleh', Auth::user()->id)
            ->first();

        if (!$offtake) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tidak ada keranjang offtake yang aktif.',
            ], 404);
        }

        $offtake->pelanggan_id = $request->pelanggan_id;
        $offtake->keterangan = $request->keterangan;
        $offtake->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Keranjang offtake berhasil diperbarui.',
            'data' => $offtake
        ]);
    }
}
