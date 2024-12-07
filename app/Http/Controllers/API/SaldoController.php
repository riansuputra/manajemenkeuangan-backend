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

            // ambil tahun dan bulan
            $tahun = $tanggal->year;
            $bulan = $tanggal->month;

            // ambil informasi saldo user untuk pertama kali
            $cek_saldo = Saldo::where('user_id', $request->auth['user']['id'])
                ->first();

            // jika user telah memiliki saldo
            if ($cek_saldo) {

                // cek total saldo user
                $total_saldo = Saldo::where('user_id', $request->auth['user']['id'])
                    ->sum('saldo');
                
                // jika user telah memiliki saldo dan ada saldo masuk
                if ($request->tipe_saldo == 'masuk') {

                    // masukkan saldo baru
                    $saldo = new Saldo();
                    $saldo->user_id = $request->auth['user']['id'];
                    $saldo->tanggal = $request->tanggal;
                    $saldo->tipe_saldo = $request->tipe_saldo;
                    $saldo->saldo = $request->saldo;
                    $saldo->save();

                    // catat di transaksi pertama kali
                    $transaksi = new Transaksi();
                    $transaksi->user_id = $request->auth['user']['id'];
                    $transaksi->aset_id = 1; // id 1 untuk kas
                    $transaksi->jenis_transaksi = 'kas'; // khusus untuk kas
                    $transaksi->tanggal = $request->tanggal;
                    $transaksi->volume = 1; // volume 1 karna kas
                    $transaksi->harga = $request->saldo; // masuk sebagai kas
                    $transaksi->save();

                    // ambil informasi mutasi dana terakhir di tahun tersebut 
                    $mutasi = MutasiDana::where('user_id', $request->auth['user']['id'])
                        ->where('tahun', $tahun)
                        ->orderBy('created_at', 'desc')
                        ->first();

                    // ambil informasi kinerja portofolio terakhir untuk memperoleh valuasi
                    $kinerja = KinerjaPortofolio::where('user_id', $request->auth['user']['id'])
                        ->orderBy('created_at', 'desc')
                        ->first();

                    $portofolio = Portofolio::where('user_id', $request->auth['user']['id'])
                        ->where('aset_id', 1)
                        ->orderBy('created_at', 'desc')
                        ->first();

                    // jika sudah terdapat mutasi
                    if ($mutasi) {

                        // tambah mutasi baru
                        $mutasi_baru = new MutasiDana();
                        $mutasi_baru->user_id = $request->auth['user']['id'];
                        $mutasi_baru->tahun = $tahun;
                        $mutasi_baru->bulan = $bulan;
                        $mutasi_baru->modal = $mutasi->modal; // menggunakan modal di tahun yang sama
                        $mutasi_baru->harga_unit = $mutasi->harga_unit;
                        
                        // mencari harga unit baru saat ini
                        $mutasi_baru->harga_unit_saat_ini = ceil($kinerja->valuasi_saat_ini / $mutasi->jumlah_unit_penyertaan);
                        
                        // mencari jumlah unit penyertaan baru
                        $mutasi_baru->jumlah_unit_penyertaan = ($request->saldo / ($kinerja->valuasi_saat_ini / $mutasi->jumlah_unit_penyertaan)) + $mutasi->jumlah_unit_penyertaan;

                        $mutasi_baru->alur_dana = $request->saldo;
                        $mutasi_baru->save();

                        // masukkan kinerja portofolio
                        $kinerja_baru = new KinerjaPortofolio();
                        $kinerja_baru->user_id = $request->auth['user']['id'];
                        $kinerja_baru->transaksi_id = $transaksi->id;

                        $kinerja_baru->valuasi_saat_ini = $kinerja->valuasi_saat_ini + $request->saldo;
                        $kinerja_baru->yield = ($mutasi_baru->harga_unit_saat_ini - $mutasi->harga_unit) / $mutasi->harga_unit;
                        $kinerja_baru->save();

                        // masukkan portofolio untuk pertama kali               
                        $portofolio_baru = new Portofolio();
                        $portofolio_baru->user_id = $request->auth['user']['id'];
                        $portofolio_baru->aset_id = 1; // karena kas
                        $portofolio_baru->kinerja_portofolio_id = $kinerja_baru->id;
                        $portofolio_baru->volume = 1; // karena kas
                        $portofolio_baru->cur_price = $portofolio->cur_price + $request->saldo; // current price = harga = saldo kas
                        $portofolio_baru->save();
                    
                    // jika tidak ada mutasi di tahun tersebut
                    } else {

                        // START PINDAH DANA TAHUN BARU

                        // masukkan mutasi dana untuk pertama kali di tahun tersebut
                        $mutasi_tahun_baru = new MutasiDana();
                        $mutasi_tahun_baru->user_id = $request->auth['user']['id'];
                        $mutasi_tahun_baru->tahun = $tahun;
                        $mutasi_tahun_baru->bulan = $bulan;

                        // menggunakan valuasi terakhir sebelum tahun tersebut
                        $mutasi_tahun_baru->modal = $kinerja->valuasi_saat_ini;

                        // mencari harga unit dan harga unit saat ini menggunakan valuasi dan jumalh unit penyertaan terakhir di tahun tersebut
                        $mutasi_tahun_baru->harga_unit = ceil($kinerja->valuasi_saat_ini / $mutasi->jumlah_unit_penyertaan);
                        $mutasi_tahun_baru_baru->harga_unit_saat_ini = ceil($kinerja->valuasi_saat_ini / $mutasi->jumlah_unit_penyertaan);

                        // mencari jumlah unit penyertaan dengan 
                        $mutasi_tahun_baru_baru->jumlah_unit_penyertaan = $kinerja->valuasi_saat_ini / ($kinerja->valuasi_saat_ini / $mutasi->jumlah_unit_penyertaan);

                        $mutasi_tahun_baru->alur_dana = $kinerja->valuasi_saat_ini;
                        $mutasi_tahun_baru->save();

                        // masukkan kinerja portofolio untuk pertama kali di tahun tersebut
                        $kinerja_tahun_baru = new KinerjaPortofolio();
                        $kinerja_tahun_baru->user_id = $request->auth['user']['id'];
                        $kinerja_tahun_baru->transaksi_id = $transaksi->id;
                        $kinerja_tahun_baru->valuasi_saat_ini = $kinerja->valuasi_saat_ini;
                        $kinerja_tahun_baru->yield = 0.00;
                        $kinerja_tahun_baru->save();

                        // masukkan portofolio untuk pertama kali di tahun tersebut          
                        $portofolio_tahun_baru = new Portofolio();
                        $portofolio_tahun_baru->user_id = $request->auth['user']['id'];
                        $portofolio_tahun_baru->aset_id = 1; // karena kas
                        $portofolio_tahun_baru->kinerja_portofolio_id = $kinerja_tahun_baru->id;
                        $portofolio_tahun_baru->volume = 1; // karena kas
                        $portofolio_tahun_baru->cur_price = $kinerja_tahun_baru->valuasi_saat_ini; // current price = harga = saldo kas
                        $portofolio_tahun_baru->save();

                        // END PINDAH DANA TAHUN BARU




                    }

                    

                


                // jika tipe saldo keluar dan jumlah saldo keluar mencukupi
                } else if ($request->tipe_saldo == 'keluar' && $request->saldo <= $total_saldo) {
                    

                // jika tipe saldo keluar dan jumlah saldo keluar lebih sedikit dari total saldo yang ada
                } else if ($request->tipe_saldo == 'keluar' && $request->saldo > $total_saldo) {
                    return response()->json([
                        'message' => 'Saldo tidak cukup.'
                    ], Response::HTTP_BAD_REQUEST);

                // jika tipe saldo adalah dividen
                } else if ($request->tipe_saldo == 'dividen') {
                    

                } else if ('tes') {
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
            
            // jika belum terdapat saldo dan tipe saldo adalah masuk
            } else if ($request->tipe_saldo == 'masuk') {

                // masukkan saldo untuk pertama kali
                $saldo = new Saldo();
                $saldo->user_id = $request->auth['user']['id'];
                $saldo->tanggal = $request->tanggal;
                $saldo->tipe_saldo = $request->tipe_saldo;
                $saldo->saldo = $request->saldo;
                $saldo->save();

                // catat di transaksi pertama kali
                $transaksi = new Transaksi();
                $transaksi->user_id = $request->auth['user']['id'];
                $transaksi->aset_id = 1; // id 1 untuk kas
                $transaksi->jenis_transaksi = 'kas'; // khusus untuk kas
                $transaksi->tanggal = $request->tanggal;
                $transaksi->volume = 1; // volume 1 karna kas
                $transaksi->harga = $request->saldo; // masuk sebagai kas
                $transaksi->save();

                // masukkan mutasi dana untuk pertama kali
                $mutasi = new MutasiDana();
                $mutasi->user_id = $request->auth['user']['id'];
                $mutasi->tahun = $tahun;
                $mutasi->bulan = $bulan;
                $mutasi->modal = $request->saldo;
                $mutasi->harga_unit = 1000;
                $mutasi->harga_unit_saat_ini = 1000;
                $mutasi->jumlah_unit_penyertaan = $request->saldo / 1000;
                $mutasi->alur_dana = $request->saldo;
                $mutasi->save();

                // masukkan kinerja portofolio untuk pertama kali
                $kinerja = new KinerjaPortofolio();
                $kinerja->user_id = $request->auth['user']['id'];
                $kinerja->transaksi_id = $transaksi->id;
                $kinerja->valuasi_saat_ini = $request->saldo;
                $kinerja->yield = 0.00;
                $kinerja->save();

                // masukkan portofolio untuk pertama kali               
                $portofolio = new Portofolio();
                $portofolio->user_id = $request->auth['user']['id'];
                $portofolio->aset_id = 1; // karena kas
                $portofolio->kinerja_portofolio_id = $kinerja->id;
                $portofolio->volume = 1; // karena kas
                $portofolio->cur_price = $request->saldo; // current price = harga = saldo kas
                $portofolio->save();

            // jika belum terdapat saldo dan tipe saldo adalah top up dividen   
            } else if ($request->tipe_saldo == 'dividen') {
                return response()->json([
                    'message' => 'Tidak dapat melakukan penarikan karena belum terdapat saldo.'
                ], Response::HTTP_BAD_REQUEST);
                  
            // jika belum terdapat saldo dan tipe saldo keluar
            } else if ($request->tipe_saldo == 'keluar') {
                return response()->json([
                    'message' => 'Tidak dapat melakukan penarikan karena belum terdapat saldo.'
                ], Response::HTTP_BAD_REQUEST);

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
