<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\POS\KeranjangController;
use App\Http\Controllers\Produk\DiskonController;
use App\Http\Controllers\Produk\NampanController;
use App\Http\Controllers\Produk\ProdukController;
use App\Http\Controllers\Cetak\CetakBarcodeProduk;
use App\Http\Controllers\Produk\KondisiController;
use App\Http\Controllers\Suplier\SuplierController;
use App\Http\Controllers\Produk\JenisProdukController;
use App\Http\Controllers\Authentication\AuthController;
use App\Http\Controllers\Pelanggan\PelangganController;
use App\Http\Controllers\Produk\NampanProdukController;
use App\Http\Controllers\Transaksi\PembelianController;
use App\Http\Controllers\Transaksi\PerbaikanController;
use App\Http\Controllers\Transaksi\TransaksiController;
use App\Http\Controllers\UserManagement\RoleController;
use App\Http\Controllers\UserManagement\UserController;
use App\Http\Controllers\UserManagement\JabatanController;
use App\Http\Controllers\UserManagement\PegawaiController;
use App\Http\Controllers\Transaksi\PembelianTokoController;
use App\Http\Controllers\Transaksi\KeranjangOfftakeController;
use App\Http\Controllers\Transaksi\OfftakeController;
use App\Http\Controllers\Transaksi\PembelianLuarTokoController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/


Route::middleware(['guest'])->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
});

Route::middleware(['auth:sanctum'])->group(function () {

    Route::get('me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('jabatan/getJabatan', [JabatanController::class, 'getJabatan']);
    Route::post('jabatan/storeJabatan', [JabatanController::class, 'storeJabatan']);
    Route::get('jabatan/getJabatanByID/{id}', [JabatanController::class, 'getJabatanByID']);
    Route::put('jabatan/updateJabatan/{id}', [JabatanController::class, 'updateJabatan']);
    Route::delete('jabatan/deleteJabatan/{id}', [JabatanController::class, 'deleteJabatan']);

    //API ROLE
    Route::get('role/getRole', [RoleController::class, 'getRole']);
    Route::post('role/storeRole', [RoleController::class, 'storeRole']);
    Route::get('role/getRoleByID/{id}', [RoleController::class, 'getRoleByID']);
    Route::post('role/updateRole/{id}', [RoleController::class, 'updateRole']);
    Route::delete('role/deleteRole/{id}', [RoleController::class, 'deleteRole']);

    //API PEGAWAI
    Route::get('pegawai/getPegawai', [PegawaiController::class, 'getPegawai']);
    Route::post('pegawai/storePegawai', [PegawaiController::class, 'storePegawai']);
    Route::get('pegawai/getPegawaiByID/{id}', [PegawaiController::class, 'getPegawaiByID']);
    Route::put('pegawai/updatePegawai/{id}', [PegawaiController::class, 'updatePegawai']);
    Route::delete('pegawai/deletePegawai/{id}', [PegawaiController::class, 'deletePegawai']);

    //API USERS
    Route::get('users/getUsers', [UserController::class, 'getUsers']);
    Route::get('users/getUsersByID/{id}', [UserController::class, 'getUsersByID']);
    Route::put('users/updateUsers/{id}', [UserController::class, 'updateUsers']);

    //API KONDISI
    Route::get('kondisi/getKondisi', [KondisiController::class, 'getKondisi']);
    Route::post('kondisi/storeKondisi', [KondisiController::class, 'storeKondisi']);
    Route::get('kondisi/getKondisiByID/{id}', [KondisiController::class, 'getKondisiByID']);
    Route::put('kondisi/updateKondisi/{id}', [KondisiController::class, 'updateKondisi']);
    Route::delete('kondisi/deleteKondisi/{id}', [KondisiController::class, 'deletekondisi']);

    //API DISKON
    Route::get('diskon/getDiskon', [DiskonController::class, 'getDiskon']);
    Route::post('diskon/storeDiskon', [DiskonController::class, 'storeDiskon']);
    Route::get('diskon/getDiskonByID/{id}', [DiskonController::class, 'getDiskonByID']);
    Route::put('diskon/updateDiskon/{id}', [DiskonController::class, 'updateDiskon']);
    Route::delete('diskon/deleteDiskon/{id}', [DiskonController::class, 'deleteDiskon']);

    //API JENISPRODUK
    Route::get('jenisproduk/getJenisProduk', [JenisProdukController::class, 'getJenisProduk']);
    Route::post('jenisproduk/storeJenisProduk', [JenisProdukController::class, 'storeJenisProduk']);
    Route::get('jenisproduk/getJenisProdukByID/{id}', [JenisProdukController::class, 'getJenisProdukByID']);
    Route::put('jenisproduk/updateJenisProduk/{id}', [JenisProdukController::class, 'updateJenisProduk']);
    Route::delete('jenisproduk/deleteJenisProduk/{id}', [JenisProdukController::class, 'deleteJenisProduk']);

    //API PRODUK
    Route::get('produk/getProduk', [ProdukController::class, 'getProduk']);
    Route::post('produk/storeProduk', [ProdukController::class, 'storeProduk']);
    Route::get('produk/getProdukByID/{id}', [ProdukController::class, 'getProdukByID']);
    Route::put('produk/updateProduk/{id}', [ProdukController::class, 'updateProduk']);
    Route::delete('produk/deleteProduk/{id}', [ProdukController::class, 'deleteProduk']);
    Route::post('produk/getProdukByBarcode',[ProdukController::class, 'getProdukByBarcode']);

    //API NAMPAN
    Route::get('nampan/getNampan', [NampanController::class, 'getNampan']);
    Route::post('nampan/storeNampan', [NampanController::class, 'storeNampan']);
    Route::get('nampan/getNampanByID/{id}', [NampanController::class, 'getNampanByID']);
    Route::put('nampan/updateNampan/{id}', [NampanController::class, 'updateNampan']);

    //API NAMPAN PRODUK
    Route::get('nampanProduk/getNampanProduk/{id}', [NampanProdukController::class, 'getNampanProduk']);
    Route::get('nampanProduk/getProdukNampan/{id}', [NampanProdukController::class, 'getProdukNampan']);
    Route::get('nampanProduk/getProdukByJenis/{id}', [NampanProdukController::class, 'getProdukByJenis']);
    Route::get('nampanProduk/getProduk', [NampanProdukController::class, 'getProduk']);
    Route::post('nampanProduk/storeProdukNampan/{id}', [NampanProdukController::class, 'storeProdukNampan']);
    Route::post('nampanproduk/pindahProdukNampan', [NampanProdukController::class, 'pindahProduk']);
    Route::get('/produk/{id}/get-signed-url', [CetakBarcodeProduk::class, 'getSignedPrintUrl']);
    Route::get('/nampanProduk/getKategoriByJenis', [NampanProdukController::class, 'getKategoriByJenis']);
    Route::get('/nampanProduk/getProdukToStoreNampan/{id}', [NampanProdukController::class, 'getProdukToStoreNampan']);

    //API PELANGGAN
    Route::get('pelanggan/getPelanggan', [PelangganController::class, 'getPelanggan']);
    Route::post('pelanggan/storePelanggan', [PelangganController::class, 'storePelanggan']);
    Route::get('pelanggan/getPelangganByID/{id}', [PelangganController::class, 'getPelangganByID']);
    Route::put('pelanggan/updatePelanggan/{id}', [PelangganController::class, 'updatePelanggan']);
    Route::delete('pelanggan/deletePelanggan/{id}', [PelangganController::class, 'deletePelanggan']);

    //API SUPLIER
    Route::get('suplier/getSuplier', [SuplierController::class, 'getSuplier']);
    Route::post('suplier/storeSuplier', [SuplierController::class, 'storeSuplier']);
    Route::get('suplier/getSuplierByID/{id}', [SuplierController::class, 'getSuplierByID']);
    Route::put('suplier/updateSuplier/{id}', [SuplierController::class, 'updateSuplier']);
    Route::delete('suplier/deleteSuplier/{id}', [SuplierController::class, 'deleteSuplier']);

    //API KERANJANG & TRANSAKSI
    Route::get('pos/getKeranjang', [KeranjangController::class, 'getKeranjang']);
    Route::get('pos/getKodeTransaksi', [KeranjangController::class, 'getKodeTransaksi']);
    Route::post('pos/addToCart', [KeranjangController::class, 'addToCart']);
    Route::delete('pos/clearAllKeranjang', [KeranjangController::class, 'clearAllKeranjangApi']);
    Route::delete('pos/deleteKeranjangByID/{id}', [KeranjangController::class, 'deleteKeranjangApi']);
    Route::post('pos/payment', [TransaksiController::class, 'payment']);
    Route::post('pos/konfirmasiPayment', [TransaksiController::class, 'konfirmasiPembayaran']);

    //API PEMBELIAN DARI TOKO
    Route::get('transaksi/getTransaksiByKode/{id}', [TransaksiController::class, 'getTransaksiByKode']);
    Route::get('pembelianToko/getPembelianProduk', [PembelianTokoController::class, 'getPembelianProduk']);
    Route::get('pembelianToko/getKodePembelianAktif', [PembelianTokoController::class, 'getKodePembelianAktif']);
    Route::post('pembelianToko/pilihProduk', [PembelianTokoController::class, 'pilihProduk']);
    Route::put('/pembelianToko/updateProduk/{id}', [PembelianTokoController::class, 'updatehargaPembelianProduk']);
    Route::delete('pembelianToko/deleteProduk/{id}', [PembelianTokoController::class, 'deleteProduk']);
    Route::post('pembelianToko/storePembelian', [PembelianTokoController::class, 'storePembelian']);
    Route::post('pembelianToko/cancelPembelian', [PembelianTokoController::class, 'batalPembelian']);

    //API PEMBELIAN LUAR TOKO
    Route::get('pembelianLuarToko/getPembelianProduk', [PembelianLuarTokoController::class, 'getPembelianProduk']);
    Route::post('pembelianLuarToko/storePembelianProduk', [PembelianLuarTokoController::class, 'storeProduk']);
    Route::put('pembelianLuarToko/updateProduk/{id}', [PembelianLuarTokoController::class, 'updateProduk']);
    Route::delete('pembelianLuarToko/deleteProduk/{id}', [PembelianLuarTokoController::class, 'deleteProduk']);
    Route::post('pembelianLuarToko/storePembelian', [PembelianLuarTokoController::class, 'storePembelian']);

    //API TRANSAKSI PENJUALAN
    Route::get('transaksi/getTransaksi', [TransaksiController::class, 'getTransaksi']);
    Route::post('transaksi/batalTransaksi',[TransaksiController::class, 'batalTransaksi']);

    //API TRANSAKSI PEMBELIAN
    Route::get('pembelian/getPembelian', [PembelianController::class, 'getPembelian']);
    Route::post('pembelian/batalTransaksi',[PembelianController::class, 'batalTransaksi']);

    //API PERBAIKAN
    Route::get('perbaikan/kodePerbaikan', [PerbaikanController::class, 'kodePerbaikan']);
    Route::get('perbaikan/getPerbaikan', [PerbaikanController::class, 'getPerbaikan']);

    //API OFFTAKE
    Route::get('keranjangOfftake/getKeranjangOfftake', [KeranjangOfftakeController::class, 'getKeranjangOfftake']);
    Route::get('keranjangOfftake/getKeranjangOfftakeAktif', [KeranjangOfftakeController::class, 'getKeranjangOfftakeAktif']);
    Route::post('keranjangOfftake/storeKeranjangOfftake', [KeranjangOfftakeController::class, 'storeKeranjangOfftake']);
    Route::delete('keranjangOfftake/deleteProduk/{id}', [KeranjangOfftakeController::class, 'deleteProduk']);
    Route::post('keranjangOfftake/submitTransaksiOfftake', [KeranjangOfftakeController::class, 'submitTransaksi']);

    Route::get('offtake/getTransaksiOfftake',[OfftakeController::class, 'getTransaksiOfftake']);
});


Route::get('/produk/{id}/cetakbarcodeproduk', [CetakBarcodeProduk::class, 'PrintBarcodeProduk'])->name('produk.cetak_barcode'); // Nama route yang baru
