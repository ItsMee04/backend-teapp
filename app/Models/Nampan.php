<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Nampan extends Model
{
    use HasFactory;
    protected $hidden = ['created_at', 'updated_at', 'deleted_at']; // Menyembunyikan created_at dan updated_at secara global
    protected $table = 'nampan'; // atau sesuai nama tabel
    protected $fillable = [
        'jenisproduk_id',
        'nampan',
        'tanggal',
        'status',
        'status_final',
    ];

    /**
     * Get the jenisProduk that owns the Nampan
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function jenisProduk(): BelongsTo
    {
        return $this->belongsTo(JenisProduk::class, 'jenisproduk_id', 'id');
    }

    /**
     * Get all of the produk for the Nampan
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function produk(): HasMany
    {
        return $this->hasMany(NampanProduk::class, 'nampan_id');
    }

    /**
     * Get all of the nampanProduk for the Nampan
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function nampanProduk(): HasMany
    {
        return $this->hasMany(NampanProduk::class, 'nampan_id');
    }
}
