<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\PermintaanKategori;
use App\Models\KategoriPengeluaran;
use App\Models\KategoriPemasukan;
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
        $request->validate([
            'tipe_kategori' => 'required|in:pengeluaran,pemasukan',
            'nama_kategori' => ['required','string','max:100',Rule::unique('category_requests')],
        ]);

        $exists = false;
        if ($request->tipe_kategori == 'pengeluaran') {
            $exists = \App\Models\KategoriPengeluaran::where('nama_kategori_pengeluaran', $request->nama_kategori)->exists();
        } else if ($request->tipe_kategori == 'pemasukan') {
            $exists = \App\Models\KategoriPemasukan::where('nama_kategori_pemasukan', $request->nama_kategori)->exists();
        }

        if ($exists) {
            return response()->json(['message' => 'Category name already exists in the selected category type.'], 422);
        }

        PermintaanKategori::create([
            'tipe_kategori' => $request->tipe_kategori,
            'nama_kategori' => $request->nama_kategori,
            'user_id' => $request->user_id,
        ]);

        return response()->json(['message' => 'Category request submitted successfully.']);
    }

    public function approve(Request $request, $id)
    {
        $permintaan = PermintaanKategori::findOrFail($id);
        if ($permintaan->status == 'approved') {
            return response()->json(['message' => 'Category request already approved.'], 400);
        }

        $permintaan->update([
            'status' => 'approved',
            'admin_id' => $request->auth['admin']['admin_id'],
        ]);

        if ($permintaan->tipe_kategori == 'pengeluaran') {
            KategoriPengeluaran::create(['nama_kategori_pengeluaran' => $permintaan->nama_kategori]);
        } else {
            KategoriPemasukan::create(['nama_kategori_pemasukan' => $permintaan->nama_kategori]);
        }

        return response()->json(['message' => 'Category request approved successfully.']);
    }

    public function reject(Request $request, $id)
    {
        $request->validate([
            'message' => 'nullable',
        ]);

        $permintaan = PermintaanKategori::findOrFail($id);
        if ($permintaan->status == 'rejected') {
            return response()->json(['message' => 'Category request already rejected.'], 400);
        }

        $permintaan->update([
            'status' => 'rejected',
            'admin_id' => $request->auth['admin']['admin_id'],
        ]);

        return response()->json(['message' => 'Category request rejected successfully.']);
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
