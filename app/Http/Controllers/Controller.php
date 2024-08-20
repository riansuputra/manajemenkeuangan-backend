<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public static function user($user) {
        return $user;
    }

    public static function userAuth($user_type = null, $guard = null) {
        if($user_type == null || $guard == null) {
            return [
                'user_type' => 'guest'
            ];
        } else {
            return [
                'user_type' => $user_type,
                $user_type => $guard,
                'token' => $guard->api_token
            ];
        }
    }
}