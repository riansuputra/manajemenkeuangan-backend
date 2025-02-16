<?php

namespace App\Http\Controllers\API;

use App\Models\Sekuritas;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Validation\ValidationException;
use Exception;

class SekuritasController extends Controller
{
    public function index(Request $request) {
        try {
            $sekuritas = Sekuritas::all();
            return response()->json([
                'message' => 'Berhasil mendapatkan sekuritas.',
                'auth' => $request->auth,
                'data' => [
                    'sekuritas' => $sekuritas
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
                'nama_sekuritas' => 'required',
                'fee' => 'nullable',
            ]);
            $sekuritas = new Sekuritas();
            $sekuritas->nama_sekuritas = $request->nama_sekuritas;
            $sekuritas->fee = $request->fee;
            $sekuritas->save();
            return response()->json([
                'message' => 'Berhasil menambah sekuritas.',
                'auth' => $request->auth,
                'data' => [
                    'sekuritas' => $sekuritas
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
            $sekuritas = new Sekuritas();
            $sekuritas = $sekuritas->findOrFail($id);
            return response()->json([
                'message' => 'Berhasil mendapatkan detail sekuritas.',
                'auth' => $request->auth,
                'data' => [
                    'sekuritas' => $sekuritas
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
            $sekuritas = new Sekuritas();
            $sekuritas = $sekuritas->findOrFail($id);
            $request->validate([
                'nama_sekuritas' => 'required',
                'fee' => 'nullable',
            ]);
            $sekuritas->nama_sekuritas = $request->nama_sekuritas;
            $sekuritas->fee = $request->fee;
            $sekuritas->save();
            return response()->json([
                'message' => 'Berhasil mengubah sekuritas.',
                'auth' => $request->auth,
                'data' => [
                    'sekuritas' => $sekuritas
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
            $sekuritas = new Sekuritas();
            $sekuritas = $sekuritas->findOrFail($id)
                                   ->delete();
            return response()->json([
                'message' => 'Berhasil menghapus sekuritas.',
                'auth' => $request->auth,
                'data' => [
                    'sekuritas' => $sekuritas
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
