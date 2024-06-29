<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\KategoriPemasukan;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Validation\ValidationException;
use Exception;

class KategoriPemasukanController extends Controller
{
    public function index()
    {
        return KategoriPemasukan::paginate(10);
    }

    public function indexWeb(Request $request) {
        try {
            $kategoriPemasukan = new KategoriPemasukan();
            $kategoriPemasukan = $kategoriPemasukan->get();
            
            return response()->json([
                'message' => 'Berhasil mendapatkan daftar toko.',
                'auth' => $request->auth,
                'data' => [
                    'kategoriPemasukan' => $kategoriPemasukan
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
        $kategori = KategoriPemasukan::create($request->all());
        return response()->json($kategori, 201);
    }

    public function show($id)
    {
        return KategoriPemasukan::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $kategori = KategoriPemasukan::findOrFail($id);
        $kategori->update($request->all());
        return response()->json($kategori, 200);
    }

    public function destroy($id)
    {
        $kategori = KategoriPemasukan::findOrFail($id);
        $kategori->delete();
        return response()->json(['message' => 'Kategori Pemasukan deleted'], 200);
    }
}