<?php

namespace App\Http\Controllers\API;

use Exception;
use App\Models\Transaksi;
use App\Models\Portofolio;
use App\Models\Aset;
use App\Models\Saldo;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Validation\ValidationException;

class TransaksiController extends Controller
{
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

    public function store(Request $request)
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
                'cur_price' => 'nullable',
                'avg_price' => 'nullable',
            ]);

            $saldo = Saldo::where('user_id', $request->auth['user']['id'])->sum('saldo');
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
                        'tipe_saldo' => 'saldo',
                        'saldo' => -($total)
                    ]);
                }
            } else if (!($request['harga'])) {
                if ($saldo >= $request['volume']) {
                    $addsaldo = Saldo::create([
                        'user_id' => $request->auth['user']['id'],
                        'tanggal' => Carbon::now()->format('Y-m-d'),
                        'tipe_saldo' => 'saldo',
                        'saldo' => -($request['volume'])
                    ]);
                }
            } else {
                return response()->json([
                    'error' => 'Saldo tidak cukup.'
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
