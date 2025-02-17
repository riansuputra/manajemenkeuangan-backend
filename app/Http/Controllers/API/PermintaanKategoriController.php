<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\PermintaanKategori;
use App\Models\KategoriPengeluaran;
use App\Models\KategoriPemasukan;
use App\Models\KategoriPribadi;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Exception;
use Laravel\Sanctum\PersonalAccessToken;

class PermintaanKategoriController extends Controller
{
    public function index(Request $request)
    {
        try {
            $permintaan = PermintaanKategori::where('user_id', $request->auth['user']['id'])->get();
            return response()->json([
                'message' => 'Berhasil mendapatkan daftar permintaan kategori.',
                'auth' => $request->auth,
                'data' => [
                    'permintaan' => $permintaan
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

    public function indexAdmin(Request $request)
    {
        try {
            $permintaan = PermintaanKategori::with(['user', 'admin'])->get();
            return response()->json([
                'message' => 'Berhasil mendapatkan daftar permintaan kategori.',
                'auth' => $request->auth,
                'data' => [
                    'permintaan' => $permintaan
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
                'tipe_kategori' => 'required|in:pengeluaran,pemasukan',
                'nama_kategori' => [
                    'required', 
                    'string', 
                    'max:100',
                    function ($attribute, $value, $fail) use ($request) {
                        $namaKategori = strtolower($value);
                        $existsApproved = false;
            
                        if ($request->cakupan_kategori === 'global') {
                            // Cek kategori global yang sudah di-approve di kategori pemasukan atau pengeluaran
                            if ($request->tipe_kategori === 'pengeluaran') {
                                $existsApproved = \App\Models\KategoriPengeluaran::whereRaw('LOWER(nama_kategori_pengeluaran) = ?', [$namaKategori])->exists();
                            } elseif ($request->tipe_kategori === 'pemasukan') {
                                $existsApproved = \App\Models\KategoriPemasukan::whereRaw('LOWER(nama_kategori_pemasukan) = ?', [$namaKategori])->exists();
                            }
                        } elseif ($request->cakupan_kategori === 'personal') {
                            if ($request->tipe_kategori === 'pengeluaran') {
                                $existsApproved = \App\Models\KategoriPengeluaran::whereRaw('LOWER(nama_kategori_pengeluaran) = ?', [$namaKategori.' (Personal)'])
                                ->where('user_id', $request->auth['user']['id'])
                                ->exists();
                            } elseif ($request->tipe_kategori === 'pemasukan') {
                                $existsApproved = \App\Models\KategoriPemasukan::whereRaw('LOWER(nama_kategori_pemasukan) = ?', [$namaKategori.' (Personal)'])
                                ->where('user_id', $request->auth['user']['id'])
                                ->exists();
                            }
                        }
            
                        if ($existsApproved) {
                            $fail('Nama kategori sudah ada dan telah disetujui.');
                        }
                    },
                    Rule::unique('permintaan_kategori')->where(function ($query) use ($request) {
                        return $query->where('tipe_kategori', $request->tipe_kategori)
                                    ->where('scope', $request->cakupan_kategori)
                                    ->where('user_id', $request->auth['user']['id']); 
                    }),
                ],
                'cakupan_kategori' => 'required|in:global,personal',
            ]);
            
            $existsGlobal = false;
            $existsPersonal = false;
            if ($request->tipe_kategori == 'pengeluaran') {
                $existsGlobal = \App\Models\KategoriPengeluaran::whereRaw('LOWER(nama_kategori_pengeluaran) = ?', [strtolower($request->nama_kategori)])->exists();
                $existsPersonal = \App\Models\PermintaanKategori::whereRaw('LOWER(nama_kategori) = ?', [strtolower($request->nama_kategori.' (Personal)')])
                    ->where('tipe_kategori', $request->tipe_kategori)
                    ->where('user_id', $request->auth['user']['id'])
                    ->where('scope', $request->cakupan_kategori)->exists();
            } else if ($request->tipe_kategori == 'pemasukan') {
                $existsGlobal = \App\Models\KategoriPemasukan::whereRaw('LOWER(nama_kategori_pemasukan) = ?', [strtolower($request->nama_kategori)])->exists();
                $existsPersonal = \App\Models\PermintaanKategori::whereRaw('LOWER(nama_kategori) = ?', [strtolower($request->nama_kategori.' (Personal)')])
                    ->where('tipe_kategori', $request->tipe_kategori)
                    ->where('user_id', $request->auth['user']['id'])
                    ->where('scope', $request->cakupan_kategori)->exists();
            }
            if ($existsGlobal && $existsPersonal) {
                return response()->json(['message' => 'Kategori sudah ada untuk tipe kategori yang dipilih secara global dan personal.'], 422);
            } else if ($existsGlobal) {
                return response()->json(['message' => 'Nama kategori sudah ada untuk tipe kategori yang dipilih secara global.'], 422);
            } else if ($existsPersonal) {
                return response()->json(['message' => 'Nama kategori sudah ada untuk tipe kategori yang dipilih secara personal.'], 422);
            }

            $nama = $request->cakupan_kategori == 'personal' ? $request->nama_kategori . ' (Personal)' : $request->nama_kategori;
            $admin = isset($request->auth['admin']['id']) ? $request->auth['admin']['id'] : 1;

            $permintaanKategori = PermintaanKategori::create([
                'tipe_kategori' => $request->tipe_kategori,
                'nama_kategori' => $nama,
                'admin_id' => $admin,
                'user_id' => $request->auth['user']['id'],
                'scope' => $request->cakupan_kategori,
            ]);
            return response()->json([
                'status' => 'success',
                'message' => 'Permintaan kategori berhasil ditambahkan.',
                'auth' => $request->auth,
                'data' => [
                    'permintaan_kategori' => $permintaanKategori,
                ],
            ], Response::HTTP_CREATED);
        } catch (Exception $e) {
            if ($e instanceof ValidationException) {
                return response()->json([
                    'message' => $e->getMessage(),
                    'auth' => $request->auth,
                    'errors' =>  $e->validator->errors(),
                ], Response::HTTP_BAD_REQUEST);
            } else {
                return response()->json([
                    'message' => $e->getMessage(),
                    'auth' => $request->auth
                ], Response::HTTP_BAD_REQUEST);
            }
        }
    }

    public function approve(Request $request)
    {
        try{
            $permintaan = PermintaanKategori::findOrFail($request->id);
            if ($permintaan->status == 'approved') {
                return response()->json(['message' => 'Permintaan kategori sudah disetujui.'], 400);
            }

            $permintaan->update([
                'status' => 'approved',
                'scope' => $request->scope,
                'admin_id' => $request->auth['admin']['id'],
                'message' => $request->message,
            ]);

            if ($request->scope == 'personal') {
                if ($permintaan->tipe_kategori == 'pengeluaran') {
                    KategoriPengeluaran::create(['nama_kategori_pengeluaran' => $permintaan->nama_kategori, 'user_id' => $permintaan->user_id]);
                } else {
                    KategoriPemasukan::create(['nama_kategori_pemasukan' => $permintaan->nama_kategori, 'user_id' => $permintaan->user_id]);
                }
            } else if ($request->scope == 'global'){
                $namaKategori = preg_replace('/\s?\(.*\)$/', '', $permintaan->nama_kategori);
                if ($permintaan->tipe_kategori == 'pengeluaran') {
                    KategoriPengeluaran::create(['nama_kategori_pengeluaran' => $namaKategori]);
                } else {
                    KategoriPemasukan::create(['nama_kategori_pemasukan' => $namaKategori]);
                }
            }
            
            return response()->json([
                'status' => 'success',
                'message' => 'Permintaan kategori berhasil disetujui.',
                'auth' => $request->auth,
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            if ($e instanceof ValidationException) {
                return response()->json([
                    'message' => $e->getMessage(),
                    'auth' => $request->auth,
                    'errors' =>  $e->validator->errors(),
                ], Response::HTTP_BAD_REQUEST);
            } else {
                return response()->json([
                    'message' => $e->getMessage(),
                    'auth' => $request->auth
                ], Response::HTTP_BAD_REQUEST);
            }
        }
    }

    public function reject(Request $request)
    {
        try{
            // dd($request);
            $request->validate([
                'message' => 'nullable',
                'id' => 'required',
            ]);

            $permintaan = PermintaanKategori::findOrFail($request->id);
            if ($permintaan->status == 'rejected') {
                return response()->json(['message' => 'Permintaan kategori sudah ditolak.'], 400);
            }

            $permintaan->update([
                'status' => 'rejected',
                'admin_id' => $request->auth['admin']['id'],
                'message' => $request->message,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Permintaan kategori berhasil ditolak.',
                'auth' => $request->auth,
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            if ($e instanceof ValidationException) {
                return response()->json([
                    'message' => $e->getMessage(),
                    'auth' => $request->auth,
                    'errors' =>  $e->validator->errors(),
                ], Response::HTTP_BAD_REQUEST);
            } else {
                return response()->json([
                    'message' => $e->getMessage(),
                    'auth' => $request->auth
                ], Response::HTTP_BAD_REQUEST);
            }
        }
    }

    public function show(string $id)
    {
        //
    }

    public function edit(string $id)
    {
        //
    }

    public function update(Request $request, string $id)
    {
        //
    }

    public function destroy(string $id)
    {
        //
    }
}
