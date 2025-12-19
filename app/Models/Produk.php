<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Produk extends Model
{
    use HasFactory;
    protected $hidden = ['created_at', 'updated_at', 'deleted_at']; // Menyembunyikan created_at dan updated_at secara global
    protected $table    = 'produk';
    protected $fillable =
    [
        'kodeproduk',
        'nama',
        'berat',
        'jenisproduk_id',
        'karat_id',
        'jenis_karat_id',
        'harga_jual',
        'harga_beli',
        'lingkar',
        'panjang',
        'keterangan',
        'kondisi_id',
        'image_produk',
        'status'
    ];

    /**
     * Get the jenisproduk that owns the Produk
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function jenisproduk(): BelongsTo
    {
        return $this->belongsTo(JenisProduk::class, 'jenisproduk_id', 'id');
    }

    /**
     * Get the kondisi that owns the Produk
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function kondisi(): BelongsTo
    {
        return $this->belongsTo(Kondisi::class, 'kondisi_id', 'id');
    }

    public function keranjang()
    {
        return $this->hasMany(Keranjang::class, 'produk_id');
    }

    public function karat()
    {
        return $this->belongsTo(Karat::class, 'karat_id', 'id');
    }

    public function jeniskarat()
    {
        return $this->belongsTo(JenisKarat::class, 'jenis_karat_id', 'id');
    }

    public function harga()
    {
        return $this->belongsTo(Harga::class, 'harga_jual', 'id');
    }
}
