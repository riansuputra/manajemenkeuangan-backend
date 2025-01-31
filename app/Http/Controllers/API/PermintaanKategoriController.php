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
            $permintaan = PermintaanKategori::where('user_id', $request->auth['user']['id'])
                                        ->get();
            // dd($permintaan);
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
            $permintaan = new PermintaanKategori();
            $permintaan = PermintaanKategori::with(['user', 'admin'])->get();
            
            return response()->json([
                'message' => 'Berhasil mendapatkan daftar permintaan kategori.',
                'auth' => $request->auth,
                'data' => [
                    'PermintaanKategori' => $permintaan
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
                    Rule::unique('permintaan_kategori')->where(function ($query) use ($request) {
                        return $query->where('tipe_kategori', $request->tipe_kategori)
                                    ->where('scope', $request->cakupan_kategori);
                    })
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


    public function approve(Request $request, $id)
    {
        try{
        $permintaan = PermintaanKategori::findOrFail($id);
        if ($permintaan->status == 'approved') {
            return response()->json(['message' => 'Category request already approved.'], 400);
        }

        $permintaan->update([
            'status' => 'approved',
            'admin_id' => $request->auth['admin']['id'],
        ]);

        if ($permintaan->tipe_kategori == 'pengeluaran') {
            KategoriPengeluaran::create(['nama_kategori_pengeluaran' => $permintaan->nama_kategori]);
        } else {
            KategoriPemasukan::create(['nama_kategori_pemasukan' => $permintaan->nama_kategori]);
        }

        return response()->json(['message' => 'Category request approved successfully.']);
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

    public function reject(Request $request, $id)
    {
        try{
        $request->validate([
            'message' => 'nullable',
        ]);

        $permintaan = PermintaanKategori::findOrFail($id);
        if ($permintaan->status == 'rejected') {
            return response()->json(['message' => 'Category request already rejected.'], 400);
        }

        $permintaan->update([
            'status' => 'rejected',
            'admin_id' => $request->auth['admin']['id'],
        ]);

        return response()->json(['message' => 'Category request rejected successfully.']);
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

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
