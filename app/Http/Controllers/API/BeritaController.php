<?php

namespace App\Http\Controllers\API;

use Symfony\Component\HttpFoundation\Response;
use App\Http\Controllers\Controller;
use Illuminate\Validation\ValidationException;
use App\Models\Berita;
use App\Models\Dividen;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;

class BeritaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function store()
{
    $response = Http::acceptJson()
        ->withHeaders([
            'X-API-KEY' => config('goapi.apikey')
        ])->withoutVerifying()->get('https://api.goapi.io/stock/idx/news?page=5')->json();

    $data = $response['data']['results'];

    foreach ($data as $item) {
        Berita::updateOrCreate(
            [
                'judul' => $item['title'],
                'tanggal_terbit' => $item['published_at'],
                'link' => $item['url']
            ],
            [
                'gambar' => $item['image'],
                'deskripsi' => $item['description'],
                'nama_penerbit' => $item['publisher']['name'],
                'logo_penerbit' => $item['publisher']['logo'],
            ]
        );
    }

    $paginatedRecords = Berita::paginate(10);

    return response()->json($paginatedRecords);
}


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function index(Request $request)
    {
        try {
            $berita = Berita::all();
            return response()->json([
                'message' => 'Berhasil mendapatkan daftar berita.',
                'auth' => $request->auth,
                'data' => [
                    'berita' => $berita
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

    public function dividen(Request $request)
    {
        try {
            $dividen = Dividen::all();
            return response()->json([
                'message' => 'Berhasil mendapatkan daftar dividen.',
                'auth' => $request->auth,
                'data' => [
                    'dividen' => $dividen
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

    /**
     * Display the specified resource.
     */
    public function show(Berita $berita)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Berita $berita)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update() {
    
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Berita $berita)
    {
        //
    }
}
