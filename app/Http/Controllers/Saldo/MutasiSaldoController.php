<?php

namespace App\Http\Controllers\Saldo;

use App\Http\Controllers\Controller;
use App\Models\MutasiSaldo;
use Illuminate\Http\Request;

class MutasiSaldoController extends Controller
{
    public function getMutasiSaldo()
    {
        $data = MutasiSaldo::where('status',1 )->get();

        if($data->isEmpty()){
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
            'keterangan'=> 'required',
            'jenis'     => 'required',
            'jumlah'    => 'required|integer'
        ], $messages);

        $data = MutasiSaldo::create([
            'tanggal'       => $request->tanggal,
            'keterangan'    => $request->keterangan,
            'jenis'         => $request->jenis,
            'jumlah'        => $request->jumlah
        ]);

        return response()->json([
            'success'   => true,
            'message'   => 'Data mutasi saldo berhasil di simpan',
            'data'      => $data,
        ]);
    }
}
