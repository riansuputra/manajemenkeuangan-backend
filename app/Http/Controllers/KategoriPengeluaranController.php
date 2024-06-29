<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\KategoriPengeluaran;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Validation\ValidationException;
use Exception;

class KategoriPengeluaranController extends Controller
{
    public function index()
    {
        return KategoriPengeluaran::paginate(10);
    }

    public function indexWeb(Request $request) {
        try {
            $kategoriPengeluaran = new KategoriPengeluaran();
            $kategoriPengeluaran = $kategoriPengeluaran->get();
            
            return response()->json([
                'message' => 'Berhasil mendapatkan daftar toko.',
                'auth' => $request->auth,
                'data' => [
                    'kategoriPengeluaran' => $kategoriPengeluaran
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
        $kategori = KategoriPengeluaran::create($request->all());
        return response()->json($kategori, 201);
    }

    public function show($id)
    {
        return KategoriPengeluaran::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $kategori = KategoriPengeluaran::findOrFail($id);
        $kategori->update($request->all());
        return response()->json($kategori, 200);
    }

    public function destroy($id)
    {
        $kategori = KategoriPengeluaran::findOrFail($id);
        $kategori->delete();
        return response()->json(['message' => 'Kategori Pengeluaran deleted'], 200);
    }
}
