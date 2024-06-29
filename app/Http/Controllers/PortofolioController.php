<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Portofolio;
use App\Models\Saham;
use App\Models\User;
use App\Models\Sekuritas;
use Illuminate\Support\Facades\Auth;


Class PortofolioController extends Controller
{
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