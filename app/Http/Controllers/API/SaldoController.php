<?php

namespace App\Http\Controllers\API;

use Exception;
use Carbon\Carbon;
use App\Models\Saldo;
use App\Models\MutasiDana;
use App\Models\KinerjaPortofolio;
use App\Models\Portofolio;
use App\Models\Historis;
use App\Models\Transaksi;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class SaldoController extends Controller
{
    public function topUp(Request $request)
    {
        try {

            $request->validate([
                'saldo' => 'required|integer|min:1',
            ]);
            
            $userId = $request->auth['user']['id'];
            $jumlahSaldo = $request->input('saldo');
            $tipeSaldo = $request->input('tipe_saldo');
            $tanggal = $request->input('tanggal');
            

            if ($tipeSaldo === 'masuk') {
                return $this->handleSaldoMasuk($request, $userId, $jumlahSaldo, $tanggal);
            } elseif ($tipeSaldo === 'keluar') {
                return $this->handleSaldoKeluar($request, $userId, $jumlahSaldo, $tanggal);
            }

            return response()->json([
                'auth' => $request->auth,
                'status' => 'error',
                'message' => 'Tipe saldo tidak valid.',
            ], 400);

        } catch (\Exception $e) {
            if ($e instanceof ValidationException) {
                return response()->json([
                    'message' => $e->getMessage(),
                    'auth' => $request->auth,
                    'errors' => $e->validator->errors(),
                ], Response::HTTP_BAD_REQUEST);
            } else {
                return response()->json([
                    'message' => $e->getMessage(),
                    'auth' => $request->auth,
                ], Response::HTTP_BAD_REQUEST);
            }
        }
    }

    private function handleSaldoMasuk(Request $request, $userId, $jumlahSaldo, $tanggal)
    {
        $tahun = date('Y', strtotime($tanggal));
        $bulan = date('n', strtotime($tanggal));
        // Tambahkan data ke Transaksi
        $transaksiBaru = Transaksi::create([
            'user_id' => $userId,
            'aset_id' => 1, // ID aset untuk "kas"
            'jenis_transaksi' => 'kas',
            'tanggal' => $tanggal,
            'volume' => 1,
            'harga' => $jumlahSaldo,
            'deskripsi' => 'Top-up saldo',
        ]);

        // Data pendukung
        $mutasiDanaTerakhir = MutasiDana::where('user_id', $userId)
            ->orderByDesc('id')
            ->first();

        $kinerjaPortofolioTerakhir = KinerjaPortofolio::where('user_id', $userId)
            ->orderByDesc('id')
            ->first();

        $portofolioTerakhir = Portofolio::where('user_id', $userId)
            ->where('aset_id', 1) // ID aset untuk "kas"
            ->orderByDesc('id')
            ->first();

        $historisTerkait = Historis::where('user_id', $userId)
            ->where('tahun', $tahun)
            ->where('bulan', $bulan)
            ->first();

        // Jika belum ada data mutasi dana untuk tahun ini
        if (!$mutasiDanaTerakhir || $mutasiDanaTerakhir->tahun < $tahun) {
            $modalBaru = ($kinerjaPortofolioTerakhir->valuasi_saat_ini ?? 0) + $jumlahSaldo;
            $hargaUnitLama = $mutasiDanaTerakhir->harga_unit_saat_ini ?? 1000;

            $jumlahUnitPenyertaanBaru = ceil(
                $modalBaru / $hargaUnitLama
            );

            $hargaUnitSaatIni = ceil(
                $modalBaru / $jumlahUnitPenyertaanBaru
            );

            $mutasiDanaBaru = MutasiDana::create([
                'user_id' => $userId,
                'tahun' => $tahun,
                'bulan' => $bulan,
                'modal' => $modalBaru,
                'harga_unit' => $hargaUnitSaatIni,
                'harga_unit_saat_ini' => $hargaUnitSaatIni,
                'jumlah_unit_penyertaan' => $jumlahUnitPenyertaanBaru,
                'alur_dana' => $jumlahSaldo,
            ]);

            // Tambahkan data ke Kinerja Portofolio
            $valuasiSaatIniBaru = $modalBaru;
            $kinerjaPortofolioBaru = KinerjaPortofolio::create([
                'user_id' => $userId,
                'transaksi_id' => $transaksiBaru->id, // ID transaksi akan diisi setelah transaksi dibuat
                'valuasi_saat_ini' => $valuasiSaatIniBaru,
                'yield' => 0.00,
            ]);

            // Tambahkan data ke Historis
            if (!$historisTerkait) {
                Historis::create([
                    'user_id' => $userId,
                    'tahun' => $tahun,
                    'bulan' => $bulan,
                    'yield' => 0.00,
                ]);
            } else {
                $historisTerkait->update([
                    'yield' => 0.00,
                ]);
            }

            // Tambahkan data ke Portofolio
            Portofolio::create([
                'user_id' => $userId,
                'aset_id' => 1, // ID aset untuk "kas"
                'kinerja_portofolio_id' => $kinerjaPortofolioBaru->id,
                'volume' => 1,
                'cur_price' => $valuasiSaatIniBaru,
            ]);

        } else {
            // Jika sudah ada data mutasi dana untuk tahun ini
            $modalLama = $mutasiDanaTerakhir->modal;
            $hargaUnitSaatIni = ceil(
                ($kinerjaPortofolioTerakhir->valuasi_saat_ini ?? 0) / ($mutasiDanaTerakhir->jumlah_unit_penyertaan ?? 0)
            );
            $hargaUnit = $mutasiDanaTerakhir->harga_unit;

            $jumlahUnitPenyertaanBaru = ($jumlahSaldo / $hargaUnitSaatIni) + $mutasiDanaTerakhir->jumlah_unit_penyertaan;

            $mutasiDanaBaru = MutasiDana::create([
                'user_id' => $userId,
                'tahun' => $tahun,
                'bulan' => $bulan,
                'modal' => $modalLama,
                'harga_unit' => $hargaUnit,
                'harga_unit_saat_ini' => $hargaUnitSaatIni,
                'jumlah_unit_penyertaan' => $jumlahUnitPenyertaanBaru,
                'alur_dana' => $jumlahSaldo,
            ]);

            // Tambahkan data ke Kinerja Portofolio
            $valuasiSaatIniBaru = ($kinerjaPortofolioTerakhir->valuasi_saat_ini ?? 0) + $jumlahSaldo;
            $yield = ($hargaUnitSaatIni - ($mutasiDanaTerakhir->harga_unit ?? 0)) / ($mutasiDanaTerakhir->harga_unit ?? 1);

            $kinerjaPortofolioBaru = KinerjaPortofolio::create([
                'user_id' => $userId,
                'transaksi_id' => $transaksiBaru->id, // ID transaksi akan ditambahkan di bawah
                'valuasi_saat_ini' => $valuasiSaatIniBaru,
                'yield' => $yield,
            ]);

            // Update atau tambahkan data ke Historis
            if ($historisTerkait) {
                $historisTerkait->update([
                    'yield' => $yield,
                ]);
            } else {
                Historis::create([
                    'user_id' => $userId,
                    'tahun' => $tahun,
                    'bulan' => $bulan,
                    'yield' => $yield,
                ]);
            }

            // Tambahkan data ke Portofolio
            $curPriceBaru = ($portofolioTerakhir->cur_price ?? 0) + $jumlahSaldo;

            Portofolio::create([
                'user_id' => $userId,
                'aset_id' => 1, // ID aset untuk "kas"
                'kinerja_portofolio_id' => $kinerjaPortofolioBaru->id,
                'volume' => 1,
                'cur_price' => $curPriceBaru,
            ]);
        }

        // Tambahkan data ke Saldo
        $saldoBaru = Saldo::create([
            'user_id' => $userId,
            'tanggal' => $tanggal,
            'tipe_saldo' => 'masuk',
            'saldo' => $jumlahSaldo,
        ]);

        return response()->json([
            'status' => 'success',
            'auth' => $request->auth,
            'message' => 'Top-up saldo berhasil diproses.',
            'data' => [
                'saldo' => $saldoBaru,
                'transaksi' => $transaksiBaru,
                'mutasi_dana' => $mutasiDanaBaru ?? null,
                'kinerja_portofolio' => $kinerjaPortofolioBaru ?? null,
            ],
        ], Response::HTTP_CREATED);
    }

    private function handleSaldoKeluar(Request $request, $userId, $jumlahSaldo, $tanggal)
    {
        $tahun = date('Y', strtotime($tanggal));
        $bulan = date('n', strtotime($tanggal));
        // Validasi saldo cukup
        $totalSaldo = Saldo::where('user_id', $userId)->sum('saldo');
        if ($jumlahSaldo > $totalSaldo) {
            return response()->json([
                'status' => 'error',
                'message' => 'Saldo tidak mencukupi.',
            ], 400);
        } else if ($jumlahSaldo == $totalSaldo) {
            $maksimalWd = number_format(($jumlahSaldo - 1), 0, ',', '.');
            return response()->json([
                'status' => 'warning',
                'message' => 'Maksimal withdraw saldo adalah Rp. '.$maksimalWd,
            ], 400);
        }

        $jumlahSaldo = -abs($jumlahSaldo);

        // Tambahkan data ke Transaksi
        $transaksiBaru = Transaksi::create([
            'user_id' => $userId,
            'aset_id' => 1, // ID aset untuk "kas"
            'jenis_transaksi' => 'kas',
            'tanggal' => $tanggal,
            'volume' => 1,
            'harga' => $jumlahSaldo,
            'deskripsi' => 'Withdraw saldo',
        ]);

        // Data pendukung
        $mutasiDanaTerakhir = MutasiDana::where('user_id', $userId)
            ->orderByDesc('id')
            ->first();

        $kinerjaPortofolioTerakhir = KinerjaPortofolio::where('user_id', $userId)
            ->orderByDesc('id')
            ->first();

        $portofolioTerakhir = Portofolio::where('user_id', $userId)
            ->where('aset_id', 1) // ID aset untuk "kas"
            ->orderByDesc('id')
            ->first();

        $historisTerkait = Historis::where('user_id', $userId)
            ->where('tahun', $tahun)
            ->where('bulan', $bulan)
            ->first();

        // Jika belum ada data mutasi dana untuk tahun ini
        if (!$mutasiDanaTerakhir || $mutasiDanaTerakhir->tahun < $tahun) {
            $modalBaru = ($kinerjaPortofolioTerakhir->valuasi_saat_ini ?? 0) + $jumlahSaldo;
            $hargaUnitLama = $mutasiDanaTerakhir->harga_unit_saat_ini ?? 1000;

            $jumlahUnitPenyertaanBaru = ceil(
                $modalBaru / $hargaUnitLama
            );

            $hargaUnitSaatIni = ceil(
                $modalBaru / $jumlahUnitPenyertaanBaru
            );

            $mutasiDanaBaru = MutasiDana::create([
                'user_id' => $userId,
                'tahun' => $tahun,
                'bulan' => $bulan,
                'modal' => $modalBaru,
                'harga_unit' => $hargaUnitLama,
                'harga_unit_saat_ini' => $hargaUnitSaatIni,
                'jumlah_unit_penyertaan' => $jumlahUnitPenyertaanBaru,
                'alur_dana' => $jumlahSaldo,
            ]);

            // Tambahkan data ke Kinerja Portofolio
            $valuasiSaatIniBaru = $modalBaru;
            $kinerjaPortofolioBaru = KinerjaPortofolio::create([
                'user_id' => $userId,
                'transaksi_id' => $transaksiBaru->id, // ID transaksi akan diisi setelah transaksi dibuat
                'valuasi_saat_ini' => $valuasiSaatIniBaru,
                'yield' => 0.00,
            ]);

            // Tambahkan data ke Historis
            if (!$historisTerkait) {
                Historis::create([
                    'user_id' => $userId,
                    'tahun' => $tahun,
                    'bulan' => $bulan,
                    'yield' => 0.00,
                ]);
            } else {
                $historisTerkait->update([
                    'yield' => 0.00,
                ]);
            }

            // Tambahkan data ke Portofolio
            Portofolio::create([
                'user_id' => $userId,
                'aset_id' => 1, // ID aset untuk "kas"
                'kinerja_portofolio_id' => $kinerjaPortofolioBaru->id,
                'volume' => 1,
                'cur_price' => $valuasiSaatIniBaru,
            ]);

        } else {
            // Jika sudah ada data mutasi dana untuk tahun ini
            $modalLama = $mutasiDanaTerakhir->modal;
            $hargaUnit = $mutasiDanaTerakhir->harga_unit;
            $hargaUnitSaatIni = ceil(
                ($kinerjaPortofolioTerakhir->valuasi_saat_ini ?? 0) / $mutasiDanaTerakhir->jumlah_unit_penyertaan
            );

            $jumlahUnitPenyertaanBaru = ($jumlahSaldo / $hargaUnitSaatIni) + $mutasiDanaTerakhir->jumlah_unit_penyertaan;

            $mutasiDanaBaru = MutasiDana::create([
                'user_id' => $userId,
                'tahun' => $tahun,
                'bulan' => $bulan,
                'modal' => $modalLama,
                'harga_unit' => $hargaUnit,
                'harga_unit_saat_ini' => $hargaUnitSaatIni,
                'jumlah_unit_penyertaan' => $jumlahUnitPenyertaanBaru,
                'alur_dana' => $jumlahSaldo,
            ]);

            // Tambahkan data ke Kinerja Portofolio
            $valuasiSaatIniBaru = ($kinerjaPortofolioTerakhir->valuasi_saat_ini ?? 0) + $jumlahSaldo;
            $yield = ($hargaUnitSaatIni - ($mutasiDanaTerakhir->harga_unit ?? 0)) / ($mutasiDanaTerakhir->harga_unit ?? 1);

            $kinerjaPortofolioBaru = KinerjaPortofolio::create([
                'user_id' => $userId,
                'transaksi_id' => $transaksiBaru->id, // ID transaksi akan ditambahkan di bawah
                'valuasi_saat_ini' => $valuasiSaatIniBaru,
                'yield' => $yield,
            ]);

            // Update atau tambahkan data ke Historis
            if ($historisTerkait) {
                $historisTerkait->update([
                    'yield' => $yield,
                ]);
            } else {
                Historis::create([
                    'user_id' => $userId,
                    'tahun' => $tahun,
                    'bulan' => $bulan,
                    'yield' => $yield,
                ]);
            }

            // Tambahkan data ke Portofolio
            $curPriceBaru = ($portofolioTerakhir->cur_price ?? 0) + $jumlahSaldo;

            Portofolio::create([
                'user_id' => $userId,
                'aset_id' => 1, // ID aset untuk "kas"
                'kinerja_portofolio_id' => $kinerjaPortofolioBaru->id,
                'volume' => 1,
                'cur_price' => $curPriceBaru,
            ]);
        }

        // Tambahkan data ke Saldo
        $saldoBaru = Saldo::create([
            'user_id' => $userId,
            'tanggal' => $tanggal,
            'tipe_saldo' => 'keluar',
            'saldo' => $jumlahSaldo,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Withdraw saldo berhasil diproses.',
            'auth' => $request->auth,
            'data' => [
                'saldo' => $saldoBaru,
                'transaksi' => $transaksiBaru,
                'mutasi_dana' => $mutasiDanaBaru ?? null,
                'kinerja_portofolio' => $kinerjaPortofolioBaru ?? null,
            ],
        ],Response::HTTP_CREATED);
    }

    public function topUp1(Request $request)
    {
        $userId = $request->auth['user']['id'];
        $jumlahSaldo = $request->input('jumlah_saldo');
        $tipeSaldo = $request->input('tipe_saldo');
        $tanggal = $request->input('tanggal');
        $tahun = date('Y', strtotime($tanggal));
        $bulan = date('n', strtotime($tanggal));

        if ($tipeSaldo === 'masuk') {
            // Tambahkan data ke Transaksi
            $transaksiBaru = Transaksi::create([
                'user_id' => $userId,
                'aset_id' => 1, // ID aset untuk "kas"
                'jenis_transaksi' => 'kas',
                'tanggal' => $tanggal,
                'volume' => 1,
                'harga' => $jumlahSaldo,
                'deskripsi' => 'Top-up saldo',
            ]);

            // Data pendukung
            $mutasiDanaTerakhir = MutasiDana::where('user_id', $userId)
                ->orderByDesc('id')
                ->first();

            $kinerjaPortofolioTerakhir = KinerjaPortofolio::where('user_id', $userId)
                ->orderByDesc('id')
                ->first();

            $portofolioTerakhir = Portofolio::where('user_id', $userId)
                ->where('aset_id', 1) // ID aset untuk "kas"
                ->orderByDesc('id')
                ->first();

            $historisTerkait = Historis::where('user_id', $userId)
                ->where('tahun', $tahun)
                ->where('bulan', $bulan)
                ->first();

            // Jika belum ada data mutasi dana untuk tahun ini
            if (!$mutasiDanaTerakhir || $mutasiDanaTerakhir->tahun < $tahun) {
                $modalBaru = ($kinerjaPortofolioTerakhir->valuasi_saat_ini ?? 0) + $jumlahSaldo;
                $hargaUnitLama = $mutasiDanaTerakhir->harga_unit ?? 1000;
    
                $jumlahUnitPenyertaanBaru = ceil(
                    $modalBaru / $hargaUnitLama
                );
    
                $hargaUnitSaatIni = ceil(
                    $modalBaru / $jumlahUnitPenyertaanBaru
                );
    
                $mutasiDanaBaru = MutasiDana::create([
                    'user_id' => $userId,
                    'tahun' => $tahun,
                    'bulan' => $bulan,
                    'modal' => $modalBaru,
                    'harga_unit' => $hargaUnitSaatIni,
                    'jumlah_unit_penyertaan' => $jumlahUnitPenyertaanBaru,
                    'alur_dana' => $jumlahSaldo,
                ]);
    
                // Tambahkan data ke Kinerja Portofolio
                $valuasiSaatIniBaru = $modalBaru;
                $kinerjaPortofolioBaru = KinerjaPortofolio::create([
                    'user_id' => $userId,
                    'transaksi_id' => $transaksiBaru->id, // ID transaksi akan diisi setelah transaksi dibuat
                    'valuasi_saat_ini' => $valuasiSaatIniBaru,
                    'yield' => 0.00,
                ]);
    
                // Tambahkan data ke Historis
                if (!$historisTerkait) {
                    Historis::create([
                        'user_id' => $userId,
                        'tahun' => $tahun,
                        'bulan' => $bulan,
                        'yield' => 0.00,
                    ]);
                } else {
                    $historisTerkait->update([
                        'yield' => 0.00,
                    ]);
                }
    
                // Tambahkan data ke Portofolio
                Portofolio::create([
                    'user_id' => $userId,
                    'aset_id' => 1, // ID aset untuk "kas"
                    'kinerja_portofolio_id' => $kinerjaPortofolioBaru->id,
                    'volume' => 1,
                    'cur_price' => $valuasiSaatIniBaru,
                ]);

            } else {
                // Jika sudah ada data mutasi dana untuk tahun ini
                $modalLama = $mutasiDanaTerakhir->modal;
                $hargaUnitSaatIni = ceil(
                    ($kinerjaPortofolioTerakhir->valuasi_saat_ini ?? 0) / $mutasiDanaTerakhir->jumlah_unit_penyertaan
                );

                $jumlahUnitPenyertaanBaru = ($jumlahSaldo / $hargaUnitSaatIni) + $mutasiDanaTerakhir->jumlah_unit_penyertaan;

                $mutasiDanaBaru = MutasiDana::create([
                    'user_id' => $userId,
                    'tahun' => $tahun,
                    'bulan' => $bulan,
                    'modal' => $modalLama,
                    'harga_unit' => $hargaUnitSaatIni,
                    'jumlah_unit_penyertaan' => $jumlahUnitPenyertaanBaru,
                    'alur_dana' => $jumlahSaldo,
                ]);

                // Tambahkan data ke Kinerja Portofolio
                $valuasiSaatIniBaru = ($kinerjaPortofolioTerakhir->valuasi_saat_ini ?? 0) + $jumlahSaldo;
                $yield = ($hargaUnitSaatIni - ($mutasiDanaTerakhir->harga_unit ?? 0)) / ($mutasiDanaTerakhir->harga_unit ?? 1);

                $kinerjaPortofolioBaru = KinerjaPortofolio::create([
                    'user_id' => $userId,
                    'transaksi_id' => $transaksiBaru->id, // ID transaksi akan ditambahkan di bawah
                    'valuasi_saat_ini' => $valuasiSaatIniBaru,
                    'yield' => $yield,
                ]);

                // Update atau tambahkan data ke Historis
                if ($historisTerkait) {
                    $historisTerkait->update([
                        'yield' => $yield,
                    ]);
                } else {
                    Historis::create([
                        'user_id' => $userId,
                        'tahun' => $tahun,
                        'bulan' => $bulan,
                        'yield' => $yield,
                    ]);
                }

                // Tambahkan data ke Portofolio
                $curPriceBaru = ($portofolioTerakhir->cur_price ?? 0) + $jumlahSaldo;

                Portofolio::create([
                    'user_id' => $userId,
                    'aset_id' => 1, // ID aset untuk "kas"
                    'kinerja_portofolio_id' => $kinerjaPortofolioBaru->id,
                    'volume' => 1,
                    'cur_price' => $curPriceBaru,
                ]);
            }

            // Tambahkan data ke Saldo
            $saldoBaru = Saldo::create([
                'user_id' => $userId,
                'tanggal' => $tanggal,
                'tipe_saldo' => 'masuk',
                'saldo' => $jumlahSaldo,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Top-up saldo berhasil diproses.',
                'data' => [
                    'saldo' => $saldoBaru,
                    'transaksi' => $transaksiBaru,
                    'mutasi_dana' => $mutasiDanaBaru ?? null,
                    'kinerja_portofolio' => $kinerjaPortofolioBaru ?? null,
                ],
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Tipe saldo tidak valid.',
        ], 400);
    }


    public function index(Request $request) {
        try {
            $saldo = Saldo::where('user_id', $request->auth['user']['id'])
                                ->get();
            return response()->json([
                'message' => 'Berhasil mendapatkan dana.',
                'auth' => $request->auth,
                'data' => [
                    'saldo' => $saldo
                ],
            ], Response::HTTP_OK);
        } catch (Exception $e) { 
            if($e instanceof ValidationException){
                return response()->json([
                    'message' => $e->getMessage(),
                    'auth' => $request->auth,
                    'errors' =>  $e->validator->errors(),
                ], Response::HTTP_BAD_REQUEST);
            }else{
                return response()->json([
                    'message' => $e->getMessage(),
                    'auth' => $request->auth
                ], Response::HTTP_BAD_REQUEST);
            }
        }
    }

    public function store(Request $request)
    {
        try{
            $request->validate([
                'tanggal' => 'required',
                'tipe_saldo' => 'required',
                'saldo' => 'required',
            ]);

            $tanggal = Carbon::parse($request->tanggal);

            // ambil tahun dan bulan
            $tahun = $tanggal->year;
            $bulan = $tanggal->month;
            $reqsaldo = (int) $request->saldo;
            $userId = $request->auth['user']['id'];

            // ambil informasi saldo user untuk pertama kali
            $cek_saldo = Saldo::where('user_id', $userId)
                ->first();

            // jika user telah memiliki saldo
            if ($cek_saldo) {

                // cek total saldo user
                $total_saldo = Saldo::where('user_id', $userId)
                    ->sum('saldo');
                
                // jika user telah memiliki saldo dan ada saldo masuk
                if ($request->tipe_saldo == 'masuk') {

                    // masukkan saldo masuk baru
                    $saldo = new Saldo();
                    $saldo->user_id = $userId;
                    $saldo->tanggal = $request->tanggal;
                    $saldo->tipe_saldo = $request->tipe_saldo;
                    $saldo->saldo = $reqsaldo;
                    $saldo->save();

                    // catat di transaksi pertama kali test
                    $transaksi = new Transaksi();
                    $transaksi->user_id = $userId;
                    $transaksi->aset_id = 1; // id 1 untuk kas
                    $transaksi->jenis_transaksi = 'kas'; // khusus untuk kas
                    $transaksi->tanggal = $request->tanggal;
                    $transaksi->volume = 1; // volume 1 karna kas
                    $transaksi->harga = $reqsaldo; // masuk sebagai kas
                    $transaksi->save();

                    // ambil informasi mutasi dana terakhir di tahun tersebut 
                    $mutasi = MutasiDana::where('user_id', $userId)
                        ->where('tahun', $tahun)
                        ->orderBy('created_at', 'desc')
                        ->first();

                    // ambil informasi kinerja portofolio terakhir untuk memperoleh valuasi
                    $kinerja = KinerjaPortofolio::where('user_id', $userId)
                        ->orderBy('created_at', 'desc')
                        ->first();

                        
                    $portofolio = Portofolio::where('user_id', $userId)
                        ->where('aset_id', 1)
                        ->orderBy('created_at', 'desc')
                        ->first();
                        
                    $histori = Historis::where('user_id', $userId)
                        ->where('tahun', $tahun)
                        ->first();
                        
                    // jika sudah terdapat mutasi
                    if ($mutasi) {

                        // tambah mutasi baru
                        $mutasi_baru = new MutasiDana();
                        $mutasi_baru->user_id = $userId;
                        $mutasi_baru->tahun = $tahun;
                        $mutasi_baru->bulan = $bulan;
                        $mutasi_baru->modal = $mutasi->modal; // menggunakan modal di tahun yang sama
                        $mutasi_baru->harga_unit = $mutasi->harga_unit;
                        
                        // mencari harga unit baru saat ini
                        $mutasi_baru->harga_unit_saat_ini = ceil($kinerja->valuasi_saat_ini / $mutasi->jumlah_unit_penyertaan);
                        
                        // mencari jumlah unit penyertaan baru
                        $mutasi_baru->jumlah_unit_penyertaan = ($reqsaldo / ($kinerja->valuasi_saat_ini / $mutasi->jumlah_unit_penyertaan)) + $mutasi->jumlah_unit_penyertaan;

                        $mutasi_baru->alur_dana = $reqsaldo;
                        $mutasi_baru->save();

                        // masukkan kinerja portofolio
                        $kinerja_baru = new KinerjaPortofolio();
                        $kinerja_baru->user_id = $userId;
                        $kinerja_baru->transaksi_id = $transaksi->id;
                            
                        $kinerja_baru->valuasi_saat_ini = $kinerja->valuasi_saat_ini + $reqsaldo;
                        $kinerja_baru->yield = ($mutasi_baru->harga_unit_saat_ini - $mutasi->harga_unit) / $mutasi->harga_unit;
                        $kinerja_baru->save();

                        // masukkan ke histori tahunan dan bulanan
                        $ht_tahun->yield = $kinerja_baru->yield;
                        $ht_tahun->save();

                        if ($ht_bulan) {
                            $ht_bulan->yield = $kinerja_baru->yield;
                            $ht_bulan->save();
                        } else {
                            $ht_bulan_baru = new HistorisBulanan();
                            $ht_bulan_baru->user_id = $userId;
                            $ht_bulan_baru->historis_tahunan_id = $ht_tahun->id;
                            $ht_bulan_baru->bulan = $bulan;
                            $ht_bulan_baru->yield = $kinerja_baru->yield;
                            $ht_bulan_baru->save();
                        }

                        // masukkan portofolio untuk pertama kali               
                        $portofolio_baru = new Portofolio();
                        $portofolio_baru->user_id = $userId;
                        $portofolio_baru->aset_id = 1; // karena kas
                        $portofolio_baru->kinerja_portofolio_id = $kinerja_baru->id;
                        $portofolio_baru->volume = 1; // karena kas
                        $portofolio_baru->cur_price = $portofolio->cur_price + $reqsaldo; // current price = harga = saldo kas
                        $portofolio_baru->save();
                    
                    // jika tidak ada mutasi di tahun tersebut
                    } else {

                        // START PINDAH DANA TAHUN BARU

                        $mutasi_terakhir = MutasiDana::where('user_id', $userId)
                            ->orderBy('created_at', 'desc')
                            ->first();

                        // masukkan mutasi dana untuk pertama kali di tahun tersebut
                        $mutasi_tahun_baru = new MutasiDana();
                        $mutasi_tahun_baru->user_id = $userId;
                        $mutasi_tahun_baru->tahun = $tahun;
                        $mutasi_tahun_baru->bulan = $bulan;

                        // menggunakan valuasi terakhir sebelum tahun tersebut
                        $mutasi_tahun_baru->modal = $kinerja->valuasi_saat_ini + $reqsaldo;

                        // mencari harga unit dan harga unit saat ini menggunakan valuasi dan jumalh unit penyertaan terakhir di tahun tersebut
                        $mutasi_tahun_baru->harga_unit = $mutasi_terakhir->harga_unit_saat_ini;
                        
                        // mencari jumlah unit penyertaan dengan 
                        $mutasi_tahun_baru->jumlah_unit_penyertaan = ceil(($kinerja->valuasi_saat_ini + $reqsaldo) / $mutasi_terakhir->harga_unit_saat_ini);

                        $mutasi_tahun_baru->harga_unit_saat_ini = ceil(($kinerja->valuasi_saat_ini + $reqsaldo) / (ceil($kinerja->valuasi_saat_ini + $reqsaldo) / $mutasi_terakhir->harga_unit_saat_ini));

                        $mutasi_tahun_baru->alur_dana = $reqsaldo;
                        $mutasi_tahun_baru->save();

                        // masukkan kinerja portofolio untuk pertama kali di tahun tersebut
                        $kinerja_tahun_baru = new KinerjaPortofolio();
                        $kinerja_tahun_baru->user_id = $userId;
                        $kinerja_tahun_baru->transaksi_id = $transaksi->id;
                        $kinerja_tahun_baru->valuasi_saat_ini = $kinerja->valuasi_saat_ini + $reqsaldo;
                        $kinerja_tahun_baru->yield = 0.00;
                        $kinerja_tahun_baru->save();

                        // masukkan ke histori tahunan dan bulanan
                        $ht_tahun_baru2 = new HistorisTahunan();
                        $ht_tahun_baru2->user_id = $userId;
                        $ht_tahun_baru2->tahun = $tahun;
                        $ht_tahun_baru2->yield = $kinerja_tahun_baru->yield;
                        $ht_tahun_baru2->save();
                       
                        $ht_bulan_baru2 = new HistorisBulanan();
                        $ht_bulan_baru2->user_id = $userId;
                        $ht_bulan_baru2->historis_tahunan_id = $ht_tahun_baru2->id;
                        $ht_bulan_baru2->bulan = $bulan;
                        $ht_bulan_baru2->yield = $kinerja_tahun_baru->yield;
                        $ht_bulan_baru2->save();
                        

                        // masukkan portofolio untuk pertama kali di tahun tersebut          
                        $portofolio_tahun_baru = new Portofolio();
                        $portofolio_tahun_baru->user_id = $userId;
                        $portofolio_tahun_baru->aset_id = 1; // karena kas
                        $portofolio_tahun_baru->kinerja_portofolio_id = $kinerja_tahun_baru->id;
                        $portofolio_tahun_baru->volume = 1; // karena kas
                        $portofolio_tahun_baru->cur_price = $kinerja_tahun_baru->valuasi_saat_ini; // current price = harga = saldo kas
                        $portofolio_tahun_baru->save();

                        // END PINDAH DANA TAHUN BARU
                    }

                // jika tipe saldo keluar dan jumlah saldo keluar mencukupi
                } else if ($request->tipe_saldo == 'keluar' && $request->saldo <= $total_saldo) {

                    // masukkan saldo keluar baru
                    $saldo = new Saldo();
                    $saldo->user_id = $userId;
                    $saldo->tanggal = $request->tanggal;
                    $saldo->tipe_saldo = $request->tipe_saldo;
                    $saldo->saldo = -$reqsaldo;
                    $saldo->save();

                    // catat di transaksi pertama kali
                    $transaksi = new Transaksi();
                    $transaksi->user_id = $userId;
                    $transaksi->aset_id = 1; // id 1 untuk kas
                    $transaksi->jenis_transaksi = 'kas'; // khusus untuk kas
                    $transaksi->tanggal = $request->tanggal;
                    $transaksi->volume = 1; // volume 1 karna kas
                    $transaksi->harga = -$reqsaldo; // masuk sebagai kas
                    $transaksi->save();

                    // ambil informasi mutasi dana terakhir di tahun tersebut 
                    $mutasi = MutasiDana::where('user_id', $userId)
                        ->where('tahun', $tahun)
                        ->orderBy('created_at', 'desc')
                        ->first();

                    // ambil informasi kinerja portofolio terakhir untuk memperoleh valuasi
                    $kinerja = KinerjaPortofolio::where('user_id', $userId)
                        ->orderBy('created_at', 'desc')
                        ->first();

                    $ht_tahun = HistorisTahunan::where('user_id', $userId)
                        ->where('tahun', $tahun)
                        ->first();

                    $ht_bulan = HistorisBulanan::where('user_id', $userId)
                        ->where('bulan', $bulan)
                        ->first();

                    $portofolio = Portofolio::where('user_id', $userId)
                        ->where('aset_id', 1)
                        ->orderBy('created_at', 'desc')
                        ->first();

                    // jika sudah terdapat mutasi
                    if ($mutasi) {

                        // tambah mutasi baru
                        $mutasi_baru = new MutasiDana();
                        $mutasi_baru->user_id = $userId;
                        $mutasi_baru->tahun = $tahun;
                        $mutasi_baru->bulan = $bulan;
                        $mutasi_baru->modal = $mutasi->modal; // menggunakan modal di tahun yang sama
                        $mutasi_baru->harga_unit = $mutasi->harga_unit;
                        
                        // mencari harga unit baru saat ini
                        $mutasi_baru->harga_unit_saat_ini = ceil($kinerja->valuasi_saat_ini / $mutasi->jumlah_unit_penyertaan);
                        
                        // mencari jumlah unit penyertaan baru
                        $mutasi_baru->jumlah_unit_penyertaan = ((-$reqsaldo) / ($kinerja->valuasi_saat_ini / $mutasi->jumlah_unit_penyertaan)) + $mutasi->jumlah_unit_penyertaan;

                        $mutasi_baru->alur_dana = -$reqsaldo;
                        $mutasi_baru->save();

                        // masukkan kinerja portofolio
                        $kinerja_baru = new KinerjaPortofolio();
                        $kinerja_baru->user_id = $userId;
                        $kinerja_baru->transaksi_id = $transaksi->id;
                            
                        $kinerja_baru->valuasi_saat_ini = $kinerja->valuasi_saat_ini + (-$reqsaldo);
                        $kinerja_baru->yield = ($mutasi_baru->harga_unit_saat_ini - $mutasi->harga_unit) / $mutasi->harga_unit;
                        $kinerja_baru->save();

                        $ht_tahun->yield = $kinerja_baru->yield;
                        $ht_tahun->save();

                        if ($ht_bulan) {
                            $ht_bulan->yield = $kinerja_baru->yield;
                            $ht_bulan->save();
                        } else {
                            $ht_bulan_baru = new HistorisBulanan();
                            $ht_bulan_baru->user_id = $userId;
                            $ht_bulan_baru->historis_tahunan_id = $ht_tahun->id;
                            $ht_bulan_baru->bulan = $bulan;
                            $ht_bulan_baru->yield = $kinerja_baru->yield;
                            $ht_bulan_baru->save();
                        }

                        // masukkan portofolio untuk pertama kali               
                        $portofolio_baru = new Portofolio();
                        $portofolio_baru->user_id = $userId;
                        $portofolio_baru->aset_id = 1; // karena kas
                        $portofolio_baru->kinerja_portofolio_id = $kinerja_baru->id;
                        $portofolio_baru->volume = 1; // karena kas
                        $portofolio_baru->cur_price = $portofolio->cur_price + (-$reqsaldo); // current price = harga = saldo kas
                        $portofolio_baru->save();
                    
                    // jika tidak ada mutasi di tahun tersebut
                    } else {

                        // START PINDAH DANA TAHUN BARU

                        $mutasi_terakhir = MutasiDana::where('user_id', $userId)
                            ->orderBy('created_at', 'desc')
                            ->first();

                        // masukkan mutasi dana untuk pertama kali di tahun tersebut
                        $mutasi_tahun_baru = new MutasiDana();
                        $mutasi_tahun_baru->user_id = $userId;
                        $mutasi_tahun_baru->tahun = $tahun;
                        $mutasi_tahun_baru->bulan = $bulan;

                        // menggunakan valuasi terakhir sebelum tahun tersebut
                        $mutasi_tahun_baru->modal = $kinerja->valuasi_saat_ini + (-$reqsaldo);

                        // mencari harga unit dan harga unit saat ini menggunakan valuasi dan jumalh unit penyertaan terakhir di tahun tersebut
                        $mutasi_tahun_baru->harga_unit = $mutasi_terakhir->harga_unit_saat_ini;

                        $mutasi_tahun_baru->jumlah_unit_penyertaan = ceil(($kinerja->valuasi_saat_ini + (-$reqsaldo)) / $mutasi_terakhir->harga_unit_saat_ini);

                        $mutasi_tahun_baru->harga_unit_saat_ini = ceil(($kinerja->valuasi_saat_ini + (-$reqsaldo)) / (ceil($kinerja->valuasi_saat_ini + $reqsaldo) / $mutasi_terakhir->harga_unit_saat_ini));

                        // mencari jumlah unit penyertaan dengan 

                        $mutasi_tahun_baru->alur_dana = -($reqsaldo);
                        $mutasi_tahun_baru->save();

                        // masukkan kinerja portofolio untuk pertama kali di tahun tersebut
                        $kinerja_tahun_baru = new KinerjaPortofolio();
                        $kinerja_tahun_baru->user_id = $userId;
                        $kinerja_tahun_baru->transaksi_id = $transaksi->id;
                        $kinerja_tahun_baru->valuasi_saat_ini = $kinerja->valuasi_saat_ini + (-$reqsaldo);
                        $kinerja_tahun_baru->yield = 0.00;
                        $kinerja_tahun_baru->save();

                        // masukkan ke histori tahunan dan bulanan
                        $ht_tahun_baru2 = new HistorisTahunan();
                        $ht_tahun_baru2->user_id = $userId;
                        $ht_tahun_baru2->tahun = $tahun;
                        $ht_tahun_baru2->yield = $kinerja_tahun_baru->yield;
                        $ht_tahun_baru2->save();
                       
                        $ht_bulan_baru2 = new HistorisBulanan();
                        $ht_bulan_baru2->user_id = $userId;
                        $ht_bulan_baru2->historis_tahunan_id = $ht_tahun_baru2->id;
                        $ht_bulan_baru2->bulan = $bulan;
                        $ht_bulan_baru2->yield = $kinerja_tahun_baru->yield;
                        $ht_bulan_baru2->save();

                        // masukkan portofolio untuk pertama kali di tahun tersebut          
                        $portofolio_tahun_baru = new Portofolio();
                        $portofolio_tahun_baru->user_id = $userId;
                        $portofolio_tahun_baru->aset_id = 1; // karena kas
                        $portofolio_tahun_baru->kinerja_portofolio_id = $kinerja_tahun_baru->id;
                        $portofolio_tahun_baru->volume = 1; // karena kas
                        $portofolio_tahun_baru->cur_price = $kinerja_tahun_baru->valuasi_saat_ini; // current price = harga = saldo kas
                        $portofolio_tahun_baru->save();

                        // END PINDAH DANA TAHUN BARU
                    }

                // jika tipe saldo keluar dan jumlah saldo keluar lebih sedikit dari total saldo yang ada
                } else if ($request->tipe_saldo == 'keluar' && $request->saldo > $total_saldo) {
                    return response()->json([
                        'message' => 'Dana tidak cukup.'
                    ], Response::HTTP_BAD_REQUEST);

                // jika tipe saldo adalah dividen
                } else if ($request->tipe_saldo == 'dividen') {

                }
            
            // jika belum terdapat saldo dan tipe saldo adalah masuk
            } else if ($request->tipe_saldo == 'masuk') {

                // masukkan saldo untuk pertama kali
                $saldo = new Saldo();
                $saldo->user_id = $userId;
                $saldo->tanggal = $request->tanggal;
                $saldo->tipe_saldo = $request->tipe_saldo;
                $saldo->saldo = $reqsaldo;
                $saldo->save();

                // catat di transaksi pertama kali
                $transaksi = new Transaksi();
                $transaksi->user_id = $userId;
                $transaksi->aset_id = 1; // id 1 untuk kas
                $transaksi->jenis_transaksi = 'kas'; // khusus untuk kas
                $transaksi->tanggal = $request->tanggal;
                $transaksi->volume = 1; // volume 1 karna kas
                $transaksi->harga = $reqsaldo; // masuk sebagai kas
                $transaksi->save();

                // masukkan mutasi dana untuk pertama kali
                $mutasi = new MutasiDana();
                $mutasi->user_id = $userId;
                $mutasi->tahun = $tahun;
                $mutasi->bulan = $bulan;
                $mutasi->modal = $reqsaldo;
                $mutasi->harga_unit = 1000;
                $mutasi->harga_unit_saat_ini = 1000;
                $mutasi->jumlah_unit_penyertaan = $reqsaldo / 1000;
                $mutasi->alur_dana = $reqsaldo;
                $mutasi->save();

                // masukkan kinerja portofolio untuk pertama kali
                $kinerja = new KinerjaPortofolio();
                $kinerja->user_id = $userId;
                $kinerja->transaksi_id = $transaksi->id;
                $kinerja->valuasi_saat_ini = $reqsaldo;
                $kinerja->yield = 0.00;
                $kinerja->save();

                // masukkan ke histori tahunan dan bulanan
                $ht_tahun = new HistorisTahunan();
                $ht_tahun->user_id = $userId;
                $ht_tahun->tahun = $tahun;
                $ht_tahun->yield = $kinerja->yield;
                $ht_tahun->save();

                $ht_bulan = new HistorisBulanan();
                $ht_bulan->user_id = $userId;
                $ht_bulan->historis_tahunan_id = $ht_tahun->id;
                $ht_bulan->bulan = $bulan;
                $ht_bulan->yield = $kinerja->yield;
                $ht_bulan->save();

                // masukkan portofolio untuk pertama kali               
                $portofolio = new Portofolio();
                $portofolio->user_id = $userId;
                $portofolio->aset_id = 1; // karena kas
                $portofolio->kinerja_portofolio_id = $kinerja->id;
                $portofolio->volume = 1; // karena kas
                $portofolio->cur_price = $reqsaldo; // current price = harga = saldo kas
                $portofolio->save();

            // jika belum terdapat saldo dan tipe saldo adalah top up dividen   
            } else if ($request->tipe_saldo == 'dividen') {
                
                  
            // jika belum terdapat saldo dan tipe saldo keluar
            } else if ($request->tipe_saldo == 'keluar') {
                return response()->json([
                    'message' => 'Tidak dapat melakukan penarikan karena belum terdapat dana.'
                ], Response::HTTP_BAD_REQUEST);
            }
                
            return response()->json([
                'message' => 'Berhasil kelola dana.',
                'auth' => $request->auth,
                'data' => [
                    'saldo' => $saldo,
                    'mutasi' => $mutasi_tahun_baru ?? $mutasi_baru ?? $mutasi,
                    'kinerja' => $kinerja_tahun_baru ?? $kinerja_baru ?? $kinerja,
                    'transaksi' => $tahun_baru ?? $transaksi_baru ?? $transaksi,
                    'portofolio' => $tahun_baru ?? $portofolio_baru ?? $portofolio,
                ],
            ], Response::HTTP_CREATED);
        } catch (Exception $e) {
            if($e instanceof ValidationException){
                return response()->json([
                    'message' => $e->getMessage(),
                    'auth' => $request->auth,
                    'errors' =>  $e->validator->errors(),
                ], Response::HTTP_BAD_REQUEST);
            }else{
                return response()->json([
                    'message' => $e->getMessage(),
                    'auth' => $request->auth
                ], Response::HTTP_BAD_REQUEST);
            }
        }
    }

    public function show(Request $request, $id)
    {
        try{
            $saldo = new Saldo();
            if($request->auth['user_type'] == 'user') {
                $saldo = $saldo->where('user_id', $request->auth['user']['id']);
            }
            $saldo = $saldo->findOrFail($id);
            return response()->json([
                'message' => 'Berhasil mendapatkan detail dana.',
                'auth' => $request->auth,
                'data' => [
                    'saldo' => $saldo
                ],
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            if($e instanceof ValidationException){
                return response()->json([
                    'message' => $e->getMessage(),
                    'auth' => $request->auth,
                    'errors' =>  $e->validator->errors(),
                ], Response::HTTP_BAD_REQUEST);
            }else{
                return response()->json([
                    'message' => $e->getMessage(),
                    'auth' => $request->auth
                ], Response::HTTP_BAD_REQUEST);
            }
        }
    }

    public function update(Request $request, $id)
    {
        try{
            $saldo = new Saldo();
            $saldo = $saldo->where('user_id', $request->auth['user']['id'])
                                   ->findOrFail($id);
            $request->validate([
                'user_id' => 'required',
                'tanggal' => 'required',
                'tipe_saldo' => 'required',
                'saldo' => 'required',
            ]);
            $saldo->user_id = $request->auth['user']['id'];
            $saldo->tipe_saldo = $request->tipe_saldo;
            $saldo->tanggal = $request->tanggal;
            $saldo->saldo = $request->saldo;
            $saldo->save();
            return response()->json([
                'message' => 'Berhasil mengubah dana.',
                'auth' => $request->auth,
                'data' => [
                    'saldo' => $saldo
                ],
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            if($e instanceof ValidationException){
                return response()->json([
                    'message' => $e->getMessage(),
                    'auth' => $request->auth,
                    'errors' =>  $e->validator->errors(),
                ], Response::HTTP_BAD_REQUEST);
            }else{
                return response()->json([
                    'message' => $e->getMessage(),
                    'auth' => $request->auth
                ], Response::HTTP_BAD_REQUEST);
            }
        }
    }

    public function destroy(Request $request)
    {
        try{
            $saldo = new Saldo();
            $saldo = $saldo->where('user_id', $request->auth['user']['id'])
                                   ->delete();
            return response()->json([
                'message' => 'Berhasil menghapus dana.',
                'auth' => $request->auth,
                'data' => [
                    'saldo' => $saldo
                ],
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            if($e instanceof ValidationException){
                return response()->json([
                    'message' => $e->getMessage(),
                    'auth' => $request->auth,
                    'errors' =>  $e->validator->errors(),
                ], Response::HTTP_BAD_REQUEST);
            }else{
                return response()->json([
                    'message' => $e->getMessage(),
                    'auth' => $request->auth
                ], Response::HTTP_BAD_REQUEST);
            }
        }
    }

    public function mutasiDana(Request $request) {
        try {
            $mutasi = new MutasiDana();
            if($request->auth['user_type'] == 'user') {
                $mutasi = $mutasi->where('user_id', $request->auth['user']['id']);
            }
            $mutasi = $mutasi->get();
            return response()->json([
                'message' => 'Berhasil mendapatkan mutasi.',
                'auth' => $request->auth,
                'data' => [
                    'mutasi' => $mutasi
                ],
            ], Response::HTTP_OK);
        } catch (Exception $e) { 
            if($e instanceof ValidationException){
                return response()->json([
                    'message' => $e->getMessage(),
                    'auth' => $request->auth,
                    'errors' =>  $e->validator->errors(),
                ], Response::HTTP_BAD_REQUEST);
            }else{
                return response()->json([
                    'message' => $e->getMessage(),
                    'auth' => $request->auth
                ], Response::HTTP_BAD_REQUEST);
            }
        }
    }
}
