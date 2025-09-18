<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KeranjangOfftake extends Model
{
    use HasFactory;
    protected $hidden = ['created_at', 'updated_at', 'deleted_at']; // Menyembunyikan created_at dan updated_at secara global
    protected $table    = 'keranjang_offtake';
    protected $fillable =
    [
        'kodetransaksi',
        'produk_id',
        'harga_jual',
        'berat',
        'karat',
        'lingkar',
        'panjang',
        'total',
        'terbilang',
        'oleh',
        'status'
    ];

    public function produk()
    {
        return $this->belongsTo(Produk::class, 'produk_id');
    }
}
