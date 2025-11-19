<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StokNampanBulanan extends Model
{
    use HasFactory;
    protected $hidden = ['created_at', 'updated_at', 'deleted_at']; // Menyembunyikan created_at dan updated_at secara global
    protected $table    = 'stok_nampan_bulanan';
    protected $fillable =
    [
        'tanggal',
        'tanggal_input',
        'keterangan',
        'status_final',
        'oleh',
        'status'
    ];

    /**
     * Get the user that owns the StokNampanBulanan
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'oleh', 'id');
    }
}
