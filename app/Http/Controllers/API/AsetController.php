<?php

namespace App\Http\Controllers\API;

use Exception;
use App\Models\Aset;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Validation\ValidationException;

class AsetController extends Controller
{
    public function index(Request $request) {
        try {
            $aset = Aset::all();
            return response()->json([
                'message' => 'Berhasil mendapatkan daftar aset.',
                'auth' => $request->auth,
                'data' => [
                    'aset' => $aset
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
                Log::error('Error in index method: ' . $e->getMessage());
                return response()->json([
                    'message' => $e->getMessage(),
                    'auth' => $request->auth
                ], Response::HTTP_BAD_REQUEST);
            }
        }
    }

    public function storeSaham(Request $request)
    {
        try {
            $response = Http::acceptJson()
            ->withHeaders([
                'X-API-KEY' => config('goapi.apikey')
            ])->withoutVerifying()->get('https://api.goapi.io/stock/idx/companies')->json();

            $data = $response['data']['results'];

            foreach ($data as $item) {
                $insert = Aset::updateOrCreate(
                    [
                        'nama' => $item['symbol']
                    ],
                    [
                        'nama' => $item['symbol'],
                        'tipe_aset' => 'saham',
                        'deskripsi' => $item['name'],
                        'info' => $item['logo']
                    ]
                );
            }
            return response()->json([
                'message' => 'Berhasil input data saham ke tabel aset.',
                'auth' => $request->auth,
            ], Response::HTTP_OK);    
        } catch (Exception $e) {
            if ($e instanceof ValidationException) {
                return response()->json([
                    'message' => $e->getMessage(),
                    'auth' => $request->auth,
                    'errors' => $e->validator->errors(),
                ], Response::HTTP_BAD_REQUEST);
            } else {
                Log::error('Error in index method: ' . $e->getMessage());
                return response()->json([
                    'message' => $e->getMessage(),
                    'auth' => $request->auth
                ], Response::HTTP_BAD_REQUEST);
            }
        }
    }

    public function storeEwallet(Request $request)
    {
        try {
            $eWallets = [
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'GoPay',
                    'deskripsi' => 'E-wallet dari Gojek',
                    'info' => 'https://s3.goapi.io/logo/gopay.jpg',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'OVO',
                    'deskripsi' => 'E-wallet dari Grab dan Tokopedia',
                    'info' => 'https://s3.goapi.io/logo/ovo.jpg',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'DANA',
                    'deskripsi' => 'E-wallet untuk pembayaran online dan offline',
                    'info' => 'https://s3.goapi.io/logo/dana.jpg',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'LinkAja',
                    'deskripsi' => 'E-wallet BUMN untuk berbagai pembayaran',
                    'info' => 'https://s3.goapi.io/logo/linkaja.jpg',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'ShopeePay',
                    'deskripsi' => 'E-wallet terintegrasi dengan Shopee',
                    'info' => 'https://s3.goapi.io/logo/shopeepay.jpg',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'Sakuku',
                    'deskripsi' => 'E-wallet dari Bank BCA',
                    'info' => 'https://s3.goapi.io/logo/sakuku.jpg',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'Jenius Pay',
                    'deskripsi' => 'E-wallet dari Bank BTPN',
                    'info' => 'https://s3.goapi.io/logo/jeniuspay.jpg',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'i.saku',
                    'deskripsi' => 'E-wallet dari Indomaret',
                    'info' => 'https://s3.goapi.io/logo/isaku.jpg',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'Paytren',
                    'deskripsi' => 'E-wallet untuk berbagai pembayaran dan layanan',
                    'info' => 'https://s3.goapi.io/logo/paytren.jpg',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'Doku Wallet',
                    'deskripsi' => 'E-wallet untuk pembayaran online dan offline',
                    'info' => 'https://s3.goapi.io/logo/dokuwallet.jpg',
                ],
            ];

            foreach ($eWallets as $item) {
                $insert = Aset::create($item);
            }
            return response()->json([
                'message' => 'Berhasil input data saham ke tabel aset.',
                'auth' => $request->auth,
            ], Response::HTTP_OK);    
        } catch (Exception $e) {
            if ($e instanceof ValidationException) {
                return response()->json([
                    'message' => $e->getMessage(),
                    'auth' => $request->auth,
                    'errors' => $e->validator->errors(),
                ], Response::HTTP_BAD_REQUEST);
            } else {
                Log::error('Error in index method: ' . $e->getMessage());
                return response()->json([
                    'message' => $e->getMessage(),
                    'auth' => $request->auth
                ], Response::HTTP_BAD_REQUEST);
            }
        }
    }

    public function storeBank(Request $request)
    {
        try {
            $bankt = [
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'ACEH SYARIAH',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'ALADIN SYARIAH',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'ALLO BANK',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'ALTOREM/PAY',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'AMAR INDONESIA',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'ANZ INDONESIA',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'ARTHA GRAHA',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'ATMBPLUS',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'BALI',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'BANK BTPN',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'BANK DBS',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'BANK JAGO',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'BANK JAGO',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'BANK MEGA SYARIAH ',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'BANK NEO COMMERCE',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'BANK RAYA',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'BANTEN',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'BCA SYARIAH',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'BENGKULU',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'BJB',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'BJB SYARIAH',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'BLU BY BCA DIGITAL',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'BMRI-BPR',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'BNI',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'BOC INDONESIA',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'BOI INDONESIA',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'BPR EKA',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'BPR KS',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'BPR PRIMA MASTER',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'BPR SUPRA',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'BRI',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'BRK SYARIAH',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'BSG',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'BSI',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'BTN',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'BTPN SYARIAH',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'BUMI ARTA',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'CAPITAL INDONESIA',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'CCB INDONESIA',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'CIMB NIAGA',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'CITIBANK',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'COMMONWEALTH',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'CTBC INDONESIA',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'DANAMON',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'DIY',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'DKI',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'DOKU',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'GANESHA',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'HIBANK DH MAYORA',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'HSBC',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'IBK BANK',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'ICBC',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'INA PERDANA',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'INDEX',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'JAMBI',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'JASA JAKARTA',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'JATENG',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'JATIM',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'JTRUST BANK',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'KALBAR',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'KALSEL',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'KALTENG',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'KALTIMKALTARA',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'KB BANK',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'KB BUKOPIN SYARIAH',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'KEB HANA',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'KROM',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'LAMPUNG',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'MALUKUMALUT',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'MANDIRI',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'MANDIRI TASPEN',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'MAS',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'MASPION',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'MAYAPADA',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'MAYBANK (D/H BII)',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'MEGA',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'MESTIKA',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'MNC BANK',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'MUAMALAT',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'MUFG BANK, LTD',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'NAGARI',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'NOBU ',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'NTB SYARIAH',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'NTT',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'OCBC',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'OKE',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'PANIN',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'PANIN SYARIAH',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'PAPUA',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'PAYPRO',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'PERMATA',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'QNB INDONESIA',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'SAHABAT SAMPOERN',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'SBI INDONESIA',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'SEABANK',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'SHINHAN',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'SINARAS',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'SLEMAN',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'STANDARD CHARTERED',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'SULSELBAR',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'SULTENG',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'SULTRA',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'SUMSELBABEL',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'SUMUT',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'SUPERBANK',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'TCASH',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'TELKOM',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'UOB',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'VICTORIA',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'VICTORIA SYARAH',
                ],
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'WOORI SAUDARA',
                ],
            ];
            $bankd = [
                [
                    'tipe_aset' => 'deposito',
                    'nama' => 'ACEH SYARIAH',
                ],
                [
                    'tipe_aset' => 'deposito',
                    'nama' => 'ALADIN SYARIAH',
                ],
                [
                    'tipe_aset' => 'deposito',
                    'nama' => 'ALLO BANK',
                ],
                [
                    'tipe_aset' => 'deposito',
                    'nama' => 'ALTOREM/PAY',
                ],
                [
                    'tipe_aset' => 'deposito',
                    'nama' => 'AMAR INDONESIA',
                ],
                [
                    'tipe_aset' => 'deposito',
                    'nama' => 'ANZ INDONESIA',
                ],
                [
                    'tipe_aset' => 'deposito',
                    'nama' => 'ARTHA GRAHA',
                ],
                [
                    'tipe_aset' => 'deposito',
                    'nama' => 'ATMBPLUS',
                ],
                [
                    'tipe_aset' => 'deposito',
                    'nama' => 'BALI',
                ],
                [
                    'tipe_aset' => 'deposito',
                    'nama' => 'BANK BTPN',
                ],
                [
                    'tipe_aset' => 'deposito',
                    'nama' => 'BANK DBS',
                ],
                [
                    'tipe_aset' => 'deposito',
                    'nama' => 'BANK JAGO',
                ],
                [
                    'tipe_aset' => 'deposito',
                    'nama' => 'BANK JAGO',
                ],
                [
                    'tipe_aset' => 'deposito',
                    'nama' => 'BANK MEGA SYARIAH ',
                ],
                [
                    'tipe_aset' => 'deposito',
                    'nama' => 'BANK NEO COMMERCE',
                ],
                [
                    'tipe_aset' => 'deposito',
                    'nama' => 'BANK RAYA',
                ],
                [
                    'tipe_aset' => 'deposito',
                    'nama' => 'BANTEN',
                ],
                [
                    'tipe_aset' => 'deposito',
                    'nama' => 'BCA SYARIAH',
                ],
                [
                    'tipe_aset' => 'deposito',
                    'nama' => 'BENGKULU',
                ],
                [
                    'tipe_aset' => 'deposito',
                    'nama' => 'BJB',
                ],
                [
                    'tipe_aset' => 'deposito',
                    'nama' => 'BJB SYARIAH',
                ],
                [
                    'tipe_aset' => 'deposito',
                    'nama' => 'BLU BY BCA DIGITAL',
                ],
                [
                    'tipe_aset' => 'deposito',
                    'nama' => 'BMRI-BPR',
                ],
                [
                    'tipe_aset' => 'deposito',
                    'nama' => 'BNI',
                ],
                [
                    'tipe_aset' => 'deposito',
                    'nama' => 'BOC INDONESIA',
                ],
                [
                    'tipe_aset' => 'deposito',
                    'nama' => 'BOI INDONESIA',
                ],
                [
                    'tipe_aset' => 'deposito',
                    'nama' => 'BPR EKA',
                ],
                [
                    'tipe_aset' => 'deposito',
                    'nama' => 'BPR KS',
                ],
                [
                    'tipe_aset' => 'deposito',
                    'nama' => 'BPR PRIMA MASTER',
                ],
                [
                    'tipe_aset' => 'deposito',
                    'nama' => 'BPR SUPRA',
                ],
                [
                    'tipe_aset' => 'deposito',
                    'nama' => 'BRI',
                ],
                [
                    'tipe_aset' => 'deposito',
                    'nama' => 'BRK SYARIAH',
                ],
                [
                    'tipe_aset' => 'deposito',
                    'nama' => 'BSG',
                ],
                [
                    'tipe_aset' => 'deposito',
                    'nama' => 'BSI',
                ],
                [
                    'tipe_aset' => 'deposito',
                    'nama' => 'BTN',
                ],
                [
                    'tipe_aset' => 'deposito',
                    'nama' => 'BTPN SYARIAH',
                ],
                [
                    'tipe_aset' => 'deposito',
                    'nama' => 'BUMI ARTA',
                ],
                [
                    'tipe_aset' => 'deposito',
                    'nama' => 'CAPITAL INDONESIA',
                ],
                [
                    'tipe_aset' => 'deposito',
                    'nama' => 'CCB INDONESIA',
                ],
                [
                    'tipe_aset' => 'deposito',
                    'nama' => 'CIMB NIAGA',
                ],
                [
                    'tipe_aset' => 'deposito',
                    'nama' => 'CITIBANK',
                ],
                [
                    'tipe_aset' => 'deposito',
                    'nama' => 'COMMONWEALTH',
                ],
                [
                    'tipe_aset' => 'deposito',
                    'nama' => 'CTBC INDONESIA',
                ],
                [
                    'tipe_aset' => 'deposito',
                    'nama' => 'DANAMON',
                ],
                [
                    'tipe_aset' => 'deposito',
                    'nama' => 'DIY',
                ],
                [
                    'tipe_aset' => 'deposito',
                    'nama' => 'DKI',
                ],
                [
                    'tipe_aset' => 'deposito',
                    'nama' => 'DOKU',
                ],
                [
                    'tipe_aset' => 'deposito',
                    'nama' => 'GANESHA',
                ],
                [
                    'tipe_aset' => 'deposito',
                    'nama' => 'HIBANK DH MAYORA',
                ],
                [
                    'tipe_aset' => 'deposito',
                    'nama' => 'HSBC',
                ],
                [
                    'tipe_aset' => 'deposito',
                    'nama' => 'IBK BANK',
                ],
                [
                    'tipe_aset' => 'deposito',
                    'nama' => 'ICBC',
                ],
                [
                    'tipe_aset' => 'deposito',
                    'nama' => 'INA PERDANA',
                ],
                [
                    'tipe_aset' => 'deposito',
                    'nama' => 'INDEX',
                ],
                [
                    'tipe_aset' => 'deposito',
                    'nama' => 'JAMBI',
                ],
                [
                    'tipe_aset' => 'deposito',
                    'nama' => 'JASA JAKARTA',
                ],
                [
                    'tipe_aset' => 'deposito',
                    'nama' => 'JATENG',
                ],
                [
                    'tipe_aset' => 'deposito',
                    'nama' => 'JATIM',
                ],
                [
                    'tipe_aset' => 'deposito',
                    'nama' => 'JTRUST BANK',
                ],
                [
                    'tipe_aset' => 'deposito',
                    'nama' => 'KALBAR',
                ],
                [
                    'tipe_aset' => 'deposito',
                    'nama' => 'KALSEL',
                ],
                [
                    'tipe_aset' => 'deposito',
                    'nama' => 'KALTENG',
                ],
                [
                    'tipe_aset' => 'deposito',
                    'nama' => 'KALTIMKALTARA',
                ],
                [
                    'tipe_aset' => 'deposito',
                    'nama' => 'KB BANK',
                ],
                [
                    'tipe_aset' => 'deposito',
                    'nama' => 'KB BUKOPIN SYARIAH',
                ],
                [
                    'tipe_aset' => 'deposito',
                    'nama' => 'KEB HANA',
                ],
                [
                    'tipe_aset' => 'deposito',
                    'nama' => 'KROM',
                ],
                [
                    'tipe_aset' => 'deposito',
                    'nama' => 'LAMPUNG',
                ],
                [
                    'tipe_aset' => 'deposito',
                    'nama' => 'MALUKUMALUT',
                ],
                [
                    'tipe_aset' => 'deposito',
                    'nama' => 'MANDIRI',
                ],
                [
                    'tipe_aset' => 'deposito',
                    'nama' => 'MANDIRI TASPEN',
                ],
                [
                    'tipe_aset' => 'deposito',
                    'nama' => 'MAS',
                ],
                [
                    'tipe_aset' => 'deposito',
                    'nama' => 'MASPION',
                ],
                [
                    'tipe_aset' => 'deposito',
                    'nama' => 'MAYAPADA',
                ],
                [
                    'tipe_aset' => 'deposito',
                    'nama' => 'MAYBANK (D/H BII)',
                ],
                [
                    'tipe_aset' => 'deposito',
                    'nama' => 'MEGA',
                ],
                [
                    'tipe_aset' => 'deposito',
                    'nama' => 'MESTIKA',
                ],
                [
                    'tipe_aset' => 'deposito',
                    'nama' => 'MNC BANK',
                ],
                [
                    'tipe_aset' => 'deposito',
                    'nama' => 'MUAMALAT',
                ],
                [
                    'tipe_aset' => 'deposito',
                    'nama' => 'MUFG BANK, LTD',
                ],
                [
                    'tipe_aset' => 'deposito',
                    'nama' => 'NAGARI',
                ],
                [
                    'tipe_aset' => 'deposito',
                    'nama' => 'NOBU ',
                ],
                [
                    'tipe_aset' => 'deposito',
                    'nama' => 'NTB SYARIAH',
                ],
                [
                    'tipe_aset' => 'deposito',
                    'nama' => 'NTT',
                ],
                [
                    'tipe_aset' => 'deposito',
                    'nama' => 'OCBC',
                ],
                [
                    'tipe_aset' => 'deposito',
                    'nama' => 'OKE',
                ],
                [
                    'tipe_aset' => 'deposito',
                    'nama' => 'PANIN',
                ],
                [
                    'tipe_aset' => 'deposito',
                    'nama' => 'PANIN SYARIAH',
                ],
                [
                    'tipe_aset' => 'deposito',
                    'nama' => 'PAPUA',
                ],
                [
                    'tipe_aset' => 'deposito',
                    'nama' => 'PAYPRO',
                ],
                [
                    'tipe_aset' => 'deposito',
                    'nama' => 'PERMATA',
                ],
                [
                    'tipe_aset' => 'deposito',
                    'nama' => 'QNB INDONESIA',
                ],
                [
                    'tipe_aset' => 'deposito',
                    'nama' => 'SAHABAT SAMPOERN',
                ],
                [
                    'tipe_aset' => 'deposito',
                    'nama' => 'SBI INDONESIA',
                ],
                [
                    'tipe_aset' => 'deposito',
                    'nama' => 'SEABANK',
                ],
                [
                    'tipe_aset' => 'deposito',
                    'nama' => 'SHINHAN',
                ],
                [
                    'tipe_aset' => 'deposito',
                    'nama' => 'SINARAS',
                ],
                [
                    'tipe_aset' => 'deposito',
                    'nama' => 'SLEMAN',
                ],
                [
                    'tipe_aset' => 'deposito',
                    'nama' => 'STANDARD CHARTERED',
                ],
                [
                    'tipe_aset' => 'deposito',
                    'nama' => 'SULSELBAR',
                ],
                [
                    'tipe_aset' => 'deposito',
                    'nama' => 'SULTENG',
                ],
                [
                    'tipe_aset' => 'deposito',
                    'nama' => 'SULTRA',
                ],
                [
                    'tipe_aset' => 'deposito',
                    'nama' => 'SUMSELBABEL',
                ],
                [
                    'tipe_aset' => 'deposito',
                    'nama' => 'SUMUT',
                ],
                [
                    'tipe_aset' => 'deposito',
                    'nama' => 'SUPERBANK',
                ],
                [
                    'tipe_aset' => 'deposito',
                    'nama' => 'TCASH',
                ],
                [
                    'tipe_aset' => 'deposito',
                    'nama' => 'TELKOM',
                ],
                [
                    'tipe_aset' => 'deposito',
                    'nama' => 'UOB',
                ],
                [
                    'tipe_aset' => 'deposito',
                    'nama' => 'VICTORIA',
                ],
                [
                    'tipe_aset' => 'deposito',
                    'nama' => 'VICTORIA SYARAH',
                ],
                [
                    'tipe_aset' => 'deposito',
                    'nama' => 'WOORI SAUDARA',
                ],
            ];
            foreach ($bankd as $item) {
                $insert = Aset::create($item);
            }
            foreach ($bankt as $item) {
                $insert = Aset::create($item);
            }
            return response()->json([
                'message' => 'Berhasil input data bank ke tabel aset.',
                'auth' => $request->auth,
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

    public function storeLainnya(Request $request)
    {
        try {
            $other = [
                [
                    'tipe_aset' => 'tabungan',
                    'nama' => 'Lainnya',
                ],
                [
                    'tipe_aset' => 'deposito',
                    'nama' => 'Lainnya',
                ],
                [
                    'tipe_aset' => 'saham',
                    'nama' => 'Lainnya',
                ],
            ];

            foreach ($other as $item) {
                $insert = Aset::create($item);
            }
            return response()->json([
                'message' => 'Berhasil input data lainnya ke tabel aset.',
                'auth' => $request->auth,
            ], Response::HTTP_OK);    
        } catch (Exception $e) {
            if ($e instanceof ValidationException) {
                return response()->json([
                    'message' => $e->getMessage(),
                    'auth' => $request->auth,
                    'errors' => $e->validator->errors(),
                ], Response::HTTP_BAD_REQUEST);
            } else {
                Log::error('Error in index method: ' . $e->getMessage());
                return response()->json([
                    'message' => $e->getMessage(),
                    'auth' => $request->auth
                ], Response::HTTP_BAD_REQUEST);
            }
        }
    }
}
