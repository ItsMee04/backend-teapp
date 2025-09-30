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
        $produk = Produk::where('status', '!=', 0)->with(['jenisproduk', 'kondisi'])->get();

        return response()->json(['success' => true, 'message' => 'Data Produk Berhasil Ditemukan', 'Data' => $produk]);
    }

    public function storeProduk(Request $request)
    {
        $messages = [
            'required' => ':attribute wajib di isi !!!',
            'integer'  => ':attribute format wajib menggunakan angka',
            'mimes'    => ':attribute format wajib menggunakan PNG/JPG'
        ];

        $credentials = $request->validate([
            'nama'          =>  'required',
            'jenis'         =>  'required|' . Rule::in(JenisProduk::where('status', 1)->pluck('id')),
            'harga_jual'    =>  'integer',
            'harga_beli'    =>  'integer',
            'keterangan'    =>  'nullable|string',
            'berat'         =>  [
                'required',
                'regex:/^\d+\.\d{1,}$/'
            ],
            'kondisi'       =>  'required',
            'karat'         =>  'required|integer',
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
            'jenisproduk_id'    =>  $request->jenis,
            'kondisi_id'        =>  $request->kondisi,
            'berat'             =>  $request->berat,
            'karat'             =>  $request->karat,
            'lingkar'           =>  $request->lingkar??0,
            'panjang'           =>  $request->panjang??0,
            'harga_jual'        =>  $request->hargajual,
            'harga_beli'        =>  $request->hargabeli,
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

    public function updateProduk(Request $request, $id)
    {
        $produk = Produk::where('id', $id)->first();

        $messages = [
            'required' => ':attribute wajib di isi !!!',
            'integer'  => ':attribute format wajib menggunakan angka',
            'mimes'    => ':attribute format wajib menggunakan PNG/JPG'
        ];

        $credentials = $request->validate([
            'nama'          =>  'required',
            'jenis'         =>  'required|' . Rule::in(JenisProduk::where('status', 1)->pluck('id')),
            'harga_jual'    =>  'integer',
            'harga_beli'    =>  'integer',
            'keterangan'    =>  'string',
            'berat'         =>  [
                'required',
                'regex:/^\d+\.\d{1,}$/'
            ],
            'kondisi'       =>  'required',
            'karat'         =>  'required|integer',
            'lingkar'       =>  'integer',
            'panjang'       =>  'integer',
            'imageProduk'   =>  'nullable|mimes:png,jpg',
        ], $messages);

        if ($request->file('imageProduk')) {
            $pathavatar     = 'storage/produk/' . $produk->image_produk;

            if (File::exists($pathavatar)) {
                File::delete($pathavatar);
            }

            $extension = $request->file('imageProduk')->getClientOriginalExtension();
            $newImage = $produk->kodeproduk . '.' . $extension;
            $request->file('imageProduk')->storeAs('produk', $newImage);
            $request['imageProduk'] = $newImage;

            $updateProduk = Produk::where('id', $id)
                ->update([
                    'nama'              =>  toUpper($request->nama),
                    'jenisproduk_id'    =>  $request->jenis,
                    'kondisi_id'        =>  $request->kondisi,
                    'berat'             =>  $request->berat,
                    'karat'             =>  $request->karat,
                    'lingkar'           =>  $request->lingkar??0,
                    'panjang'           =>  $request->panjang??0,
                    'harga_jual'        =>  $request->hargajual,
                    'harga_beli'        =>  $request->hargabeli,
                    'keterangan'        =>  toUpper($request->keterangan),
                    'image_produk'      =>  $newImage,
                ]);
        } else {
            $updateProduk = Produk::where('id', $id)
                ->update([
                    'nama'              =>  toUpper($request->nama),
                    'jenisproduk_id'    =>  $request->jenis,
                    'kondisi_id'        =>  $request->kondisi,
                    'berat'             =>  $request->berat,
                    'karat'             =>  $request->karat,
                    'lingkar'           =>  $request->lingkar,
                    'panjang'           =>  $request->panjang,
                    'harga_jual'        =>  $request->hargajual,
                    'harga_beli'        =>  $request->hargabeli,
                    'keterangan'        =>  toUpper($request->keterangan),
                ]);
        }

        return response()->json(['success' => true, 'message' => 'Data Produk Berhasil Disimpan', 'Data' => $produk]);
    }

    public function deleteProduk($id)
    {
        // Cari data produk berdasarkan ID
        $produk = Produk::find($id);

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

        $produk = Produk::where('kodeproduk', $kodeproduk)->first();

        if (!$produk) {
            return response()->json(['success' => false, 'message' => 'Produk tidak ditemukan.'], 404);
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
