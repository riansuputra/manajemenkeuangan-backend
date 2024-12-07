<?php

namespace App\Http\Controllers\API;

use Exception;
use Carbon\Carbon;
use App\Models\Saldo;
use App\Models\MutasiDana;
use App\Models\KinerjaPortofolio;
use App\Models\Portofolio;
use App\Models\Transaksi;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class SaldoController extends Controller
{
    public function index(Request $request) {
        try {
            $saldo = new Saldo();
            if($request->auth['user_type'] == 'user') {
                $saldo = $saldo->where('user_id', $request->auth['user']['id']);
            }
            $saldo = $saldo->sum('saldo');
            return response()->json([
                'message' => 'Berhasil mendapatkan saldo.',
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

            $tahun = $tanggal->year;
            $bulan = $tanggal->month;

            $cek_saldo = Saldo::where('user_id', $request->auth['user']['id'])
                              ->first();

            $mutasi = MutasiDana::where('user_id', $request->auth['user']['id'])
                             ->where('tahun', $tahun)
                             ->where('bulan', $bulan)
                             ->first();

            if ($cek_saldo) {
                $total_saldo = Saldo::where('user_id', $request->auth['user']['id'])
                                    ->sum('saldo');
                // dd($total_saldo);
                if ($request->tipe_saldo == 'keluar' && $request->saldo > $total_saldo) {
                    return response()->json([
                        'message' => 'Saldo tidak cukup.'
                    ], Response::HTTP_BAD_REQUEST);
                } else {
                    $saldo = new Saldo();
                    $saldo->user_id = $request->auth['user']['id'];
                    $saldo->tanggal = $request->tanggal;
                    $saldo->tipe_saldo = $request->tipe_saldo;
                    $saldo->saldo = $request->tipe_saldo == 'keluar' ? -($request->saldo) : $request->saldo;
                    $saldo->save();

                    $kinerja = KinerjaPortofolio::where('user_id', $request->auth['user']['id'])
                                                ->first();

                    // dd($kinerja->valuasi_saat_ini);
                    
                    $harga_unit = ceil($kinerja->valuasi_saat_ini / $mutasi->jumlah_unit_penyertaan);
                    $jumlah_unit_penyertaan = $mutasi->jumlah_unit_penyertaan + ($saldo->saldo / $harga_unit);
                    // dd($harga_unit, $jumlah_unit_penyertaan);
                    if ($mutasi) {
                        $mutasi->alur_dana += $saldo->saldo;
                        $mutasi->jumlah_unit_penyertaan = $jumlah_unit_penyertaan;
                        $mutasi->harga_unit_saat_ini = $kinerja->valuasi_saat_ini / $jumlah_unit_penyertaan;
                        $mutasi->save();
                        $kinerja->valuasi_saat_ini += $saldo->saldo;
                        $harga_unit_baru = ceil($kinerja->valuasi_saat_ini / $jumlah_unit_penyertaan);
                        $kinerja->yield = ($harga_unit_baru - $mutasi->harga_unit) / $mutasi->harga_unit;
                        $kinerja->save();
                    } else {
                        $mutasi_baru = new MutasiDana();
                        $mutasi_baru->user_id = $request->auth['user']['id'];
                        $mutasi_baru->tahun = $tahun;
                        $mutasi_baru->bulan = $bulan;
                        $mutasi_baru->harga_unit = 1000;
                        $mutasi_baru->harga_unit_saat_ini = $harga_unit;
                        $mutasi_baru->jumlah_unit_penyertaan = $saldo->saldo / 1000;
                        $mutasi_baru->alur_dana += $saldo->saldo;
                        $mutasi_baru->save();
                        
                        $kinerja->yield = ($mutasi_baru->harga_unit_saat_ini - $mutasi->harga_unit) / $mutasi->harga_unit;
                        $kinerja->save();
                    }

                    $transaksi = new Transaksi();
                    $transaksi->user_id = $request->auth['user']['id'];
                    $transaksi->aset_id = 1;
                    $transaksi->jenis_transaksi = 'kas';
                    $transaksi->tanggal = $request->tanggal;
                    $transaksi->volume = 1;
                    $transaksi->harga = $request->tipe_saldo == 'keluar' ? -($request->saldo) : $request->saldo;
                    $transaksi->save();

                    $portofolio = new Portofolio();
                    $portofolio = $portofolio->where('user_id', $request->auth['user']['id'])
                                             ->where('aset_id', 1)
                                             ->first();
                    $portofolio->cur_price += $saldo->saldo;
                    $portofolio->save();
                }
            } else if ($request->tipe_saldo == 'keluar') {
                return response()->json([
                    'message' => 'Tidak dapat melakukan penarikan karena belum terdapat saldo.'
                ], Response::HTTP_BAD_REQUEST);
            } else {
                $saldo = new Saldo();
                $saldo->user_id = $request->auth['user']['id'];
                $saldo->tanggal = $request->tanggal;
                $saldo->tipe_saldo = $request->tipe_saldo;
                $saldo->saldo = $request->tipe_saldo == 'keluar' ? -($request->saldo) : $request->saldo;
                $saldo->save();

                $mutasi_baru = new MutasiDana();
                $mutasi_baru->user_id = $request->auth['user']['id'];
                $mutasi_baru->tahun = $tahun;
                $mutasi_baru->bulan = $bulan;
                $mutasi_baru->modal = $request->saldo;
                $mutasi_baru->harga_unit = 1000;
                $mutasi_baru->harga_unit_saat_ini = 1000;
                $mutasi_baru->jumlah_unit_penyertaan = $request->saldo / 1000;
                $mutasi_baru->alur_dana = $request->tipe_saldo == 'masuk' ? $request->saldo : -($request->saldo);
                $mutasi_baru->save();

                $kinerja = new KinerjaPortofolio();
                $kinerja->user_id = $request->auth['user']['id'];
                $kinerja->mutasi_dana_id = $mutasi_baru->id;
                $kinerja->valuasi_saat_ini = $request->saldo;
                $kinerja->yield = 0.00;
                $kinerja->save();

                $transaksi = new Transaksi();
                $transaksi->user_id = $request->auth['user']['id'];
                $transaksi->aset_id = 1; 
                $transaksi->jenis_transaksi = 'kas';
                $transaksi->tanggal = $request->tanggal;
                $transaksi->volume = 1;
                $transaksi->harga = $request->saldo;
                $transaksi->save();
                
                $portofolio = new Portofolio();
                $portofolio->user_id = $request->auth['user']['id'];
                $portofolio->kinerja_portofolio_id = $kinerja->id;
                $portofolio->aset_id = 1; 
                $portofolio->volume = 1;
                $portofolio->cur_price = $request->saldo;
                $portofolio->save();
            }
            
            return response()->json([
                'message' => 'Berhasil menambah saldo.',
                'auth' => $request->auth,
                'data' => [
                    'saldo' => $saldo,
                    'mutasi' => $mutasi_baru ?? $mutasi,
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
                'message' => 'Berhasil mendapatkan detail saldo.',
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
                'message' => 'Berhasil mengubah saldo.',
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
                'message' => 'Berhasil menghapus saldo.',
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
