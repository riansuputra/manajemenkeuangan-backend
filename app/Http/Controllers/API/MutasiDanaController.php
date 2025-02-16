<?php

namespace App\Http\Controllers\API;

use Exception;
use Carbon\Carbon;
use App\Models\MutasiDana;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class MutasiDanaController extends Controller
{
    public function index(Request $request) {
        try {
            $mutasi = new Saldo();
            if($request->auth['user_type'] == 'user') {
                $mutasi = $mutasi->where('user_id', $request->auth['user']['id']);
            }
            $mutasi = $mutasi->get();
            return response()->json([
                'message' => 'Berhasil mendapatkan mutasi dana.',
                'auth' => $request->auth,
                'data' => [
                    'mutasi' => $mutasi
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

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        //
    }

    public function show(string $id)
    {
        //
    }

    public function edit(string $id)
    {
        //
    }

    public function update(Request $request, string $id)
    {
        //
    }

    public function destroy(string $id)
    {
        //
    }
}
