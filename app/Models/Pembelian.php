<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pembelian extends Model
{
    use HasFactory;
    protected $table = 'pembelian';
    protected $primaryKey = 'id';

    protected $fillable = [
        'kodepembelian',
        'suplier_id',
        'pelanggan_id',
        'tanggal',
        'total',
        'terbilang',
        'oleh',
        'catatan',
        'jenispembelian',
        'status',
    ];

    // ===========================
    // 🔗 RELASI
    // ===========================

    // Relasi ke keranjang_pembelian (kodepembelian -> kodepembelian)
    public function keranjangPembelian()
    {
        return $this->hasMany(KeranjangPembelian::class, 'kodepembelian', 'kodepembelian');
    }

    // Relasi ke suplier
    public function suplier()
    {
        return $this->belongsTo(Suplier::class, 'suplier_id');
    }

    // Relasi ke pelanggan
    public function pelanggan()
    {
        return $this->belongsTo(Pelanggan::class, 'pelanggan_id');
    }

    // Relasi ke user (yang input)
    public function user()
    {
        return $this->belongsTo(User::class, 'oleh');
    }
}
