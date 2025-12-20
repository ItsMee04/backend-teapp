<?php

namespace App\Http\Controllers\Pelanggan;

use App\Http\Controllers\Controller;
use App\Models\TemplatePesan;
use Illuminate\Http\Request;

class TemplatePesanController extends Controller
{
    public function getPesan()
    {
        $data = TemplatePesan::where('status', 1)->get();

        if ($data->isEmpty()) {
            return response()->json([
                'status'    => 404,
                'success'    => false,
                'message'   => 'Data pesan tidak ditemukan',
            ]);
        }

        return response()->json([
            'success' => true,
            'message'   => 'Data pesan berhasil ditemukan',
            'data'  => $data
        ]);
    }

    public function storePesan(Request $request)
    {
        $messages = [
            'required' => ':attribute wajib diisi !!!',
        ];

        $request->validate([
            'judul' => 'required',
            'pesan' => 'required'
        ], $messages);

        $pesan = TemplatePesan::create([
            'judul'     => $request->judul,
            'pesan'     => $request->pesan,
            'status'    => 1,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Data pesan berhasil disimpan',
            'data'    => $pesan,
        ], 201);
    }

    public function updatePesan(Request $request)
    {
        $messages = [
            'required' => ':attribute wajib diisi !!!',
        ];

        $request->validate([
            'id'    => 'required',
            'judul' => 'required',
            'pesan' => 'required'
        ], $messages);

        $pesan = TemplatePesan::where('id', $request->id)->first();

        if (!$pesan) {
            return response()->json([
                'status'    => 404,
                'success'   => false,
                'message'   => "Data pesan tidak ditemukan",
            ]);
        }

        $pesan->pesan = $request->pesan;
        $pesan->save();

        return response()->json([
            'success'   => true,
            'message'   => 'Data pesan berhasil di update',
            'data'      => $pesan,
        ]);
    }

    public function deletePesan(Request $request)
    {
        $messages = [
            'required' => ':attribute wajib diisi !!!',
        ];

        $request->validate([
            'id'    => 'required',
        ], $messages);

        $pesan = TemplatePesan::where('id', $request->id)->first();

        if (!$pesan) {
            return response()->json([
                'status'    => 404,
                'success'   => false,
                'message'   => "Data pesan tidak ditemukan",
            ]);
        }

        $pesan->update([
            'status'    => 0,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Data pesan berhasil dihapus.',
            'data'    => $pesan
        ]);

    }
}
