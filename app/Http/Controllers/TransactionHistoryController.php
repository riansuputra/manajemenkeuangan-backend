<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pemasukan;
use App\Http\Controllers\Controller;
use App\Models\KategoriPengeluaran;
use App\Models\Pengeluaran;
use Illuminate\Support\Facades\DB;

class TransactionHistoryController extends Controller
{
    public function filterByMonthAndYear($month = null, $year = null)
    {
        if ($month !== null && (!is_numeric($month) || $month < 1 || $month > 12)) {
            return response()->json(['error' => 'Invalid month parameter. Provide a valid month number between 1 and 12.'], 400);
        }

        if ($year !== null && (!is_numeric($year) || $year < 1970)) {
            return response()->json(['error' => 'Invalid year parameter. Provide a valid year greater than or equal to 1970.'], 400);
        }

        $pemasukan = new Pemasukan();
        $pemasukan = $pemasukan->with("kategori_pemasukan");

        if ($month !== null) {
            $pemasukan = $pemasukan->whereMonth('created_at', $month);
        }

        if ($year !== null) {
            $pemasukan = $pemasukan->whereYear('created_at', $year);
        }

        $pengeluaran = new Pengeluaran();
        $pengeluaran->with("kategori_pengeluaran");

        if ($month !== null) {
            $pengeluaran = $pengeluaran->whereMonth('created_at', $month);
        }

        if ($year !== null) {
            $pengeluaran = $pengeluaran->whereYear('created_at', $year);
        }

        $pemasukanList = $pemasukan->get();
        $pengeluaranList = $pengeluaran->get();
        $all = [];

        foreach ($pemasukanList as $p) {
            array_push($all, [
                "id" => $p['id_pemasukan'],
                "user_id" => $p['user_id'],
                "tanggal" => $p['tanggal'],
                "jumlah" => floatval($p['jumlah']),
                "catatan" => $p['catatan'],
                "id_kategori" => $p['id_kategori_pemasukan'],
                "nama_kategori" => $p['kategori_pemasukan']['nama_kategori_pemasukan'],
                "created_at" => $p['created_at'],
                "updated_at" => $p['updated_at'],
                "type" => "Pemasukan"
            ]);
        }
        foreach ($pengeluaranList as $p) {
            array_push($all, [
                "id" => $p['id_pengeluaran'],
                "user_id" => $p['user_id'],
                "tanggal" => $p['tanggal'],
                "jumlah" => floatval($p['jumlah']),
                "catatan" => $p['catatan'],
                "id_kategori" => $p['id_kategori_pengeluaran'],
                "nama_kategori" => $p['kategori_pengeluaran']['nama_kategori_pengeluaran'],
                "created_at" => $p['created_at'],
                "updated_at" => $p['updated_at'],
                "type" => "Pengeluaran"
            ]);
        }

        $created_at = array_column($all, 'created_at');
        array_multisort($created_at, SORT_DESC, $all);

        return response()->json(['data' => $all], 200);
    }
    public function filterByYear($year = null)
    {
        $monthlyData = [];

        for ($month = 1; $month <= 12; $month++) {
            $startDate = "$year-$month-01";
            $endDate = date('Y-m-t', strtotime($startDate));

            $totalPemasukan = Pemasukan::whereBetween('tanggal', [$startDate, $endDate])->sum('jumlah');
            $totalPengeluaran = Pengeluaran::whereBetween('tanggal', [$startDate, $endDate])->sum('jumlah');

            $monthlyData[] = [
                'month' => date('F', strtotime($startDate)),
                'total_pengeluaran' => $totalPengeluaran,
                'total_pemasukan' => $totalPemasukan,
            ];
        }

        return response()->json([
            "data" => $monthlyData
        ]);
    }

    public function filterCategoriesByMonthAndYear($month = null, $year = null)
    {
        // Validasi bulan dan tahun, jika tidak diberikan, gunakan bulan dan tahun saat ini
        if (is_null($month)) {
            $month = now()->month;
        }

        if (is_null($year)) {
            $year = now()->year;
        }

        // Ambil semua kategori pengeluaran
        $kategoriPengeluaran = KategoriPengeluaran::all();

        // Query untuk menghitung total pengeluaran per kategori
        $totalPengeluaran = Pengeluaran::whereMonth('tanggal', $month)
            ->whereYear('tanggal', $year)
            ->select('id_kategori_pengeluaran', DB::raw('SUM(jumlah) as total'))
            ->groupBy('id_kategori_pengeluaran')
            ->get();

        // Membuat array untuk menyimpan hasil
        $result = [];

        // Loop melalui semua kategori pengeluaran
        foreach ($kategoriPengeluaran as $kategori) {
            $kategoriId = $kategori->id_kategori_pengeluaran;
            $kategoriNama = $kategori->nama_kategori_pengeluaran;

            // Cari total pengeluaran untuk kategori saat ini
            $total = 0;
            $matchingTotal = $totalPengeluaran->where('id_kategori_pengeluaran', $kategoriId)->first();
            if ($matchingTotal) {
                $total = $matchingTotal->total;
            }

            // Menambahkan data ke array hasil
            $result[] = [
                'nama_kategori_pengeluaran' => $kategoriNama,
                'total' => $total,
            ];
        }

        return [
            "data" => $result
        ];
    }
}
