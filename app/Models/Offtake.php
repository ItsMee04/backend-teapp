<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Offtake extends Model
{
    use HasFactory;
    protected $hidden = ['created_at', 'updated_at', 'deleted_at']; // Menyembunyikan created_at dan updated_at secara global
    protected $table    = 'offtake';
    protected $fillable =
    [
        'kodetransaksi',
        'tanggal',
        'suplier_id',
        'total',
        'hargatotal',
        'pembayaran',
        'keterangan',
        'oleh',
        'status'
    ];

    public function suplier()
    {
        return $this->belongsTo(Suplier::class, 'suplier_id');
    }

    /**
     * Get all of the keranjangofftake for the Offtake
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function keranjangofftake(): HasMany
    {
        return $this->hasMany(KeranjangOfftake::class, 'kodetransaksi', 'kodetransaksi');
    }

    /**
     * Get the user that owns the Offtake
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'oleh', 'id');
    }
}
