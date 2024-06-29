<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StockAPIController;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/updatestock', [StockAPIController::class, 'updateStock']);
Route::get('/update', [StockAPIController::class, 'updateStock']);
Route::post('/dividen', [StockAPIController::class, 'dividen'])->name('dividen');

// Route::get('/', [StockAPIController::class, 'index']);

