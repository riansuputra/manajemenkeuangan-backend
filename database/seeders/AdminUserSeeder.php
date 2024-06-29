<?php

namespace Database\Seeders;

use App\Models\KategoriPemasukan;
use App\Models\KategoriPengeluaran;
use App\Models\Pemasukan;
use App\Models\Pengeluaran;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name' => 'Admin',
            'email' => 'admin@demo.com',
            'password' => Hash::make('123456'),
        ]);

        User::create([
            'name' => 'User',
            'email' => 'user@demo.com',
            'password' => Hash::make('123456'),
        ]);

        $pemasukans = ["Uang Saku", " Upah", "Bonus", "Lainnya"];
        $pengeluarans = ["Makanan", "Minuman", "Tagihan", "Shopping", "Kesehatan & Olahraga", "Lainnya"];

        foreach ($pemasukans as $pemasukan) {
            KategoriPemasukan::create([
                "nama_kategori_pemasukan" => $pemasukan
            ]);
        }

        foreach ($pengeluarans as $pengeluaran) {
            KategoriPengeluaran::create([
                "nama_kategori_pengeluaran" => $pengeluaran
            ]);
        }

        // for ($month = 1; $month <= 12; $month++) {
        //     for ($i = 1; $i <= 5; $i++) {
        //         $randomDays = rand(1, 28); // Angka acak antara 1 dan 28
        //         $date = Carbon::create(now()->year, $month, $randomDays)->addDays(rand(0, 30)); // Tambahkan angka acak antara 0 dan 30 hari
        
        //         Pemasukan::create([
        //             'user_id' => rand(1, 2),
        //             'tanggal' => $date,
        //             'jumlah' => rand(50000, 200000),
        //             'catatan' => 'Catatan Pemasukan ' . $i,
        //             'id_kategori_pemasukan' => rand(1, 2),
        //             'created_at' => $date, // Tambahkan nilai created_at yang sama dengan tanggal
        //         ]);
        //     }
        // }

        // for ($month = 1; $month <= 12; $month++) {
        //     for ($i = 1; $i <= 5; $i++) {
        //         $randomDays = rand(1, 28); // Angka acak antara 1 dan 28
        //         $date = Carbon::create(now()->year, $month, $randomDays)->addDays(rand(0, 30)); // Tambahkan angka acak antara 0 dan 30 hari
        
        //         Pengeluaran::create([
        //             'user_id' => rand(1, 2),
        //             'tanggal' => $date,
        //             'jumlah' => rand(50000, 200000),
        //             'catatan' => 'Catatan Pengeluaran ' . $i,
        //             'id_kategori_pengeluaran' => rand(1, 2),
        //             'created_at' => $date, // Tambahkan nilai created_at yang sama dengan tanggal
        //         ]);
        //     }
        // }
    }
}
