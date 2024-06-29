<?php

namespace App\Http\Controllers;

use App\Models\Sekuritas;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Validation\ValidationException;
use Exception;

class SekuritasController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Sekuritas::paginate(10);
    }

    public function indexWeb(Request $request) {
        try {
            $sekuritas = new Sekuritas();
            $sekuritas = Sekuritas::where('user_id', $request->auth['user']['user_id'])
                                ->with('kategori_pemasukan')
                                ->get();
            
            return response()->json([
                'message' => 'Berhasil mendapatkan daftar toko.',
                'auth' => $request->auth,
                'data' => [
                    'pemasukan' => $sekuritas
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

    public function store(Request $request)
    {
        $data = $request->validate([
            'nama_sekuritas' => 'required',
            'fee_beli' => 'required',
            'fee_jual' => 'required',
        ]);

        $sekuritas = Sekuritas::create($data);
        return response()->json(['message' => 'Sekuritas created', 'sekuritas' => $sekuritas], 201);
    }

    public function show($id)
    {
        $sekuritas = Sekuritas::findOrFail($id);
        return response()->json(['sekuritas' => $sekuritas], 200);
    }

    public function update(Request $request, $id)
    {
        $sekuritas = Sekuritas::findOrFail($id);
        $data = $request->validate([
            'nama_sekuritas' => 'required',
            'fee_beli' => 'required',
            'fee_jual' => 'required',
        ]);

        $sekuritas->update($data);
        return response()->json(['message' => 'Sekuritas updated', 'sekuritas' => $sekuritas], 200);
    }

    public function destroy($id)
    {
        $sekuritas = Sekuritas::findOrFail($id);
        $sekuritas->delete();
        return response()->json(['message' => 'Sekuritas deleted'], 200);
    }
}
