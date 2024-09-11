<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\KategoriPemasukan;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Validation\ValidationException;
use Exception;

class KategoriPemasukanController extends Controller
{
    public function index(Request $request) 
    {
        try {
            $kategori_pemasukan = KategoriPemasukan::all();
            return response()->json([
                'message' => 'Berhasil mendapatkan kategori pemasukan.',
                'auth' => $request->auth,
                'data' => [
                    'kategori_pemasukan' => $kategori_pemasukan
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
                'nama_kategori_pemasukan' => 'required',
            ]);
            $kategori_pemasukan = new KategoriPemasukan();
            $kategori_pemasukan->nama_kategori_pemasukan = $request->nama_kategori_pemasukan;
            $kategori_pemasukan->save();
            return response()->json([
                'message' => 'Berhasil menambah kategori pemasukan.',
                'auth' => $request->auth,
                'data' => [
                    'kategori_pemasukan' => $kategori_pemasukan
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
            $kategori_pemasukan = new KategoriPemasukan();
            $kategori_pemasukan = $kategori_pemasukan->findOrFail($id);
            return response()->json([
                'message' => 'Berhasil mendapatkan detail kategori pemasukan.',
                'auth' => $request->auth,
                'data' => [
                    'kategori_pemasukan' => $kategori_pemasukan
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
            $kategori_pemasukan = new KategoriPemasukan();
            $kategori_pemasukan = $kategori_pemasukan->findOrFail($id);
            $request->validate([
                'nama_kategori_pemasukan' => 'required',
            ]);
            $kategori_pemasukan->nama_kategori_pemasukan = $request->nama_kategori_pemasukan;
            $kategori_pemasukan->save();
            return response()->json([
                'message' => 'Berhasil mengubah kategori pemasukan.',
                'auth' => $request->auth,
                'data' => [
                    'kategori_pemasukan' => $kategori_pemasukan
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
            $kategori_pemasukan = new KategoriPemasukan();
            $kategori_pemasukan = $kategori_pemasukan->findOrFail($id)
                                   ->delete();
            return response()->json([
                'message' => 'Berhasil menghapus kategori pemasukan.',
                'auth' => $request->auth,
                'data' => [
                    'kategori_pemasukan' => $kategori_pemasukan
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