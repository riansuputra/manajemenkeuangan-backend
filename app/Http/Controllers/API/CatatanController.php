<?php

namespace App\Http\Controllers\API;

use App\Models\Catatan;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Carbon\Carbon;
use Exception;

class CatatanController extends Controller
{
    public function index(Request $request) 
    {
        try {
            $now = Carbon::now();
            $catatan = Catatan::where('user_id', $request->auth['user']['id'])
                                ->get();
            return response()->json([
                'message' => 'Berhasil mendapatkan daftar catatan.',
                'auth' => $request->auth,
                'data' => [
                    'catatan' => $catatan,
                ],
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            if ($e instanceof ValidationException) {
                return response()->json([
                    'message' => $e->getMessage(),
                    'auth' => $request->auth,
                    'errors' => $e->validator->errors(),
                ], Response::HTTP_BAD_REQUEST);
            } else {
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
            $request->validate([
                'judul' => 'required',
                'catatan' => 'required',
                'tipe' => 'required',
            ]);
            $catatan = new Catatan();
            $catatan->user_id = $request->auth['user']['id'];
            $catatan->judul = $request->judul;
            $catatan->catatan = $request->catatan;
            $catatan->tipe = $request->tipe;
            $catatan->warna = $request->warna;
            $catatan->save();
            return response()->json([
                'message' => 'Berhasil menambah catatan.',
                'auth' => $request->auth,
                'data' => [
                    'catatan' => $catatan
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
        try {
            $catatan = new Catatan();
            if($request->auth['user_type'] == 'user') {
                $catatan = $catatan->where('user_id', $request->auth['user']['id']);
            }
            $catatan = $catatan->findOrFail($id);
            return response()->json([
                'message' => 'Berhasil mendapatkan detail catatan',
                'auth' => $request->auth,
                'data' => [
                    'catatan' => $catatan
                ]
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
            $catatan = new Catatan();
            $catatan = $catatan->where('user_id', $request->auth['user']['id'])
                                   ->findOrFail($id);
            $request->validate([
                'judul' => 'required',
                'catatan' => 'required',
                'tipe' => 'required',
            ]);
            $catatan->judul = $request->judul;
            $catatan->catatan = $request->catatan;
            $catatan->tipe = $request->tipe;
            $catatan->warna = $request->warna;
            $catatan->save();
            return response()->json([
                'message' => 'Berhasil mengubah catatan.',
                'auth' => $request->auth,
                'data' => [
                    'catatan' => $catatan
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
            $catatan = new Catatan();
            $catatan = $catatan->where('user_id', $request->auth['user']['id'])
                                   ->findOrFail($id)
                                   ->delete();
            return response()->json([
                'message' => 'Berhasil menghapus catatan.',
                'auth' => $request->auth,
                'data' => [
                    'catatan' => $catatan
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
