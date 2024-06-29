<?php

namespace App\Http\Controllers;

use App\Models\PortofolioBeli;
use App\Models\Saham;
use App\Models\PortofolioJual;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Validation\ValidationException;
use Exception;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Support\Facades\Http;


class PostBeliController extends Controller
{
    // $token = request()->bearerToken();
    // $accessToken = PersonalAccessToken::findToken($token);
    // $current_user = $accessToken->tokenable;
    // dd($portobeli);
    // $portobeli = PortofolioBeli::where('user_id', '=', $current_user->user_id)->get();
    
    public function indexporto(Request $request)
{
    // Fetch buy and sell portfolios
    $portobeli = PortofolioBeli::where('user_id', $request->auth['user']['user_id'])->with('emiten')->get()->toArray();
    $portojual = PortofolioJual::where('user_id', $request->auth['user']['user_id'])->with('emiten')->get()->toArray();

    // Initialize variables
    $groupedByEmiten = [];
    $result = [];

    // Process buy portfolios
    foreach ($portobeli as $item) {
        $emitenId = $item['emiten'][0]['id_saham'];
        if (!isset($groupedByEmiten[$emitenId])) {
            $groupedByEmiten[$emitenId] = [
                'nama_saham' => $item['emiten'][0]['nama_saham'],
                'vol_beli' => 0,
                'vol_jual' => 0,
                'avg_beli' => 0,
                'avg_jual' => 0,
                'equity' => 0, // Initialize equity
                'return' => 0,
                'tanggal' => $item['tanggal_beli'], // Use the buy date initially
                'buy_count' => 0, // Track number of buy transactions
                'sell_count' => 0 // Track number of sell transactions
            ];
        }
        $groupedByEmiten[$emitenId]['vol_beli'] += (int)$item['volume_beli'];
        $groupedByEmiten[$emitenId]['equity'] += isset($item['harga_total']) ? (float)$item['harga_total'] : 0; // Add to equity
        $groupedByEmiten[$emitenId]['buy_count']++; // Increment buy transaction count
    }

    // Process sell portfolios
    foreach ($portojual as $item) {
        $emitenId = $item['emiten'][0]['id_saham'];
        if (!isset($groupedByEmiten[$emitenId])) {
            $groupedByEmiten[$emitenId] = [
                'nama_saham' => $item['emiten'][0]['nama_saham'],
                'vol_beli' => 0,
                'vol_jual' => 0,
                'avg_beli' => 0,
                'avg_jual' => 0,
                'equity' => 0, // Initialize equity
                'return' => 0,
                'tanggal' => $item['tanggal_jual'], // Use the sell date initially
                'buy_count' => 0, // Track number of buy transactions
                'sell_count' => 0 // Track number of sell transactions
            ];
        }
        $groupedByEmiten[$emitenId]['vol_jual'] += (int)$item['volume_jual'];
        $groupedByEmiten[$emitenId]['equity'] -= isset($item['harga_total']) ? (float)$item['harga_total'] : 0; // Subtract from equity
        $groupedByEmiten[$emitenId]['sell_count']++; // Increment sell transaction count

        // Ensure vol_beli doesn't go negative
        if ($groupedByEmiten[$emitenId]['vol_beli'] < 0) {
            $groupedByEmiten[$emitenId]['vol_beli'] = 0;
        }

        // Ensure equity doesn't go negative
        if ($groupedByEmiten[$emitenId]['equity'] < 0) {
            $groupedByEmiten[$emitenId]['equity'] = 0;
        }
    }

    // Calculate vol_total and other values for each entry
    foreach ($groupedByEmiten as &$data) {
        $vol_total = max($data['vol_beli'] - $data['vol_jual'], 0); // Ensure vol_total is not negative
        $data['avg_beli'] = $data['buy_count'] > 0 ? ceil($data['vol_beli'] / $data['buy_count']) : 0; // Calculate avg_beli
        $data['avg_jual'] = $data['sell_count'] > 0 ? ceil($data['vol_jual'] / $data['sell_count']) : 0; // Calculate avg_jual

        // Fetch the latest stock price for the emiten
        $response = Http::acceptJson()
            ->withHeaders([
                'X-API-KEY' => config('goapi.apikey')
            ])->withoutVerifying() // Disable SSL verification
            ->get('https://api.goapi.io/stock/idx/prices?symbols='. $data['nama_saham'])
            ->json();
        $hargasaham = $response['data']['results'][0]['close'];
        $hargaclose = $vol_total * $hargasaham;
        $data['return'] = $hargaclose - $data['equity'];

        $result[] = [
            'close' => $hargasaham,
            'emiten' => $data['nama_saham'],
            'vol_beli' => $data['vol_beli'],
            'vol_jual' => $data['vol_jual'],
            'vol_total' => $vol_total,
            'avg_beli' => $data['avg_beli'],
            'avg_jual' => $data['avg_jual'],
            'tanggal' => $data['tanggal'],
            'equity' => $data['equity'], // Add equity to the result
            'return' => $data['return'],
            'buy_count' => $data['buy_count'], // Add buy_count to the result
            'sell_count' => $data['sell_count'] // Add sell_count to the result
        ];
    }

    return response()->json($result);
}







}
