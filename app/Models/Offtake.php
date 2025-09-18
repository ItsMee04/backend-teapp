<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        'pembayaran',
        'keterangan',
        'oleh',
        'status'
    ];

    public function suplier()
    {
        return $this->belongsTo(Suplier::class, 'suplier_id');
    }
}
