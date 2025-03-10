<?php

namespace App\Http\Controllers\API;

use App\Models\Dividen;
use Illuminate\Http\Request;
use Exception;
use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;
use Illuminate\Validation\Rule;

class DividenController extends Controller
{
    public function index(Request $request)
    {
        try {
            $dividen = Dividen::with('aset')->get();
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
        try {
            $request->validate([
                'aset_id' => 'required',
                'dividen' => 'required',
                'cum_date' => 'required',
                'ex_date' => 'required',
                'recording_date' => 'required',
                'payment_date' => 'required',
            ]);
            $dividen = new Dividen();
            $dividen->aset_id = $request->aset_id;
            $dividen->dividen = $request->dividen;
            $dividen->cum_date = $request->cum_date;
            $dividen->ex_date = $request->ex_date;
            $dividen->recording_date = $request->recording_date;
            $dividen->payment_date = $request->payment_date;
            $dividen->save();
            return response()->json([
                'message' => 'Berhasil menambah dividen.',
                'auth' => $request->auth,
                'data' => [
                    'dividen' => $dividen
                ],
            ], Response::HTTP_CREATED);
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

    public function show(Request $request, Dividen $dividen)
    {
        try {
            $dividen = new Dividen();
            $dividen = $dividen->findOrFail($id);
            return response()->json([
                'message' => 'Berhasil mendapatkan detail dividen',
                'auth' => $request->auth,
                'data' => [
                    'dividen' => $dividen
                ]
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

    public function edit(Dividen $dividen)
    {
        //
    }

    public function update(Request $request, Dividen $dividen)
    {
        try{
            $dividen = new Dividen();
            $dividen = $dividen->findOrFail($id);
            $request->validate([
                'aset_id' => 'required',
                'dividen' => 'required',
                'cum_date' => 'required',
                'ex_date' => 'required',
                'recording_date' => 'required',
                'payment_date' => 'required',
            ]);
            $dividen->aset_id = $request->aset_id;
            $dividen->dividen = $request->dividen;
            $dividen->cum_date = $request->cum_date;
            $dividen->ex_date = $request->ex_date;
            $dividen->recording_date = $request->recording_date;
            $dividen->payment_date = $request->payment_date;
            $dividen->save();
            return response()->json([
                'message' => 'Berhasil mengubah dividen.',
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

    public function destroy(Request $request, Dividen $dividen)
    {
        try{
            $dividen = new Dividen();
            $dividen = $dividen->findOrFail($id)->delete();
            return response()->json([
                'message' => 'Berhasil menghapus catatan.',
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
}
