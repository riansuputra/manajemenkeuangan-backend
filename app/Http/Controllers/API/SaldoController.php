<?php

namespace App\Http\Controllers\API;

use Exception;
use App\Models\Saldo;
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
                'user_id' => 'required',
                'tanggal' => 'required',
                'tipe_saldo' => 'required',
                'saldo' => 'required',
            ]);
            $saldo = new Saldo();
            $saldo->user_id = $request->auth['user']['id'];
            $saldo->tanggal = $request->tanggal;
            $saldo->tipe_saldo = $request->tipe_saldo;
            $saldo->saldo = $request->saldo;
            $saldo->save();
            return response()->json([
                'message' => 'Berhasil menambah saldo.',
                'auth' => $request->auth,
                'data' => [
                    'saldo' => $saldo
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
}
