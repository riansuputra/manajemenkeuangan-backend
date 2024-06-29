<?php

namespace App\Http\Controllers;

use App\Models\Kurs;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;

class KursController extends Controller
{
    /**
     * Display a listing of the resource.
     */


     public function index()
     {
         // Define the currency names mapping
         $currencyNames = [
             'AUD' => 'Australian Dollar (AUD)',
             'CAD' => 'Canadian Dollar (CAD)',
             'CHF' => 'Swiss Franc (CHF)',
             'CNY' => 'Chinese Yuan (CNY)',
             'EUR' => 'Euro (EUR)',
             'GBP' => 'British Pound Sterling (GBP)',
             'HKD' => 'Hong Kong Dollar (HKD)',
             'INR' => 'Indian Rupee (INR)',
             'JPY' => 'Japanese Yen (JPY)',
             'KRW' => 'South Korean Won (KRW)',
             'MYR' => 'Malaysian Ringgit (MYR)',
             'NZD' => 'New Zealand Dollar (NZD)',
             'SGD' => 'Singapore Dollar (SGD)',
             'THB' => 'Thai Baht (THB)',
             'USD' => 'US Dollar (USD)'
         ];
     
         $money_apikey = config('goapi.money_apikey');
         $response = Http::acceptJson()
             ->withoutVerifying()
             ->get('https://api.freecurrencyapi.com/v1/latest', [
                 'apikey' => $money_apikey,
                 'currencies' => 'USD,AUD,EUR,GBP,JPY,MYR,CAD,CHF,NZD,HKD,SGD,CNY,KRW,THB,INR',
                 'base_currency' => 'IDR'
             ])->json();
     
         $data = $response['data'];
     
         $convertedData = [];
         foreach ($data as $currency => $rate) {
             // Calculate IDR equivalent
             $idrEquivalent = ceil(1 / $rate); // Round up to nearest whole number
             // Add currency name and formatted IDR equivalent to converted data
             $convertedData[$currency] = [
                 'name' => $currencyNames[$currency],
                 'value' => "Rp. " . number_format($idrEquivalent, 0, ',', '.') // Format IDR with Rp. prefix and thousand separators
             ];
         }

         foreach ($convertedData as $currency => $currencyData) {
            Kurs::updateOrCreate(
                [
                    'nilai_tukar' => $currencyData['value'],
                ],
                [
                    'mata_uang' => $currencyData['name'],
                ]
            );
        }
        
        $paginatedRecords = Kurs::paginate(15);

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
    public function show(Kurs $kurs)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Kurs $kurs)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Kurs $kurs)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Kurs $kurs)
    {
        //
    }
}
