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
    public function store(Request $request)
    {
        try{
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
            return response()->json([
                'message' => 'Berhasil update data kurs.',
                'auth' => $request->auth,
                'berita' => $paginatedRecords,
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

    public function create()
    {
        //
    }

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

    public function show(Berita $berita)
    {
        //
    }

    public function edit(Berita $berita)
    {
        //
    }

    public function update() {
        //
    }

    public function destroy(Berita $berita)
    {
        //
    }
}
