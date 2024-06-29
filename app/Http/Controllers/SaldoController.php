<?php

namespace App\Http\Controllers;

use App\Models\Saldo;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Validation\ValidationException;
use Exception;

class SaldoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Saldo::paginate(10);
    }

    public function indexWeb(Request $request) {
        try {
            $saldo = new Saldo();
            $saldo = Saldo::where('user_id', $request->auth['user']['user_id'])
                                ->with('kategori_pemasukan')
                                ->get();
            
            return response()->json([
                'message' => 'Berhasil mendapatkan daftar toko.',
                'auth' => $request->auth,
                'data' => [
                    'pemasukan' => $saldo
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
        $data = $request->validate([
            'user_id' => 'required',
            'saldo' => 'required',
        ]);

        $saldo = Saldo::create($data);
        return response()->json(['message' => 'Saldo created', 'saldo' => $saldo], 201);
    }

    public function saldoUser(Request $request)
    {
        $data = $request->validate([
            'user_id' => 'required',
        ]);

        $saldo = Saldo::where('user_id', $data['user_id'])->sum('saldo');
        return response()->json([
            "saldo" => $saldo
        ]);
    }

    public function show($id)
    {
        $saldo = Saldo::findOrFail($id);
        return response()->json(['saldo' => $saldo], 200);
    }

    public function update(Request $request, $id)
    {
        $saldo = Saldo::findOrFail($id);
        $data = $request->validate([
            'user_id' => 'required',
            'saldo' => 'required',
        ]);

        $saldo->update($data);
        return response()->json(['message' => 'Saldo updated', 'saldo' => $saldo], 200);
    }

    public function destroy($id)
    {
        $saldo = Saldo::findOrFail($id);
        $saldo->delete();
        return response()->json(['message' => 'Saldo deleted'], 200);
    }
}
