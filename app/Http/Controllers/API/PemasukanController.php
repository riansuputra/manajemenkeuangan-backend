<?php

namespace App\Http\Controllers\API;

use Exception;
use App\Models\Pemasukan;
use App\Models\Pengeluaran;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Validation\ValidationException;

class PemasukanController extends Controller
{
    public function index(Request $request) 
    {
        try {
            $pemasukan = new Pemasukan();
            if($request->auth['user_type'] == 'user') {
                $pemasukan = $pemasukan->where('user_id', $request->auth['user']['id']);
            }
            $pemasukan = $pemasukan->with('kategori_pemasukan')
                                   ->get();
            return response()->json([
                'message' => 'Berhasil mendapatkan pemasukan.',
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

    public function store(Request $request)
    {
        try{
            $request->validate([
                'user_id' => 'required',
                'kategori_pemasukan_id' => 'required',
                'tanggal' => 'required',
                'jumlah' => 'required',
                'catatan' => 'nullable',
            ]);
            $pemasukan = new Pemasukan();
            $pemasukan->user_id = $request->auth['user']['id'];
            $pemasukan->kategori_pemasukan_id = $request->kategori_pemasukan_id;
            $pemasukan->tanggal = $request->tanggal;
            $pemasukan->jumlah = $request->jumlah;
            $pemasukan->catatan = $request->catatan;
            $pemasukan->save();
            return response()->json([
                'message' => 'Berhasil menambah pemasukan.',
                'auth' => $request->auth,
                'data' => [
                    'pemasukan' => $pemasukan
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