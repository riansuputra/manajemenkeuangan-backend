<?php

namespace App\Http\Controllers;

use App\Models\PortofolioBeli;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Sekuritas;
use App\Models\Saham;
use App\Models\Saldo;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Http;
use Exception;
class PortofolioBeliController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return PortofolioBeli::paginate(10);
    }

    public function indexWeb(Request $request) {
        try {
            $portofolioBeli = new PortofolioBeli();
            $portofolioBeli = PortofolioBeli::where('user_id', $request->auth['user']['user_id'])
                                ->with('kategori_pemasukan')
                                ->get();
            
            return response()->json([
                'message' => 'Berhasil mendapatkan daftar toko.',
                'auth' => $request->auth,
                'data' => [
                    'pemasukan' => $portofolioBeli
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

    public function store(Request $request) {
        $data = $request->validate([
            'user_id' => 'required',
            'id_saham' => 'required',
            'tanggal_beli' => 'required',
            'volume_beli' => ' required',
            'id_sekuritas' => 'nullable',
        ]);

        $saham = Saham::where('id_saham', '=', $data['id_saham'])->first()->toArray();
        $response = Http::acceptJson()
            ->withHeaders([
                'X-API-KEY' => config('goapi.apikey')
            ])->withoutVerifying() // Disable SSL verification
            ->get('https://api.goapi.io/stock/idx/prices?symbols='. $saham['nama_saham'])
            ->json();
        $hargasaham = $response['data']['results'][0]['close'];

        // dd($hargasaham);
        $lot = 100;
        $pembelian = $data['volume_beli'] * $lot * $hargasaham;
        // dd($volume);

        $sekuritas = Sekuritas::where('id_sekuritas', '=', $data['id_sekuritas'])->first()->toArray();
        $potongan = ceil($pembelian * $sekuritas['fee'] / 100);
        $data['harga_total'] = $pembelian;
        $data['pembelian'] = $pembelian + $potongan;
        $data['harga_beli'] = $hargasaham;
        
        // Sum all 'saldo' values for the given user_id
        $saldo = Saldo::where('user_id', $request->auth['user']['user_id'])->sum('saldo');
        // dd($saldo);


        if (!$saldo) {
            return response()->json(['error' => 'Saldo not found for the user.'], 404);
        }
    
        // Check if saldo is sufficient
        if ($saldo >= $data['pembelian']) {
            // Deduct pembelian from saldo
            $addsaldo = Saldo::create([
                'user_id' => $request->auth['user']['user_id'],
                'saldo' => -($data['pembelian'])
            ]);
    
        } else {
            return response()->json(['error' => 'Insufficient saldo.'], 400);
        }

        $portofolioBeli = PortofolioBeli::create($data);
        return response()->json(['message' => 'PortofolioBeli created', 'portofolioBeli' => $portofolioBeli], 201);
    }

    public function show($id)
    {
        $portofolioBeli = PortofolioBeli::findOrFail($id);
        return response()->json(['portofolioBeli' => $portofolioBeli], 200);
    }

    public function update(Request $request, $id)
    {
        $portofolioBeli = PortofolioBeli::findOrFail($id);
        $data = $request->validate([
            'user_id' => 'required',
            'id_saham' => 'required',
            'volume_beli' => 'required',
            'tanggal_beli' => 'required',
            'harga_beli' => 'required',
            'harga_total' => 'required',
            'pembelian' => ' required',
            'id_sekuritas' => 'nullable',
        ]);

        $portofolioBeli->update($data);
        return response()->json(['message' => 'PortofolioBeli updated', 'portofolioBeli' => $portofolioBeli], 200);
    }

    public function destroy($id)
    {
        $portofolioBeli = PortofolioBeli::findOrFail($id);
        $portofolioBeli->delete();
        return response()->json(['message' => 'PortofolioBeli deleted'], 200);
    }
}
