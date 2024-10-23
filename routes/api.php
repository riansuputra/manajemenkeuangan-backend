<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\UserMiddleware;
use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\GuestMiddleware;
use App\Http\Middleware\ApiKeyMiddleware;
use App\Http\Controllers\API\AuthController;
use App\Http\Middleware\AdminUserMiddleware;
use App\Http\Controllers\API\KursController;
use App\Http\Controllers\API\AsetController;
use App\Http\Controllers\API\SahamController;
use App\Http\Controllers\API\SaldoController;
use App\Http\Controllers\API\BeritaController;
use App\Http\Controllers\API\DividenController;
use App\Http\Controllers\API\AnggaranController;
use App\Http\Controllers\API\HistorisController;
use App\Http\Controllers\API\TransaksiController;
use App\Http\Controllers\API\BeliSahamController;
use App\Http\Controllers\API\JualSahamController;
use App\Http\Controllers\API\SekuritasController;
use App\Http\Controllers\API\PemasukanController;
use App\Http\Controllers\API\PortofolioController;
use App\Http\Controllers\API\PengeluaranController;
use App\Http\Controllers\API\PortofolioBeliController;
use App\Http\Controllers\API\PortofolioJualController;
use App\Http\Controllers\API\CategoryRequestController;
use App\Http\Controllers\API\KategoriPemasukanController;
use App\Http\Controllers\API\TransactionHistoryController;
use App\Http\Controllers\API\KategoriPengeluaranController;
use App\Http\Controllers\API\ManajemenPortofolioController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware([ApiKeyMiddleware::class])->group(function () {
    Route::get('/auth', [AuthController::class, 'auth']);

    Route::middleware([GuestMiddleware::class])->group(function () {
        Route::post('/register', [AuthController::class, 'registerUser']);
        Route::post('/login', [AuthController::class, 'loginUser']);

        Route::post('/admin/login', [AuthController::class, 'loginAdmin']);
        Route::post('/admin/register', [AuthController::class, 'registerAdmin']);
        
        Route::post('/kirim-verifikasi', [AuthController::class, 'sendTestEmail']);
        Route::get('/verifikasi-email/{code}', [AuthController::class, 'verifyEmail']);
    });

    Route::middleware([AdminUserMiddleware::class])->group(function () {
        Route::get('/logout', [AuthController::class, 'logout']);

        Route::apiResource('pemasukan', PemasukanController::class);
        Route::apiResource('pengeluaran', PengeluaranController::class);
        Route::apiResource('beli-saham', BeliSahamController::class);
        Route::apiResource('portofolio', PortofolioController::class);
        Route::apiResource('kategori-pemasukan', KategoriPemasukanController::class);
        Route::apiResource('kategori-pengeluaran', KategoriPengeluaranController::class);
        Route::apiResource('anggaran', AnggaranController::class);
        Route::apiResource('kurs', KursController::class);
        Route::apiResource('dividen', DividenController::class);
        Route::apiResource('transaksi', TransaksiController::class);
        Route::apiResource('sekuritas', SekuritasController::class);
        Route::apiResource('historis', HistorisController::class);
        
        Route::apiResource('saldo', SaldoController::class);
        Route::get('/mutasi-dana', [SaldoController::class, 'mutasiDana']);

        // Route::apiResource('aset', AsetController::class);
        Route::get('/aset/store-ewallet', [AsetController::class, 'storeEwallet']);
        Route::get('/aset/store-bank', [AsetController::class, 'storeBank']);
        Route::get('/aset/store-lainnya', [AsetController::class, 'storeLainnya']);


        Route::get('/saham', [SahamController::class, 'index']);
        Route::get('/saham/update', [SahamController::class, 'update']);

        
        Route::get('/histori', [PortofolioController::class, 'histori_tahunan']);
        Route::get('/histori-bulanan', [PortofolioController::class, 'histori_bulanan']);
        Route::get('/mutasi-dana', [PortofolioController::class, 'mutasi_dana']);
        

        Route::post('/category-requests', [CategoryRequestController::class, 'storeWeb']);

        Route::get('/pengeluaransWeb', [PengeluaranController::class, 'indexWeb']);



        // Route::get('/kategori_pemasukansWeb', [KategoriPemasukanController::class, 'indexWeb']);

        // Route::get('/kategori_pengeluaransWeb', [KategoriPengeluaranController::class, 'indexWeb']);

        Route::get('/category-request', [CategoryRequestController::class, 'index']);

        Route::get('/porto', [ManajemenPortofolioController::class, 'indexporto']);
        Route::get('/yield', [ManajemenPortofolioController::class, 'yield']);
        Route::apiResource('portofoliobeli', PortofolioBeliController::class);
        Route::post('/portofolio-beli', [PortofolioBeliController::class, 'storeWeb']);
        Route::get('/portofolio-beli', [PortofolioBeliController::class, 'indexWeb']);
        Route::apiResource('portofoliojual', PortofolioJualController::class);

        
        
    });
    
    Route::middleware([AdminMiddleware::class])->group(function () {
        Route::get('/aset/store-saham', [AsetController::class, 'storeSaham']);
        
        
        Route::get('/category-requests-admin', [CategoryRequestController::class, 'indexAdmin']);
        Route::post('/category-requests/{id}/approve', [CategoryRequestController::class, 'approve']);
        Route::post('/category-requests/{id}/reject', [CategoryRequestController::class, 'reject']);
        
        
    });
});
// Route::get('/porto', [ManajemenPortofolioController::class, 'indexporto']);
// Route::get('/dividen', [StockAPIController::class, 'indexdividen']);
Route::get('/berita/store', [BeritaController::class, 'store']);
Route::get('/berita', [BeritaController::class, 'index']);
// Route::get('/ihsg', [StockAPIController::class, 'ihsg']);

// Route::get('/stock/{$emiten}', [StockAPIController::class, 'stock']); // Harga
// Route::get('/stock', [StockAPIController::class, 'index']); // List Saham
// Route::get('/stock', [StockAPIController::class, 'indexStock']);
// Route::get('/saham', [StockAPIController::class, 'indexWeb']);

// Route::get('/stocks', [StockAPIController::class, 'index']);


// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

// Route::post('/category-requests', [CategoryRequestController::class, 'store']); // Simpan Category
// Route::get('/category-requests', [CategoryRequestController::class, 'indexMobile']); // List request categorie

// Route::apiResource('portofoliobeli', PortofolioBeliController::class);
// Route::apiResource('portofoliojual', PortofolioJualController::class);
// Route::post('saldo/user', [SaldoController::class, 'saldoUser']);
// Route::apiResource('saldo', SaldoController::class);

// Route::apiResource('pemasukans',PemasukanController::class);
// Route::apiResource('pengeluarans', PengeluaranController::class);
// Route::apiResource('tagihans', TagihanController::class);
// Route::resource('kategori_pemasukans', KategoriPemasukanController::class);
// Route::resource('kategori_pengeluarans', KategoriPengeluaranController::class);

// Route::post('login', [AuthController::class, 'login']);
// Route::post('register', [AuthController::class, 'register']);
// Route::get('test-send-email', [AuthController::class, 'testSendEmail']);
// Route::get('verify/{code}', [AuthController::class, 'verify']);
// Route::post('logout', [AuthController::class, 'logout']);

// Route::get('/transaction-histories/{month}/{year}', [TransactionHistoryController::class, 'filterByMonthAndYear']);
// Route::get('/transaction-histories/{year}', [TransactionHistoryController::class, 'filterByYear']);
// Route::get('/transaction-histories/categories/{month}/{year}', [TransactionHistoryController::class, 'filterCategoriesByMonthAndYear']);

// // Route buat manggil histori saham
// Route::get('/histori_30hari/{symbol}', [StockAPIController::class, 'historical_30hari']);
// Route::get('/histori_60hari/{symbol}', [StockAPIController::class, 'historical_60hari']);
// Route::get('/histori_90hari/{symbol}', [StockAPIController::class, 'historical_90hari']);
// Route::get('/histori_1tahun/{symbol}', [StockAPIController::class, 'historical_1tahun']);

// Route::get('/updatestock', [StockAPIController::class, 'updateStock']);
// Route::get('/emiten', [StockAPIController::class, 'getDataAdmin']);
// Route::get('/emiten/update', [StockAPIController::class, 'updateStock']);
// Route::get('/emiten/delete/{emiten}', [StockAPIController::class, 'delete']);