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
        $pemasukans = [
            'Pendapatan', 'Pendapatan Usaha', 'Freelance', 'Komisi', 'Royalti',
            'Investasi', 'Dividen', 'Bunga', 'Gaji', 'Cek', 
            'Bonus', 'Upah', 'Hadiah', 'Iuran', 'Hibah',
            'Pendapatan Sewa', 'Penjualan', 'Pengembalian Pajak', 'Santunan', 
            'Lainnya',
        ];        
        
        $pengeluarans = [
            'Makanan Pokok', 'Jajan', 'Makan di Restoran', 'Cafe',
            'Belanja', 'Alat Tulis', 'Busana', 'Elektronik', 'Perhiasan', 'Hadiah',
            'Dokter', 'Obat', 'Pemeriksaan Medis', 'Asuransi Kesehatan',
            'Sewa', 'Listrik', 'Air', 'Internet', 'TV Kabel', 'Telepon', 
            'Kebersihan', 'Peralatan Rumah', 'Dekorasi Rumah',
            'Bensin', 'Parkir', 'Transportasi Umum', 'Ojek Online', 'Taksi',
            'Hiburan', 'Buku', 'Olahraga', 'Hobi',
            'Pendidikan', 'Sumbangan', 
            'Pajak', 'Denda', 'Pinjaman', 'Tagihan', 'Biaya Layanan',
            'Pembelian Saham', 'Pembelian Obligasi', 'Reksa Dana', 'Investasi Properti',
            'Lainnya',
        ];

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
