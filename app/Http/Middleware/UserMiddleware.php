<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;

class UserMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if($request->header('user-type') == 'user') {
            if($user = Auth::guard('users')->user()) {
                $user = Controller::user($user);
                $auth = Controller::userAuth('user', $user);
                $request->merge(['auth' => $auth]);
                return $next($request);
            }
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Akses ditolak.',
        ], Response::HTTP_FORBIDDEN);        
    }
}
