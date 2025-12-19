<?php

namespace App\Http\Controllers\Produk;

use App\Models\Produk;
use Milon\Barcode\DNS1D;
use App\Models\JenisProduk;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class ProdukController extends Controller
{
    public function generateKodeProduk()
    {
        $length = 10;
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomCode = '';

        for ($i = 0; $i < $length; $i++) {
            $randomCode .= $characters[rand(0, $charactersLength - 1)];
        }

        return $randomCode;
    }

    public function getProduk()
    {
        $produk = Produk::with(['jenisproduk', 'karat', 'jeniskarat', 'harga', 'kondisi'])
            ->withCount(['keranjang as jumlah_terjual' => function ($q) {
                $q->where('status', 2); // hanya yang benar-benar selesai
            }])
            ->where('status', 1)
            ->get();

        if ($produk->isEmpty()) {
            return response()->json([
                'status'  => 404,
                'success' => false,
                'message' => 'Data Produk Tidak Ditemukan',
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Data Produk Berhasil Ditemukan',
            'data' => $produk
        ]);
    }

    public function storeProduk(Request $request)
    {
        $messages = [
            'required' => ':attribute wajib di isi !!!',
            'integer'  => ':attribute format wajib menggunakan angka',
            'exists'   => ':attribute yang dipilih tidak valid atau tidak ditemukan !!!',
            'mimes'    => ':attribute format wajib menggunakan PNG/JPG'
        ];

        $credentials = $request->validate([
            'nama'          =>  'required',
            'berat'         =>  [
                'required',
                'regex:/^\d+\.\d{1,}$/'
            ],
            'jenis'         =>  'required|exists:jenis_produk,id',
            'karat'         =>  'required|exists:karat,id',
            'jeniskarat'    =>  'required|exists:jenis_karat,id',
            'lingkar'       =>  'nullable|integer',
            'panjang'       =>  'nullable|integer',
            'harga_jual'    =>  'integer',
            'keterangan'    =>  'nullable|string',
            'lingkar'       =>  'nullable|integer',
            'panjang'       =>  'nullable|integer',
            'imageProduk'   =>  'nullable|mimes:png,jpg',
        ], $messages);

        $kodeproduk = $this->generateKodeProduk();

        /**
         * Generate BARCODE (Code128) pakai milon/barcode
         */
        $barcodeGenerator = new DNS1D();
        $barcodeGenerator->setStorPath(storage_path('app/public/barcode/'));

        // hasil barcode berupa base64 string
        $barcodeBase64 = $barcodeGenerator->getBarcodeJPG($kodeproduk, 'C128');

        // ubah base64 ke binary PNG
        $barcodeImage = base64_decode($barcodeBase64);

        // nama file barcode
        $barcodeFileName = 'barcode/' . $kodeproduk . '.jpg';

        // simpan ke storage/app/public/barcode/
        Storage::disk('public')->put($barcodeFileName, $barcodeImage);

        $imageProduk = null;

        if ($request->file('imageProduk')) {
            $extension = $request->file('imageProduk')->getClientOriginalExtension();
            $fileName = $kodeproduk . '.' . $extension;
            $request->file('imageProduk')->storeAs('produk', $fileName);
            $imageProduk = $request['imageProduk'] = $fileName;
        }

        $data = Produk::create([
            'kodeproduk'        =>  $kodeproduk,
            'nama'              =>  toUpper($request->nama),
            'berat'             =>  $request->berat,
            'jenisproduk_id'    =>  $request->jenis,
            'karat_id'          =>  $request->karat,
            'jenis_karat_id'    =>  $request->jeniskarat,
            'lingkar'           =>  $request->lingkar ?? 0,
            'panjang'           =>  $request->panjang ?? 0,
            'harga_jual'        =>  $request->hargajual,
            'keterangan'        =>  toUpper($request->keterangan),
            'image_produk'      =>  $imageProduk,
            'status'            =>  1,
        ]);

        return response()->json(['success' => true, 'message' => 'Data Produk Berhasil Disimpan', 'Data' => $data]);
    }

    public function getProdukByID($id)
    {
        $produk = Produk::where('id', $id)->with(['jenisproduk', 'kondisi'])->get();

        return response()->json(['success' => true, 'message' => 'Data Produk Berhasil Ditemukan', 'Data' => $produk]);
    }

    public function updateProduk(Request $request)
    {
        $produk = Produk::findOrFail($request->id);

        $request->validate([
            'nama'        => 'required',
            'berat'       => 'required|numeric',
            'jenis'       => 'required|exists:jenis_produk,id',
            'karat'       => 'required|exists:karat,id',
            'jeniskarat'  => 'required|exists:jenis_karat,id',
            'hargajual'  => 'required|integer',
            'imageProduk' => 'nullable|mimes:png,jpg',
            'lingkar'     => 'nullable|integer',
            'panjang'     => 'nullable|integer',
        ], [
            'required' => ':attribute wajib diisi!',
            'exists'   => ':attribute tidak ditemukan!',
            'mimes'    => 'Format gambar harus PNG/JPG'
        ]);

        // Persiapkan data dasar untuk diupdate
        $data = [
            'nama'           => toUpper($request->nama),
            'jenisproduk_id' => $request->jenis,
            'karat_id'       => $request->karat,
            'jenis_karat_id' => $request->jeniskarat,
            'harga_jual'     => $request->hargajual, // Simpan ID Harga referensi
            'berat'          => $request->berat,
            'lingkar'        => $request->lingkar ?? 0,
            'panjang'        => $request->panjang ?? 0,
            'keterangan'     => toUpper($request->keterangan),
        ];

        // Logika upload gambar jika ada file baru
        if ($request->hasFile('imageProduk')) {
            // Hapus file lama jika ada
            if ($produk->image_produk) {
                Storage::delete('produk/' . $produk->image_produk);
            }

            $newImage = $produk->kodeproduk . '.' . $request->file('imageProduk')->extension();
            $request->file('imageProduk')->storeAs('produk', $newImage);
            $data['image_produk'] = $newImage;
        }

        $produk->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Data Produk Berhasil Diperbarui',
            'data'    => $produk
        ]);
    }

    public function deleteProduk(Request $request)
    {
        // Cari data produk berdasarkan ID
        $produk = Produk::find($request->id);

        // Periksa apakah data ditemukan
        if (!$produk) {
            return response()->json(['success' => false, 'message' => 'Produk tidak ditemukan.'], 404);
        }

        // Update status menjadi 0 (soft delete manual)
        $produk->update([
            'status' => 0,
        ]);

        return response()->json(['success' => true, 'message' => 'Produk Berhasil Dihapus.']);
    }

    public function getProdukByBarcode(Request $request)
    {
        $kodeproduk = $request->kodeproduk;

        $produk = Produk::with(['jenisProduk', 'kondisi'])->where('kodeproduk', $kodeproduk)->first();

        if (!$produk) {
            return response()->json(['success' => false, 'message' => 'Produk tidak ditemukan.']);
        }

        return response()->json(['success' => true, 'message' => 'Data Produk Berhasil Ditemukan', 'data' => $produk]);
    }

    public function getProdukBySearch(Request $request)
    {
        $keyword = $request->query('q');

        // Jika kosong langsung balikan array kosong
        if (!$keyword) {
            return response()->json([]);
        }

        $produk = Produk::select('id', 'nama', 'kodeproduk')
            ->where('nama', 'like', '%' . $keyword . '%')
            ->orWhere('kodeproduk', 'like', '%' . $keyword . '%')
            ->limit(10)
            ->get();

        return response()->json($produk);
    }
}
