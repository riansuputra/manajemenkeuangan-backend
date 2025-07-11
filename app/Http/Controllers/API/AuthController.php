<?php

namespace App\Http\Controllers\API;

use Illuminate\Support\Arr;
use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\Admin;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;
use Exception;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function registerAdmin(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required',
            'name' => 'required',
            'password' => 'required',
            'konfirmasiPassword' => 'required|same:password',
        ]);
        $admin = array(
            'email' => $request->email,
            'name' => $request->name,
            'password' => Hash::make($request->password)
        );

        try {
            DB::beginTransaction();
            $now = Carbon::now()->timezone(env('APP_TIMEZONE'));
            Admin::create($admin);
            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Berhasil daftar akun. Silahkan masuk sebagai admin.'
            ], Response::HTTP_CREATED);
        } catch (Exception $e) {
            DB::rollback();
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal daftar akun admin.'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function loginAdmin(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required',
            'password' => 'required',
        ]);
        $admin = Admin::where('email', strtolower($validated['email']))->first();
        if (isset($request->admin)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Masuk tidak diijinkan saat terautentikasi.',
            ], Response::HTTP_FORBIDDEN);
        }
        if (!Hash::check($validated['password'], $admin->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Password yang Anda masukkan salah. Silakan coba lagi.',
                'errors' => [
                    'password' => 'Password tidak sesuai.'
                ]
            ], Response::HTTP_UNAUTHORIZED);
        }
        $admin->api_token = Str::random(60);
        $admin->save();
        return response()->json([
            'status' => 'success',
            'message' => 'Berhasil masuk sebagai admin.',
            'auth' => [
                'user_type' => 'admin',
                'admin' => $admin,
                'token' => $admin->api_token
            ]
        ], Response::HTTP_OK);
    }

    public function registerUser(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|string|unique:App\Models\User,email',
            'name' => 'required|string|min:3|max:100',
            'password' => 'required|string|min:8|max:20|same:konfirmasiPassword',
            'konfirmasiPassword' => 'required|string|same:password',
        ], [
            'email.unique' => 'Email ini sudah terdaftar. Silakan gunakan email lain atau login.',
            'name.required' => 'Nama harus diisi dan minimal 3 karakter.',
            'name.min' => 'Nama harus diisi minimal 3 karakter.',
            'password.min' => 'Password harus memiliki panjang minimal 8 karakter.',
            'password.max' => 'Password harus memiliki panjang maksimal 20 karakter.',
            'password.same' => 'Password dan konfirmasi password harus sesuai.',
            'konfirmasiPassword.same' => 'Konfirmasi password dan password harus sesuai.',
        ]);

        Log::info('Validation result:', $validated);

        $code = Str::random(60);
        $userData = [
            'email' => $request->email,
            'name' => $request->name,
            'password' => Hash::make($request->password),
            'email_verification_code' => $code,
        ];

        try {
            DB::beginTransaction();
            $user = User::create($userData);
            DB::commit();

            Mail::send('emails.verify', ['code' => $code, 'email' => $user->email], function ($message) use ($user) {
                $message->to($user->email);
                $message->subject('Verifikasi Email Anda');
            });

            return response()->json([
                'status' => 'success',
                'message' => 'Berhasil daftar akun. Silakan verifikasi terlebih dahulu sebelum login.',
            ], Response::HTTP_CREATED);
        } catch (Exception $e) {
            DB::rollback();
            Log::error('Error during user registration: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal daftar akun user.',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function resendVerification(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|string|exists:user,email',
            ]);

            $user = User::where('email', $request->email)->first();
            if ($user->email_verified_at) {
                return back()->with('info', 'Email sudah diverifikasi.');
            }

            $code = Str::random(60);
            $user->email_verification_code = $code;
            $user->save();

            Mail::send('emails.verify', ['code' => $code, 'email' => $user->email], function ($message) use ($user) {
                $message->to($user->email);
                $message->subject('Verifikasi Email Anda');
            });

            return response()->json([
                'status' => 'success',
                'message' => 'Berhasil kirim ulang verifikasi. Silakan verifikasi terlebih dahulu sebelum login.',
            ], Response::HTTP_CREATED);
        } catch (Exception $e) {
            DB::rollback();
            Log::error('Error during user resend verification: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal kirim ulang verifikasi.',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function sendResetLink(Request $request)
    {
        try {
            $request->validate(['email' => 'required|email|exists:user,email']);

            $user = User::where('email', $request->email)->first();

            // // Cek apakah email sudah diverifikasi
            // if (is_null($user->email_verified_at)) {
            //     return response()->json([
            //         'status' => 'error',
            //         'message' => 'Email Anda belum diverifikasi. Silakan verifikasi terlebih dahulu.',
            //     ], Response::HTTP_FORBIDDEN);
            // }

            $token = Str::random(60);
            $expiresAt = Carbon::now()->addMinutes(30);

            $user->update([
                'password_reset_token' => $token,
                'password_reset_expires_at' => $expiresAt
            ]);

            $resetLink = 'http://localhost:8001/reset-password/' . $token;

            Mail::send('emails.change_password', ['token' => $resetLink, 'email' => $user->email], function ($message) use ($user) {
                $message->to($user->email);
                $message->subject('Reset Password Anda');
            });

            return response()->json([
                'status' => 'success',
                'message' => 'Link reset password telah dikirim ke email Anda.',
            ], Response::HTTP_CREATED);
        } catch (Exception $e) {
            Log::error('Error during user reset password: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal kirim reset link user.',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    public function resendResetLink(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email|exists:user,email',
            ]);

            $user = User::where('email', $request->email)->first();

            // // Cek apakah email sudah diverifikasi
            // if (is_null($user->email_verified_at)) {
            //     return response()->json([
            //         'status' => 'error',
            //         'message' => 'Email Anda belum diverifikasi. Silakan verifikasi terlebih dahulu.',
            //     ], Response::HTTP_FORBIDDEN);
            // }

            $now = Carbon::now();
            $token = $user->password_reset_token;
            $expiresAt = $user->password_reset_expires_at;

            if (!$token || !$expiresAt || $now->gt($expiresAt)) {
                $token = Str::random(60);
                $expiresAt = $now->addMinutes(30);

                $user->update([
                    'password_reset_token' => $token,
                    'password_reset_expires_at' => $expiresAt
                ]);
            }

            $resetLink = 'http://localhost:8001/reset-password?token=' . $token;

            Mail::send('emails.change_password', ['token' => $resetLink, 'email' => $user->email], function ($message) use ($user) {
                $message->to($user->email);
                $message->subject('Reset Password Anda (Permintaan Ulang)');
            });

            return response()->json([
                'status' => 'success',
                'message' => 'Link reset password telah dikirim ulang ke email Anda.',
            ], Response::HTTP_CREATED);
        } catch (Exception $e) {
            Log::error('Error during resend reset password: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengirim ulang reset password.',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }




    public function verifyEmail(Request $request, $code)
    {
        try {
            $user = User::where('email_verification_code', $request->code)->first();
    
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Token verifikasi tidak valid atau sudah digunakan.'
                ], 400);
            }
    
            if ($user->email_verified_at) {
                return response()->json([
                    'status' => 'info',
                    'message' => 'Email sudah diverifikasi sebelumnya. Silakan login.'
                ]);
            }
    
            DB::beginTransaction();
            $user->email_verified_at = now();
            $user->email_verification_code = null;
            $user->save();
            DB::commit();
    
            return response()->json([
                'status' => 'success',
                'message' => 'Email Anda berhasil diverifikasi! Sekarang Anda bisa login.'
            ]);
    
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error during email verification: ' . $e->getMessage());
    
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat memverifikasi email.'
            ], 500);
        }
    }

    public function verifyPassword(Request $request, $token)
    {
        $request->validate([
            'password' => 'required|min:8',
        ]);
    
        $user = User::where('password_reset_token', $token)
                    ->where('password_reset_expires_at', '>', now())
                    ->first();
    
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Token tidak valid atau sudah kadaluarsa.'
            ], 400);
        }
    
        DB::beginTransaction();
        try {
            $user->password = bcrypt($request->password);
            $user->password_reset_token = null;
            $user->password_reset_expires_at = null;
            $user->save();
    
            DB::commit();
    
            return response()->json([
                'status' => 'success',
                'message' => 'Password berhasil diubah. Silakan login dengan password baru Anda.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error during password reset: ' . $e->getMessage());
    
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat mengganti password.'
            ], 500);
        }
    }


    public function lupa_password_admin(Request $request) {
        $validated = $request->validate([
            'email' => 'required|string|max:100|email:rfc,dns|exists:App\Models\Admin,email',
            'kode_verifikasi' => 'required',
            'password_baru' => 'required|string|min:8|max:20|same:konfirmasi_password_baru',
            'konfirmasi_password_baru' => 'required|string|min:8|max:20|same:password_baru'
        ]);
        try{
            DB::beginTransaction();
            $admin = Admin::where('email', $request->email)->first();
            $admin->password = Hash::make($request->password_baru);
            $now = Carbon::now()->timezone(env('APP_TIMEZONE'));
            $verifikasi_akun = VerifikasiAkun::where('email', $request->email)
                ->where('user', 'Admin')
                ->where('jenis', 'Ganti Password')
                ->where('diverifikasi', null)
                ->where('kode', $request->kode_verifikasi)
                ->where('kadaluarsa', '>=', $now)
                ->first();
            if(!empty($verifikasi_akun)){
                $verifikasi_akun->diverifikasi = $now;
                $verifikasi_akun->save();
            }else{
                return response()->json([
                    'status' => 'error',
                    'message' => 'Kode verifikasi tidak sesuai atau sudah kadaluarsa. Silahkan mengirimkan ulang kode verifikasi.',
                ], Response::HTTP_NOT_FOUND);
            }
            $admin->save();
            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Berhasil ganti password akun admin toko. Silahkan masuk sebagai admin toko.',
            ], Response::HTTP_OK);
        }catch(Exception $e){
            DB::rollback();
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal ganti password akun admin toko.'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'password' => 'required',
            'new_password' => [
                'required', 'string', 'min:8', 'max:20', 'same:konfirmasi_password',
                'different:password'
            ],
            'konfirmasi_password' => 'required|string|same:new_password',
        ]);

        $user = $request->auth['user']['id'];

        if (!Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Password yang Anda masukkan salah. Silakan coba lagi.',
                'errors' => ['password' => 'Password tidak sesuai.']
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Simpan password baru
        $user->password = Hash::make($validated['new_password']);
        $user->save();

        $user = $this->user($user);
        return response()->json([
            'status' => 'success',
            'message' => 'Password berhasil diperbarui.',
            'auth' => [
                'user_type' => 'user',
                'user' => $user,
                'token' => $user->api_token,
            ]
        ], Response::HTTP_OK);
    }

    public function loginUser(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);
        $user = User::where('email', strtolower($validated['email']))->first();
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Email ini tidak terdaftar. Periksa kembali atau daftar akun baru.',
                'errors' => [
                    'email' => 'Email ini tidak terdaftar.',
                ]
            ], Response::HTTP_NOT_FOUND);
        }
        if (!Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Password yang Anda masukkan salah. Silakan coba lagi.',
                'errors' => [
                    'password' => 'Password tidak sesuai.'
                ]
            ], Response::HTTP_UNAUTHORIZED);
        }
        if (isset($request->user)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Masuk tidak diijinkan saat terautentikasi.',
                'errors' => [
                    'email' => 'Email tidak ditemukan.',
                    'password' => 'Password salah.'
                ]
            ], Response::HTTP_FORBIDDEN);
        }
        if (is_null($user->email_verified_at)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akun belum diverifikasi. Silakan cek email untuk verifikasi.',
                'errors' => ['email' => 'Akun belum diverifikasi.']
            ], Response::HTTP_FORBIDDEN);
        }
        $user->api_token = Str::random(60);
        $user->save();
        $user = $this->user($user);
        return response()->json([
            'status' => 'success',
            'message' => 'Berhasil masuk sebagai user.',
            'auth' => [
                'user_type' => 'user',
                'user' => $user,
                'token' => $user->api_token,
            ]
        ], Response::HTTP_OK);
    }

    public function auth(Request $request)
    {
        if ($request->header('user-type') == 'user') {
            if ($user = Auth::guard('user')->user()) {
                $user = $this->user($user);
                return response()->json([
                    'status' => 'success',
                    'message' => 'User terautentikasi.',
                    'auth' => [
                        'user_type' => 'user',
                        'user' => $user,
                        'token' => $user->api_token
                    ]
                ], Response::HTTP_OK);
            }
        }
        if ($request->header('user-type') == 'admin') {
            if ($admin = Auth::guard('admin')->user()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Admin terautentikasi.',
                    'auth' => [
                        'user_type' => 'admin',
                        'admin' => $admin,
                        'token' => $admin->api_token
                    ]
                ], Response::HTTP_OK);
            }
        }
        return response()->json([
            'status' => 'success',
            'message' => 'User tidak terautentikasi.',
            'auth' => [
                'user_type' => 'guest',
            ]
        ], Response::HTTP_OK);
    }

    public function logout(Request $request)
    {
        if ($request->auth['user_type'] == 'admin') {
            $admin = Admin::find($request->auth['admin']['id']);
            $admin->api_token = null;
            $admin->save();
            return response()->json([
                'status' => 'success',
                'message' => 'Berhasil keluar.',
                'auth' => [
                    'user_type' => 'admin',
                ]
            ], Response::HTTP_OK);
        }
        if ($request->auth['user_type'] == 'user') {
            $user = User::find($request->auth['user']['id']);
            $user->api_token = null;
            $user->save();
            return response()->json([
                'status' => 'success',
                'message' => 'Berhasil keluar.',
                'auth' => [
                    'user_type' => 'user',
                ]
            ], Response::HTTP_OK);
        }
    }
}