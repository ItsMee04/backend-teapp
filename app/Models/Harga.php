<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Harga extends Model
{
    use HasFactory;
    protected $hidden = ['created_at', 'updated_at', 'deleted_at']; // Menyembunyikan created_at dan updated_at secara global
    protected $table    = 'harga';
    protected $fillable =
    [
        'karat_id',
        'jenis_karat_id',
        'harga',
        'status'
    ];

    public function karat()
    {
        return $this->belongsTo(Karat::class, 'karat_id', 'id');
    }

    public function jenisKarat()
    {
        return $this->belongsTo(JenisKarat::class, 'jenis_karat_id', 'id');
    }
}
