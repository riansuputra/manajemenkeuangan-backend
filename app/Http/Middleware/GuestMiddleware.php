<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;

class GuestMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if(Auth::guard('users')->check() || Auth::guard('admins')->check()){
            return response()->json([
                'status' => 'error',
                'message' => 'Akses ditolak.',
            ], Response::HTTP_FORBIDDEN);
        }
        
        $auth = Controller::userAuth();
        $request->merge(['auth' => $auth]);
        return $next($request);
    }
}
