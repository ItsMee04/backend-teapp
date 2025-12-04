<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KeranjangPembelian extends Model
{
    use HasFactory;
    protected $table = 'keranjang_pembelian';
    protected $primaryKey = 'id';

    protected $fillable = [
        'kodetransaksi',
        'kodepembelian',
        'produk_id',
        'harga_beli',
        'berat',
        'karat',
        'lingkar',
        'panjang',
        'kondisi_id',
        'jenisproduk_id',
        'jenis_pembelian',
        'jenis_hargabeli',
        'total',
        'terbilang',
        'keterangan',
        'oleh',
        'status',
    ];

    // Relasi ke pembelian
    public function pembelian()
    {
        return $this->belongsTo(Pembelian::class, 'kodepembelian', 'kodepembelian');
    }

    // Relasi ke produk
    public function produk()
    {
        return $this->belongsTo(Produk::class, 'produk_id');
    }

    // Relasi ke kondisi
    public function kondisi()
    {
        return $this->belongsTo(Kondisi::class, 'kondisi_id');
    }

    // Relasi ke jenis produk
    public function jenisProduk()
    {
        return $this->belongsTo(JenisProduk::class, 'jenisproduk_id');
    }

    // Relasi ke user
    public function user()
    {
        return $this->belongsTo(User::class, 'oleh');
    }

    // relasi ke keranjang
    public function keranjang()
    {
        return $this->belongsTo(Keranjang::class, 'produk_id', 'produk_id');
    }
}
