<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

// Halaman-halaman Multi-Page Dashboard (UI Only)
Route::get('/', [DashboardController::class, 'index']);
Route::get('/dashboard', [DashboardController::class, 'dashboard'])->name('dashboard');
Route::get('/machine-control', [DashboardController::class, 'machineControl'])->name('machine-control');
Route::get('/chart', [DashboardController::class, 'chart'])->name('chart');
Route::get('/riwayat', [DashboardController::class, 'riwayat'])->name('riwayat');
Route::get('/detail', [DashboardController::class, 'detail'])->name('detail');
Route::get('/detail-shift', [DashboardController::class, 'detailShift'])->name('detail-shift');
Route::get('/pengaturan', [DashboardController::class, 'pengaturan'])->name('pengaturan');
Route::get('/kwh-control', [DashboardController::class, 'kwhControl'])->name('kwh-control');
Route::get('/ai-vision', [DashboardController::class, 'aiVision'])->name('ai-vision');

// API endpoint untuk saling berbagi IP dengan master-painting
use Illuminate\Http\Request;
Route::match(['get', 'post', 'options'], '/api/set-cam-ip', function (Request $request) {
    if ($request->has('ip')) {
        file_put_contents(storage_path('app/cam_ip.txt'), $request->ip);
    }
    return response()->json(['status' => 'ok'])
        ->header('Access-Control-Allow-Origin', '*')
        ->header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
        ->header('Access-Control-Allow-Headers', '*');
});

Route::match(['get', 'options'], '/api/get-cam-ip', function () {
    $ip = @file_get_contents(storage_path('app/cam_ip.txt'));
    return response()->json(['ip' => trim($ip ?: '')])
        ->header('Access-Control-Allow-Origin', '*')
        ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
        ->header('Access-Control-Allow-Headers', '*');
});

// Kompatibilitas untuk request dengan /index.php?line=...&view=...
Route::get('/index.php', [DashboardController::class, 'index']);
