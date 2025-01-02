<?php

namespace App\Http\Controllers\API;

use Exception;
use App\Models\HistorisBulanan;
use App\Models\HistorisTahunan;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Validation\ValidationException;

class HistorisDraftController extends Controller
{
    public function index(Request $request) 
    {
        try {
            $historis = new HistorisBulanan();
            if($request->auth['user_type'] == 'user') {
                $historis = $historis->where('user_id', $request->auth['user']['id']);
            }
            $historis = $historis->with('historis_tahunan')
                                   ->get();
            return response()->json([
                'message' => 'Berhasil mendapatkan historis.',
                'auth' => $request->auth,
                'data' => [
                    'historis' => $historis
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
                'user_id' => 'required',
                'bulan' => 'required',
                'yield' => 'nullable',
                'ihsg' => 'required',
                'lq45' => 'nullable',
                'tahun' => 'required',
            ]);
            $historis_tahunan = HistorisTahunan::where('user_id', $request->auth['user']['id'])
                                               ->where('tahun', $request->tahun)
                                               ->first();
            if ($historis_tahunan) {
                $historis_bulanan = HistorisBulanan::where('user_id', $request->auth['user']['id'])
                                                   ->where('historis_tahunan_id', $historis_tahunan->id)
                                                   ->where('bulan', $request->bulan)
                                                   ->first();
                if ($historis_bulanan) {
                    $historis_bulanan->yield = $request->yield;
                    $historis_bulanan->ihsg = $request->ihsg;
                    $historis_bulanan->lq45 = $request->lq45;
                    $historis_bulanan->save();
                } else {
                    $historis_bulanan_baru = new HistorisBulanan;
                    $historis_bulanan_baru->user_id = $request->auth['user']['id'];
                    $historis_bulanan_baru->historis_tahunan_id = $historis_tahunan->id;
                    $historis_bulanan_baru->bulan = $request->bulan;
                    $historis_bulanan_baru->yield = $request->yield;
                    $historis_bulanan_baru->ihsg = $request->ihsg;
                    $historis_bulanan_baru->lq45 = $request->lq45;
                    $historis_bulanan_baru->save();
                }
                $latest_bulanan = HistorisBulanan::where('user_id', $request->auth['user']['id'])
                                                 ->where('historis_tahunan_id', $historis_tahunan->id)
                                                 ->orderBy('bulan', 'desc')
                                                 ->first();
                if ($latest_bulanan) {
                    $historis_tahunan->yield = $latest_bulanan->yield;
                    $historis_tahunan->ihsg = $latest_bulanan->ihsg;
                    $historis_tahunan->lq45 = $latest_bulanan->lq45;
                    $historis_tahunan->save();
                }
            } else {
                $historis_tahunan_baru = new HistorisTahunan;
                $historis_tahunan_baru->user_id = $request->auth['user']['id'];
                $historis_tahunan_baru->tahun = $request->tahun;
                $historis_tahunan_baru->yield = $request->yield;
                $historis_tahunan_baru->ihsg = $request->ihsg;
                $historis_tahunan_baru->lq45 = $request->lq45;
                $historis_tahunan_baru->save();
            
                $historis_bulanan = new HistorisBulanan;
                $historis_bulanan->user_id = $request->auth['user']['id'];
                $historis_bulanan->historis_tahunan_id = $historis_tahunan_baru->id;
                $historis_bulanan->bulan = $request->bulan;
                $historis_bulanan->yield = $request->yield;
                $historis_bulanan->ihsg = $request->ihsg;
                $historis_bulanan->lq45 = $request->lq45;
                $historis_bulanan->save();
            }
            
            return response()->json([
                'message' => 'Berhasil menambah historis.',
                'auth' => $request->auth,
                'data' => [
                    'historis' => $historis_bulanan,
                    'historis_tahunan' => $historis_tahunan ?? $historis_tahunan_baru,
                    'historis_bulanan' => $historis_bulanan ?? $historis_bulanan_baru,
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

    public function show(Request $request, $year)
    {
        try{
            $historis_tahunan = HistorisTahunan::where('tahun', $year);
            if ($request->auth['user_type'] == 'user') {
                $historis_tahunan = $historis_tahunan->where('user_id', $request->auth['user']['id']);
            }
            $historis_tahunan = $historis_tahunan->with('historis_bulanan')->firstOrFail();
            return response()->json([
                'message' => 'Berhasil mendapatkan detail historis.',
                'auth' => $request->auth,
                'data' => [
                    'historis_tahunan' => $historis_tahunan,
                    'historis_bulanan' => $historis_tahunan->historis_bulanan
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
            $historis = new HistorisBulanan();
            $historis = $historis->where('user_id', $request->auth['user']['id'])
                                   ->findOrFail($id);
            $request->validate([
                'user_id' => 'required',
                'bulan' => 'required',
                'yield' => 'nullable',
                'ihsg' => 'required',
                'lq45' => 'nullable',
                'tahun' => 'required',
            ]);
            $historis->kategori_historis_id = $request->kategori_historis_id;
            $historis->tanggal = $request->tanggal;
            $historis->jumlah = $request->jumlah;
            $historis->catatan = $request->catatan;
            $historis->save();

            
            $historis_tahunan = HistorisTahunan::where('user_id', $request->auth['user']['id'])
                                               ->where('tahun', $request->tahun)
                                               ->first();
            if ($historis_tahunan) {
                $historis_bulanan = HistorisBulanan::where('user_id', $request->auth['user']['id'])
                                                   ->where('historis_tahunan_id', $historis_tahunan->id)
                                                   ->where('bulan', $request->bulan)
                                                   ->first();
                if ($historis_bulanan) {
                    $historis_bulanan->yield = $request->yield;
                    $historis_bulanan->ihsg = $request->ihsg;
                    $historis_bulanan->lq45 = $request->lq45;
                    $historis_bulanan->save();
                } else {
                    $historis_bulanan_baru = new HistorisBulanan;
                    $historis_bulanan_baru->user_id = $request->auth['user']['id'];
                    $historis_bulanan_baru->historis_tahunan_id = $historis_tahunan->id;
                    $historis_bulanan_baru->bulan = $request->bulan;
                    $historis_bulanan_baru->yield = $request->yield;
                    $historis_bulanan_baru->ihsg = $request->ihsg;
                    $historis_bulanan_baru->lq45 = $request->lq45;
                    $historis_bulanan_baru->save();
                }
                $latest_bulanan = HistorisBulanan::where('user_id', $request->auth['user']['id'])
                                                 ->where('historis_tahunan_id', $historis_tahunan->id)
                                                 ->orderBy('bulan', 'desc')
                                                 ->first();
                if ($latest_bulanan) {
                    $historis_tahunan->yield = $latest_bulanan->yield;
                    $historis_tahunan->ihsg = $latest_bulanan->ihsg;
                    $historis_tahunan->lq45 = $latest_bulanan->lq45;
                    $historis_tahunan->save();
                }
            } else {
                $historis_tahunan_baru = new HistorisTahunan;
                $historis_tahunan_baru->user_id = $request->auth['user']['id'];
                $historis_tahunan_baru->tahun = $request->tahun;
                $historis_tahunan_baru->yield = $request->yield;
                $historis_tahunan_baru->ihsg = $request->ihsg;
                $historis_tahunan_baru->lq45 = $request->lq45;
                $historis_tahunan_baru->save();
            
                $historis_bulanan = new HistorisBulanan;
                $historis_bulanan->user_id = $request->auth['user']['id'];
                $historis_bulanan->historis_tahunan_id = $historis_tahunan_baru->id;
                $historis_bulanan->bulan = $request->bulan;
                $historis_bulanan->yield = $request->yield;
                $historis_bulanan->ihsg = $request->ihsg;
                $historis_bulanan->lq45 = $request->lq45;
                $historis_bulanan->save();
            }
            
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
