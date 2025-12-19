<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JenisKarat extends Model
{
    use HasFactory;
    protected $hidden = ['created_at', 'updated_at', 'deleted_at']; // Menyembunyikan created_at dan updated_at secara global
    protected $table    = 'jenis_karat';
    protected $fillable =
    [
        'karat_id',
        'jenis',
        'status'
    ];

    public function karat()
    {
        return $this->belongsTo(Karat::class, 'karat_id', 'id');
    }

    public function harga()
    {
        return $this->hasMany(Harga::class, 'jenis_karat_id', 'id');
    }
}
