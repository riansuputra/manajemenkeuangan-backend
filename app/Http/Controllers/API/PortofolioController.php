<?php

namespace App\Http\Controllers\API;

use Carbon\Carbon;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\KinerjaPortofolio;
use App\Models\Portofolio;
use App\Models\Saham;
use App\Models\Aset;
use App\Models\MutasiDana;
use App\Models\HistorisBulanan;
use App\Models\HistorisTahunan;
use App\Models\User;
use App\Models\Sekuritas;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Validation\ValidationException;

Class PortofolioController extends Controller
{

    public function index(Request $request)
    {
        try {
            $portofolio = new Portofolio();
            if($request->auth['user_type'] == 'user') {
                $portofolio = $portofolio->where('user_id', $request->auth['user']['id']);
            }
            $portofolio = $portofolio->with('kinerja_portofolio.transaksi', 'aset')
                                     ->get();
            return response()->json([
                'message' => 'Berhasil mendapatkan data pembelian saham.',
                'auth' => $request->auth,
                'data' => [
                    'portofolio' => $portofolio
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

    public function kinerja(Request $request)
    {
        try {
            $kinerja = new KinerjaPortofolio();
            if($request->auth['user_type'] == 'user') {
                $kinerja = $kinerja->where('user_id', $request->auth['user']['id']);
            }
            $kinerja = $kinerja->with('transaksi')->get();
            return response()->json([
                'message' => 'Berhasil mendapatkan data kinerja portofolio.',
                'auth' => $request->auth,
                'data' => [
                    'kinerja' => $kinerja
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

    public function mutasi_dana(Request $request)
    {
        try {
            $mutasi_dana = new MutasiDana();
            if($request->auth['user_type'] == 'user') {
                $mutasi_dana = $mutasi_dana->where('user_id', $request->auth['user']['id']);
            }
            $mutasi_dana = $mutasi_dana->get();
            return response()->json([
                'message' => 'Berhasil mendapatkan data mutasi dana.',
                'auth' => $request->auth,
                'data' => [
                    'mutasi_dana' => $mutasi_dana
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

    public function histori_bulanan(Request $request)
    {
        try {
            $histori_bulanan = new HistorisBulanan();
            if($request->auth['user_type'] == 'user') {
                $histori_bulanan = $histori_bulanan->where('user_id', $request->auth['user']['id']);
            }
            $histori_bulanan = $histori_bulanan->get();
            return response()->json([
                'message' => 'Berhasil mendapatkan data historis bulanan.',
                'auth' => $request->auth,
                'data' => [
                    'histori_bulanan' => $histori_bulanan
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

    public function histori_tahunan(Request $request)
    {
        try {
            $histori_tahunan = new HistorisTahunan();
            if($request->auth['user_type'] == 'user') {
                $histori_tahunan = $histori_tahunan->where('user_id', $request->auth['user']['id']);
            }
            $histori_tahunan = $histori_tahunan->get();
            return response()->json([
                'message' => 'Berhasil mendapatkan data historis tahunan.',
                'auth' => $request->auth,
                'data' => [
                    'histori_tahunan' => $histori_tahunan
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


public function insertData(Request $request)
    {
        try {
            $id = Auth::id();
            $unique_id = uniqid('', true);
            $currentDateTime = Carbon::now()->format('Y-m-d H:i:s');
            $reqType = $request->type;
            $idSekuritas = $request->id_sekuritas;
            $total = 100*($request->volume * $request->harga);

            if ($total > 10000000){

               // $total = (100*($request->volume * $request->harga)) + (($request->fee/100)*(100*($request->volume * $request->harga))) + 10000 ;
                $total = (100*($request->volume * $request->harga)) ;
            }else{
                $total = (100*($request->volume * $request->harga));
               // $total = (100*($request->volume * $request->harga)) + (($request->fee/100)*(100*($request->volume * $request->harga))) ;
            }
            $saham = Saham::where('nama_saham', $request->id_saham)->first();
            if ($reqType == 'jual') {
                $insert = Portofolio::create([
                    'id_saham' => $saham->id_saham,
                    'user_id' => $id,
                    'volume' => $request->volume,
                    'tanggal_transaksil' => $request->tanggal,
                    'harga' => $request->harga,
                    'id_sekuritas' => $idSekuritas,
                    'total_jual' => $total,
                ]);
            }
            if ($reqType == 'beli') {
                $insert = Portofolio::create([
                    'id_saham' => $saham->id_saham,
                    'user_id' => $id,
                    'volume' => $request->volume,
                    'tanggal_transaksi' => $request->tanggal,
                    'harga' => $request->harga,
                    'id_sekuritas' => $idSekuritas,
                    'total_beli' => $total,'feeku' => $request->feeku,

                ]);

            }

            return response()->json(['messsage' => 'Berhasil', 'data' => $insert]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e
            ]);
        }

    }


    public function editDataBeli(Request $request)
    {
        try {
            $dataporto = Portofolio::where('id_portofolio_beli', $request->id_portofolio_beli)->firstOrFail();
           $fee = Sekuritas::where('id_sekuritas', $dataporto->id_sekuritas)->first();
           $fee = $fee->fee_beli;


            $dataporto->volume = $request->volume;
            $dataporto->tanggal_beli = $request->tanggal_beli;
            $dataporto->harga_beli = $request->harga_beli;
            $dataporto->id_sekuritas = $request->id_sekuritas;

            $total = 100*($request->volume * $request->harga_beli);




            if ($total > 10000000){

                 $total = (100*($request->volume * $request->harga_beli)) ;
             }else{
                 $total = (100*($request->volume * $request->harga_beli));
             }


            $dataporto->total_beli = $total;
            $dataporto->save();



            return response()->json(['messsage' => 'Data Berhasil di Update']);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }


    }

    public function editDataJual(Request $request)
    {
        try {
            $dataporto = Portofolio::where('id_portofolio_jual', $request->id_portofolio_jual)->firstOrFail();
            $fee = Sekuritas::where('id_sekuritas', $dataporto->id_sekuritas)->first();
           $fee = $fee->fee_jual;

            $dataporto->volume = $request->volume;
            $dataporto->tanggal_jual = $request->tanggal_jual;
            $dataporto->harga_jual = $request->harga_jual;
            $dataporto->id_sekuritas = $request->id_sekuritas;

            $total = 100*($request->volume * $request->harga_jual);


             if ($total > 10000000){

                 $total = (100*($request->volume * $request->harga_jual)) ;
             }else{
                 $total = (100*($request->volume * $request->harga_jual));
             }


            $dataporto->total_jual = $total;
            $dataporto->save();


            return response()->json(['messsage' => 'Data Berhasil di Update']);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }


    }

    public function deleteDataBeli(Request $request)
    {


        try {
            $dataporto = Portofolio::where('id_portofolio_beli', $request->id_portofolio_beli)->firstOrFail();
            $dataporto->delete();

            return response()->json(['success' => true, 'messsage' => 'Data Berhasil di Delete']);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Delete data gagal'
            ]);
        }


    }

    public function deleteDataJual(Request $request)
    {

        try {
            $dataporto = Portofolio::where('id_portofolio_jual', $request->id_portofolio_jual)->firstOrFail();
            $dataporto->delete();


            return response()->json(['success' => true, 'messsage' => 'Data Berhasil di Delete']);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Delete data gagal'
            ]);
        }


    }
}