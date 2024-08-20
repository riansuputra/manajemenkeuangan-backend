<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\KategoriPemasukan;
use App\Models\KategoriPengeluaran;
use App\Models\Pemasukan;
use App\Models\Pengeluaran;
use Carbon\Carbon;

class KategoriSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pemasukans = ['Uang Saku', ' Upah', 'Bonus', 'Lainnya'];
        $pengeluarans = ['Makanan', 'Minuman', 'Tagihan', 'Shopping', 'Kesehatan & Olahraga', 'Lainnya'];
        $now = Carbon::now();

        foreach ($pemasukans as $pemasukan) {
            KategoriPemasukan::create([
                'nama_kategori_pemasukan' => $pemasukan,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        foreach ($pengeluarans as $pengeluaran) {
            KategoriPengeluaran::create([
                'nama_kategori_pengeluaran' => $pengeluaran,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
