<?php

namespace App\Http\Controllers\API;

use App\Models\Anggaran;
use App\Models\KategoriPengeluaran;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Carbon\Carbon;
use Exception;

class AnggaranController extends Controller
{
    public function index(Request $request) 
    {
        try {
            $now = Carbon::now();
            $anggaran = Anggaran::where('user_id', $request->auth['user']['id'])
                                ->with('kategori_pengeluaran')
                                ->get();
            foreach ($anggaran as $entry) {
                if (Carbon::parse($entry->tanggal_selesai)->lt($now)) {
                    switch ($entry->periode) {
                        case 'Tahunan':
                            $entry->tanggal_mulai = $now->copy()->startOfYear()->toDateString();
                            $entry->tanggal_selesai = $now->copy()->endOfYear()->toDateString();
                            break;
                        case 'Mingguan':
                            $entry->tanggal_mulai = $now->copy()->startOfWeek()->toDateString();
                            $entry->tanggal_selesai = $now->copy()->endOfWeek()->toDateString();
                            break;
                        case 'Bulanan':
                            $entry->tanggal_mulai = $now->copy()->startOfMonth()->toDateString();
                            $entry->tanggal_selesai = $now->copy()->endOfMonth()->toDateString();
                            break;
                        default:
                            throw new Exception('Invalid periode value');
                    }
                    $entry->save();
                }
            }
            return response()->json([
                'message' => 'Berhasil mendapatkan daftar anggaran.',
                'auth' => $request->auth,
                'data' => [
                    'anggaran' => $anggaran
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
        try {
            $request->validate([
                'kategori_pengeluaran_id' => [
                    'required',
                    Rule::unique('anggaran')->where(function ($query) use ($request) {
                        return $query->where('periode', $request->periode);
                    }),
                ],
                'periode' => 'required',
                'tanggal_mulai' => 'required',
                'tanggal_selesai' => 'required',
                'anggaran' => 'required',
            ]);
            $anggaran = new Anggaran();
            $anggaran->user_id = $request->auth['user']['id'];
            $anggaran->kategori_pengeluaran_id = $request->kategori_pengeluaran_id;
            $anggaran->periode = $request->periode;
            $anggaran->tanggal_mulai = $request->tanggal_mulai;
            $anggaran->tanggal_selesai = $request->tanggal_selesai;
            $anggaran->anggaran = $request->anggaran;
            $anggaran->save();
            return response()->json([
                'message' => 'Berhasil menambah anggaran.',
                'auth' => $request->auth,
                'data' => [
                    'anggaran' => $anggaran
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

    public function show(Request $request, $id) 
    {
        try {
            $anggaran = new Anggaran();
            if($request->auth['user_type'] == 'user') {
                $anggaran = $anggaran->where('user_id', $request->auth['user']['id']);
            }
            $anggaran = $anggaran->with('kategori_pengeluaran')
                                 ->findOrFail($id);
            return response()->json([
                'message' => 'Berhasil mendapatkan detail anggaran',
                'auth' => $request->auth,
                'data' => [
                    'anggaran' => $anggaran
                ]
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

    public function update(Request $request, $id)
    {
        try {
            $anggaran = new Anggaran();
            if($request->auth['user_type'] == 'user') {
                $anggaran = $anggaran->where('user_id', $request->auth['user']['id']);
            }
            $anggaran = $anggaran->with('kategori_pengeluaran')
                                 ->findOrFail($id);
            $isKategoriPengeluaranChanged = $anggaran->kategori_pengeluaran_id != $request->kategori_pengeluaran_id;

            $rules = [
                'periode' => 'required',
                'tanggal_mulai' => 'required|date',
                'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
                'anggaran' => 'required|numeric',
            ];

            if ($isKategoriPengeluaranChanged) {
                $rules['kategori_pengeluaran_id'] = [
                    'required',
                    Rule::unique('anggaran')->where(function ($query) use ($request) {
                        return $query->where('periode', $request->periode);
                    }),
                ];
            } else {
                $rules['kategori_pengeluaran_id'] = 'required';
            }

            $validatedData = $request->validate($rules);
            $anggaran->periode = $request->periode;
            $anggaran->tanggal_mulai = $request->tanggal_mulai;
            $anggaran->tanggal_selesai = $request->tanggal_selesai;
            $anggaran->anggaran = $request->anggaran;
            $anggaran->kategori_pengeluaran_id = $request->kategori_pengeluaran_id;
            $anggaran->save();
            return response()->json([
                'message' => 'Berhasil mengubah anggaran.',
                'auth' => $request->auth,
                'data' => [
                    'anggaran' => $anggaran
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

    public function destroy(Request $request, $id)
    {
        try {
            $anggaran = new Anggaran();
            $anggaran = $anggaran->where('user_id', $request->auth['user']['id'])
                                 ->with(['kategori_pengeluaran'])
                                 ->findOrFail($id);
            $anggaran->delete();
            return response()->json([
                'message' => 'Berhasil menghapus anggaran.',
                'auth' => $request->auth,
                'data' => [
                    'anggaran' => $anggaran
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
