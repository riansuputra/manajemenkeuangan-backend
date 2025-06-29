<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\KategoriPengeluaran;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Validation\ValidationException;
use Exception;
use Illuminate\Validation\Rule;

class KategoriPengeluaranController extends Controller
{
    public function index(Request $request) 
    {
        try {
            $kategori_pengeluaran = KategoriPengeluaran::query();

            if ($request->auth['user_type'] == 'user') {
                $kategori_pengeluaran->where('user_id', $request->auth['user']['id'])
                                ->orWhereNull('user_id');
            }
            return response()->json([
                'message' => 'Berhasil mendapatkan kategori pengeluaran.',
                'auth' => $request->auth,
                'data' => [
                    'kategori_pengeluaran' => $kategori_pengeluaran->get()->map(function($item) {
                        return [
                            'id' => $item->id,
                            'user_id' => $item->user_id,
                            'nama_kategori_pengeluaran' => $item->nama_kategori, // field dinamis dari accessor
                            // Tambahkan field lain jika perlu
                        ];
                    })
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
                'nama_kategori_pengeluaran' => [
                    'required', 
                    'string', 
                    'max:100',
                    Rule::unique('kategori_pengeluaran')
                ],
            ]);
            $kategori_pengeluaran = new KategoriPengeluaran();
            $kategori_pengeluaran->nama_kategori_pengeluaran = $request->nama_kategori_pengeluaran;
            $kategori_pengeluaran->save();
            return response()->json([
                'message' => 'Berhasil menambah kategori pengeluaran.',
                'auth' => $request->auth,
                'data' => [
                    'kategori_pengeluaran' => $kategori_pengeluaran
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
            $kategori_pengeluaran = new KategoriPengeluaran();
            $kategori_pengeluaran = $kategori_pengeluaran->findOrFail($id);
            return response()->json([
                'message' => 'Berhasil mendapatkan detail kategori pengeluaran.',
                'auth' => $request->auth,
                'data' => [
                    'pemasukan' => $kategori_pengeluaran
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
            $kategori_pengeluaran = new KategoriPengeluaran();
            $kategori_pengeluaran = $kategori_pengeluaran->findOrFail($id);
            $request->validate([
                'nama_kategori_pengeluaran' => 'required',
            ]);
            $kategori_pengeluaran->nama_kategori_pengeluaran = $request->nama_kategori_pengeluaran;
            $kategori_pengeluaran->save();
            return response()->json([
                'message' => 'Berhasil mengubah kategori pengeluaran.',
                'auth' => $request->auth,
                'data' => [
                    'pemasukan' => $kategori_pengeluaran
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
            $kategori_pengeluaran = new KategoriPengeluaran();
            $kategori_pengeluaran = $kategori_pengeluaran->findOrFail($id)
                                   ->delete();
            return response()->json([
                'message' => 'Berhasil menghapus kategori pengeluaran.',
                'auth' => $request->auth,
                'data' => [
                    'pemasukan' => $kategori_pengeluaran
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
