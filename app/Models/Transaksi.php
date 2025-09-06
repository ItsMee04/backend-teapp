<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaksi extends Model
{
    use HasFactory;
    protected $hidden = ['created_at', 'updated_at', 'deleted_at']; // Menyembunyikan created_at dan updated_at secara global
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'transaksi';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'kodetransaksi',
        'pelanggan_id',
        'diskon_id',
        'total',
        'terbilang',
        'tanggal',
        'oleh',
        'status',
    ];

    /**
     * Get the keranjang items for the transaction.
     * Transaksi ini memiliki banyak item di keranjang.
     * 'kodetransaksi' di tabel keranjang merujuk ke 'kodetransaksi' di tabel ini.
     */
    public function keranjang()
    {
        return $this->hasMany(Keranjang::class, 'kodetransaksi', 'kodetransaksi');
    }

    /**
     * Get the customer that owns the transaction.
     * Transaksi ini dimiliki oleh satu pelanggan.
     */
    public function pelanggan()
    {
        return $this->belongsTo(Pelanggan::class, 'pelanggan_id');
    }

    /**
     * Get the discount that was applied to the transaction.
     * Transaksi ini memiliki satu diskon.
     */
    public function diskon()
    {
        return $this->belongsTo(Diskon::class, 'diskon_id');
    }

    /**
     * Get the user that created the transaction.
     * Transaksi ini dibuat oleh satu user.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'oleh');
    }
}
