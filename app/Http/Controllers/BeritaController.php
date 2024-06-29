<?php

namespace App\Http\Controllers;

use App\Models\Berita;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;

class BeritaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
{
    $response = Http::acceptJson()
        ->withHeaders([
            'X-API-KEY' => config('goapi.apikey')
        ])->withoutVerifying()->get('https://api.goapi.io/stock/idx/news?page=5')->json();

    $data = $response['data']['results'];

    foreach ($data as $item) {
        Berita::updateOrCreate(
            [
                'title' => $item['title'],
                'published_at' => $item['published_at'],
                'url' => $item['url']
            ],
            [
                'image' => $item['image'],
                'description' => $item['description'],
                'publisher_name' => $item['publisher']['name'],
                'publisher_logo' => $item['publisher']['logo'],
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
    public function store(Request $request)
    {
        //
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
