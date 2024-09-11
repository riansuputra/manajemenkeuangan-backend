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

class AuthController extends Controller
{
    public function registerAdmin(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required',
            'name' => 'required',
            'password' => 'required',
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
        $admin->api_token = Str::random(60);
        $admin->save();
        return response()->json([
            'status' => 'success',
            'message' => 'Masuk sebagai admin berhasil.',
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
            'email' => 'required|unique:App\Models\User,email',
            'name' => 'required',
            'password' => 'required',
        ]);
        $user = array(
            'email' => $request->email,
            'name' => $request->name,
            'password' => Hash::make($request->password),
            'email_verification_code' => Str::random(60),
        );

        try {
            DB::beginTransaction();
            $user = User::create($user);
            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Berhasil daftar akun. Silahkan masuk sebagai user.',
            ], Response::HTTP_CREATED);
        } catch (Exception $e) {
            DB::rollback();
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal daftar akun user.'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function loginUser(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required',
            'password' => 'required',
        ]);
        $user = User::where('email', strtolower($validated['email']))->first();
        if (isset($request->user)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Masuk tidak diijinkan saat terautentikasi.',
            ], Response::HTTP_FORBIDDEN);
        }
        $user->api_token = Str::random(60);
        $user->save();
        $user = $this->user($user);
        return response()->json([
            'status' => 'success',
            'message' => 'Masuk sebagai user berhasil.',
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
                    'message' => 'user terautentikasi.',
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



    
    public function register(Request $request)
    {
        $checkuser = User::where('email', $request['email'])->first();
        if ($checkuser) {
            return response()->json([
                'error' => 1,
                'message' => 'user already exists',
                'code' => 409
            ]);
        }

        $user = User::create([
            'email' => $request['email'],
            'password' => Hash::make($request['password']),
            'name' => $request['name'],
        ]);

        // Send email with verification code Mailtrap?
        // Mail::to($user->email)->send(new VerificationMail($user));
        $email = $request['email'];
        $subject = 'Silahkan verifikasi akun anda';
        $message = "Silahkan verifikasi akun anda dengan klik link berikut: ' . url('/') . '/api/verify/' . $user->email_verification_code . ' Terima kasih.";

        $x = Mail::raw($message, function ($message) use ($email, $subject) {
            $message->to($email)
                ->subject($subject);
        });

        return response()->json([
            'error' => 1,
            'message' => 'Registration Successfully',
            'code' => 200,
            "data" => $user
        ]);

        // return $this->successResponse($user, 'Registration Successfully');
    }

    public function testSendEmail()
    {
        //send plain text email to "coba@mailinator.com"?
        $email = 'denyocr.world@gmail.com';
        $subject = 'Silahkan verifikasi akun anda';
        $message = "Silahkan verifikasi akun anda dengan klik link berikut: ' . url('/') . '/api/verify/testing Terima kasih.";

        $x = Mail::raw($message, function ($message) use ($email, $subject) {
            $message->to($email)
                ->subject($subject);
        });

        return response()->json([
            "message" => "Email sent!",
            "x" => $x,
            "email" => $email,
            "subject" => $subject,
            "message" => $message,
        ]);
    }

    public function verify($code)
    {
        $user = User::where('email_verification_code', $code)->first();
        if ($user) {
            $user->email_verified_at = now();
            $user->email_verification_code = null;
            $user->save();
            return response()->json([
                'error' => 0,
                'message' => 'Email Verified Successfully',
                'code' => Response::HTTP_OK
            ]);
        } else {
            return response()->json([
                'error' => 1,
                'message' => 'Invalid Verification Code',
                'code' => Response::HTTP_NOT_FOUND
            ]);
        }
    }
}