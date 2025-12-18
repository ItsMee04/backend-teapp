<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Karat extends Model
{
    use HasFactory;
    protected $hidden = ['created_at', 'updated_at', 'deleted_at']; // Menyembunyikan created_at dan updated_at secara global
    protected $table    = 'karat';
    protected $fillable =
    [
        'karat',
        'status'
    ];

    public function jenisKarat()
    {
        return $this->hasMany(JenisKarat::class, 'karat_id', 'id');
    }
}
