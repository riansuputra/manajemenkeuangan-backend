<?php

namespace App\Http\Controllers\API;

use App\Models\Dividen;
use Illuminate\Http\Request;
use Exception;
use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Validation\ValidationException;

class DividenController extends Controller
{
    public function index(Request $request)
    {
        try {
            $dividen = Dividen::all();
            return response()->json([
                'message' => 'Berhasil mendapatkan dividen.',
                'auth' => $request->auth,
                'data' => [
                    'dividen' => $dividen
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
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Dividen $dividen)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Dividen $dividen)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Dividen $dividen)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Dividen $dividen)
    {
        //
    }
}
