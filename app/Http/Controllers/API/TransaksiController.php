<?php

namespace App\Http\Controllers\API;

use Exception;
use App\Models\Saldo;
use App\Models\MutasiDana;
use App\Models\KinerjaPortofolio;
use App\Models\Portofolio;
use App\Models\HistorisBulanan;
use App\Models\HistorisTahunan;
use App\Models\Transaksi;
use App\Models\Aset;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Validation\ValidationException;

class TransaksiController extends Controller
{
    public function store(Request $request)
    {
        try {
            $userId = $request->auth['user']['id'];
            $tanggal = $request->input('tanggal');
            $volume = $request->input('volume');
            $harga = $request->input('harga');
            $totalHarga = $volume * $harga;
            $asetId = $request->input('aset_id');

            // Cek saldo user
            $saldoUser = Saldo::where('user_id', $userId)->sum('saldo');
            if ($totalHarga > $saldoUser) {
                return response()->json(['error' => 'Saldo tidak mencukupi'], 400);
            }

            // Kurangi saldo user
            $saldo = Saldo::create([
                'user_id' => $userId,
                'tanggal' => $tanggal,
                'saldo' => -$totalHarga,
                'tipe_saldo' => 'keluar',
            ]);

            // Tambahan untuk aset kas (aset_id = 1)
            // Ambil kinerja_portofolio terakhir
            $kinerjaPortofolioTerakhir = KinerjaPortofolio::where('user_id', $userId)
                ->orderBy('id', 'desc')
                ->first();

            $transaksiKas = Transaksi::create([
                'user_id' => $userId,
                'tanggal' => $tanggal,
                'jenis_transaksi' => 'kas',
                'volume' => 1,
                'harga' => -$totalHarga,
                'aset_id' => 1, // Aset kas
                'deskripsi' => 'Berkurang beli aset', 
            ]);

            $kinerjaPortofolioKas = KinerjaPortofolio::create([
                'user_id' => $userId,
                'transaksi_id' => $transaksiKas->id,
                'valuasi_saat_ini' => ($kinerjaPortofolioTerakhir->valuasi_saat_ini ?? 0) - $totalHarga,
                'yield' => $kinerjaPortofolioTerakhir->yield ?? 0.0,
            ]);

            Portofolio::create([
                'user_id' => $userId,
                'aset_id' => 1, // Aset kas
                'volume' => 1,
                'avg_price' => null, // Kosongkan
                'cur_price' => $kinerjaPortofolioKas->valuasi_saat_ini - $totalHarga,
                'kinerja_portofolio_id' => $kinerjaPortofolioKas->id,
            ]);

            // Buat transaksi baru
            $transaksi = Transaksi::create([
                'user_id' => $userId,
                'tanggal' => $tanggal,
                'jenis_transaksi' => 'beli',
                'volume' => $volume,
                'harga' => $harga,
                'aset_id' => $asetId,
                'deskripsi' => 'Beli Aset '.$asetId,
            ]);

            // Tambahkan data ke kinerja_portofolio
            $kinerjaPortofolio = KinerjaPortofolio::create([
                'user_id' => $userId,
                'transaksi_id' => $transaksi->id,
                'valuasi_saat_ini' => ($kinerjaPortofolioKas->valuasi_saat_ini ?? 0) + $totalHarga,
                'yield' => $kinerjaPortofolioKas->yield ?? 0.0,
            ]);

            // Cek apakah aset sudah ada di portofolio user
            $portofolio = Portofolio::where('user_id', $userId)
                ->where('aset_id', $asetId)
                ->first();

            if ($portofolio) {
                // Jika aset sudah ada di portofolio
                $volumeBaru = $portofolio->volume + $volume;
                $totalHargaSebelumnya = $portofolio->avg_price * $portofolio->volume;
                $avgPriceBaru = ($totalHargaSebelumnya + $totalHarga) / $volumeBaru;

                Portofolio::create([
                    'user_id' => $userId,
                    'aset_id' => $asetId,
                    'volume' => $volumeBaru,
                    'avg_price' => $avgPriceBaru,
                    'cur_price' => $harga,
                    'kinerja_portofolio_id' => $kinerjaPortofolio->id,
                ]);
            } else {
                // Jika aset belum ada di portofolio
                Portofolio::create([
                    'user_id' => $userId,
                    'aset_id' => $asetId,
                    'volume' => $volume,
                    'avg_price' => $harga,
                    'cur_price' => $harga,
                    'kinerja_portofolio_id' => $kinerjaPortofolio->id,
                ]);
            }

            return response()->json([
                'message' => 'Berhasil mendapatkan transaksi.',
                'auth' => $request->auth,
                'data' => [
                    'transaksi' => $transaksi,
                    'saldo' => $saldo,
                    'portofolio' => Portofolio::where('user_id', $userId)
                        ->latest()
                        ->get(),
                    'kinerja_portofolio' => $kinerjaPortofolio,
                ],
            ], Response::HTTP_OK);

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


    


    public function index(Request $request) 
    {
        try {
            $transaksi = new Transaksi();
            if($request->auth['user_type'] == 'user') {
                $transaksi = $transaksi->where('user_id', $request->auth['user']['id']);
            }
            $transaksi = $transaksi->with(['aset', 'sekuritas'])
                                   ->get();
            return response()->json([
                'message' => 'Berhasil mendapatkan transaksi.',
                'auth' => $request->auth,
                'data' => [
                    'transaksi' => $transaksi
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

    public function store1(Request $request)
    {
        try{
            $request->validate([
                'aset_id' => 'required',
                'sekuritas_id' => 'nullable',
                'jenis_transaksi' => 'required',
                'tanggal' => 'required',
                'volume' => 'nullable',
                'harga' => 'nullable',
                'deskripsi' => 'nullable',
                // 'cur_price' => 'nullable',
                // 'avg_price' => 'nullable',
            ]);

            $tanggal = Carbon::parse($request->tanggal);

            // ambil tahun dan bulan
            $tahun = $tanggal->year;
            $bulan = $tanggal->month;

            $userId = $request->auth['user']['id'];
            $totalHarga = $request->volume * $request->harga;
            // Calculate total balance
            $totalSaldo = Saldo::where('user_id', $userId)->sum('saldo');

            $kinerja = KinerjaPortofolio::where('user_id', $request->auth['user']['id'])
                        ->orderBy('created_at', 'desc')
                        ->first();

            $mutasi = MutasiDana::where('user_id', $request->auth['user']['id'])
                        ->orderBy('created_at', 'desc')
                        ->first();

            $ht_tahun = HistorisTahunan::where('user_id', $request->auth['user']['id'])
                ->where('tahun', $tahun)
                ->first();

            $ht_bulan = HistorisBulanan::where('user_id', $request->auth['user']['id'])
                ->where('bulan', $bulan)
                ->first();

            $portofolio = Portofolio::where('user_id', $request->auth['user']['id'])
                ->where('aset_id', $request->aset_id)
                ->orderBy('created_at', 'desc')
                ->first();

            // untuk saham beli, deposit tabungan
            if ($totalSaldo > 0 && $totalHarga <= $totalSaldo) {

                $transaksi = new Transaksi();
                $transaksi->user_id = $userId;
                $transaksi->aset_id = $request->aset_id;
                $transaksi->sekuritas_id = $request->sekuritas_id;
                $transaksi->jenis_transaksi = $request->jenis_transaksi;
                $transaksi->tanggal = $request->tanggal;
                $transaksi->volume = $request->volume;
                $transaksi->harga = $request->harga;
                $transaksi->deskripsi = $request->deskripsi;
                $transaksi->save();

                if ($request->jenis_transaksi == 'beli') {

                    // masukkan kinerja portofolio untuk pertama kali
                    $kinerja_baru = new KinerjaPortofolio();
                    $kinerja_baru->user_id = $userId;
                    $kinerja_baru->transaksi_id = $transaksi->id;
                    $kinerja_baru->valuasi_saat_ini = $kinerja->valuasi_saat_ini + $totalHarga;

                    $harga_unit_saat_ini = ceil(($kinerja->valuasi_saat_ini + $totalHarga) / $mutasi->jumlah_unit_penyertaan);
                    // dd($harga_unit_saat_ini, $mutasi->harga_unit, (($harga_unit_saat_ini - $mutasi->harga_unit) / $mutasi->harga_unit));

                    $mutasi->harga_unit_saat_ini = $harga_unit_saat_ini;
                    $mutasi->save();


                    $kinerja_baru->yield = ($harga_unit_saat_ini - $mutasi->harga_unit) / $mutasi->harga_unit;
                    $kinerja_baru->save();

                    if ($ht_tahun) {
                        $ht_tahun->yield = $kinerja_baru->yield;
                        $ht_tahun->save();

                        if ($ht_bulan) {
                            $ht_bulan->yield = $kinerja_baru->yield;
                            $ht_bulan->save();
                        } else {
                            $ht_bulan_baru = new HistorisBulanan();
                            $ht_bulan_baru->user_id = $request->auth['user']['id'];
                            $ht_bulan_baru->historis_tahunan_id = $ht_tahun->id;
                            $ht_bulan_baru->bulan = $bulan;
                            $ht_bulan_baru->yield = $kinerja_baru->yield;
                            $ht_bulan_baru->save();
                        }
                    } else {
                        $ht_tahun_baru = new HistorisTahunan();
                        $ht_tahun_baru->user_id = $request->auth['user']['id'];
                        $ht_tahun_baru->bulan = $bulan;
                        $ht_tahun_baru->yield = $kinerja_baru->yield;
                        $ht_tahun_baru->save();

                        $ht_bulan_baru = new HistorisBulanan();
                        $ht_bulan_baru->user_id = $request->auth['user']['id'];
                        $ht_bulan_baru->historis_tahunan_id = $ht_tahun_baru->id;
                        $ht_bulan_baru->bulan = $bulan;
                        $ht_bulan_baru->yield = $kinerja_baru->yield;
                        $ht_bulan_baru->save();
                    }

                    // jika sudah ada pembelian saham sebelumnya
                    if ($portofolio) {
                        $portofolio_baru = new Portofolio();
                        $portofolio_baru->user_id = $userId;
                        $portofolio_baru->aset_id = $request->aset_id; 
                        $portofolio_baru->kinerja_portofolio_id = $kinerja_baru->id;
                        $portofolio_baru->volume = $portofolio->volume + $request->volume; 
                        $portofolio_baru->avg_price = ($portofolio->volume * $portofolio->cur_price) / ($request->volume * $request->harga); 
                        $portofolio_baru->cur_price = $request->harga; 
                        $portofolio_baru->save();

                    // jika belum ada
                    } else {
                        $portofolio_baru = new Portofolio();
                        $portofolio_baru->user_id = $userId;
                        $portofolio_baru->aset_id = $request->aset_id; 
                        $portofolio_baru->kinerja_portofolio_id = $kinerja_baru->id;
                        $portofolio_baru->volume = $request->volume; 
                        $portofolio_baru->avg_price = $request->harga; 
                        $portofolio_baru->cur_price = $request->harga; 
                        $portofolio_baru->save();

                    }

                    

                } else if ($request->jenis_transaksi == 'deposit') {

                }


                return response()->json([
                    'total_saldo' => $totalSaldo
                ], Response::HTTP_OK);
            } else if ($totalSaldo) {

            } else {
                return response()->json([
                    'error' => 'Saldo user tidak ditemukan atau tidak mencukupi.'
                ], Response::HTTP_NOT_FOUND);
            }
            

            if (!$saldo) {
                return response()->json([
                    'error' => 'Saldo tidak ditemukan untuk user.'
                ], Response::HTTP_NOT_FOUND);
            } else if ($request['harga']) {
                $total = $request['volume'] * $request['harga'];
                if ($saldo >= $total) {
                    $addsaldo = Saldo::create([
                        'user_id' => $request->auth['user']['id'],
                        'tanggal' => Carbon::now()->format('Y-m-d'),
                        'tipe_saldo' => 'keluar',
                        'saldo' => -($total)
                    ]);
                }
            } else if (!($request['harga'])) {
                if ($saldo >= $request['volume']) {
                    $addsaldo = Saldo::create([
                        'user_id' => $request->auth['user']['id'],
                        'tanggal' => Carbon::now()->format('Y-m-d'),
                        'tipe_saldo' => 'keluar',
                        'saldo' => -($request['volume'])
                    ]);
                }
            } else {
                return response()->json([
                    'message' => 'Saldo tidak cukup.'
                ], Response::HTTP_BAD_REQUEST);
            }

            $transaksi = new Transaksi();
            $transaksi->user_id = $request->auth['user']['id'];
            $transaksi->aset_id = $request->aset_id;
            $transaksi->sekuritas_id = $request->sekuritas_id;
            $transaksi->jenis_transaksi = $request->jenis_transaksi;
            $transaksi->tanggal = $request->tanggal;
            $transaksi->volume = $request->volume;
            $transaksi->harga = $request->harga;
            $transaksi->deskripsi = $request->deskripsi;
            $transaksi->save();
            
            $portofolio = Portofolio::where('user_id', $request->auth['user']['id'])
                                    ->where('aset_id', $request->aset_id)
                                    ->first();

            if ($portofolio) {
                $aset = $portofolio->aset;
                if ($aset) {
                    if ($aset->tipe_aset === 'saham') {
                        $portofolio->volume += $request->volume;
                        $total_value_before = $portofolio->avg_price * ($portofolio->volume - $request->volume);
                        $total_value_now = $request->harga * $request->volume;
                        $portofolio->avg_price = ($total_value_before + $total_value_now) / $portofolio->volume;
                        $portofolio->cur_price = $request->harga;
                    } elseif ($aset->tipe_aset === 'deposito') {
                        $portofolio->volume += $request->volume;
                    } else {
                        $portofolio->volume += $request->volume;
                    }
                }
            } else {
                $portofolio = new Portofolio();
                $portofolio->user_id = $request->auth['user']['id'];
                $portofolio->aset_id = $request->aset_id;
                $portofolio->volume = $request->volume;
                $portofolio->avg_price = $request->harga;
                $portofolio->cur_price = $request->harga;
            }
            $portofolio->save();

            return response()->json([
                'message' => 'Berhasil menambah transaksi.',
                'auth' => $request->auth,
                'data' => [
                    'transaksi' => $transaksi
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
            $pemasukan = new Pemasukan();
            if($request->auth['user_type'] == 'user') {
                $pemasukan = $pemasukan->where('user_id', $request->auth['user']['id']);
            }
            $pemasukan = $pemasukan->findOrFail($id);
            return response()->json([
                'message' => 'Berhasil mendapatkan detail pemasukan.',
                'auth' => $request->auth,
                'data' => [
                    'pemasukan' => $pemasukan
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
            $pemasukan = new Pemasukan();
            $pemasukan = $pemasukan->where('user_id', $request->auth['user']['id'])
                                   ->findOrFail($id);
            $request->validate([
                'kategori_pemasukan_id' => 'required',
                'tanggal' => 'required',
                'jumlah' => 'required',
                'catatan' => 'nullable',
            ]);
            $pemasukan->kategori_pemasukan_id = $request->kategori_pemasukan_id;
            $pemasukan->tanggal = $request->tanggal;
            $pemasukan->jumlah = $request->jumlah;
            $pemasukan->catatan = $request->catatan;
            $pemasukan->save();
            return response()->json([
                'message' => 'Berhasil mengubah pemasukan.',
                'auth' => $request->auth,
                'data' => [
                    'pemasukan' => $pemasukan
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

    public function destroy(Request $request, $id)
    {
        try{
            $pemasukan = new Pemasukan();
            $pemasukan = $pemasukan->where('user_id', $request->auth['user']['id'])
                                   ->findOrFail($id)
                                   ->delete();
            return response()->json([
                'message' => 'Berhasil menghapus pemasukan.',
                'auth' => $request->auth,
                'data' => [
                    'pemasukan' => $pemasukan
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