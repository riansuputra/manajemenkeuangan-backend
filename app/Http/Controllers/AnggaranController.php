<?php

namespace App\Http\Controllers;

use App\Models\Anggaran;
use App\Models\KategoriPengeluaran;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;


class AnggaranController extends Controller
{
    public function index(Request $request) {
        try {
            // Get the current date
            $now = Carbon::now();
    
            // Retrieve all anggaran entries for the authenticated user
            $anggaran = Anggaran::where('user_id', $request->auth['user']['user_id'])
                                ->with('kategori_pengeluaran')
                                ->get();
    
            // Check each anggaran entry to see if tanggal_selesai has passed
            foreach ($anggaran as $entry) {
                if (Carbon::parse($entry->tanggal_selesai)->lt($now)) {
                    // Update tanggal_mulai and tanggal_selesai based on the periode
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
                Log::error('Error in index method: ' . $e->getMessage());
                return response()->json([
                    'message' => $e->getMessage(),
                    'auth' => $request->auth
                ], Response::HTTP_BAD_REQUEST);
            }
        }
    }

    public function store(Request $request) {
        try {
            $request->validate([
                'periode' => 'required',
                'tanggal_mulai' => 'required',
                'tanggal_selesai' => 'required',
                'anggaran' => 'required',
                'id_kategori_pengeluaran' => [
                    'required',
                    Rule::unique('anggarans')->where(function ($query) use ($request) {
                        return $query->where('periode', $request->periode);
                    }),
                ],
            ]);
            $anggaran = new Anggaran();
            $anggaran->user_id = $request->auth['user']['user_id'];
            $anggaran->periode = $request->periode;
            $anggaran->tanggal_mulai = $request->tanggal_mulai;
            $anggaran->tanggal_selesai = $request->tanggal_selesai;
            $anggaran->anggaran = $request->anggaran;
            $anggaran->id_kategori_pengeluaran = $request->id_kategori_pengeluaran;
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

    public function show(Request $request, $id) {
        try {
            $anggaran = new Anggaran();
            $anggaran = $anggaran
                        ->with('kategori_pengeluaran')
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
        // Fetch the existing record
        $anggaran = Anggaran::where('user_id', $request->auth['user']['user_id'])->findOrFail($id);
        
        // Determine if the id_kategori_pengeluaran has changed
        $isKategoriPengeluaranChanged = $anggaran->id_kategori_pengeluaran != $request->id_kategori_pengeluaran;

        // Define validation rules
        $rules = [
            'periode' => 'required',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'anggaran' => 'required|numeric',
        ];

        if ($isKategoriPengeluaranChanged) {
            $rules['id_kategori_pengeluaran'] = [
                'required',
                Rule::unique('anggarans')->where(function ($query) use ($request) {
                    return $query->where('periode', $request->periode);
                }),
            ];
        } else {
            $rules['id_kategori_pengeluaran'] = 'required';
        }

        // Validate the request
        $validatedData = $request->validate($rules);

        // Update the record
        $anggaran->user_id = $request->auth['user']['user_id'];
        $anggaran->periode = $request->periode;
        $anggaran->tanggal_mulai = $request->tanggal_mulai;
        $anggaran->tanggal_selesai = $request->tanggal_selesai;
        $anggaran->anggaran = $request->anggaran;
        $anggaran->id_kategori_pengeluaran = $request->id_kategori_pengeluaran;
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
            $anggaran = $anggaran
                        ->where('user_id', $request->auth['user']['user_id'])
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
