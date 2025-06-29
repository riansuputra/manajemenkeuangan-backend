<?php

namespace App\Http\Controllers\API;

use Exception;
use App\Models\Saldo;
use App\Models\MutasiDana;
use App\Models\KinerjaPortofolio;
use App\Models\Portofolio;
use App\Models\Historis;
use App\Models\TutupBuku;
use App\Models\Transaksi;
use App\Models\PerubahanHarga;
use App\Models\Aset;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

class TransaksiController extends Controller
{
    public function indexAll(Request $request)
    {
        try {
            $transaksi = Transaksi::all();
            return response()->json([
                'message' => 'Berhasil mendapatkan transaksi semua.',
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
    
    public function store(Request $request)
    {
        try {
            $kodeGrup = Str::uuid();

            $request->validate([
                'volume' => 'required|min:1',
                'harga' => 'required|min:1',
            ]);
            
            if ($request->input('jenis_transaksi') === 'beli') {
                $userId = $request->auth['user']['id'];
                $tanggal = $request->input('tanggal');
                $volume = $request->input('volume');
                $harga = $request->input('harga');
                $totalHarga = $volume * $harga;
                $asetId = $request->input('aset_id');
                $sekuritasId = $request->input('sekuritas');
            
                // Cek saldo user
                $saldoUser = Saldo::where('user_id', $userId)->sum('saldo');
            
                if ($totalHarga > $saldoUser) {
                    return response()->json([
                        'error' => 'Saldo tidak mencukupi',
                        'message' => 'Saldo tidak mencukupi'
                    ], Response::HTTP_BAD_REQUEST);
                }
            
                $kinerjaPortofolioTerakhir = KinerjaPortofolio::where('user_id', $userId)
                    ->orderBy('id', 'desc')
                    ->first();

                $mutasiDanaTerakhir = MutasiDana::where('user_id', $userId)
                    ->orderByDesc('id')
                    ->first();
                
                    $transaksiKas = Transaksi::create([
                        'user_id' => $userId,
                        'kode_grup_transaksi' => $kodeGrup,
                        'tanggal' => $tanggal,
                        'jenis_transaksi' => 'kas',
                        'volume' => 1,
                        'harga' => -$totalHarga,
                        'aset_id' => 1, // Aset kas
                        'deskripsi' => 'Berkurang beli aset', 
                    ]);                

                $saldo = Saldo::create([
                    'user_id' => $userId,
                    'kode_grup_transaksi' => $kodeGrup,
                    'transaksi_id' => $transaksiKas->id,
                    'tanggal' => $tanggal,
                    'saldo' => -$totalHarga,
                    'tipe_saldo' => 'keluar',
                ]);

                $kinerjaPortofolioKas = KinerjaPortofolio::create([
                    'user_id' => $userId,
                    'tanggal' => $tanggal,
                    'kode_grup_transaksi' => $kodeGrup,
                    'transaksi_id' => $transaksiKas->id,
                    'valuasi_saat_ini' => ($kinerjaPortofolioTerakhir->valuasi_saat_ini ?? 0) - $totalHarga,
                    'yield' => $kinerjaPortofolioTerakhir->yield ?? 0.00,
                ]);

                $portofolio = Portofolio::where('user_id', $userId)
                    ->where('aset_id', $asetId)
                    ->orderBy('id', 'desc')
                    ->first();

                $portofolioKas = Portofolio::where('user_id', $userId)
                    ->where('aset_id', 1)
                    ->orderBy('id', 'desc')
                    ->first();

                $curPrice = $portofolioKas->cur_price - $totalHarga;

                $portofolioKasTerakhir = Portofolio::create([
                    'user_id' => $userId,
                    'tanggal' => $tanggal,
                    'kode_grup_transaksi' => $kodeGrup, 
                    'aset_id' => 1, 
                    'volume' => 1,
                    'avg_price' => null,
                    'cur_price' => $curPrice,
                    'kinerja_portofolio_id' => $kinerjaPortofolioKas->id,
                ]);

                $transaksi = Transaksi::create([
                    'user_id' => $userId,
                    'kode_grup_transaksi' => $kodeGrup,
                    'tanggal' => $tanggal,
                    'jenis_transaksi' => 'beli',
                    'volume' => $volume,
                    'harga' => $harga,
                    'aset_id' => $asetId,
                    'deskripsi' => 'Beli Aset '.$asetId,
                    'sekuritas_id' => $sekuritasId ?? null,
                ]);
                 
                if ($portofolio) {
                    // Ambil data terakhir untuk tiap aset_id yang bukan 1
                    $subquery = Portofolio::selectRaw('MAX(id) as last_id')
                        ->where('user_id', $userId)
                        ->whereNotIn('aset_id', [1, $asetId])
                        ->groupBy('aset_id');

                    // Ambil data dari hasil subquery dan hitung total valuasi
                    $totalValuasiPorto = Portofolio::whereIn('id', $subquery->pluck('last_id'))
                        ->selectRaw('SUM(volume * cur_price) as total_value')
                        ->value('total_value');

                    // Jika aset sudah ada di portofolio
                    $volumeBaru = $portofolio->volume + $volume;
                    $totalHargaBaru = $volumeBaru * $harga;
                    // if ($portofolio->cur_price != $harga) {
                    $valuasiSaatIniBaru = ($portofolioKasTerakhir->cur_price ?? 0) + ($totalValuasiPorto ?? 0) + ($totalHargaBaru ?? 0); 
                    $hargaUnitSaatIni = round(
                        ($valuasiSaatIniBaru ?? 0) / ($mutasiDanaTerakhir->jumlah_unit_penyertaan ?? 0)
                    , 4);

                    $mutasiDanaTerakhir->update([
                        'kode_grup_transaksi' => $kodeGrup,
                        'harga_unit_saat_ini' => $hargaUnitSaatIni,
                    ]);

                    $yield = round(($hargaUnitSaatIni - ($mutasiDanaTerakhir->harga_unit ?? 0)) / ($mutasiDanaTerakhir->harga_unit ?? 1) * 100, 2);
                    // }
                    
                    // Tambahkan data ke kinerja_portofolio
                    $kinerjaPortofolio = KinerjaPortofolio::create([
                        'user_id' => $userId,
                        'tanggal' => $tanggal,
                        'kode_grup_transaksi' => $kodeGrup,
                        'transaksi_id' => $transaksi->id,
                        'valuasi_saat_ini' => ($valuasiSaatIniBaru ?? ($kinerjaPortofolioKas->valuasi_saat_ini ?? 0) + $totalHarga),
                        'yield' => $yield ?? 0.00,
                    ]);

                    // Perbarui atau buat data baru di historis
                    $tahun = date('Y', strtotime($tanggal));
                    $bulan = date('n', strtotime($tanggal));

                    $historisTerakhir = Historis::where('tahun', $tahun)
                        ->where('bulan', $bulan)
                        ->first();

                    $historis = Historis::firstOrNew([
                        'user_id' => $userId,
                        'bulan' => $bulan,
                        'tahun' => $tahun,
                    ]);
                    $historis->yield = $yield;
                    if ($historisTerakhir) {
                        $historis->ihsg_start -> $historisTerakhir->ihsg_start ?? null;
                        $historis->ihsg_end -> $historisTerakhir->ihsg_end ?? null;
                        $historis->yield_ihsg -> $historisTerakhir->yield_ihsg ?? null;
                    }
                    $historis->save();
                    
                    $totalHargaSebelumnya = $portofolio->avg_price * $portofolio->volume;
                    $avgPriceBaru = ($totalHargaSebelumnya + $totalHarga) / $volumeBaru;

                    Portofolio::create([
                        'user_id' => $userId,
                        'tanggal' => $tanggal,
                        'kode_grup_transaksi' => $kodeGrup,
                        'aset_id' => $asetId,
                        'volume' => $volumeBaru,
                        'avg_price' => $avgPriceBaru,
                        'cur_price' => $harga,
                        'kinerja_portofolio_id' => $kinerjaPortofolio->id,
                    ]);
                } else {
                    // Tambahkan data ke kinerja_portofolio
                    $kinerjaPortofolio = KinerjaPortofolio::create([
                        'user_id' => $userId,
                        'tanggal' => $tanggal,
                        'kode_grup_transaksi' => $kodeGrup,
                        'transaksi_id' => $transaksi->id,
                        'valuasi_saat_ini' => ($kinerjaPortofolioKas->valuasi_saat_ini ?? 0) + $totalHarga,
                        'yield' => $kinerjaPortofolioKas->yield ?? 0.00,
                    ]);
                    // Jika aset belum ada di portofolio
                    Portofolio::create([
                        'user_id' => $userId,
                        'tanggal' => $tanggal,
                        'kode_grup_transaksi' => $kodeGrup,
                        'aset_id' => $asetId,
                        'volume' => $volume,
                        'avg_price' => $harga,
                        'cur_price' => $harga,
                        'kinerja_portofolio_id' => $kinerjaPortofolio->id,
                    ]);
                }

                $harga_terakhir = PerubahanHarga::where('user_id', $userId)
                    ->where('aset_id', $asetId)
                    ->orderByRaw("FIELD(sumber, 'manual', 'transaksi')") // Prioritaskan yang manual
                    ->orderByDesc('created_at')
                    ->first();

                if (
                    !$harga_terakhir ||
                    ( $harga_terakhir->sumber === 'transaksi' &&
                    $harga_terakhir->harga != $harga)) {
                    PerubahanHarga::create([
                        'user_id' => $userId,
                        'kode_grup_transaksi' => $kodeGrup,
                        'aset_id' => $asetId,
                        'harga' => $harga,
                        'tanggal' => now(),
                        'sumber' => 'transaksi',
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
                ], Response::HTTP_CREATED);
            }
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

    public function storeJual(Request $request)
    {
        try {
            $kodeGrup = Str::uuid();

            if ($request->input('jenis_transaksi') === 'jual') {
                $userId = $request->auth['user']['id'];
                $tanggal = $request->input('tanggal');
                $volume = $request->input('volume');
                $harga = $request->input('harga'); // Jual menggunakan avg price
                $totalHarga = $volume * $harga;
                $asetId = $request->input('aset_id');
                $sekuritasId = $request->input('sekuritas');
                // Ambil portofolio terakhir berdasarkan aset
                $portofolio = Portofolio::where('user_id', $userId)
                    ->where('aset_id', $asetId)
                    ->latest('id')
                    ->first();

                if (!$portofolio) {
                    return response()->json([
                        'error' => 'Portofolio tidak ditemukan untuk aset tersebut.',
                        'message' => 'Portofolio tidak ditemukan untuk aset tersebut.',
                    ], Response::HTTP_BAD_REQUEST);
                }

                if ($volume > $portofolio->volume) {
                    return response()->json(['error' => 'Volume jual melebihi volume yang ada.'], Response::HTTP_NOT_FOUND);
                } else {                   

                    $kinerjaPortofolioTerakhir = KinerjaPortofolio::where('user_id', $userId)
                        ->orderBy('id', 'desc')
                        ->first();

                    $mutasiDanaTerakhir = MutasiDana::where('user_id', $userId)
                        ->orderByDesc('id')
                        ->first();
                    
                    $portofolioKas = Portofolio::where('user_id', $userId)
                        ->where('aset_id', 1)
                        ->orderBy('id', 'desc')
                        ->first();

                    $transaksiKas = Transaksi::create([
                        'user_id' => $userId,
                        'kode_grup_transaksi' => $kodeGrup,
                        'tanggal' => $tanggal,
                        'jenis_transaksi' => 'kas',
                        'volume' => 1,
                        'harga' => $totalHarga,
                        'aset_id' => 1, // Aset kas
                        'deskripsi' => 'Bertambah jual aset', 
                    ]);
                
                    $saldo = Saldo::create([
                        'user_id' => $userId,
                        'kode_grup_transaksi' => $kodeGrup,
                        'transaksi_id' => $transaksiKas->id,
                        'tanggal' => $tanggal,
                        'saldo' => $totalHarga,
                        'tipe_saldo' => 'masuk',
                    ]);
                    $kinerjaPortofolioKas = KinerjaPortofolio::create([
                        'user_id' => $userId,
                        'tanggal' => $tanggal,
                        'kode_grup_transaksi' => $kodeGrup,
                        'transaksi_id' => $transaksiKas->id,
                        'valuasi_saat_ini' => ($kinerjaPortofolioTerakhir->valuasi_saat_ini ?? 0) + $totalHarga,
                        'yield' => $kinerjaPortofolioTerakhir->yield ?? 0.00,
                    ]);

                    $curPrice = $portofolioKas->cur_price + $totalHarga;

                    $portofolioKasTerakhir = Portofolio::create([
                        'user_id' => $userId,
                        'tanggal' => $tanggal,
                        'kode_grup_transaksi' => $kodeGrup,
                        'aset_id' => 1, // Aset kas
                        'volume' => 1,
                        'avg_price' => null, // Kosongkan
                        'cur_price' => $curPrice,
                        'kinerja_portofolio_id' => $kinerjaPortofolioKas->id,
                    ]);

                    // Buat transaksi baru
                    $transaksi = Transaksi::create([
                        'user_id' => $userId,
                        'kode_grup_transaksi' => $kodeGrup,
                        'tanggal' => $tanggal,
                        'jenis_transaksi' => 'jual',
                        'volume' => $volume,
                        'harga' => $harga,
                        'avg_price' => $portofolio->avg_price,
                        'aset_id' => $asetId,
                        'deskripsi' => 'Jual Aset '.$asetId,
                        'sekuritas_id' => $sekuritasId ?? null,
                    ]);
                    
                    // Ambil data terakhir untuk tiap aset_id yang bukan 1
                    $subquery = Portofolio::selectRaw('MAX(id) as last_id')
                        ->where('user_id', $userId)
                        ->whereNotIn('aset_id', [1, $asetId])
                        ->where('volume', '>', 0)
                        ->groupBy('aset_id');

                    // Ambil data dari hasil subquery dan hitung total valuasi
                    $totalValuasiPorto = Portofolio::whereIn('id', $subquery->pluck('last_id'))
                        ->where('volume', '>', 0)
                        ->selectRaw('SUM(volume * cur_price) as total_value')
                        ->value('total_value');

                    // Jika aset sudah ada di portofolio
                    $volumeBaru = $portofolio->volume - $volume;
                    $totalHargaBaru = $volumeBaru * $harga;
                        
                    $valuasiSaatIniBaru = ($portofolioKasTerakhir->cur_price ?? 0) + ($totalValuasiPorto ?? 0) + ($totalHargaBaru ?? 0); 

                    $hargaUnitSaatIni = round(
                        ($valuasiSaatIniBaru ?? 0) / ($mutasiDanaTerakhir->jumlah_unit_penyertaan ?? 0)
                    , 4);

                    $mutasiDanaTerakhir->update([
                        'harga_unit_saat_ini' => $hargaUnitSaatIni,
                        'kode_grup_transaksi' => $kodeGrup,
                    ]);

                    $yield = round(($hargaUnitSaatIni - ($mutasiDanaTerakhir->harga_unit ?? 0)) / ($mutasiDanaTerakhir->harga_unit ?? 1) * 100 , 2);
                    
                    // Tambahkan data ke kinerja_portofolio
                    $kinerjaPortofolio = KinerjaPortofolio::create([
                        'user_id' => $userId,
                        'tanggal' => $tanggal,
                        'kode_grup_transaksi' => $kodeGrup,
                        'transaksi_id' => $transaksi->id,
                        'valuasi_saat_ini' => ($valuasiSaatIniBaru ?? ($kinerjaPortofolioKas->valuasi_saat_ini ?? 0) + $totalHarga),
                        'yield' => $yield ?? 0.00,
                    ]);

                    // Perbarui atau buat data baru di historis
                    $tahun = date('Y', strtotime($tanggal));
                    $bulan = date('n', strtotime($tanggal));

                    $historisTerakhir = Historis::where('tahun', $tahun)
                        ->where('bulan', $bulan)
                        ->first();

                    $historis = Historis::firstOrNew([
                        'user_id' => $userId,
                        'bulan' => $bulan,
                        'tahun' => $tahun,
                    ]);
                    $historis->yield = $yield;
                    if ($historisTerakhir) {
                        $historis->ihsg_start -> $historisTerakhir->ihsg_start ?? null;
                        $historis->ihsg_end -> $historisTerakhir->ihsg_end ?? null;
                        $historis->yield_ihsg -> $historisTerakhir->yield_ihsg ?? null;
                    }
                    $historis->save();
                    
                    Portofolio::create([
                        'user_id' => $userId,
                        'tanggal' => $tanggal,
                        'kode_grup_transaksi' => $kodeGrup,
                        'aset_id' => $asetId,
                        'volume' => $volumeBaru,
                        'avg_price' => $portofolio->avg_price,
                        'cur_price' => $harga,
                        'kinerja_portofolio_id' => $kinerjaPortofolio->id,
                    ]);

                    $harga_terakhir = PerubahanHarga::where('user_id', $userId)
                        ->where('aset_id', $asetId)
                        ->orderByRaw("FIELD(sumber, 'manual', 'transaksi')") // Prioritaskan yang manual
                        ->orderByDesc('created_at')
                        ->first();
    
                    if (!$harga_terakhir ||
                        ($harga_terakhir->sumber === 'transaksi' &&
                        $harga_terakhir->harga != $harga)) {
                        PerubahanHarga::create([
                            'user_id' => $userId,
                            'kode_grup_transaksi' => $kodeGrup,
                            'aset_id' => $asetId,
                            'harga' => $harga,
                            'tanggal' => now(),
                            'sumber' => 'transaksi',
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
                }
            }
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

    public function updateCurrentPrice(Request $request, $userId, $asetId, $newPrice, $tanggalPrice)
    {
        try {
            $kodeGrup = Str::uuid();

            // Ambil portofolio terakhir berdasarkan aset
            $portofolio = Portofolio::where('user_id', $userId)
                ->where('aset_id', $asetId)
                ->latest('id')
                ->first();

            if ($portofolio) {
                $portofolio->update(['cur_price' => $newPrice]);
                // Ambil data terakhir untuk tiap aset_id yang bukan 1
                $subquery = Portofolio::selectRaw('MAX(id) as last_id')
                    ->where('user_id', $userId)
                    ->where('aset_id', '!=', 1)
                    ->where('volume', '>', 0)
                    ->groupBy('aset_id');

                // Ambil data dari hasil subquery dan hitung total valuasi
                $totalValuasiPorto = Portofolio::whereIn('id', $subquery->pluck('last_id'))
                    ->where('volume', '>', 0)
                    ->selectRaw('SUM(volume * cur_price) as total_value')
                    ->value('total_value');

            } else {
                return response()->json(['error' => 'Portofolio tidak ditemukan untuk aset tersebut.'], Response::HTTP_NOT_FOUND);

            }

            $mutasiDanaTerakhir = MutasiDana::where('user_id', $userId)
                ->orderByDesc('id')
                ->first();

            $kinerjaPortofolioTerakhir = KinerjaPortofolio::where('user_id', $userId)
                ->orderByDesc('id')
                ->first();

            $portofolioKasTerakhir = Portofolio::where('user_id', $userId)
                ->where('aset_id', 1) // ID aset untuk "kas"
                ->orderByDesc('id')
                ->first();
            
            // dd($totalValuasiPorto);

            $modalLama = $mutasiDanaTerakhir->modal;
            $valuasiSaatIniBaru = ($portofolioKasTerakhir->cur_price ?? 0) + ($totalValuasiPorto ?? 0); 
 
            $hargaUnitSaatIni = round(
                ($valuasiSaatIniBaru ?? 0) / ($mutasiDanaTerakhir->jumlah_unit_penyertaan ?? 0)
            , 4);

            $mutasiDanaTerakhir->update([
                'harga_unit_saat_ini' => $hargaUnitSaatIni,
                'kode_grup_transaksi' => $kodeGrup,

            ]);

            $yield = round(($hargaUnitSaatIni - ($mutasiDanaTerakhir->harga_unit ?? 0)) / ($mutasiDanaTerakhir->harga_unit ?? 1) * 100 , 2);

            $kinerjaPortofolioBaru = KinerjaPortofolio::create([
                'user_id' => $userId,
                'tanggal' => $tanggalPrice,
                'kode_grup_transaksi' => $kodeGrup,
                'transaksi_id' => $kinerjaPortofolioTerakhir->transaksi_id, // ID transaksi akan ditambahkan di bawah
                'valuasi_saat_ini' => $valuasiSaatIniBaru,
                'yield' => $yield,
            ]);

            // Perbarui atau buat data baru di historis
            $tahun = date('Y', strtotime($tanggalPrice));
            $bulan = date('n', strtotime($tanggalPrice));

            $historisTerakhir = Historis::where('tahun', $tahun)
                        ->where('bulan', $bulan)
                        ->first();

            $historis = Historis::firstOrNew([
                'user_id' => $userId,
                'bulan' => $bulan,
                'tahun' => $tahun,
            ]);
            $historis->yield = $yield;
            if ($historisTerakhir) {
                $historis->ihsg_start = $historisTerakhir->ihsg_start ?? null;
                $historis->ihsg_end = $historisTerakhir->ihsg_end ?? null;
                $historis->yield_ihsg = $historisTerakhir->yield_ihsg ?? null;
            }
            $historis->save();

           $harga_terakhir = PerubahanHarga::where('user_id', $userId)
                    ->where('aset_id', $asetId)
                    ->orderByRaw("FIELD(sumber, 'manual', 'transaksi')") // Prioritaskan yang manual
                    ->orderByDesc('created_at')
                    ->first();
            if (!$harga_terakhir || $harga_terakhir->harga != $newPrice) {
                PerubahanHarga::create([
                    'user_id' => $userId,
                    'kode_grup_transaksi' => $kodeGrup,
                    'aset_id' => $asetId,
                    'harga' => $newPrice,
                    'tanggal' => $tanggalPrice,
                    'sumber' => 'manual',
                ]);
            }

            return response()->json([
                'message' => 'Berhasil update data harga.',
                'auth' => $userId,
                'data' => [
                    'portofolio' => $portofolio,
                    'mutasi_dana' => $mutasiDanaTerakhir,
                    'kinerja_portofolio' => KinerjaPortofolio::latest('id')->first(),
                    'historis' => $historis,
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

    public function updatePrice(Request $request)
    {
        $request->validate([
            'updateHarga1' => 'required|numeric|min:1',
        ]);

        $userId = $request->auth['user']['id'];
        $asetId = $request->input('id_aset');
        $newPrice = $request->input('updateHarga1');
        $tanggalPrice = $request->input('tanggal_harga');

        return $this->updateCurrentPrice($request, $userId, $asetId, $newPrice, $tanggalPrice);
    }

    // public function updateBeli(Request $request)
    // {
    //     try {
    //         $request->validate([
    //             'volume' => 'required|min:1',
    //             'harga' => 'required|min:1',
    //         ]);

    //         if ($request->input('jenis_transaksi') === 'beli') {
    //             $userId = $request->auth['user']['id'];
    //             $tanggal = $request->input('tanggal');
    //             $volume = $request->input('volume');
    //             $harga = $request->input('harga');
    //             $totalHarga = $volume * $harga;
    //             $asetId = $request->input('aset_id');
    //             $sekuritasId = $request->input('sekuritas');

    //             // Cek saldo user
    //             $saldoUser = Saldo::where('user_id', $userId)->sum('saldo');
    //             if ($totalHarga > $saldoUser) {
    //                 return response()->json([
    //                     'error' => 'Saldo tidak mencukupi',
    //                     'message' => 'Saldo tidak mencukupi'
    //                 ], Response::HTTP_BAD_REQUEST);
    //             }

    //             // Kurangi saldo user
                

    //             // Tambahan untuk aset kas (aset_id = 1)
    //             // Ambil kinerja_portofolio terakhir
    //             $kinerjaPortofolioTerakhir = KinerjaPortofolio::where('user_id', $userId)
    //                 ->orderBy('id', 'desc')
    //                 ->first();

    //             $mutasiDanaTerakhir = MutasiDana::where('user_id', $userId)
    //                 ->orderByDesc('id')
    //                 ->first();

                    
    //             $transaksiKas = Transaksi::create([
    //                 'user_id' => $userId,
    //                 'tanggal' => $tanggal,
    //                 'jenis_transaksi' => 'kas',
    //                 'volume' => 1,
    //                 'harga' => -$totalHarga,
    //                 'aset_id' => 1, // Aset kas
    //                 'deskripsi' => 'Berkurang beli aset', 
    //             ]);
            

    //             $saldo = Saldo::create([
    //                 'user_id' => $userId,
    //                 'tanggal' => $tanggal,
    //                 'transaksi_id' => $transaksiKas->id,
    //                 'saldo' => -$totalHarga,
    //                 'tipe_saldo' => 'keluar',
    //             ]);

    //             $kinerjaPortofolioKas = KinerjaPortofolio::create([
    //                 'user_id' => $userId,
    //                 'transaksi_id' => $transaksiKas->id,
    //                 'valuasi_saat_ini' => ($kinerjaPortofolioTerakhir->valuasi_saat_ini ?? 0) - $totalHarga,
    //                 'yield' => $kinerjaPortofolioTerakhir->yield ?? 0.00,
    //             ]);

    //             // Cek apakah aset sudah ada di portofolio user
    //             $portofolio = Portofolio::where('user_id', $userId)
    //                 ->where('aset_id', $asetId)
    //                 ->orderBy('id', 'desc')
    //                 ->first();

    //             $portofolioKas = Portofolio::where('user_id', $userId)
    //                 ->where('aset_id', 1)
    //                 ->orderBy('id', 'desc')
    //                 ->first();

    //             $curPrice = $portofolioKas->cur_price - $totalHarga;

    //             $portofolioKasTerakhir = Portofolio::create([
    //                 'user_id' => $userId,
    //                 'aset_id' => 1, // Aset kas
    //                 'volume' => 1,
    //                 'avg_price' => null, // Kosongkan
    //                 'cur_price' => $curPrice,
    //                 'kinerja_portofolio_id' => $kinerjaPortofolioKas->id,
    //             ]);

    //             // Buat transaksi baru
                
    //             $transaksi = Transaksi::create([
    //                 'user_id' => $userId,
    //                 'tanggal' => $tanggal,
    //                 'jenis_transaksi' => 'beli',
    //                 'volume' => $volume,
    //                 'harga' => $harga,
    //                 'aset_id' => $asetId,
    //                 'deskripsi' => 'Beli Aset '.$asetId,
    //                 'sekuritas_id' => $sekuritasId ?? null,
    //             ]);
            
                
    //             if ($portofolio) {
    //                 // Ambil data terakhir untuk tiap aset_id yang bukan 1
    //                 $subquery = Portofolio::selectRaw('MAX(id) as last_id')
    //                     ->where('user_id', $userId)
    //                     ->whereNotIn('aset_id', [1, $asetId])
    //                     ->groupBy('aset_id');

    //                 // Ambil data dari hasil subquery dan hitung total valuasi
    //                 $totalValuasiPorto = Portofolio::whereIn('id', $subquery->pluck('last_id'))
    //                     ->selectRaw('SUM(volume * cur_price) as total_value')
    //                     ->value('total_value');

    //                 // Jika aset sudah ada di portofolio
    //                 $volumeBaru = $portofolio->volume + $volume;
    //                 $totalHargaBaru = $volumeBaru * $harga;
    //                 // if ($portofolio->cur_price != $harga) {
                        
    //                     $valuasiSaatIniBaru = ($portofolioKasTerakhir->cur_price ?? 0) + ($totalValuasiPorto ?? 0) + ($totalHargaBaru ?? 0); 

    //                     $hargaUnitSaatIni = ceil(
    //                         ($valuasiSaatIniBaru ?? 0) / ($mutasiDanaTerakhir->jumlah_unit_penyertaan ?? 0)
    //                     );

    //                     $mutasiDanaTerakhir->update([
    //                         'harga_unit_saat_ini' => $hargaUnitSaatIni,
    //                     ]);

    //                     $yield = round(($hargaUnitSaatIni - ($mutasiDanaTerakhir->harga_unit ?? 0)) / ($mutasiDanaTerakhir->harga_unit ?? 1) * 100, 2);
    //                 // }
                    
    //                 // Tambahkan data ke kinerja_portofolio
    //                 $kinerjaPortofolio = KinerjaPortofolio::create([
    //                     'user_id' => $userId,
    //                     'transaksi_id' => $transaksi->id,
    //                     'valuasi_saat_ini' => ($valuasiSaatIniBaru ?? ($kinerjaPortofolioKas->valuasi_saat_ini ?? 0) + $totalHarga),
    //                     'yield' => $yield ?? 0.00,
    //                 ]);

    //                 // Perbarui atau buat data baru di historis
    //                 $tahun = date('Y', strtotime($tanggal));
    //                 $bulan = date('n', strtotime($tanggal));

    //                 $historisTerakhir = Historis::where('tahun', $tahun)
    //                     ->where('bulan', $bulan)
    //                     ->first();

    //                 $historis = Historis::firstOrNew([
    //                     'user_id' => $userId,
    //                     'bulan' => $bulan,
    //                     'tahun' => $tahun,
    //                 ]);
    //                 $historis->yield = $yield;
    //                 if ($historisTerakhir) {
    //                     $historis->ihsg_start -> $historisTerakhir->ihsg_start ?? null;
    //                     $historis->ihsg_end -> $historisTerakhir->ihsg_end ?? null;
    //                     $historis->yield_ihsg -> $historisTerakhir->yield_ihsg ?? null;
    //                 }
    //                 $historis->save();
                    
    //                 $totalHargaSebelumnya = $portofolio->avg_price * $portofolio->volume;
    //                 $avgPriceBaru = ($totalHargaSebelumnya + $totalHarga) / $volumeBaru;

    //                 Portofolio::create([
    //                     'user_id' => $userId,
    //                     'aset_id' => $asetId,
    //                     'volume' => $volumeBaru,
    //                     'avg_price' => $avgPriceBaru,
    //                     'cur_price' => $harga,
    //                     'kinerja_portofolio_id' => $kinerjaPortofolio->id,
    //                 ]);
    //             } else {
    //                 // Tambahkan data ke kinerja_portofolio
    //                 $kinerjaPortofolio = KinerjaPortofolio::create([
    //                     'user_id' => $userId,
    //                     'transaksi_id' => $transaksi->id,
    //                     'valuasi_saat_ini' => ($kinerjaPortofolioKas->valuasi_saat_ini ?? 0) + $totalHarga,
    //                     'yield' => $kinerjaPortofolioKas->yield ?? 0.00,
    //                 ]);
    //                 // Jika aset belum ada di portofolio
    //                 Portofolio::create([
    //                     'user_id' => $userId,
    //                     'aset_id' => $asetId,
    //                     'volume' => $volume,
    //                     'avg_price' => $harga,
    //                     'cur_price' => $harga,
    //                     'kinerja_portofolio_id' => $kinerjaPortofolio->id,
    //                 ]);
    //             }

    //             $harga_terakhir = PerubahanHarga::where('aset_id', $asetId)
    //                 ->where('user_id', $userId)->orderBy('id', 'desc')->first();
    //             if (!$harga_terakhir || $harga_terakhir->harga != $harga) {
    //                 PerubahanHarga::create([
    //                     'user_id' => $userId,
    //                     'aset_id' => $asetId,
    //                     'harga' => $harga,
    //                     'tanggal' => now(),
    //                 ]);
    //             }
                
    //             return response()->json([
    //                 'message' => 'Berhasil mendapatkan transaksi.',
    //                 'auth' => $request->auth,
    //                 'data' => [
    //                     'transaksi' => $transaksi,
    //                     'saldo' => $saldo,
    //                     'portofolio' => Portofolio::where('user_id', $userId)
    //                         ->latest()
    //                         ->get(),
    //                     'kinerja_portofolio' => $kinerjaPortofolio,
    //                 ],
    //             ], Response::HTTP_CREATED);
    //         }
    //     } catch (\Exception $e) {
    //         if ($e instanceof ValidationException) {
    //             return response()->json([
    //                 'message' => $e->getMessage(),
    //                 'auth' => $request->auth,
    //                 'errors' => $e->validator->errors(),
    //             ], Response::HTTP_BAD_REQUEST);
    //         } else {
    //             return response()->json([
    //                 'message' => $e->getMessage(),
    //                 'auth' => $request->auth,
    //             ], Response::HTTP_BAD_REQUEST);
    //         }
    //     }
    // }

    public function updateBeli(Request $request)
    {
        try {
            $userId = $request->auth['user']['id'];
            $transaksiId = $request->input('id_transaksi');
            $asetId = $request->input('aset_id');

            // Cek apakah ini transaksi terakhir untuk aset tersebut
            $lastTransaction = Transaksi::where('aset_id', $asetId)
                ->where('user_id', $userId)
                ->orderByDesc('created_at')
                ->first();

            $isLastTransaction = $lastTransaction && $lastTransaction->id == $transaksiId;

            if (!$isLastTransaction) {
                return response()->json([
                    'message' => 'Transaksi bukan yang terakhir dan tidak bisa diedit.',
                ], Response::HTTP_BAD_REQUEST);
            }

            // Hapus transaksi lama
            $this->destroyPortofolio($request, $transaksiId);

            // (Opsional) Jalankan rebuild jika tidak otomatis
            // $this->rebuildPortofolioDanHarga($userId);

            // Simpan transaksi baru hasil edit
            $this->store($request);

            return response()->json([
                'message' => 'Transaksi berhasil diedit.',
            ]);
        } catch (\Exception $e) {
            if ($e instanceof ValidationException) {
                return response()->json([
                    'message' => $e->getMessage(),
                    'errors' => $e->validator->errors(),
                ], Response::HTTP_BAD_REQUEST);
            } else {
                return response()->json([
                    'message' => $e->getMessage(),
                ], Response::HTTP_BAD_REQUEST);
            }
        }
    }


    public function updateJual(Request $request)
    {
        try {
            if ($request->input('jenis_transaksi') === 'jual') {
                $userId = $request->auth['user']['id'];
                $tanggal = $request->input('tanggal');
                $volume = $request->input('volume');
                $harga = $request->input('harga'); // Jual menggunakan avg price
                $totalHarga = $volume * $harga;
                $asetId = $request->input('aset_id');
                $sekuritasId = $request->input('sekuritas');
                // Ambil portofolio terakhir berdasarkan aset
                $portofolio = Portofolio::where('user_id', $userId)
                    ->where('aset_id', $asetId)
                    ->latest('id')
                    ->first();

                if (!$portofolio) {
                    return response()->json([
                        'error' => 'Portofolio tidak ditemukan untuk aset tersebut.',
                        'message' => 'Portofolio tidak ditemukan untuk aset tersebut.',
                    ], Response::HTTP_BAD_REQUEST);
                }

                if ($volume > $portofolio->volume) {
                    return response()->json(['error' => 'Volume jual melebihi volume yang ada.'], Response::HTTP_NOT_FOUND);
                } else {                   

                    $kinerjaPortofolioTerakhir = KinerjaPortofolio::where('user_id', $userId)
                        ->orderBy('id', 'desc')
                        ->first();

                    $mutasiDanaTerakhir = MutasiDana::where('user_id', $userId)
                        ->orderByDesc('id')
                        ->first();
                    
                    $portofolioKas = Portofolio::where('user_id', $userId)
                        ->where('aset_id', 1)
                        ->orderBy('id', 'desc')
                        ->first();


                    // Tambah saldo user
                  

                    $transaksiKas = Transaksi::create([
                        'user_id' => $userId,
                        'tanggal' => $tanggal,
                        'jenis_transaksi' => 'kas',
                        'volume' => 1,
                        'harga' => $totalHarga,
                        'aset_id' => 1, // Aset kas
                        'deskripsi' => 'Bertambah jual aset', 
                    ]);

                      $saldo = Saldo::create([
                        'user_id' => $userId,
                        'tanggal' => $tanggal,
                        'transaksi_id' => $transaksiKas->id,
                        'saldo' => $totalHarga,
                        'tipe_saldo' => 'masuk',
                    ]);

                    $kinerjaPortofolioKas = KinerjaPortofolio::create([
                        'user_id' => $userId,
                        'transaksi_id' => $transaksiKas->id,
                        'valuasi_saat_ini' => ($kinerjaPortofolioTerakhir->valuasi_saat_ini ?? 0) + $totalHarga,
                        'yield' => $kinerjaPortofolioTerakhir->yield ?? 0.00,
                    ]);

                    $curPrice = $portofolioKas->cur_price + $totalHarga;

                    $portofolioKasTerakhir = Portofolio::create([
                        'user_id' => $userId,
                        'aset_id' => 1, // Aset kas
                        'volume' => 1,
                        'avg_price' => null, // Kosongkan
                        'cur_price' => $curPrice,
                        'kinerja_portofolio_id' => $kinerjaPortofolioKas->id,
                    ]);

                    // Buat transaksi baru
                    $transaksi = Transaksi::create([
                        'user_id' => $userId,
                        'tanggal' => $tanggal,
                        'jenis_transaksi' => 'jual',
                        'volume' => $volume,
                        'harga' => $harga,
                        'avg_price' => $portofolio->avg_price,
                        'aset_id' => $asetId,
                        'deskripsi' => 'Jual Aset '.$asetId,
                        'sekuritas_id' => $sekuritasId ?? null,
                    ]);

                    
                    // Ambil data terakhir untuk tiap aset_id yang bukan 1
                    $subquery = Portofolio::selectRaw('MAX(id) as last_id')
                        ->where('user_id', $userId)
                        ->whereNotIn('aset_id', [1, $asetId])
                        ->where('volume', '>', 0)
                        ->groupBy('aset_id');

                    // Ambil data dari hasil subquery dan hitung total valuasi
                    $totalValuasiPorto = Portofolio::whereIn('id', $subquery->pluck('last_id'))
                        ->where('volume', '>', 0)
                        ->selectRaw('SUM(volume * cur_price) as total_value')
                        ->value('total_value');

                    // Jika aset sudah ada di portofolio
                    $volumeBaru = $portofolio->volume - $volume;
                    $totalHargaBaru = $volumeBaru * $harga;
                        
                    $valuasiSaatIniBaru = ($portofolioKasTerakhir->cur_price ?? 0) + ($totalValuasiPorto ?? 0) + ($totalHargaBaru ?? 0); 

                    $hargaUnitSaatIni = round(
                        ($valuasiSaatIniBaru ?? 0) / ($mutasiDanaTerakhir->jumlah_unit_penyertaan ?? 0)
                    , 4);

                    $mutasiDanaTerakhir->update([
                        'harga_unit_saat_ini' => $hargaUnitSaatIni,
                    ]);

                    $yield = round(($hargaUnitSaatIni - ($mutasiDanaTerakhir->harga_unit ?? 0)) / ($mutasiDanaTerakhir->harga_unit ?? 1) * 100 , 2);
                    
                    // Tambahkan data ke kinerja_portofolio
                    $kinerjaPortofolio = KinerjaPortofolio::create([
                        'user_id' => $userId,
                        'transaksi_id' => $transaksi->id,
                        'valuasi_saat_ini' => ($valuasiSaatIniBaru ?? ($kinerjaPortofolioKas->valuasi_saat_ini ?? 0) + $totalHarga),
                        'yield' => $yield ?? 0.00,
                    ]);

                    // Perbarui atau buat data baru di historis
                    $tahun = date('Y', strtotime($tanggal));
                    $bulan = date('n', strtotime($tanggal));

                    $historisTerakhir = Historis::where('tahun', $tahun)
                        ->where('bulan', $bulan)
                        ->first();

                    $historis = Historis::firstOrNew([
                        'user_id' => $userId,
                        'bulan' => $bulan,
                        'tahun' => $tahun,
                    ]);
                    $historis->yield = $yield;
                    if ($historisTerakhir) {
                        $historis->ihsg_start -> $historisTerakhir->ihsg_start ?? null;
                        $historis->ihsg_end -> $historisTerakhir->ihsg_end ?? null;
                        $historis->yield_ihsg -> $historisTerakhir->yield_ihsg ?? null;
                    }
                    $historis->save();
                    
                    Portofolio::create([
                        'user_id' => $userId,
                        'aset_id' => $asetId,
                        'volume' => $volumeBaru,
                        'avg_price' => $portofolio->avg_price,
                        'cur_price' => $harga,
                        'kinerja_portofolio_id' => $kinerjaPortofolio->id,
                    ]);

                    $harga_terakhir = PerubahanHarga::where('aset_id', $asetId)
                        ->where('user_id', $userId)->orderBy('id', 'desc')->first();
                    if (!$harga_terakhir || $harga_terakhir->harga != $harga) {
                        PerubahanHarga::create([
                            'user_id' => $userId,
                            'aset_id' => $asetId,
                            'harga' => $harga,
                            'tanggal' => now(),
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
                }
            
            }
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

    public function storeSaham(Request $request)
    {
        try {
            $request->validate([
                'id_aset' => 'required',
                'nama_aset' => 'required',
            ]);
            $response = Http::acceptJson()
                ->withHeaders([
                    'X-API-KEY' => config('goapi.apikey')
                ])->withoutVerifying()->get('https://api.goapi.io/stock/idx/prices?symbols='.$request->nama_aset)->json();

            $hargaTerkini = $response['data']['results']['close'];
            return response()->json([
                'message' => 'Berhasil input data saham ke tabel aset.',
                'auth' => $request->auth,
                'hargaTerkini' => $hargaTerkini,
                'idAset' => $request->id_aset,
            ], Response::HTTP_OK);    
        } catch (Exception $e) {
            if ($e instanceof ValidationException) {
                return response()->json([
                    'message' => $e->getMessage(),
                    'auth' => $request->auth,
                    'errors' => $e->validator->errors(),
                ], Response::HTTP_BAD_REQUEST);
            } else {
                Log::error('Error in index method: ' . $e->getMessage());
                return response()->json([
                    'message' => $e->getMessage(),
                    'auth' => $request->auth
                ], Response::HTTP_BAD_REQUEST);
            }
        }
    }

    private function rebuildPortofolio($userId)
    {
        $mutasiDanaTerakhir = MutasiDana::where('user_id', $userId)->latest()->first();

        if ($mutasiDanaTerakhir) {
            $subquery = Portofolio::selectRaw('MAX(id) as last_id')
                ->where('user_id', $userId)
                ->where('aset_id', '!=', 1)
                ->groupBy('aset_id');

            $totalSaham = Portofolio::whereIn('id', $subquery->pluck('last_id'))
                ->selectRaw('SUM(volume * cur_price) as total_value')
                ->value('total_value') ?? 0;

            $totalKas = Saldo::where('user_id', $userId)->sum('saldo');
            $totalValuasi = $totalSaham + $totalKas;

            $jumlahUnit = $mutasiDanaTerakhir->jumlah_unit_penyertaan ?? 1;
            $hargaUnitAwal = $mutasiDanaTerakhir->harga_unit ?? 0;

            $hargaUnitBaru = round($totalValuasi / $jumlahUnit, 4);
            $yieldBaru = round(($hargaUnitBaru - $hargaUnitAwal) / max($hargaUnitAwal, 1) * 100, 2);

            $mutasiDanaTerakhir->update([
                'harga_unit_saat_ini' => $hargaUnitBaru,
            ]);

            Historis::updateOrCreate(
                [
                    'user_id' => $userId,
                    'bulan' => now()->month,
                    'tahun' => now()->year,
                ],
                [
                    'yield' => $yieldBaru,
                ]
            );
        }
    }

    // public function destroyPortofolio(Request $request, $id)
    // {
    // DB::beginTransaction();

    //     try {
    //         $userId = $request->auth['user']['id'];
            
    //         $asetIds = PerubahanHarga::where('user_id', $userId)
    //             ->pluck('aset_id')
    //             ->unique();

    //         // Hapus semua perubahan harga selain manual
    //         PerubahanHarga::where('user_id', $userId)
    //             ->where('sumber', '!=', 'manual')
    //             ->delete();

    //         // Cek dan hapus harga manual untuk aset yang tidak punya transaksi sama sekali
    //         foreach ($asetIds as $asetId) {
    //             $adaTransaksi = Transaksi::where('user_id', $userId)
    //                 ->where('aset_id', $asetId)
    //                 ->exists();

    //             if (!$adaTransaksi) {
    //                 PerubahanHarga::where('user_id', $userId)
    //                     ->where('aset_id', $asetId)
    //                     ->where('sumber', 'manual')
    //                     ->delete();
    //             }
    //         }

    //         // Ambil dan clone semua transaksi user (supaya tidak terhapus di bawah)
    //         $semuaTransaksi = Transaksi::where('user_id', $userId)
    //             ->orderBy('tanggal')
    //             ->orderBy('id')
    //             ->get()
    //             ->map(fn($t) => clone $t); // simpan sebagai collection objek biasa

    //         // Temukan transaksi yang ingin dihapus
    //         $transaksiTarget = $semuaTransaksi->firstWhere('id', $id);
    //         if (!$transaksiTarget) {
    //             throw new \Exception("Transaksi tidak ditemukan");
    //         }
    //         $kodeGrup = $transaksiTarget->kode_grup_transaksi;

    //         // Filter semua transaksi yang akan disimulasikan ulang (kecuali transaksi yang dihapus)
    //         $transaksiUntukDijalankan = $semuaTransaksi->filter(function ($t) use ($kodeGrup) {
    //             return $t->kode_grup_transaksi !== $kodeGrup;
    //         });

    //         // dd($transaksiUntukDijalankan);

    //         // Hapus semua data lama (kecuali kas di portofolio)
    //         Portofolio::where('user_id', $userId)->delete();
    //         KinerjaPortofolio::where('user_id', $userId)->delete();
    //         MutasiDana::where('user_id', $userId)->delete();
    //         Historis::where('user_id', $userId)->delete();
    //         Saldo::where('user_id', $userId)->delete();
    //         Transaksi::where('user_id', $userId)->delete();

    //         // dd($transaksiUntukDijalankan);

    //         // Simulasikan ulang semua transaksi yang masih tersisa
    //         foreach ($transaksiUntukDijalankan as $transaksi) {
    //             Log::info("Menjalankan ulang transaksi ID: {$transaksi->id}, grup: {$transaksi->kode_grup_transaksi}, jenis: {$transaksi->jenis_transaksi}");
    //             Log::info("Menghapus transaksi grup: {$kodeGrup}");
    //             // dd($transaksi);
    //             $requestBaru = $this->generateRequestFromTransaksi($transaksi);
    //             Log::info("Request:", $requestBaru->all());
    //             // dd($requestBaru);

    //             if ($transaksi->jenis_transaksi === 'beli') {
    //                 $this->store($requestBaru);
    //             } elseif ($transaksi->jenis_transaksi === 'jual') {
    //                 $this->storeJual($requestBaru);
    //             } elseif ($transaksi->jenis_transaksi === 'kas' && $transaksi->deskripsi === 'Top-up saldo') {
    //                 $this->topUp($requestBaru);
    //             } elseif ($transaksi->jenis_transaksi === 'kas' && $transaksi->deskripsi === 'Withdraw saldo') {
    //                 $this->topUp($requestBaru);
    //             }
    //         }

        

    //     $subQuery = PerubahanHarga::select('id')
    //         ->where('user_id', $userId)
    //         ->orderBy('created_at', 'desc')
    //         ->orderByRaw("FIELD(sumber, 'manual', 'realtime', 'transaksi')")
    //         ->get()
    //         ->unique('aset_id')
    //         ->pluck('id');

    //     $hargaTerakhirList = PerubahanHarga::whereIn('id', $subQuery)->get();

    //     foreach ($hargaTerakhirList as $perubahan) {
    //         $fakeRequest = new Request([
    //             'auth' => ['user' => ['id' => $userId]],
    //             'id_aset' => $perubahan->aset_id,
    //             'updateHarga1' => $perubahan->harga,
    //             'tanggal_harga' => $perubahan->tanggal,
    //         ]);

    //         $this->updateCurrentPrice(
    //             $fakeRequest,
    //             $userId,
    //             $perubahan->aset_id,
    //             $perubahan->harga,
    //             $perubahan->tanggal
    //         );
    //     }



    //         DB::commit();

    //         return response()->json([
    //             'message' => 'Berhasil menghapus transaksi dan membangun ulang data portofolio.',
    //             'auth' => $request->auth,
    //         ], Response::HTTP_OK);

    //     } catch (\Exception $e) {
    //         DB::rollBack();

    //         return response()->json([
    //             'message' => 'Gagal menghapus data: ' . $e->getMessage(),
    //             'auth' => $request->auth,
    //         ], Response::HTTP_BAD_REQUEST);
    //     }
    // }

    public function destroyPortofolio(Request $request, $id)
{
    DB::beginTransaction();

    try {
        $userId = $request->auth['user']['id'];

        // Dapatkan tahun tutup buku terakhir
        $tahunTutupBuku = TutupBuku::where('user_id', $userId)
            ->orderByDesc('tahun')
            ->first()?->tahun ?? 0;

        // Ambil semua aset ID milik user
        $asetIds = PerubahanHarga::where('user_id', $userId)
            ->pluck('aset_id')
            ->unique();

        // Hapus perubahan harga selain manual yang dibuat setelah tahun tutup buku
        PerubahanHarga::where('user_id', $userId)
            ->where('sumber', '!=', 'manual')
            ->whereYear('tanggal', '>', $tahunTutupBuku)
            ->delete();

        // Hapus harga manual untuk aset yang tidak punya transaksi sama sekali (hanya untuk tahun setelah tutup buku)
        foreach ($asetIds as $asetId) {
            $adaTransaksi = Transaksi::where('user_id', $userId)
                ->where('aset_id', $asetId)
                ->whereYear('tanggal', '>', $tahunTutupBuku)
                ->exists();

            if (!$adaTransaksi) {
                PerubahanHarga::where('user_id', $userId)
                    ->where('aset_id', $asetId)
                    ->where('sumber', 'manual')
                    ->whereYear('tanggal', '>', $tahunTutupBuku)
                    ->delete();
            }
        }

        // Ambil transaksi yang belum ditutup bukunya
        $transaksiSemua = Transaksi::where('user_id', $userId)
            ->orderBy('tanggal')
            ->orderBy('id')
            ->get();

        // Temukan transaksi target
        $transaksiTarget = $transaksiSemua->firstWhere('id', $id);
        if (!$transaksiTarget) {
            throw new \Exception("Transaksi tidak ditemukan");
        }

        // Batasi hanya jika transaksi target berada di tahun setelah tutup buku
        $tahunTransaksiTarget = Carbon::parse($transaksiTarget->tanggal)->year;
        if ($tahunTransaksiTarget <= $tahunTutupBuku) {
            throw new \Exception("Transaksi sudah termasuk tahun yang ditutup buku");
        }

        $kodeGrup = $transaksiTarget->kode_grup_transaksi;

        // Ambil transaksi yang boleh disimulasikan ulang
        $transaksiUntukDijalankan = $transaksiSemua
            ->filter(fn($t) => Carbon::parse($t->tanggal)->year > $tahunTutupBuku && $t->kode_grup_transaksi !== $kodeGrup)
            ->map(fn($t) => clone $t);

        // Ambil ID transaksi yang akan dihapus (tahun > tutup buku)
        $transaksiIdsUntukDihapus = $transaksiSemua
            ->filter(fn($t) => Carbon::parse($t->tanggal)->year > $tahunTutupBuku)
            ->pluck('id');

            
        $tahunYangBolehDihapus = $transaksiSemua
            ->filter(fn($t) => Carbon::parse($t->tanggal)->year > $tahunTutupBuku)
            ->map(fn($t) => Carbon::parse($t->tanggal)->year)
            ->unique()
            ->values()
            ->toArray();


        

        Portofolio::where('user_id', $userId)
            ->whereYear('tanggal', '>', $tahunTutupBuku)
            ->delete();

        KinerjaPortofolio::where('user_id', $userId)
            ->whereIn(DB::raw('YEAR(tanggal)'), $tahunYangBolehDihapus)
            ->delete();

        // MutasiDana::where('user_id', $userId)
        //     ->where('tahun', $tahunYangBolehDihapus)
        //     ->delete();

        MutasiDana::where('user_id', $userId)
            ->whereIn('tahun', $tahunYangBolehDihapus)
            ->where('dari_tutup_buku', '!=', 1) // hanya hapus selain dari tutup buku
            ->delete();


        Historis::where('user_id', $userId)
            ->where('tahun', '>', $tahunTutupBuku)
            ->delete();

        Saldo::where('user_id', $userId)
            ->whereIn('transaksi_id', $transaksiIdsUntukDihapus)
            ->delete();

        Transaksi::whereIn('id', $transaksiIdsUntukDihapus)->delete();

        // Hapus perubahan harga dari transaksi yang sudah tidak valid (transaksi yang dihapus)
        PerubahanHarga::where('user_id', $userId)
            ->whereIn('sumber', ['transaksi', 'realtime'])
            ->whereYear('tanggal', '>', $tahunTutupBuku)
            ->delete();


        // Jalankan ulang transaksi
        foreach ($transaksiUntukDijalankan as $transaksi) {
            $requestBaru = $this->generateRequestFromTransaksi($transaksi);

            if ($transaksi->jenis_transaksi === 'beli') {
                $this->store($requestBaru);
            } elseif ($transaksi->jenis_transaksi === 'jual') {
                $this->storeJual($requestBaru);
           } elseif ($transaksi->jenis_transaksi === 'kas' && $transaksi->deskripsi === 'Top-up saldo') {
                    $this->topUp($requestBaru);
                } elseif ($transaksi->jenis_transaksi === 'kas' && $transaksi->deskripsi === 'Withdraw saldo') {
                    $this->topUp($requestBaru);
                }
        }

        // Update harga terakhir
        $subQuery = PerubahanHarga::select('id')
            ->where('user_id', $userId)
            ->whereYear('tanggal', '>', $tahunTutupBuku)
            ->orderBy('created_at', 'desc')
            ->orderByRaw("FIELD(sumber, 'manual', 'realtime', 'transaksi')")
            ->get()
            ->unique('aset_id')
            ->pluck('id');

        $hargaTerakhirList = PerubahanHarga::whereIn('id', $subQuery)->get();

        // dd($subQuery, $hargaTerakhirList);

        foreach ($hargaTerakhirList as $perubahan) {
            $fakeRequest = new Request([
                'auth' => ['user' => ['id' => $userId]],
                'id_aset' => $perubahan->aset_id,
                'updateHarga1' => $perubahan->harga,
                'tanggal_harga' => $perubahan->tanggal,
            ]);

            $this->updateCurrentPrice(
                $fakeRequest,
                $userId,
                $perubahan->aset_id,
                $perubahan->harga,
                $perubahan->tanggal
            );
        }

        DB::commit();

        return response()->json([
            'message' => 'Berhasil menghapus transaksi dan membangun ulang data portofolio.',
            'auth' => $request->auth,
        ], Response::HTTP_OK);

    } catch (\Exception $e) {
        DB::rollBack();

        return response()->json([
            'message' => 'Gagal menghapus data: ' . $e->getMessage(),
            'auth' => $request->auth,
        ], Response::HTTP_BAD_REQUEST);
    }
}


    private function generateRequestFromTransaksi($transaksi)
    {
        $common = [
            'auth' => ['user' => ['id' => $transaksi->user_id]],
            'tanggal' => $transaksi->tanggal,
            'kode_grup_transaksi' => $transaksi->kode_grup_transaksi,
        ];

        if ($transaksi->jenis_transaksi === 'beli') {
            return new Request(array_merge($common, [
                'jenis_transaksi' => 'beli',
                'aset_id' => $transaksi->aset_id,
                'sekuritas' => $transaksi->sekuritas_id,
                'volume' => $transaksi->volume,
                'harga' => $transaksi->harga,
            ]));
        } elseif ($transaksi->jenis_transaksi === 'jual') {
            return new Request(array_merge($common, [
                'jenis_transaksi' => 'jual',
                'aset_id' => $transaksi->aset_id,
                'sekuritas' => $transaksi->sekuritas_id,
                'volume' => $transaksi->volume,
                'harga' => $transaksi->harga,
            ]));
        } elseif ($transaksi->jenis_transaksi === 'kas') {
            $isTopUp = $transaksi->deskripsi === 'Top-up saldo';

            return new Request(array_merge($common, [
                'tipe_saldo' => $isTopUp ? 'masuk' : 'keluar',
                'aset_id' => '1',
                'saldo' => $transaksi->volume * $transaksi->harga,
            ]));
        }
        return new Request($common); // fallback jika jenis_transaksi tidak dikenali
    }

    public function topUp(Request $request)
    {
        DB::beginTransaction();
        // dd($request);

        try {
            $request->validate([
                'saldo' => 'required|integer|min:1',
                'tipe_saldo' => 'required|in:masuk,keluar,dividen',
                'tanggal' => 'required|date',
            ]);
            // dd($request);
            $userId = $request->auth['user']['id'];
            $jumlahSaldo = $request->input('saldo');
            $tipeSaldo = $request->input('tipe_saldo');
            $asetId = $request->input('aset_id');
            $tanggal = $request->input('tanggal');
            // dd($userId, $jumlahSaldo, $tipeSaldo, $asetId, $tanggal);
            // dd($tipeSaldo === 'masuk');
            if ($tipeSaldo === 'masuk') {
                $response = $this->handleSaldoMasuk($request, $userId, $jumlahSaldo, $tanggal);
            } elseif ($tipeSaldo === 'keluar') {
                $response = $this->handleSaldoKeluar($request, $userId, $jumlahSaldo, $tanggal);
            } elseif ($tipeSaldo === 'dividen') {
                $response = $this->handleSaldoDividen($request, $userId, $jumlahSaldo, $tanggal, $asetId);
            } else {
                throw new \Exception('Tipe saldo tidak valid.');
            }

            DB::commit();
            return $response;

        } catch (\Exception $e) {
            DB::rollBack();

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
        // dd($request, $userId, $jumlahSaldo, $tanggal);
        $kodeGrup = Str::uuid();

        $tahun = date('Y', strtotime($tanggal));
        $bulan = date('n', strtotime($tanggal));
        // Tambahkan data ke Transaksi
        
        $transaksiBaru = Transaksi::create([
            'user_id' => $userId,
            'kode_grup_transaksi' => $kodeGrup,
            'aset_id' => 1, // ID aset untuk "kas"
            'jenis_transaksi' => 'kas',
            'tanggal' => $tanggal,
            'volume' => 1,
            'harga' => $jumlahSaldo,
            'deskripsi' => 'Top-up saldo',
        ]);
        // dd($transaksiBaru);

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
        // dd(!$mutasiDanaTerakhir || $mutasiDanaTerakhir->tahun < $tahun);
        // Jika belum ada data mutasi dana untuk tahun ini
        if (!$mutasiDanaTerakhir || $mutasiDanaTerakhir->tahun < $tahun) {
            $modalBaru = optional($kinerjaPortofolioTerakhir)->valuasi_saat_ini + $jumlahSaldo;
            $hargaUnitLama = $mutasiDanaTerakhir->harga_unit_saat_ini ?? 1000;

            $jumlahUnitPenyertaanBaru = round(
                $modalBaru / $hargaUnitLama, 4
            );

            $hargaUnitSaatIni = round(
                $modalBaru / $jumlahUnitPenyertaanBaru
            , 4);

            $mutasiDanaBaru = MutasiDana::create([
                'user_id' => $userId,
                'kode_grup_transaksi' => $kodeGrup,
                'tahun' => $tahun,
                'bulan' => $bulan,
                'modal' => $modalBaru,
                'harga_unit' => $hargaUnitSaatIni,
                'harga_unit_saat_ini' => $hargaUnitSaatIni,
                'jumlah_unit_penyertaan' => $jumlahUnitPenyertaanBaru,
                'alur_dana' => $jumlahSaldo,
            ]);
            // dd($mutasiDanaBaru);
            // Tambahkan data ke Kinerja Portofolio
            $valuasiSaatIniBaru = $modalBaru;
            $kinerjaPortofolioBaru = KinerjaPortofolio::create([
                'user_id' => $userId,
                'tanggal' => $tanggal,
                'kode_grup_transaksi' => $kodeGrup,
                'transaksi_id' => $transaksiBaru->id, // ID transaksi akan diisi setelah transaksi dibuat
                'valuasi_saat_ini' => $valuasiSaatIniBaru,
                'yield' => 0.00,
            ]);
            // dd($kinerjaPortofolioBaru);
            // dd(!$historisTerkait);
            // dd($kodeGrup);
            // Tambahkan data ke Historis
            if (!$historisTerkait) {
                Historis::create([
                    'kode_grup_transaksi' => $kodeGrup,
                    'user_id' => $userId,
                    'tahun' => $tahun,
                    'bulan' => $bulan,
                    'yield' => 0.00,
                ]);
            } else {
                $historisTerkait->update([
                    'kode_grup_transaksi' => $kodeGrup,
                    'yield' => 0.00,
                ]);
            }

            // Tambahkan data ke Portofolio
            Portofolio::create([
                'user_id' => $userId,
                'tanggal' => $tanggal,
                'kode_grup_transaksi' => $kodeGrup,
                'aset_id' => 1, // ID aset untuk "kas"
                'kinerja_portofolio_id' => $kinerjaPortofolioBaru->id,
                'volume' => 1,
                'cur_price' => $valuasiSaatIniBaru,
            ]);

        } else {
            // Jika sudah ada data mutasi dana untuk tahun ini
            $modalLama = $mutasiDanaTerakhir->modal;
            // $hargaUnitSaatIni = ceil(
            //     ($kinerjaPortofolioTerakhir->valuasi_saat_ini ?? 0) / ($mutasiDanaTerakhir->jumlah_unit_penyertaan ?? 0)
            // );
            $jumlahUnitPenyertaan = $mutasiDanaTerakhir->jumlah_unit_penyertaan ?? 0;
            $hargaUnitSaatIni = $jumlahUnitPenyertaan > 0
                ? round(($kinerjaPortofolioTerakhir->valuasi_saat_ini ?? 0) / $jumlahUnitPenyertaan, 4)
                : 1000;
            $hargaUnit = $mutasiDanaTerakhir->harga_unit;

            $jumlahUnitPenyertaanBaru = ($jumlahSaldo / $hargaUnitSaatIni) + $mutasiDanaTerakhir->jumlah_unit_penyertaan;

            $mutasiDanaBaru = MutasiDana::create([
                'user_id' => $userId,
                'kode_grup_transaksi' => $kodeGrup,
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
            $yield = round(($hargaUnitSaatIni - ($mutasiDanaTerakhir->harga_unit ?? 0)) / ($mutasiDanaTerakhir->harga_unit ?? 1) * 100, 2);

            $kinerjaPortofolioBaru = KinerjaPortofolio::create([
                'user_id' => $userId,
                'tanggal' => $tanggal,
                'kode_grup_transaksi' => $kodeGrup,
                'transaksi_id' => $transaksiBaru->id, // ID transaksi akan ditambahkan di bawah
                'valuasi_saat_ini' => $valuasiSaatIniBaru,
                'yield' => $yield,
            ]);

            // Update atau tambahkan data ke Historis
            if ($historisTerkait) {
                $historisTerkait->update([
                    'yield' => $yield,
                    'kode_grup_transaksi' => $kodeGrup,
                ]);
            } else {
                Historis::create([
                    'user_id' => $userId,
                    'kode_grup_transaksi' => $kodeGrup,
                    'tahun' => $tahun,
                    'bulan' => $bulan,
                    'yield' => $yield,
                ]);
            }

            // Tambahkan data ke Portofolio
            $curPriceBaru = ($portofolioTerakhir->cur_price ?? 0) + $jumlahSaldo;

            Portofolio::create([
                'user_id' => $userId,
                'tanggal' => $tanggal,
                'aset_id' => 1, // ID aset untuk "kas"
                'kinerja_portofolio_id' => $kinerjaPortofolioBaru->id,
                'volume' => 1,
                'cur_price' => $curPriceBaru,
            ]);
        }

        // Tambahkan data ke Saldo
        $saldoBaru = Saldo::create([
            'user_id' => $userId,
            'kode_grup_transaksi' => $kodeGrup,
            'transaksi_id' => $transaksiBaru->id,
            'tanggal' => $tanggal,
            'tipe_saldo' => 'masuk',
            'saldo' => $jumlahSaldo,
        ]);

        // dd($saldoBaru);

        return response()->json([
            'status' => 'success',
            'auth' => $request->auth,
            'message' => 'Berhasil melakukan top-up dana.',
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
        $kodeGrup = Str::uuid();

        $tahun = date('Y', strtotime($tanggal));
        $bulan = date('n', strtotime($tanggal));
        // Validasi saldo cukup
        $totalSaldo = Saldo::where('user_id', $userId)->sum('saldo');
        if ($jumlahSaldo > $totalSaldo) {
            return response()->json([
                'status' => 'error',
                'message' => 'Saldo tidak mencukupi.',
            ], Response::HTTP_BAD_REQUEST);
        } else if ($jumlahSaldo == $totalSaldo) {
            $maksimalWd = number_format(($jumlahSaldo - 1), 0, ',', '.');
            return response()->json([
                'status' => 'error',
                'message' => 'Maksimal withdraw saldo adalah Rp. '.$maksimalWd,
            ], Response::HTTP_BAD_REQUEST);
        }

        $jumlahSaldo = -abs($jumlahSaldo);

        // Tambahkan data ke Transaksi
        
        $transaksiBaru = Transaksi::create([
            'user_id' => $userId,
            'kode_grup_transaksi' => $kodeGrup,
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

            $jumlahUnitPenyertaanBaru = round(
                $modalBaru / $hargaUnitLama
            , 4);

            $hargaUnitSaatIni = round(
                $modalBaru / $jumlahUnitPenyertaanBaru
            , 4);

            $mutasiDanaBaru = MutasiDana::create([
                'user_id' => $userId,
                'kode_grup_transaksi' => $kodeGrup,
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
                'tanggal' => $tanggal,
                'kode_grup_transaksi' => $kodeGrup,
                'transaksi_id' => $transaksiBaru->id, // ID transaksi akan diisi setelah transaksi dibuat
                'valuasi_saat_ini' => $valuasiSaatIniBaru,
                'yield' => 0.00,
            ]);

            // Tambahkan data ke Historis
            if (!$historisTerkait) {
                Historis::create([
                    'user_id' => $userId,
                    'kode_grup_transaksi' => $kodeGrup,
                    'tahun' => $tahun,
                    'bulan' => $bulan,
                    'yield' => 0.00,
                ]);
            } else {
                $historisTerkait->update([
                    'yield' => 0.00,
                    'kode_grup_transaksi' => $kodeGrup,
                ]);
            }

            // Tambahkan data ke Portofolio
            Portofolio::create([
                'user_id' => $userId,
                'tanggal' => $tanggal,
                'kode_grup_transaksi' => $kodeGrup,
                'aset_id' => 1, // ID aset untuk "kas"
                'kinerja_portofolio_id' => $kinerjaPortofolioBaru->id,
                'volume' => 1,
                'cur_price' => $valuasiSaatIniBaru,
            ]);

        } else {
            // Jika sudah ada data mutasi dana untuk tahun ini
            $modalLama = $mutasiDanaTerakhir->modal;
            $hargaUnit = $mutasiDanaTerakhir->harga_unit;
            // $hargaUnitSaatIni = ceil(
            //     ($kinerjaPortofolioTerakhir->valuasi_saat_ini ?? 0) / $mutasiDanaTerakhir->jumlah_unit_penyertaan
            // );

            $jumlahUnitPenyertaan = $mutasiDanaTerakhir->jumlah_unit_penyertaan ?? 0;
            $hargaUnitSaatIni = $jumlahUnitPenyertaan > 0
                ? round(($kinerjaPortofolioTerakhir->valuasi_saat_ini ?? 0) / $jumlahUnitPenyertaan, 4)
                : 1000;

            $jumlahUnitPenyertaanBaru = ($jumlahSaldo / $hargaUnitSaatIni) + $mutasiDanaTerakhir->jumlah_unit_penyertaan;

            $mutasiDanaBaru = MutasiDana::create([
                'user_id' => $userId,
                'kode_grup_transaksi' => $kodeGrup,
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
            $yield = round(($hargaUnitSaatIni - ($mutasiDanaTerakhir->harga_unit ?? 0)) / ($mutasiDanaTerakhir->harga_unit ?? 1) * 100, 2);

            $kinerjaPortofolioBaru = KinerjaPortofolio::create([
                'user_id' => $userId,
                'tanggal' => $tanggal,
                'kode_grup_transaksi' => $kodeGrup,
                'transaksi_id' => $transaksiBaru->id, // ID transaksi akan ditambahkan di bawah
                'valuasi_saat_ini' => $valuasiSaatIniBaru,
                'yield' => $yield,
            ]);

            // Update atau tambahkan data ke Historis
            if ($historisTerkait) {
                $historisTerkait->update([
                    'yield' => $yield,
                    'kode_grup_transaksi' => $kodeGrup,
                ]);
            } else {
                Historis::create([
                    'user_id' => $userId,
                    'kode_grup_transaksi' => $kodeGrup,
                    'tahun' => $tahun,
                    'bulan' => $bulan,
                    'yield' => $yield,
                ]);
            }

            // Tambahkan data ke Portofolio
            $curPriceBaru = ($portofolioTerakhir->cur_price ?? 0) + $jumlahSaldo;

            Portofolio::create([
                'user_id' => $userId,
                'tanggal' => $tanggal,
                'kode_grup_transaksi' => $kodeGrup,
                'aset_id' => 1, // ID aset untuk "kas"
                'kinerja_portofolio_id' => $kinerjaPortofolioBaru->id,
                'volume' => 1,
                'cur_price' => $curPriceBaru,
            ]);
        }

        // Tambahkan data ke Saldo
        $saldoBaru = Saldo::create([
            'user_id' => $userId,
            'kode_grup_transaksi' => $kodeGrup,
            'transaksi_id' => $transaksiBaru->id,
            'tanggal' => $tanggal,
            'tipe_saldo' => 'keluar',
            'saldo' => $jumlahSaldo,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Berhasil melakukan withdraw dana.',
            'auth' => $request->auth,
            'data' => [
                'saldo' => $saldoBaru,
                'transaksi' => $transaksiBaru,
                'mutasi_dana' => $mutasiDanaBaru ?? null,
                'kinerja_portofolio' => $kinerjaPortofolioBaru ?? null,
            ],
        ],Response::HTTP_CREATED);
    }

    public function tutupBuku(Request $request, $id)
    {
        // dd($request, $id);
        $userId = $request->auth['user']['id'];
        $tahun = $id;

        // Cek apakah sudah pernah ditutup
        $sudahTutup = TutupBuku::where('user_id', $userId)->where('tahun', $tahun)->exists();
        // dd($sudahTutup);
        if ($sudahTutup) {
            return response()->json(['message' => 'Tahun ini sudah ditutup.'], 400);
        }

        // Ambil data akhir tahun
        // $valuasiAkhir = KinerjaPortofolio::where('user_id', $userId)
        //     ->whereYear('updated_at', $tahun)
        //     ->latest('updated_at')
        //     ->value('valuasi_saat_ini');

            

$valuasiAkhir = KinerjaPortofolio::where('user_id', $userId)
    ->whereHas('transaksi', function ($query) use ($tahun) {
        $query->whereYear('tanggal', $tahun);
    })
    ->with('transaksi') // opsional, kalau kamu butuh data transaksinya juga
    ->latest('updated_at')
    ->value('valuasi_saat_ini');


        $hargaUnitAkhir = MutasiDana::where('user_id', $userId)
            ->where('tahun', $tahun)
            ->latest('tahun')
            ->value('harga_unit_saat_ini');

        $unitPenyertaanAwal = $valuasiAkhir / $hargaUnitAkhir;

        // dd($valuasiAkhir, $hargaUnitAkhir, $unitPenyertaanAwal);

        // Simpan data tutup buku
        TutupBuku::create([
            'user_id' => $userId,
            'tahun' => $tahun,
            'harga_unit_akhir' => $hargaUnitAkhir,
            'valuasi_akhir' => $valuasiAkhir,
            'unit_penyertaan_awal' => $unitPenyertaanAwal,
        ]);

        MutasiDana::create([
            'user_id' => $userId,
            'tahun' => $tahun + 1,
            'bulan' => 1,
            'modal' => $valuasiAkhir,
            'harga_unit' => $hargaUnitAkhir,
            'harga_unit_saat_ini' => $hargaUnitAkhir,
            'jumlah_unit_penyertaan' => $unitPenyertaanAwal,
            'alur_dana' => $valuasiAkhir,
            'dari_tutup_buku' => true, // kalau Anda menambahkan field ini
        ]);

   
        return response()->json(['message' => 'Tutup buku berhasil.']);
    }

    public function indexTutupBuku(Request $request) 
    {
        try {
            $tutup_buku = new TutupBuku();
            if($request->auth['user_type'] == 'user') {
                $tutup_buku = $tutup_buku->where('user_id', $request->auth['user']['id'])->get();
            }
            return response()->json([
                'message' => 'Berhasil mendapatkan tutup buku.',
                'auth' => $request->auth,
                'data' => [
                    'tutup_buku' => $tutup_buku
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