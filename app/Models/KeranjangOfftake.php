<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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

    /**
     * Get the produk that owns the KeranjangOfftake
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function produk(): BelongsTo
    {
        return $this->belongsTo(Produk::class, 'produk_id', 'id');
    }

    /**
     * Get the user that owns the KeranjangOfftake
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'oleh', 'id');
    }
}
