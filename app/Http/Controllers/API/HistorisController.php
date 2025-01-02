<?php

namespace App\Http\Controllers\API;

use Exception;
use App\Models\Historis;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Validation\ValidationException;

class HistorisController extends Controller
{
    public function index(Request $request)
    {
        try {
            $historis = Historis::where('user_id', $request->auth['user']['id'])
                                ->get();
            return response()->json([
                'message' => 'Berhasil mendapatkan historis.',
                'auth' => $request->auth,
                'data' => [
                    'historis' => $historis
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

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'user_id' => 'required',
                'bulan' => 'required|integer',
                'tahun' => 'required|integer',
                'ihsg_start' => 'nullable|numeric',
                'ihsg_end' => 'nullable|numeric',
            ]);

            $userId = $request->auth['user']['id'];
            $bulan = $request->bulan;
            $tahun = $request->tahun;
            $ihsgStart = $request->ihsg_start;
            $ihsgEnd = $request->ihsg_end;

            // Cek data eksisting
            $historis = Historis::where('user_id', $userId)
                ->where('bulan', $bulan)
                ->where('tahun', $tahun)
                ->first();

            if ($historis) {
                // Perbarui ihsg_start hanya jika diberikan input baru
                if ($ihsgStart !== null) {
                    $historis->ihsg_start = $ihsgStart;
                }

                // Perbarui ihsg_end hanya jika diberikan input baru
                if ($ihsgEnd !== null) {
                    $historis->ihsg_end = $ihsgEnd;
                }
            } else {
                // Buat data baru jika belum ada
                $historis = new Historis();
                $historis->user_id = $userId;
                $historis->bulan = $bulan;
                $historis->tahun = $tahun;
                $historis->ihsg_start = $ihsgStart;
                $historis->ihsg_end = $ihsgEnd;
            }

            // Hitung yield_ihsg jika kedua nilai tersedia
            if ($historis->ihsg_start && $historis->ihsg_end) {
                $historis->yield_ihsg = (($historis->ihsg_end - $historis->ihsg_start) / $historis->ihsg_start) * 100;
            }

            $historis->save();

            return response()->json([
                'message' => 'Berhasil menambah atau memperbarui historis.',
                'auth' => $request->auth,
                'data' => [
                    'historis' => $historis,
                ],
            ], Response::HTTP_CREATED);
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
                    'auth' => $request->auth,
                ], Response::HTTP_BAD_REQUEST);
            }
        }
    }

    public function show(IHSG $iHSG)
    {
        //
    }

    public function edit(IHSG $iHSG)
    {
        //
    }

    public function update(Request $request, IHSG $iHSG)
    {
        //
    }

    public function destroy(IHSG $iHSG)
    {
        //
    }
}
