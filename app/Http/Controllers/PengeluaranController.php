<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pengeluaran;
use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Validation\ValidationException;
use Exception;

class PengeluaranController extends Controller
{
    public function index()
    {
        return Pengeluaran::paginate(10);
    }

    public function indexWeb(Request $request) {
        try {
            $pengeluaran = new Pengeluaran();
            $pengeluaran = $pengeluaran
                           ->where('user_id', $request->auth['user']['user_id'])
                           ->with('kategori_pengeluaran')
                           ->get();
            
            return response()->json([
                'message' => 'Berhasil mendapatkan daftar toko.',
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

    public function store(Request $request)
    {
        $data = $request->validate([
            'user_id' => 'required',
            'tanggal' => 'required',
            'jumlah' => 'required',
            'catatan' => 'nullable',
            'id_kategori_pengeluaran' => 'required',
        ]);

        $pengeluaran = Pengeluaran::create($data);
        return response()->json(['message' => 'Pengeluaran created', 'pengeluaran' => $pengeluaran], 201);
    }

    public function show($id)
    {
        $pengeluaran = Pengeluaran::findOrFail($id);
        return response()->json(['Pengeluaran' => $pengeluaran], 200);
    }

    public function update(Request $request, $id)
    {
        $pengeluaran = Pengeluaran::findOrFail($id);
        $data = $request->validate([
            'user_id' => 'required',
            'tanggal' => 'required',
            'jumlah' => 'required',
            'catatan' => 'nullable',
            'id_kategori_pengeluaran' => 'required',
        ]);

        $pengeluaran->update($data);
        return response()->json(['message' => 'Pengeluaran updated', 'pengeluaran' => $pengeluaran], 200);
    }

    public function destroy($id)
    {
        $pengeluaran = Pengeluaran::findOrFail($id);
        $pengeluaran->delete();
        return response()->json(['message' => 'Pengeluaran deleted'], 200);
    }
}
