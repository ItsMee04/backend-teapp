<?php

namespace App\Http\Controllers\Pelanggan;

use App\Models\Pelanggan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class PelangganController extends Controller
{
    private function generateCodePelanggan()
    {
        // Ambil kode customer terakhir dari database
        $lastCustomer = DB::table('pelanggan')
            ->orderBy('kodepelanggan', 'desc')
            ->first();

        // Jika tidak ada customer, mulai dari 1
        $lastNumber = $lastCustomer ? (int) substr($lastCustomer->kodepelanggan, -5) : 0;

        // Tambahkan 1 pada nomor terakhir
        $newNumber = $lastNumber + 1;

        // Format kode customer baru
        $newKodeCustomer = '#C-' . str_pad($newNumber, 5, '0', STR_PAD_LEFT);

        return $newKodeCustomer;
    }

    public function getPelanggan()
    {
        $pelanggan = Pelanggan::where('status', 1)->get();

        return response()->json(['success' => true, 'message' => 'Data Pelanggan Berhasil Ditemukan', 'Data' => $pelanggan]);
    }

    public function storePelanggan(Request $request)
    {
        $messages = [
            'required'  => ':attribute wajib di isi !!!',
            'numeric'   => ':attribute format wajib menggunakan angka',
        ];

        $credentials = $request->validate([
            'nama'            => 'required',
            'alamat'          => 'required',
            'kontak'          => 'required|numeric',
            'tanggal'         => 'required',
        ], $messages);

        $generateCode = $this->generateCodePelanggan();

        $pelanggan = Pelanggan::create([
            'kodepelanggan' =>  $generateCode,
            'nama'          =>  toUpper($request->nama),
            'alamat'        =>  toUpper($request->alamat),
            'kontak'        =>  $request->kontak,
            'tanggal'       =>  $request->tanggal,
            'status'        =>  1,
        ]);

        return response()->json(['success' => true, 'message' => 'Data Pelanggan Berhasil Ditambahkan']);
    }

    public function getPelangganByID($id)
    {
        $pelanggan = Pelanggan::where('id', $id)->get();

        return response()->json(['success' => true, 'message' => 'Data Pelanggan Berhasil Ditemukan', 'Data' => $pelanggan]);
    }

    public function updatePelanggan(Request $request, $id)
    {
        $messages = [
            'required' => ':attribute wajib diisi !!!',
            'numeric'  => ':attribute harus berupa angka'
        ];

        // Ambil data pelanggan berdasarkan ID
        $pelanggan = Pelanggan::find($id);

        if (!$pelanggan) {
            return response()->json(['success' => false, 'message' => 'Pelanggan tidak ditemukan.'], 404);
        }

        // Buat aturan validasi
        $rules = [
            'nama'    => 'required',
            'alamat'  => 'required',
            'kontak'  => 'required|numeric',
            'tanggal' => 'required',
        ];

        // Jalankan validasi
        $request->validate($rules, $messages);

        // Update data pelanggan
        $pelanggan->update([
            'nama'    => toUpper($request->nama),
            'alamat'  => toUpper($request->alamat),
            'kontak'  => $request->kontak,
            'tanggal' => $request->tanggal,
        ]);

        return response()->json(['success' => true, 'message' => 'Data Pelanggan Berhasil Diperbarui']);
    }

    public function deletePelanggan(Request $request)
    {
        // Cari data pelanggan berdasarkan ID
        $pelanggan = Pelanggan::find($request->id);

        // Periksa apakah data ditemukan
        if (!$pelanggan) {
            return response()->json(['success' => false, 'message' => 'Pelanggan tidak ditemukan.'], 404);
        }

        // Update status menjadi 0 (soft delete manual)
        $pelanggan->update([
            'status' => 0,
        ]);

        return response()->json(['success' => true, 'message' => 'Pelanggan Berhasil Dihapus.']);
    }
}
