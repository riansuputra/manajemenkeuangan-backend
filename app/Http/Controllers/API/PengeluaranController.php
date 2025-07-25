<?php

namespace App\Http\Controllers\API;

use Exception;
use App\Models\Pengeluaran;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Validation\ValidationException;

class PengeluaranController extends Controller
{
    public function index(Request $request) 
    {
        try {
            $pengeluaran = Pengeluaran::query();

            if ($request->auth['user_type'] == 'user') {
                $pengeluaran->where('user_id', $request->auth['user']['id']);
            }

            $pengeluaran = $pengeluaran->with('kategori_pengeluaran')->get();

            $result = $pengeluaran->map(function ($item) {
                return [
                    'id' => $item->id,
                    'user_id' => $item->user_id,
                    'kategori_pengeluaran_id' => $item->kategori_pengeluaran_id,
                    'tanggal' => $item->tanggal,
                    'jumlah' => $item->jumlah,
                    'catatan' => $item->catatan,
                    'created_at' => $item->created_at,
                    'updated_at' => $item->updated_at,
                    'kategori_pengeluaran' => $item->kategori_pengeluaran ? [
                        'id' => $item->kategori_pengeluaran->id,
                        'user_id' => $item->kategori_pengeluaran->user_id,
                        'nama_kategori_pengeluaran' => $item->kategori_pengeluaran->nama_kategori,
                        'created_at' => $item->kategori_pengeluaran->created_at,
                        'updated_at' => $item->kategori_pengeluaran->updated_at,
                    ] : null
                ];
            });

            return response()->json([
                'message' => 'Berhasil mendapatkan pengeluaran.',
                'auth' => $request->auth,
                'data' => [
                    'pengeluaran' => $result
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
                'kategori_pengeluaran_id' => 'required',
                'tanggal' => 'required',
                'jumlah' => 'required',
                'catatan' => 'nullable',
            ]);
            $pengeluaran = new Pengeluaran();
            $pengeluaran->user_id = $request->auth['user']['id'];
            $pengeluaran->kategori_pengeluaran_id = $request->kategori_pengeluaran_id;
            $pengeluaran->tanggal = $request->tanggal;
            $pengeluaran->jumlah = $request->jumlah;
            $pengeluaran->catatan = $request->catatan;
            $pengeluaran->save();
            return response()->json([
                'message' => 'Berhasil menambah pengeluaran.',
                'auth' => $request->auth,
                'data' => [
                    'pengeluaran' => $pengeluaran
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
            $pengeluaran = new Pengeluaran();
            if($request->auth['user_type'] == 'user') {
                $pengeluaran = $pengeluaran->where('user_id', $request->auth['user']['id']);
            }
            $pengeluaran = $pengeluaran->findOrFail($id);
            return response()->json([
                'message' => 'Berhasil mendapatkan detail pengeluaran.',
                'auth' => $request->auth,
                'data' => [
                    'pengeluaran' => $pengeluaran
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
            $pengeluaran = new Pengeluaran();
            $pengeluaran = $pengeluaran->where('user_id', $request->auth['user']['id'])
                                   ->findOrFail($id);
            $request->validate([
                'kategori_pengeluaran_id' => 'required',
                'tanggal' => 'required',
                'jumlah' => 'required',
                'catatan' => 'nullable',
            ]);
            $pengeluaran->kategori_pengeluaran_id = $request->kategori_pengeluaran_id;
            $pengeluaran->tanggal = $request->tanggal;
            $pengeluaran->jumlah = $request->jumlah;
            $pengeluaran->catatan = $request->catatan;
            $pengeluaran->save();
            return response()->json([
                'message' => 'Berhasil mengubah pengeluaran.',
                'auth' => $request->auth,
                'data' => [
                    'pengeluaran' => $pengeluaran
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
            $pengeluaran = new Pengeluaran();
            $pengeluaran = $pengeluaran->where('user_id', $request->auth['user']['id'])
                                   ->findOrFail($id)
                                   ->delete();
            return response()->json([
                'message' => 'Berhasil menghapus pengeluaran.',
                'auth' => $request->auth,
                'data' => [
                    'pengeluaran' => $pengeluaran
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