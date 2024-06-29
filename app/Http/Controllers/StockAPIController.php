<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use App\Models\Saham;
use App\Models\Dividen;
use Carbon\Carbon;
class StockAPIController extends Controller
{
    public function index()
    {
        return Saham::paginate(10);
    }

    public function indexStock()
    {
        $response = Http::acceptJson()
            ->withHeaders([
                'X-API-KEY' => config('goapi.apikey')
            ])->withoutVerifying() // Disable SSL verification
            ->get('https://api.goapi.io/stock/idx/companies')
            ->json();

        $data = $response['data']['results'];

        return response()->json(['message' => 'Pemasukan created', 'data' => $data], 200);
    }

    public function getDataAdmin()
    {
        $data = Saham::paginate(25);
        //dd($data);

        return view('admin/emiten', ['data' => $data]);
    }

    public function stock($emiten)
    {
        $response = Http::acceptJson()
            ->withHeaders([
                'X-API-KEY' => config('goapi.apikey')
            ])->withoutVerifying() // Disable SSL verification
            ->get('https://api.goapi.io/stock/idx/prices?symbols='. $emiten)
            ->json();

        return response()->json(['response' => $response], 200);

        // dd($response);
    }

    public function ihsg()
    {
        $response = Http::acceptJson()
            ->withHeaders([
                'X-API-KEY' => config('goapi.apikey')
            ])->withoutVerifying() // Disable SSL verification
            ->get('https://api.goapi.io/stock/idx/indices?symbols=COMPOSITE')
            ->json();

        $ihsg = 7350;

        // Filter the results to include only the specific symbol "COMPOSITE"
        $filteredResults = array_filter($response['data']['results'], function($result) {
            return $result['symbol'] === 'COMPOSITE';
        });

        // Reset array keys
        $filteredResults = array_values($filteredResults);

        $ihsg_end = $filteredResults[0]['price']['close'];

        $yield_ihsg = ($ihsg_end - $ihsg) / $ihsg;

        // Convert the yield to a percentage
        $yield_percentage = $yield_ihsg * 100;

        // Format the percentage to 2 decimal places and append "%"
        $yield_percentage_formatted = number_format($yield_percentage, 2) . '%';

        return response()->json($yield_percentage_formatted);
    }



    

    public function updateStock()
    {
        $response = Http::acceptJson()
            ->withHeaders([
                'X-API-KEY' => config('goapi.apikey')
            ])->withoutVerifying()->get('https://api.goapi.io/stock/idx/companies')->json();

        $data = $response['data']['results'];

        foreach ($data as $item) {
            $insert = Saham::updateOrCreate(
                [
                    'nama_saham' => $item['symbol']
                ],
                [
                    'nama_saham' => $item['symbol'],
                    'nama_perusahaan' => $item['name'],
                    'pic' => $item['logo']
                ]
            );
        }

        return redirect('/')->with('status', 'Data emiten berhasil di update');

    }

    public function dividen(Request $request)
{
    $data = $request->validate([
        'emiten' => 'required',
        'dividen' => 'required|numeric',
    ]);

    // Ensure dividen is a numeric value
    $dividenValue = (float) $request->input('dividen');

    // Format the dividen value
    $formattedDividen = 'Rp. ' . number_format($dividenValue, 0, ',', '.');

    $dividen = Dividen::create([
        'emiten' => $request->input('emiten'),
        'dividen' => $formattedDividen,
    ]);

    return redirect('/')->with('status', 'Data emiten berhasil di update');
}

    public function indexdividen() {
        return Dividen::paginate(10); 
    }





    public function historical_30hari($symbol)
    {
        $end = Carbon::now()->format('Y-m-d');
        
        $start = Carbon::now()->subDays(30)->format('Y-m-d');
        $response = Http::acceptJson()
            ->withHeaders([
                'X-API-KEY' => config('goapi.apikey')
            ])->withoutVerifying()->get('https://api.goapi.io/stock/idx/'.$symbol.'/historical?from='.$start.'&to='.$end)->json();

        return response()->json(['response' => $response], 200);
    }

    public function historical_60hari($symbol)
    {
        $end = Carbon::now()->format('Y-m-d');
        
        $start = Carbon::now()->subDays(60)->format('Y-m-d');
        $response = Http::acceptJson()
            ->withHeaders([
                'X-API-KEY' => config('goapi.apikey')
            ])->withoutVerifying()->get('https://api.goapi.io/stock/idx/'.$symbol.'/historical?from='.$start.'&to='.$end)->json();

        return response()->json(['response' => $response], 200);
    }

    public function historical_90hari($symbol)
    {
        $end = Carbon::now()->format('Y-m-d');
        
        $start = Carbon::now()->subDays(90)->format('Y-m-d');
        $response = Http::acceptJson()
            ->withHeaders([
                'X-API-KEY' => config('goapi.apikey')
            ])->withoutVerifying()->get('https://api.goapi.io/stock/idx/'.$symbol.'/historical?from='.$start.'&to='.$end)->json();

        return response()->json(['response' => $response], 200);
    }

    public function historical_1tahun($symbol)
    {
        $end = Carbon::now()->format('Y-m-d');
        
        $start = Carbon::now()->subDays(360)->format('Y-m-d');
        $response = Http::acceptJson()
            ->withHeaders([
                'X-API-KEY' => config('goapi.apikey')
            ])->withoutVerifying()->get('https://api.goapi.io/stock/idx/'.$symbol.'/historical?from='.$start.'&to='.$end)->json();

        return response()->json(['response' => $response], 200);
    }

    // 30, 60, 90, 1 tahun

    public function delete($emiten)
    {
        $ticker = $emiten;
        $delete = Saham::where('nama_saham', $emiten)->delete();

        return redirect('/admin/emiten')->with('deleted', 'Data emiten berhasil di hapus');
    }
}