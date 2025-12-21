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
        $data = Saldo::where('status', 1)->get();

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

        $data = Saldo::create([
            'rekening'  => toUpper($request->rekening),
            'saldo'     => $request->saldo,
            'oleh'      => Auth::user()->id,
            'status'    => 1,
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
            'rekening'  => 'required',
            'saldo'     => 'nullable|integer',
        ]);

        $data = Saldo::where('id', $request->id)->first();

        if (!$data) {
            return response()->json([
                'status'    => 404,
                'success'   => false,
                'message'   => "Rekening tidak ditemukan"
            ]);
        }

        $data->rekening = $request->rekening;
        $data->total    = $request->saldo ?? 0;
        $data->save();

        return response()->json([
            'success'   => true,
            'message'   => "Data berhasil di update",
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
