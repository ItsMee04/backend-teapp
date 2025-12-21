<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MutasiSaldo extends Model
{
    use HasFactory;
    protected $hidden = ['created_at', 'updated_at', 'deleted_at']; // Menyembunyikan created_at dan updated_at secara global
    protected $table    = 'mutasi_saldo';
    protected $fillable =
    [
        'saldo_id',
        'tanggal',
        'keterangan',
        'jenis',
        'jumlah',
        'oleh',
        'status'
    ];

    /**
     * Get the user that owns the MutasiSaldo
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function saldo(): BelongsTo
    {
        return $this->belongsTo(Saldo::class, 'saldo_id', 'id');
    }
}
