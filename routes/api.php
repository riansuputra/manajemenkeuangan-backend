<?php

use App\Http\Controllers\API\AnggaranController;
use App\Http\Controllers\API\AsetController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\BeritaController;
use App\Http\Controllers\API\CatatanController;
use App\Http\Controllers\API\DividenController;
use App\Http\Controllers\API\HistorisController;
use App\Http\Controllers\API\KategoriPemasukanController;
use App\Http\Controllers\API\KategoriPengeluaranController;
use App\Http\Controllers\API\KursController;
use App\Http\Controllers\API\MutasiDanaController;
use App\Http\Controllers\API\PemasukanController;
use App\Http\Controllers\API\PengeluaranController;
use App\Http\Controllers\API\PermintaanKategoriController;
use App\Http\Controllers\API\PortofolioController;
use App\Http\Controllers\API\SaldoController;
use App\Http\Controllers\API\SekuritasController;
use App\Http\Controllers\API\TransaksiController;
use App\Http\Controllers\API\UserController;

use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\AdminUserMiddleware;
use App\Http\Middleware\ApiKeyMiddleware;
use App\Http\Middleware\GuestMiddleware;
use App\Http\Middleware\UserMiddleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware([ApiKeyMiddleware::class, 'throttle:100,1'])->group(function () {
    Route::get('/auth', [AuthController::class, 'auth']);

    Route::middleware([GuestMiddleware::class])->group(function () {
        Route::post('/register', [AuthController::class, 'registerUser']);
        Route::post('/login', [AuthController::class, 'loginUser']);

        Route::post('/admin/login', [AuthController::class, 'loginAdmin']);
        Route::post('/admin/register', [AuthController::class, 'registerAdmin']);
        
        Route::post('/kirim-verifikasi', [AuthController::class, 'sendTestEmail']);
        Route::get('/verifikasi-email/{code}', [AuthController::class, 'verifyEmail']);
        Route::post('/lupa-password', [AuthController::class, 'lupaPassword']);
    });

    Route::middleware([AdminUserMiddleware::class])->group(function () {
        Route::get('/logout', [AuthController::class, 'logout']);

        Route::apiResource('pemasukan', PemasukanController::class);
        Route::apiResource('pengeluaran', PengeluaranController::class);
        Route::apiResource('kategori-pemasukan', KategoriPemasukanController::class);
        Route::apiResource('kategori-pengeluaran', KategoriPengeluaranController::class);
        Route::apiResource('anggaran', AnggaranController::class);
        Route::apiResource('catatan', CatatanController::class);
        Route::apiResource('kurs', KursController::class);
        Route::apiResource('dividen', DividenController::class);
        Route::apiResource('sekuritas', SekuritasController::class);
        Route::apiResource('historis', HistorisController::class);
        
        Route::apiResource('transaksi', TransaksiController::class);
        Route::get('/transaksi-all', [TransaksiController::class, 'indexAll']);
        Route::post('/update-price', [TransaksiController::class, 'updatePrice']);
        Route::post('/store-jual', [TransaksiController::class, 'storeJual']);

        Route::get('/mutasi-dana', [MutasiDanaController::class, 'index']);

        Route::apiResource('saldo', SaldoController::class);
        Route::post('/topup', [SaldoController::class, 'topup']);
        
        Route::apiResource('portofolio', PortofolioController::class);
        Route::get('/kinerja-portofolio', [PortofolioController::class, 'kinerja']);

        Route::apiResource('aset', AsetController::class);
        Route::post('/aset/store-kas', [AsetController::class, 'storeKas']);
        Route::post('/aset/store-ewallet', [AsetController::class, 'storeEwallet']);
        Route::post('/aset/store-bank', [AsetController::class, 'storeBank']);
        Route::post('/aset/store-lainnya', [AsetController::class, 'storeLainnya']);
        Route::post('/aset/store-saham', [AsetController::class, 'storeSaham']);

        Route::get('/berita/store', [BeritaController::class, 'store']);
        Route::get('/berita', [BeritaController::class, 'index']);

        Route::post('/permintaan-kategori-store', [PermintaanKategoriController::class, 'store']);
        Route::get('/permintaan-kategori', [PermintaanKategoriController::class, 'index']);

        Route::delete('/hapus-portofolio', [UserController::class, 'destroyPortofolio']);
        Route::delete('/hapus-keuangan', [UserController::class, 'destroyKeuangan']);
        Route::delete('/hapus-catatan', [UserController::class, 'destroyCatatan']);
        Route::post('/update-password', [AuthController::class, 'updatePassword']);
        Route::post('/update', [UserController::class, 'update']);
    });
    
    Route::middleware([AdminMiddleware::class])->group(function () {
        Route::get('/aset/store-saham', [AsetController::class, 'storeSaham']);
        
        Route::get('/permintaan-kategori-admin', [PermintaanKategoriController::class, 'indexAdmin']);
        Route::post('/permintaan-kategori/approve', [PermintaanKategoriController::class, 'approve']);
        Route::post('/permintaan-kategori/reject', [PermintaanKategoriController::class, 'reject']);

        Route::get('/user', [UserController::class, 'index']);
    });
});