<?php

namespace App\Http\Controllers\Transaksi;

use Exception;
use Carbon\Carbon;
use App\Models\Produk;
use App\Models\Pembelian;
use App\Models\Perbaikan;
use Illuminate\Http\Request;
use App\Models\KeranjangPembelian;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;

class PerbaikanController extends Controller
{
    public function kodePerbaikan()
    {
        $now = Carbon::now()->format('YmdHis');

        // Random 3 digit (untuk menghindari duplikasi pada timestamp yang sama)
        $random = rand(100, 999);

        // Ambil urutan terakhir jika ingin tetap menyimpan pola urutan
        $last = DB::table('perbaikan')
            ->orderBy('id', 'desc')
            ->first();

        $lastNumber = $last ? $last->id + 1 : 1;

        // Format akhir kode
        $kode = 'PBK-' . $now . '-' . $random . '-' . str_pad($lastNumber, 4, '0', STR_PAD_LEFT);

        return $kode;
    }

    public function getPerbaikan()
    {
        $perbaikan = Perbaikan::with(['produk', 'kondisi'])->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Data perbaikan berhasil ditemukan',
            'data' => $perbaikan
        ]);
    }

    public function batalPerbaikan(Request $request)
    {
        $credentials = $request->validate([
            'kodeperbaikan' => 'required|string',
            'alasan'        => 'required'
        ]);

        $kodeperbaikan = $request->kodeperbaikan;
        $alasan = (int) $request->alasan;

        try {
            Log::info('=== Batal Perbaikan Dijalankan ===', [
                'kodeperbaikan' => $kodeperbaikan,
                'alasan' => $alasan
            ]);

            $transaksi = Perbaikan::with(['produk'])
                ->where('kodeperbaikan', $kodeperbaikan)
                ->first();

            if (!$transaksi) {
                return response()->json(['success' => false, 'message' => 'Data perbaikan tidak ditemukan'], 404);
            }

            $Keranjang = KeranjangPembelian::where('produk_id', $transaksi->produk_id)->first();
            if (!$Keranjang) {
                return response()->json(['success' => false, 'message' => 'Data keranjang tidak ditemukan'], 404);
            }

            $jenis = $Keranjang->jenis_pembelian;
            $kodepembelian = $Keranjang->kodepembelian;

            if ($alasan === 1) {
                // === BATAL TRANSAKSI ===
                if ($jenis == "daritoko") {
                    Log::info('Proses batal transaksi dari toko');

                    $transaksi->update(['status' => 0]);
                    Pembelian::where('kodepembelian', $kodepembelian)->update(['status' => 0]);
                    KeranjangPembelian::where('kodepembelian', $kodepembelian)
                        ->where('status', 2)
                        ->update([
                            'status' => 0,
                            'keterangan' => "Batal Transaksi"
                        ]);
                    Perbaikan::where('kodeperbaikan', $kodeperbaikan)->update([
                        'tanggalkeluar' => Carbon::now(),
                        'keterangan'    => "Batal Transaksi",
                        'status'        => 0
                    ]);
                } else {
                    Log::info('Proses batal transaksi dari luar toko');

                    Pembelian::where('kodepembelian', $kodepembelian)->update(['status' => 0]);
                    Perbaikan::where('kodeperbaikan', $kodeperbaikan)->update([
                        'tanggalkeluar' => Carbon::now(),
                        'keterangan'    => "Batal Transaksi",
                        'status'        => 0
                    ]);
                    Produk::where('id', $Keranjang->produk_id)->update([
                        'status' => 0
                    ]);
                }
            } else {
                // === KONDISI BAGUS ===
                Log::info('Proses batal perbaikan karena kondisi bagus');

                KeranjangPembelian::where('kodepembelian', $kodepembelian)->update([
                    'kondisi_id' => 1,
                ]);
                Perbaikan::where('kodeperbaikan', $kodeperbaikan)->update([
                    'tanggalkeluar' => Carbon::now(),
                    'keterangan'    => "Batal Transaksi Kondisi Bagus",
                    'status'        => 0
                ]);
                Produk::where('id', $Keranjang->produk_id)->update([
                    'kondisi_id'    => 1,
                    'status'        => 1
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Data transaksi berhasil dibatalkan',
                'kodeperbaikan' => $kodeperbaikan,
                'alasan' => $alasan
            ]);
        } catch (Exception $e) {
            Log::error('Error saat batalPerbaikan: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat membatalkan transaksi: ' . $e->getMessage()
            ], 500);
        }
    }
}
