<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Saldo extends Model
{
    use HasFactory;
    protected $hidden = ['created_at', 'updated_at', 'deleted_at']; // Menyembunyikan created_at dan updated_at secara global
    protected $table    = 'saldo';
    protected $fillable =
    [
        'rekening',
        'total',
        'oleh',
        'status'
    ];

    /**
     * Get all of the MutasiSaldo for the Saldo
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function MutasiSaldo(): HasMany
    {
        return $this->hasMany(MutasiSaldo::class, 'saldo_id', 'id');
    }
}
