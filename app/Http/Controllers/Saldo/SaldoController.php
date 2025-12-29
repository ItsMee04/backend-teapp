<?php

namespace App\Http\Controllers\Saldo;

use App\Http\Controllers\Controller;
use App\Models\Saldo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SaldoController extends Controller
{
    public function getSaldo()
    {
        $data = Saldo::all();

        if ($data->isEmpty()) {
            return response()->json([
                'status'    => 404,
                'success'   => false,
                'message'   => 'Data saldo tidak ditemukan'
            ]);
        }

        return response()->json([
            'success'   => true,
            'message'   => 'Data saldo berhasil ditemukan',
            'data'      => $data,
        ]);
    }

    public function storeSaldo(Request $request)
    {
        $message = [
            'required'  => ':attribute wajib di isi',
            'integer'   => ':attribute wajib menggunakan angka'
        ];

        $request->validate([
            'rekening'  => 'required',
            'saldo'     => 'nullable|integer',
        ]);

        // 1. Cek apakah sudah ada rekening ini dengan status aktif (1)
        $isRekeningAktifExist = Saldo::where('status', 1)
            ->exists();

        // 2. Tentukan status berdasarkan hasil pengecekan
        // Jika ada yang aktif maka status = 0 (tidak aktif), jika tidak ada maka status = 1 (aktif)
        $status = $isRekeningAktifExist ? 0 : 1;

        $data = Saldo::create([
            'rekening'  => toUpper($request->rekening),
            'saldo'     => $request->saldo,
            'oleh'      => Auth::user()->id,
            'status'    => $status,
        ]);

        return response()->json([
            'success'   => true,
            'message'   => 'Data berhasil di simpan',
            'data'      => $data
        ]);
    }

    public function updateSaldo(Request $request)
    {
        $message = [
            'required'  => ':attribute wajib di isi',
            'integer'   => ':attribute wajib menggunakan angka'
        ];

        $request->validate([
            'id'        => 'required',
            'rekening'  => 'required',
            'saldo'     => 'nullable|integer',
            'status'    => 'required|in:0,1' // Tambahkan validasi status jika dikirim dari frontend
        ], $message);

        $data = Saldo::where('id', $request->id)->first();

        if (!$data) {
            return response()->json([
                'status'    => 404,
                'success'   => false,
                'message'   => "Rekening tidak ditemukan"
            ]);
        }

        // LOGIKA UTAMA: Jika user ingin mengubah status rekening ini menjadi AKTIF (1)
        if ($request->status == 1) {
            // Matikan semua rekening lain (status 0) yang memiliki nama rekening yang sama (atau seluruh tabel jika global)
            Saldo::where('id', '!=', $request->id) // Kecuali data yang sedang diupdate
                ->where('status', 1)
                ->update(['status' => 0]);
        }

        // Update data saat ini
        $data->rekening = toUpper($request->rekening);
        $data->total    = $request->saldo ?? 0;
        $data->status   = $request->status;
        $data->save();

        return response()->json([
            'success'   => true,
            'message'   => "Data berhasil diupdate. Hanya satu rekening yang aktif saat ini.",
            'data'      => $data
        ]);
    }

    public function deleteSaldo(Request $request)
    {
        $message = [
            'required'  => ':attribute wajib di isi',
            'integer'   => ':attribute wajib menggunakan angka'
        ];

        $request->validate([
            'id'  => 'required',
        ]);

        $data = Saldo::where('id', $request->id)->first();

        if (!$data) {
            return response()->json([
                'status'    => 404,
                'success'   => false,
                'message'   => "Rekening tidak ditemukan"
            ]);
        }

        $data->update([
            'status'    => 0,
        ]);

        return response()->json([
            'success'   => true,
            'message'   => 'Rekening barhasil dihapus'
        ]);
    }
}
