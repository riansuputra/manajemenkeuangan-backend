<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pemasukan;
use App\Http\Controllers\Controller;
use App\Models\Pengeluaran;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Validation\ValidationException;
use Exception;

class PemasukanController extends Controller
{
    public function index()
    {
        return Pemasukan::paginate(10);
    }

    public function indexWeb(Request $request) {
        try {
            $pemasukan = new Pemasukan();
            $pemasukan = Pemasukan::where('user_id', $request->auth['user']['user_id'])
                                ->with('kategori_pemasukan')
                                ->get();
            
            return response()->json([
                'message' => 'Berhasil mendapatkan daftar toko.',
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
        $data = $request->validate([
            'user_id' => 'required',
            'tanggal' => 'required',
            'jumlah' => 'required',
            'catatan' => 'nullable',
            'id_kategori_pemasukan' => 'required',
        ]);

        $pemasukan = Pemasukan::create($data);
        return response()->json(['message' => 'Pemasukan created', 'pemasukan' => $pemasukan], 201);
    }

    public function show($id)
    {
        $pemasukan = Pemasukan::findOrFail($id);
        return response()->json(['pemasukan' => $pemasukan], 200);
    }

    public function update(Request $request, $id)
    {
        $pemasukan = Pemasukan::findOrFail($id);
        $data = $request->validate([
            'user_id' => 'required',
            'tanggal' => 'required',
            'jumlah' => 'required',
            'catatan' => 'nullable',
            'id_kategori_pemasukan' => 'required',
        ]);

        $pemasukan->update($data);
        return response()->json(['message' => 'Pemasukan updated', 'pemasukan' => $pemasukan], 200);
    }

    public function destroy($id)
    {
        $pemasukan = Pemasukan::findOrFail($id);
        $pemasukan->delete();
        return response()->json(['message' => 'Pemasukan deleted'], 200);
    }
}
