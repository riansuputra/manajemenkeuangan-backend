<?php

namespace App\Http\Controllers\API;

use Exception;
use App\Models\Kurs;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Validation\ValidationException;

class KursController extends Controller
{
    public function index(Request $request)
    {
        try {
            $kurs = Kurs::all();
            return response()->json([
                'message' => 'Berhasil mendapatkan daftar kurs.',
                'auth' => $request->auth,
                'data' => [
                    'kurs' => $kurs
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

    public function store(Request $request)
    {
        try{
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
                'PHP' => 'Philippine Peso (PHP)',
                'SGD' => 'Singapore Dollar (SGD)',
                'THB' => 'Thai Baht (THB)',
                'USD' => 'US Dollar (USD)'
            ];
            $money_apikey = config('goapi.money_apikey');
            $response = Http::acceptJson()
                ->withoutVerifying()
                ->get('https://api.freecurrencyapi.com/v1/latest', [
                    'apikey' => $money_apikey,
                    'currencies' => 'USD,AUD,EUR,GBP,JPY,MYR,CAD,CHF,NZD,HKD,SGD,CNY,KRW,THB,INR,PHP',
                    'base_currency' => 'IDR'
                ])->json();
            $data = $response['data'];
            $convertedData = [];
            foreach ($data as $currency => $rate) {
                $idrEquivalent = ceil(1 / $rate);
                $convertedData[$currency] = [
                    'name' => $currencyNames[$currency],
                    'rate' => $rate,
                    'value' => "Rp. " . number_format($idrEquivalent, 0, ',', '.') 
                ];
            }
            foreach ($convertedData as $currency => $currencyData) {
                $existingKurs = Kurs::where('mata_uang', $currencyData['name'])->first();
            
                $oldValue = $existingKurs ? (int)filter_var($existingKurs->nilai_tukar, FILTER_SANITIZE_NUMBER_INT) : null;
                $newValue = (int)filter_var($currencyData['value'], FILTER_SANITIZE_NUMBER_INT);
            
                $differenceSymbol = '';
                if (!is_null($oldValue)) {
                    if ($newValue > $oldValue) {
                        $differenceSymbol = '+';
                    } elseif ($newValue < $oldValue) {
                        $differenceSymbol = '-';
                    }
                }
            
                $currencyData['value'] = $differenceSymbol . $currencyData['value'];
            
                Kurs::updateOrCreate(
                    [
                        'mata_uang' => $currencyData['name'], 
                    ],
                    [
                        'rate_idr' => $currencyData['rate'], 
                        'nilai_tukar' => $currencyData['value'], 
                    ]
                );
            }
            return response()->json([
                'message' => 'Berhasil update data kurs.',
                'auth' => $request->auth,
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

    public function show(Request $request, $code)
    {
        try{
            $kurs = Kurs::where('mata_uang', 'LIKE', '%' . strtoupper($code) . '%')->firstOrFail();
            return response()->json([
                'message' => 'Berhasil mendapatkan detail kurs.',
                'auth' => $request->auth,
                'data' => [
                    'kurs' => $kurs
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
}
