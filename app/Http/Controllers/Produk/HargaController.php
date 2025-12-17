<?php

namespace App\Http\Controllers\Produk;

use App\Http\Controllers\Controller;
use App\Models\Harga;
use Illuminate\Http\Request;

class HargaController extends Controller
{
    public function getHarga()
    {
        $data = Harga::where('status', 1)->get();

        // if ($data->isEmpty()) {
        //     return response()->json([
        //         'success'    => false,
        //         'message'   => 'Data harga tidak ditemukan',
        //     ], 404);
        // }

        return response()->json([
            'success'    => true,
            'message'   => 'Data harga berhasil ditemukan',
            'data'      => $data,
        ]);
    }

    public function storeHarga(Request $request)
    {
        $messages = [
            'required' => ':attribute wajib di isi !!!',
            'integer'  => ':attribute wajib menggunakan angka !!!',
        ];

        $credentials = $request->validate([
            'karat'   =>  'required|integer',
            'jenis'    =>  'required|string|max:100',
            'harga'    =>  'required|integer'
        ], $messages);

        $harga = Harga::create([
            'karat'     => $request->karat,
            'jenis'     => toUpper($request->jenis),
            'harga'     => $request->harga,
            'status'    => 1,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Data harga berhasil disimpan',
            'data'    => $harga,
        ], 201);
    }

    public function updateHarga(Request $request)
    {
        // Validasi input
        $messages = [
            'required' => ':attribute wajib di isi !!!',
            'integer'  => ':attribute wajib menggunakan angka !!!',
        ];

        $credentials = $request->validate([
            'karat'   =>  'required|integer',
            'jenis'    =>  'required|string|max:100',
            'harga'    =>  'required|integer'
        ], $messages);

        // Cari data harga berdasarkan ID
        $harga = Harga::where('id', $request->id)->first();

        // Periksa apakah data ditemukan
        if (!$harga) {
            return response()->json(['success' => false, 'message' => 'Harga tidak ditemukan.'], 404);
        }

        // Update data harga
        $harga->karat = $request->karat;
        $harga->jenis = toUpper($request->jenis);
        $harga->harga = $request->harga;
        $harga->save();

        return response()->json(['success' => true, 'message' => 'Data Harga Berhasil Diupdate', 'data' => $harga]);
    }

    public function deleteHarga(Request $request)
    {
        // Cari data kondisi berdasarkan ID
        $harga = Harga::find($request->id);

        // Periksa apakah data ditemukan
        if (!$harga) {
            return response()->json(['success' => false, 'message' => 'Kondisi tidak ditemukan.'], 404);
        }

        // Update status menjadi 0 (soft delete manual)
        $harga->update([
            'status' => 0,
        ]);

        return response()->json(['success' => true, 'message' => 'Harga Berhasil Dihapus.']);
    }
}
