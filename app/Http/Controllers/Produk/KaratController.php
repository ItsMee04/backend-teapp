<?php

namespace App\Http\Controllers\Produk;

use App\Http\Controllers\Controller;
use App\Models\Karat;
use Illuminate\Http\Request;

class KaratController extends Controller
{
    public function getKarat()
    {
        $karat = Karat::where('status', 1)->get();

        return response()->json([
            'success' => true,
            'message' => 'Data karat berhasil ditemukan.',
            'data' => $karat
        ], 200);
    }

    public function storeKarat(Request $request)
    {
        $messages = [
            'required' => ':attribute wajib di isi !!!',
            'integer'  => ':attribute wajib menggunakan angka !!!',
        ];

        $credentials = $request->validate([
            'karat'   =>  'required|integer',
        ], $messages);

        $karat = Karat::create([
            'karat' => $request->karat,
            'status' => 1,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Data karat berhasil disimpan.',
            'data' => $karat
        ], 201);
    }

    public function updateKarat(Request $request)
    {
        $messages = [
            'required' => ':attribute wajib di isi !!!',
            'integer'  => ':attribute wajib menggunakan angka !!!',
        ];

        $credentials = $request->validate([
            'karat'   =>  'required|integer',
        ], $messages);

        // Cari data karat berdasarkan ID
        $karat = Karat::find($request->id);

        if (!$karat) {
            return response()->json([
                'success' => false,
                'message' => 'Data karat tidak ditemukan.'
            ], 404);
        }

        // Update data karat
        $karat->karat = $request->karat;
        $karat->save();

        return response()->json([
            'success' => true,
            'message' => 'Data karat berhasil diperbarui.',
            'data' => $karat
        ], 200);
    }

    public function deleteKarat(Request $request)
    {
        // Cari data karat berdasarkan ID
        $karat = Karat::find($request->id);

        if (!$karat) {
            return response()->json([
                'success' => false,
                'message' => 'Data karat tidak ditemukan.'
            ], 404);
        }

        // Hapus data karat (soft delete dengan mengubah status)
        $karat->status = 0;
        $karat->save();

        return response()->json([
            'success' => true,
            'message' => 'Data karat berhasil dihapus.',
        ], 200);
    }
}
