<?php

namespace Database\Seeders;

use App\Models\Saldo;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;

class RekeningSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Saldo::create([
            'rekening'      =>  'REKENING UTAMA',
            'total'         =>  0,
            'status'        =>  1,
            'oleh'          =>  1,
        ]);
    }
}
