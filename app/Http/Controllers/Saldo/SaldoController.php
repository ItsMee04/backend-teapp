<?php

namespace App\Http\Controllers\Saldo;

use App\Http\Controllers\Controller;
use App\Models\Saldo;
use Illuminate\Http\Request;

class SaldoController extends Controller
{
    public function getSaldo()
    {
        $data = Saldo::where('status',1)->get();

        if($data->isEmpty())
        {
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
}
