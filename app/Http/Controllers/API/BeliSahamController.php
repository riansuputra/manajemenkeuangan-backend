<?php

namespace App\Http\Controllers\API;

use App\Models\BeliSaham;
use App\Models\Saham;
use App\Models\Portofolio;
use App\Models\Sekuritas;
use Illuminate\Http\Request;
use Exception;
use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Validation\ValidationException;

class BeliSahamController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $beli_saham = new BeliSaham();
            if($request->auth['user_type'] == 'user') {
                $beli_saham = $beli_saham->where('user_id', $request->auth['user']['id']);
            }
            $beli_saham = $beli_saham->with(['saham', 'sekuritas'])
                                     ->get();
            return response()->json([
                'message' => 'Berhasil mendapatkan data pembelian saham.',
                'auth' => $request->auth,
                'data' => [
                    'beli_saham' => $beli_saham
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
        try{
            $request->validate([
                'user_id' => 'required',
                'saham_id' => 'required',
                'sekuritas_id' => 'nullable',
                'tanggal_beli' => 'required',
                'volume_beli' => 'required',
                'harga_beli' => 'required',
            ]);
            $beli_saham = new BeliSaham();
            $beli_saham->user_id = $request->auth['user']['id'];
            $beli_saham->saham_id = $request->saham_id;
            $beli_saham->sekuritas_id = $request->sekuritas_id;
            $beli_saham->tanggal_beli = $request->tanggal_beli;
            $beli_saham->volume_beli = $request->volume_beli;
            $beli_saham->harga_beli = $request->harga_beli;
            $beli_saham->save();

            $portofolio = Portofolio::where('user_id', $request->auth['user']['id'])
                                    ->where('saham_id', $request->saham_id)
                                    ->first();
            if ($portofolio) {
                $portofolio->volume += $request->volume_beli;
                $total_value_before = $portofolio->avg_price * ($portofolio->volume - $request->volume_beli);
                $total_value_now = $request->harga_beli * $request->volume_beli;
                $portofolio->avg_price = ($total_value_before + $total_value_now) / $portofolio->volume;
                $portofolio->cur_price = $request->harga_beli;
            } else {
                $portofolio = new Portofolio();
                $portofolio->user_id = $request->auth['user']['id'];
                $portofolio->saham_id = $request->saham_id;
                $portofolio->volume = $request->volume_beli;
                $portofolio->avg_price = $request->harga_beli;
                $portofolio->cur_price = $request->harga_beli;
            }

            $portofolio->save();
            return response()->json([
                'message' => 'Berhasil menambah pembelian saham.',
                'auth' => $request->auth,
                'data' => [
                    'beli_saham' => $beli_saham
                ],
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

    /**
     * Display the specified resource.
     */
    public function show(BeliSaham $beliSaham)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(BeliSaham $beliSaham)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, BeliSaham $beliSaham)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(BeliSaham $beliSaham)
    {
        //
    }
}
