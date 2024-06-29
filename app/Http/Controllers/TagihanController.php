<?php

namespace App\Http\Controllers;

use App\Models\Tagihan;
use Illuminate\Http\Request;

class TagihanController extends Controller
{
    public function index()
    {
        $tagihans = Tagihan::all();
        return response()->json(['message' => 'success', 'data' => $tagihans]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required',
            'nama_tagihan' => 'required',
            'tanggal_tagihan' => 'required',
            'tanggal_jatuh_tempo' => 'required',
            'jumlah' => 'required',
            'bunga' => 'required',
            'total_tagihan' => 'required',
        ]);

        $tagihan = Tagihan::create($request->all());
        return response()->json(['message' => 'success', 'data' => $tagihan]);
    }

    public function show($id)
    {
        $tagihan = Tagihan::find($id);
        if ($tagihan) {
            return response()->json(['message' => 'success', 'data' => $tagihan]);
        } else {
            return response()->json(['message' => 'failed', 'data' => null]);
        }
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'user_id' => 'required',
            'nama_tagihan' => 'required',
            'tanggal_tagihan' => 'required',
            'tanggal_jatuh_tempo' => 'required',
            'jumlah' => 'required',
            'bunga' => 'required',
            'total_tagihan' => 'required',
        ]);

        $tagihan = Tagihan::find($id);
        if ($tagihan) {
            $tagihan->update($request->all());
            return response()->json(['message' => 'success', 'data' => $tagihan]);
        } else {
            return response()->json(['message' => 'failed', 'data' => null]);
        }
    }

    public function destroy($id)
    {
        $tagihan = Tagihan::find($id);
        if ($tagihan) {
            $tagihan->delete();
            return response()->json(['message' => 'success', 'data' => null]);
        } else {
            return response()->json(['message' => 'failed', 'data' => null]);
        }
    }
}