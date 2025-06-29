<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function index(Request $request) 
    {
        try {
            $user = User::all();
            return response()->json([
                'message' => 'Berhasil mendapatkan daftar user.',
                'auth' => $request->auth,
                'data' => [
                    'user' => $user,
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
        $user = User::create($request->all());
        return response()->json($user, 201);
    }

    public function show($id)
    {
        return User::findOrFail($id);
    }

    public function update(Request $request)
    {
        try{
            $user = User::findOrFail($request->auth['user']['id']);
            $request->validate([
                'alamat' => 'nullable',
                'no_telp' => 'nullable',
            ]);
            $user->alamat = $request->alamat;
            $user->no_telp = $request->no_telp;
            $user->save();
            $user = $this->user($user);
            return response()->json([
                'status' => 'success',
                'message' => 'Berhasil mengubah informasi akun.',
                'auth' => [
                    'user_type' => 'user',
                    'user' => $user,
                    'token' => $user->api_token,
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

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();
        return response()->json(['message' => 'User deleted'], 200);
    }

    public function destroyPortofolio(Request $request)
    {
        $userId = $request->auth['user']['id'];

        try {
            DB::beginTransaction();

            // Hapus dari semua tabel yang berhubungan
            DB::table('portofolio')->where('user_id', $userId)->delete();
            DB::table('kinerja_portofolio')->where('user_id', $userId)->delete();
            DB::table('mutasi_dana')->where('user_id', $userId)->delete();
            DB::table('saldo')->where('user_id', $userId)->delete();
            DB::table('transaksi')->where('user_id', $userId)->delete();
            DB::table('historis')->where('user_id', $userId)->delete();
            DB::table('perubahan_harga')->where('user_id', $userId)->delete();
            DB::table('tutup_buku')->where('user_id', $userId)->delete();

            DB::commit();

            return response()->json([
                'message' => 'Berhasil menghapus data portofolio.',
                'auth' => $request->auth
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Gagal menghapus data: ' . $e->getMessage(),
                'auth' => $request->auth
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    public function destroyKeuangan(Request $request)
    {
        $userId = $request->auth['user']['id'];

        try {
            DB::beginTransaction();

            // Hapus dari semua tabel yang berhubungan
            DB::table('anggaran')->where('user_id', $userId)->delete();
            DB::table('pemasukan')->where('user_id', $userId)->delete();
            DB::table('pengeluaran')->where('user_id', $userId)->delete();

            DB::commit();

            return response()->json([
                'message' => 'Berhasil menghapus data catatan keuangan dan anggaran.',
                'auth' => $request->auth
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Gagal menghapus data: ' . $e->getMessage(),
                'auth' => $request->auth
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    public function destroyCatatan(Request $request)
    {
        $userId = $request->auth['user']['id'];

        try {
            DB::beginTransaction();

            // Hapus dari semua tabel yang berhubungan
            DB::table('catatan')->where('user_id', $userId)->delete();

            DB::commit();

            return response()->json([
                'message' => 'Berhasil menghapus data catatan umum.',
                'auth' => $request->auth
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Gagal menghapus data: ' . $e->getMessage(),
                'auth' => $request->auth
            ], Response::HTTP_BAD_REQUEST);
        }
    }
}
