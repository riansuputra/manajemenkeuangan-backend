<?php

namespace App\Http\Controllers;

use App\Models\CategoryRequest;
use App\Models\KategoriPengeluaran;
use App\Models\KategoriPemasukan;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Exception;
use Laravel\Sanctum\PersonalAccessToken;
class CategoryRequestController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $categoryRequest = new CategoryRequest();
            $categoryRequest = CategoryRequest::where('user_id', $request->auth['user']['user_id'])
                                        ->get();
            
            return response()->json([
                'message' => 'Berhasil mendapatkan daftar toko.',
                'auth' => $request->auth,
                'data' => [
                    'categoryRequest' => $categoryRequest
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

    public function indexMobile()
    {
        return CategoryRequest::paginate(10);
    }

    public function indexAdmin(Request $request)
    {
        try {
            $categoryRequest = new CategoryRequest();
            $categoryRequest = CategoryRequest::with(['user', 'admin'])->get();

            
            return response()->json([
                'message' => 'Berhasil mendapatkan daftar toko.',
                'auth' => $request->auth,
                'data' => [
                    'categoryRequest' => $categoryRequest
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
        // Basic validation for required fields and valid category type
        $request->validate([
            'category_type' => 'required|in:pengeluaran,pemasukan',
            'nama_kategori' => ['required', 'string', 'max:100'],
        ]);

        // Check for uniqueness based on category type
        $exists = false;
        if ($request->category_type == 'pengeluaran') {
            $exists = \App\Models\KategoriPengeluaran::where('nama_kategori_pengeluaran', $request->nama_kategori)->exists();
        } else if ($request->category_type == 'pemasukan') {
            $exists = \App\Models\KategoriPemasukan::where('nama_kategori_pemasukan', $request->nama_kategori)->exists();
        }

        // If a category with the same name exists, return a validation error
        if ($exists) {
            return response()->json(['message' => 'Category name already exists in the selected category type.'], 422);
        }

        $token = request()->bearerToken();
        $accessToken = PersonalAccessToken::findToken($token);
        $current_user = $accessToken->tokenable;

        // Create the category request
        CategoryRequest::create([
            'category_type' => $request->category_type,
            'nama_kategori' => $request->nama_kategori,
            'user_id' => $current_user->user_id, // Assuming you are using the Laravel Auth system
        ]);

        return response()->json(['message' => 'Category request submitted successfully.']);
}

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function storeWeb(Request $request)
{
    // Basic validation for required fields and valid category type
    $request->validate([
        'category_type' => 'required|in:pengeluaran,pemasukan',
        'nama_kategori' => ['required','string','max:100',Rule::unique('category_requests')],
    ]);

    // Check for uniqueness based on category type
    $exists = false;
    if ($request->category_type == 'pengeluaran') {
        $exists = \App\Models\KategoriPengeluaran::where('nama_kategori_pengeluaran', $request->nama_kategori)->exists();
    } else if ($request->category_type == 'pemasukan') {
        $exists = \App\Models\KategoriPemasukan::where('nama_kategori_pemasukan', $request->nama_kategori)->exists();
    }

    // If a category with the same name exists, return a validation error
    if ($exists) {
        return response()->json(['message' => 'Category name already exists in the selected category type.'], 422);
    }

    // Create the category request
    CategoryRequest::create([
        'category_type' => $request->category_type,
        'nama_kategori' => $request->nama_kategori,
        'user_id' => $request->user_id, // Assuming you are using the Laravel Auth system
    ]);

    return response()->json(['message' => 'Category request submitted successfully.']);
}

    public function approve(Request $request, $id)
    {
        $categoryRequest = CategoryRequest::findOrFail($id);
        if ($categoryRequest->status == 'approved') {
            return response()->json(['message' => 'Category request already approved.'], 400);
        }

        $categoryRequest->update([
            'status' => 'approved',
            'admin_id' => $request->auth['admin']['admin_id'],
        ]);

        if ($categoryRequest->category_type == 'pengeluaran') {
            KategoriPengeluaran::create(['nama_kategori_pengeluaran' => $categoryRequest->nama_kategori]);
        } else {
            KategoriPemasukan::create(['nama_kategori_pemasukan' => $categoryRequest->nama_kategori]);
        }

        return response()->json(['message' => 'Category request approved successfully.']);
    }

    public function reject(Request $request, $id)
    {
        $request->validate([
            'message' => 'nullable',
        ]);

        $categoryRequest = CategoryRequest::findOrFail($id);
        if ($categoryRequest->status == 'rejected') {
            return response()->json(['message' => 'Category request already rejected.'], 400);
        }

        $categoryRequest->update([
            'status' => 'rejected',
            'admin_id' => $request->auth['admin']['admin_id'],
        ]);

        return response()->json(['message' => 'Category request rejected successfully.']);
    }

    /**
     * Display the specified resource.
     */
    public function show(CategoryRequest $categoryRequest)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(CategoryRequest $categoryRequest)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, CategoryRequest $categoryRequest)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CategoryRequest $categoryRequest)
    {
        //
    }
}
