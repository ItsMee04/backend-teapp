<?php

namespace App\Http\Controllers\Produk;

use Carbon\Carbon;
use App\Models\Nampan;
use App\Models\NampanProduk;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class NampanController extends Controller
{
    public function getNampan()
    {
        $totalProdukAll = NampanProduk::where('status', 1)->count();

        $nampan = Nampan::where('status',1)->with(['JenisProduk'])->withCount([
                'produk' => function ($query) {
                    $query->where('status', 1); // hanya hitung produk dengan status = 1
                }
            ])->get();

        return response()->json([
            'success' => true,
            'message' => 'Data Nampan Berhasil Ditemukan',
            'Total' => $totalProdukAll,
            'Data' => $nampan
        ]);
    }

    public function storeNampan(Request $request)
    {
        $messages = [
            'required' => ':attribute wajib di isi !!!',
        ];

        $credentials = $request->validate([
            'jenis'         => 'required',
            'nampan'        => 'required',
        ], $messages);

        $storeNampan = Nampan::create([
            'jenisproduk_id'  =>  $request->jenis,
            'nampan'          =>  $request->nampan,
            'tanggal'         =>  Carbon::now(),
            'status'          =>  1,
        ]);

        return response()->json(['success' => true, 'message' => 'Data Nampan Berhasil Disimpan']);
    }

    public function getNampanByID($id)
    {
        $nampan = Nampan::where('id', $id)->get();

        return response()->json(['success' => true, 'message' => 'Data Nampan Berhasil Ditemukan', 'Data' => $nampan]);
    }

    public function updateNampan(Request $request, $id)
    {
        $messages = [
            'required' => ':attribute wajib di isi !!!',
        ];

        $credentials = $request->validate([
            'jenis'         => 'required',
            'nampan'        => 'required',
        ], $messages);

        // Cari data nampan berdasarkan ID
        $nampan = Nampan::where('id', $id)->first();

        // Periksa apakah data ditemukan
        if (!$nampan) {
            return response()->json(['success' => false, 'message' => 'Nampan tidak ditemukan.'], 404);
        }

        // Update data nampan
        $nampan->update([
            'nampan'            =>  $request->nampan,
            'jenisproduk_id'    =>  $request->jenis
        ]);

        return response()->json(['success' => true, 'message' => 'Nampan Berhasil Diperbarui.']);
    }
}
