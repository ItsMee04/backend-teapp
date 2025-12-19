<?php

namespace App\Http\Controllers\Produk;

use App\Http\Controllers\Controller;
use App\Models\JenisKarat;
use Illuminate\Http\Request;

class JenisKaratController extends Controller
{
    public function getJenisKarat()
    {
        $jeniskarat = JenisKarat::where('status',1)->with(['karat'])->get();

        if($jeniskarat->isEmpty()){
            return response()->json([
                'status'  => 404,
                'success' => false,
                'message' => 'Data Jenis Karat Tidak Ditemukan'
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Data Jenis Karat Ditemukan',
            'data' => $jeniskarat
        ], 200);
    }

    public function storeJenisKarat(Request $request)
    {
        $messages = [
            'required' => ':attribute wajib di isi !!!',
            'integer'  => ':attribute wajib menggunakan angka !!!',
        ];

        $credentials = $request->validate([
            'karat'   =>  'required|integer',
            'jenis'   =>  'required|string',
        ], $messages);

        $jeniskarat = JenisKarat::create([
            'karat_id'  => $request->karat,
            'jenis'     => toUpper($request->jenis),
            'status' => 1,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Jenis Karat Berhasil Ditambahkan',
            'data' => $jeniskarat
        ], 201);
    }

    public function updateJenisKarat(Request $request)
    {
        $messages = [
            'required' => ':attribute wajib di isi !!!',
            'integer'  => ':attribute wajib menggunakan angka !!!',
        ];

        $credentials = $request->validate([
            'id'      =>  'required|integer',
            'karat'   =>  'required|integer',
            'jenis'   =>  'required|string',
        ], $messages);

        $jeniskarat = JenisKarat::find($request->id);
        if(!$jeniskarat){
            return response()->json([
                'success' => false,
                'message' => 'Data Jenis Karat Tidak Ditemukan'
            ], 404);
        }

        $jeniskarat->karat_id = $request->karat;
        $jeniskarat->jenis = toUpper($request->jenis);
        $jeniskarat->save();

        return response()->json([
            'success' => true,
            'message' => 'Jenis Karat Berhasil Diupdate',
            'data' => $jeniskarat
        ], 200);
    }

    public function deleteJenisKarat(Request $request)
    {
        $messages = [
            'required' => ':attribute wajib di isi !!!',
            'integer'  => ':attribute wajib menggunakan angka !!!',
        ];

        $credentials = $request->validate([
            'id'      =>  'required|integer',
        ], $messages);

        $jeniskarat = JenisKarat::find($request->id);
        if(!$jeniskarat){
            return response()->json([
                'success' => false,
                'message' => 'Data Jenis Karat Tidak Ditemukan'
            ], 404);
        }

        $jeniskarat->status = 0;
        $jeniskarat->save();

        return response()->json([
            'success' => true,
            'message' => 'Jenis Karat Berhasil Dihapus',
            'data' => $jeniskarat
        ], 200);
    }

    public function getJenisKaratByKarat(Request $request)
    {
        $jeniskarat = JenisKarat::where('karat_id', $request->karat)->where('status',1)->get();

        if($jeniskarat->isEmpty()){
            return response()->json([
                'status'  => 404,
                'success' => false,
                'message' => 'Data Jenis Karat Tidak Ditemukan'
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Data Jenis Karat Ditemukan',
            'data' => $jeniskarat
        ], 200);
    }
}
