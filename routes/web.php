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

// ===== API INTEGRASI RS-485 PHOTOSENSOR & ROBOFLOW HANGER =====

/**
 * 1. Menerima sinyal trigger dari ESP32 (via RS-485 to TTL)
 * Contoh pemanggilan dari ESP32:
 * GET /api/sensor-trigger?type=input
 * GET /api/sensor-trigger?type=output
 */
Route::match(['get', 'post', 'options'], '/api/sensor-trigger', function (Request $request) {
    $type = $request->input('type', $request->query('type', 'output'));
    $triggerId = uniqid('trig_', true);
    $timestamp = microtime(true);

    // Muat atau inisialisasi statistik hanger
    $statsFile = storage_path('app/hanger_stats.json');
    $stats = [
        'total_input_hangers' => 0,
        'total_output_hangers' => 0,
        'wip_hangers' => 0,
        'total_output_pcs' => 0,
        'empty_hangers' => 0,
        'last_updated' => now()->toIso8601String(),
    ];
    if (file_exists($statsFile)) {
        $loaded = @json_decode(file_get_contents($statsFile), true);
        if (is_array($loaded)) {
            $stats = array_merge($stats, $loaded);
        }
    }

    if ($type === 'input') {
        $stats['total_input_hangers']++;
        $stats['wip_hangers']++;
        $stats['last_updated'] = now()->toIso8601String();
        file_put_contents($statsFile, json_encode($stats, JSON_PRETTY_PRINT));
    }

    // Simpan event trigger terakhir untuk dibaca listener UI
    $triggerData = [
        'trigger_id' => $triggerId,
        'type' => $type,
        'timestamp' => $timestamp,
        'created_at' => now()->toIso8601String(),
    ];
    file_put_contents(storage_path('app/latest_sensor_trigger.json'), json_encode($triggerData));

    return response()->json([
        'status' => 'ok',
        'message' => 'Trigger ' . $type . ' berhasil dicatat',
        'data' => $triggerData,
        'stats' => $stats,
    ])
    ->header('Access-Control-Allow-Origin', '*')
    ->header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
    ->header('Access-Control-Allow-Headers', '*');
});

/**
 * 2. Mengambil event trigger sensor terbaru untuk listener frontend
 */
Route::match(['get', 'options'], '/api/get-sensor-trigger', function () {
    $triggerFile = storage_path('app/latest_sensor_trigger.json');
    $data = file_exists($triggerFile) ? @json_decode(file_get_contents($triggerFile), true) : null;

    return response()->json([
        'status' => 'ok',
        'trigger' => $data ?: ['trigger_id' => null, 'type' => null, 'timestamp' => 0]
    ])
    ->header('Access-Control-Allow-Origin', '*')
    ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
    ->header('Access-Control-Allow-Headers', '*');
});

/**
 * 3. Menyimpan konfirmasi hasil deteksi Roboflow dari kamera output
 */
Route::match(['post', 'get', 'options'], '/api/confirm-hanger-output', function (Request $request) {
    $pcsCount = max(0, (int) $request->input('pcs_count', $request->query('pcs_count', 0)));
    
    $statsFile = storage_path('app/hanger_stats.json');
    $stats = [
        'total_input_hangers' => 0,
        'total_output_hangers' => 0,
        'wip_hangers' => 0,
        'total_output_pcs' => 0,
        'empty_hangers' => 0,
        'last_updated' => now()->toIso8601String(),
    ];
    if (file_exists($statsFile)) {
        $loaded = @json_decode(file_get_contents($statsFile), true);
        if (is_array($loaded)) {
            $stats = array_merge($stats, $loaded);
        }
    }

    $stats['total_output_hangers']++;
    $stats['total_output_pcs'] += $pcsCount;
    if ($pcsCount === 0) {
        $stats['empty_hangers']++;
    }
    if ($stats['wip_hangers'] > 0) {
        $stats['wip_hangers']--;
    }
    $stats['last_updated'] = now()->toIso8601String();

    file_put_contents($statsFile, json_encode($stats, JSON_PRETTY_PRINT));

    return response()->json([
        'status' => 'ok',
        'message' => 'Hanger output berhasil dikonfirmasi',
        'confirmed_pcs' => $pcsCount,
        'stats' => $stats
    ])
    ->header('Access-Control-Allow-Origin', '*')
    ->header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
    ->header('Access-Control-Allow-Headers', '*');
});

/**
 * 4. Mendapatkan data statistik hanger & output terkini
 */
Route::match(['get', 'options'], '/api/get-hanger-stats', function () {
    $statsFile = storage_path('app/hanger_stats.json');
    $stats = [
        'total_input_hangers' => 0,
        'total_output_hangers' => 0,
        'wip_hangers' => 0,
        'total_output_pcs' => 0,
        'empty_hangers' => 0,
        'last_updated' => now()->toIso8601String(),
    ];
    if (file_exists($statsFile)) {
        $loaded = @json_decode(file_get_contents($statsFile), true);
        if (is_array($loaded)) {
            $stats = array_merge($stats, $loaded);
        }
    }

    return response()->json([
        'status' => 'ok',
        'stats' => $stats
    ])
    ->header('Access-Control-Allow-Origin', '*')
    ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
    ->header('Access-Control-Allow-Headers', '*');
});

/**
 * 5. Reset statistik sesi hanger
 */
Route::match(['post', 'get', 'options'], '/api/reset-hanger-stats', function () {
    $stats = [
        'total_input_hangers' => 0,
        'total_output_hangers' => 0,
        'wip_hangers' => 0,
        'total_output_pcs' => 0,
        'empty_hangers' => 0,
        'last_updated' => now()->toIso8601String(),
    ];
    file_put_contents(storage_path('app/hanger_stats.json'), json_encode($stats, JSON_PRETTY_PRINT));

    return response()->json([
        'status' => 'ok',
        'message' => 'Statistik hanger berhasil direset',
        'stats' => $stats
    ])
    ->header('Access-Control-Allow-Origin', '*')
    ->header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
    ->header('Access-Control-Allow-Headers', '*');
});

/**
 * 6. Ambil Target Bulanan (PCS) & Output Aktual
 */
Route::match(['get', 'options'], '/api/get-target-bulanan', function (Request $request) {
    $month = $request->query('month', date('Y-m'));
    $targetFile = storage_path('app/monthly_targets.json');
    $targets = file_exists($targetFile) ? (@json_decode(file_get_contents($targetFile), true) ?: []) : [];
    $targetPcs = isset($targets[$month]) ? (int) $targets[$month] : 50000;

    return response()->json([
        'status' => 'ok',
        'month' => $month,
        'target_pcs' => $targetPcs
    ])
    ->header('Access-Control-Allow-Origin', '*')
    ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
    ->header('Access-Control-Allow-Headers', '*');
});

/**
 * 7. Simpan Target Bulanan (PCS)
 */
Route::match(['post', 'get', 'options'], '/api/set-target-bulanan', function (Request $request) {
    $month = $request->input('month', $request->query('month', date('Y-m')));
    $targetPcs = max(1, (int) $request->input('target_pcs', $request->query('target_pcs', 50000)));

    $targetFile = storage_path('app/monthly_targets.json');
    $targets = file_exists($targetFile) ? (@json_decode(file_get_contents($targetFile), true) ?: []) : [];
    $targets[$month] = $targetPcs;

    file_put_contents($targetFile, json_encode($targets, JSON_PRETTY_PRINT));

    return response()->json([
        'status' => 'ok',
        'message' => 'Target bulanan berhasil disimpan',
        'month' => $month,
        'target_pcs' => $targetPcs
    ])
    ->header('Access-Control-Allow-Origin', '*')
    ->header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
    ->header('Access-Control-Allow-Headers', '*');
});

// Kompatibilitas untuk request dengan /index.php?line=...&view=...
Route::get('/index.php', [DashboardController::class, 'index']);
