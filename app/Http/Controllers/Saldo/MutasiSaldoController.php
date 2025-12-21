<?php

namespace App\Http\Controllers\Saldo;

use App\Http\Controllers\Controller;
use App\Models\MutasiSaldo;
use App\Models\Saldo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MutasiSaldoController extends Controller
{
    public function getMutasiSaldo()
    {
        $data = MutasiSaldo::where('status', 1)->get();

        if ($data->isEmpty()) {
            return response()->json([
                'status'    => 404,
                'success'   => false,
                'message'   => 'Data mutasi saldo tidak ditemukan'
            ]);
        }

        return response()->json([
            'success'   => true,
            'message'   => 'Data mutasi saldo berhasil ditemukan',
            'data'      => $data,
        ]);
    }

    public function storeMutasiSaldo(Request $request)
    {
        $messages = [
            'required' => ':attribute wajib diisi !!!',
            'integer'  => ':attribute wajib menggunakan angka !!!',
        ];

        $request->validate([
            'tanggal'   => 'required|date',
            'keterangan' => 'required',
            'jenis'     => 'required',
            'jumlah'    => 'required|integer'
        ], $messages);

        $rekening = Saldo::where('id', $request->rekening)->first();

        if (!$rekening) {
            return response()->json([
                'status'    => 404,
                'success'   => false,
                'message'   => "Data rekening tidak ditemukan"
            ]);
        }

        $data = MutasiSaldo::create([
            'saldo_id'      => $request->rekening,
            'tanggal'       => $request->tanggal,
            'keterangan'    => $request->keterangan,
            'jenis'         => $request->jenis,
            'jumlah'        => $request->jumlah,
            'oleh'          => Auth::user()->id,
        ]);

        if ($data) {
            if ($request->jenis === "masuk") {
                $rekening->total = $rekening->total + $request->jumlah;
                $rekening->save();
            } elseif ($request->jenis === "keluar") {
                $rekening->total = $rekening->total - $request->jumlah;
                $rekening->save();
            }
        }

        return response()->json([
            'success'   => true,
            'message'   => 'Data mutasi saldo berhasil di simpan',
            'data'      => $data,
        ]);
    }

    public function updateMutasiSaldo(Request $request)
    {
        $messages = [
            'required' => ':attribute wajib diisi !!!',
            'integer'  => ':attribute wajib menggunakan angka !!!',
        ];

        $request->validate([
            'id'        => 'required', // Validasi ID harus ada
            'tanggal'   => 'required|date',
            'keterangan' => 'required',
            'jenis'     => 'required',
            'jumlah'    => 'required|integer'
        ], $messages);

        // 1. Cari data mutasi lama berdasarkan request id
        $mutasi = MutasiSaldo::find($request->id);

        if (!$mutasi) {
            return response()->json([
                'status'    => 404,
                'success'   => false,
                'message'   => "Data mutasi tidak ditemukan"
            ]);
        }

        // 2. Ambil data rekening (Saldo) terkait
        $rekening = Saldo::where('id', $mutasi->saldo_id)->first();

        if (!$rekening) {
            return response()->json([
                'status'    => 404,
                'success'   => false,
                'message'   => "Data rekening tidak ditemukan"
            ]);
        }

        // 3. REVERT: Kembalikan saldo ke kondisi awal (sebelum mutasi lama dihitung)
        if ($mutasi->jenis === "masuk") {
            $rekening->total = $rekening->total - $mutasi->jumlah;
        } elseif ($mutasi->jenis === "keluar") {
            $rekening->total = $rekening->total + $mutasi->jumlah;
        }

        // 4. Update data Mutasi dengan data baru dari request
        $mutasi->tanggal    = $request->tanggal;
        $mutasi->keterangan = $request->keterangan;
        $mutasi->jenis      = $request->jenis;
        $mutasi->jumlah     = $request->jumlah;
        $mutasi->oleh       = Auth::user()->id;
        $mutasi->save();

        // 5. APPLY: Hitung ulang saldo berdasarkan jenis mutasi yang baru diupdate
        if ($request->jenis === "masuk") {
            $rekening->total = $rekening->total + $request->jumlah;
        } elseif ($request->jenis === "keluar") {
            $rekening->total = $rekening->total - $request->jumlah;
        }

        // Simpan perubahan total saldo di tabel Rekening/Saldo
        $rekening->save();

        return response()->json([
            'success'   => true,
            'message'   => 'Data mutasi saldo berhasil diperbarui',
            'data'      => $mutasi,
        ]);
    }

    public function cancelMutasiSaldo(Request $request)
    {
        $message = [
            'required'  => ':attribute wajib di isi',
            'integer'   => ':attribute wajib menggunakan angka'
        ];

        $request->validate([
            'id'  => 'required',
        ]);

        // 1. Cari data mutasi yang aktif (status 1)
        $mutasi = MutasiSaldo::where('id', $request->id)->where('status', 1)->first();

        if (!$mutasi) {
            return response()->json([
                'success'   => false,
                'message'   => "Data mutasi tidak ditemukan atau sudah dibatalkan"
            ], 404);
        }

        // 2. Ambil data rekening terkait
        $rekening = Saldo::where('id', $mutasi->saldo_id)->first();

        if ($rekening) {
            // 3. REVERT: Balikkan saldo karena transaksi dibatalkan
            if ($mutasi->jenis === "masuk") {
                $rekening->total -= $mutasi->jumlah;
            } elseif ($mutasi->jenis === "keluar") {
                $rekening->total += $mutasi->jumlah;
            }
            $rekening->save();
        }

        // 4. Ubah status menjadi 0 alih-alih delete
        $mutasi->status = 0;
        $mutasi->save();

        return response()->json([
            'success'   => true,
            'message'   => 'Data mutasi berhasil dibatalkan (Masuk History)',
        ]);
    }
}
