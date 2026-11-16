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

// API endpoint untuk menyimpan & mengambil IP ESP32 DevKit (RS-485 + Flash)
Route::match(['get', 'post', 'options'], '/api/set-devkit-ip', function (Request $request) {
    if ($request->has('ip')) {
        file_put_contents(storage_path('app/devkit_ip.txt'), trim($request->ip));
    }
    return response()->json(['status' => 'ok'])
        ->header('Access-Control-Allow-Origin', '*')
        ->header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
        ->header('Access-Control-Allow-Headers', '*');
});

Route::match(['get', 'options'], '/api/get-devkit-ip', function () {
    $ip = @file_get_contents(storage_path('app/devkit_ip.txt'));
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
    $val = (int) $request->input('val', $request->query('val', 1));
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
        'is_produksi' => 0,
        'last_updated' => now()->toIso8601String(),
    ];
    if (file_exists($statsFile)) {
        $loaded = @json_decode(file_get_contents($statsFile), true);
        if (is_array($loaded)) {
            $stats = array_merge($stats, $loaded);
        }
    }

    if ($type === 'btn_on') {
        $stats['is_produksi'] = 1;
        $stats['last_updated'] = now()->toIso8601String();
        file_put_contents($statsFile, json_encode($stats, JSON_PRETTY_PRINT), LOCK_EX);
    } elseif ($type === 'btn_off') {
        $stats['is_produksi'] = 0;
        $stats['last_updated'] = now()->toIso8601String();
        file_put_contents($statsFile, json_encode($stats, JSON_PRETTY_PRINT), LOCK_EX);
    } elseif ($type === 'input') {
        $stats['total_input_hangers']++;
        $stats['wip_hangers']++;
        $stats['last_updated'] = now()->toIso8601String();
        file_put_contents($statsFile, json_encode($stats, JSON_PRETTY_PRINT), LOCK_EX);
    }

    // Simpan event trigger terakhir untuk dibaca listener UI
    $triggerData = [
        'trigger_id' => $triggerId,
        'type' => $type,
        'val' => $val,
        'timestamp' => $timestamp,
        'created_at' => now()->toIso8601String(),
    ];
    file_put_contents(storage_path('app/latest_sensor_trigger.json'), json_encode($triggerData), LOCK_EX);

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

    file_put_contents($statsFile, json_encode($stats, JSON_PRETTY_PRINT), LOCK_EX);

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

/**
 * 7b. Status Mesin Realtime untuk Tower Light Dashboard (2 Warna: Merah & Hijau)
 */
Route::match(['get', 'options'], '/api/get_status.php', function (Request $request) {
    $statsFile = storage_path('app/hanger_stats.json');
    $stats = file_exists($statsFile) ? (@json_decode(file_get_contents($statsFile), true) ?: []) : [];
    $isProduksi = !empty($stats['is_produksi']);

    return response()->json([
        'status' => 'success',
        'is_produksi' => $isProduksi ? 1 : 0,
        'status_mesin' => $isProduksi ? 'running' : 'stop',
        'warna_patlite' => $isProduksi ? 'Hijau' : 'Merah',
        'updated_at' => $stats['last_updated'] ?? now()->toIso8601String(),
    ])
    ->header('Access-Control-Allow-Origin', '*')
    ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
    ->header('Access-Control-Allow-Headers', '*');
});

Route::match(['get', 'options'], '/api/get-status', function (Request $request) {
    return redirect('/api/get_status.php');
});

// ===== API KATALOG PART & SESI CHANGEOVER =====

function getPartsCatalog() {
    $file = storage_path('app/parts_catalog.json');
    if (!file_exists($file)) {
        file_put_contents($file, json_encode([], JSON_PRETTY_PRINT));
        return [];
    }
    return @json_decode(file_get_contents($file), true) ?: [];
}

function getActivePart() {
    $catalog = getPartsCatalog();
    $activeFile = storage_path('app/active_part.json');
    $activeId = file_exists($activeFile) ? trim(@file_get_contents($activeFile)) : '';

    foreach ($catalog as $p) {
        if ($p['id'] === $activeId) return $p;
    }
    // Default ke part pertama jika ada
    if (!empty($catalog)) {
        file_put_contents($activeFile, $catalog[0]['id']);
        return $catalog[0];
    }
    return null;
}

/**
 * 8. Dapatkan daftar seluruh Part & Part yang sedang aktif
 */
Route::match(['get', 'options'], '/api/get-parts', function () {
    $catalog = getPartsCatalog();
    $active = getActivePart();
    $historyFile = storage_path('app/part_session_history.json');
    $history = file_exists($historyFile) ? (@json_decode(file_get_contents($historyFile), true) ?: []) : [];

    return response()->json([
        'status' => 'ok',
        'parts' => $catalog,
        'active_part' => $active,
        'recent_sessions' => array_slice(array_reverse($history), 0, 5),
    ])
    ->header('Access-Control-Allow-Origin', '*')
    ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
    ->header('Access-Control-Allow-Headers', '*');
});

/**
 * 9. Konfirmasi Changeover (Ganti Part Produksi)
 */
Route::match(['post', 'options'], '/api/change-part', function (Request $request) {
    $newPartId = $request->input('part_id');
    $catalog = getPartsCatalog();

    $newPart = null;
    foreach ($catalog as $p) {
        if ($p['id'] === $newPartId) {
            $newPart = $p;
            break;
        }
    }

    if (!$newPart) {
        return response()->json(['status' => 'error', 'message' => 'Part tidak ditemukan'], 404);
    }

    $currentPart = getActivePart();

    // 1. Catat sesi part sebelumnya ke histori rekapitulasi
    $statsFile = storage_path('app/hanger_stats.json');
    $stats = file_exists($statsFile) ? (@json_decode(file_get_contents($statsFile), true) ?: []) : [];
    
    if ($currentPart) {
        $historyFile = storage_path('app/part_session_history.json');
        $history = file_exists($historyFile) ? (@json_decode(file_get_contents($historyFile), true) ?: []) : [];
        $history[] = [
            'part_id' => $currentPart['id'],
            'part_code' => $currentPart['code'] ?? '',
            'part_name' => $currentPart['name'],
            'model_id' => $currentPart['model_id'],
            'model_version' => $currentPart['model_version'],
            'total_input_hangers' => $stats['total_input_hangers'] ?? 0,
            'total_output_hangers' => $stats['total_output_hangers'] ?? 0,
            'total_output_pcs' => $stats['total_output_pcs'] ?? 0,
            'ended_at' => now()->toIso8601String(),
        ];
        file_put_contents($historyFile, json_encode($history, JSON_PRETTY_PRINT));
    }

    // 2. Reset counter batch untuk part baru
    $stats['total_input_hangers'] = 0;
    $stats['total_output_hangers'] = 0;
    $stats['wip_hangers'] = 0;
    $stats['total_output_pcs'] = 0;
    $stats['empty_hangers'] = 0;
    $stats['last_updated'] = now()->toIso8601String();
    file_put_contents($statsFile, json_encode($stats, JSON_PRETTY_PRINT));

    // 3. Simpan part aktif baru
    file_put_contents(storage_path('app/active_part.json'), $newPart['id']);

    return response()->json([
        'status' => 'ok',
        'message' => "Berhasil berganti ke part: {$newPart['name']}",
        'active_part' => $newPart,
        'stats' => $stats,
    ])
    ->header('Access-Control-Allow-Origin', '*')
    ->header('Access-Control-Allow-Methods', 'POST, OPTIONS')
    ->header('Access-Control-Allow-Headers', '*');
});

/**
 * 10. Tambah / Edit Part di Katalog
 */
Route::match(['post', 'options'], '/api/save-part', function (Request $request) {
    $catalog = getPartsCatalog();
    $id = $request->input('id');
    $name = trim($request->input('name', ''));
    $code = trim($request->input('code', ''));
    $modelId = trim($request->input('model_id', ''));
    $version = max(1, (int) $request->input('model_version', 1));
    $apiKey = trim($request->input('publishable_key', $request->input('api_key', '')));
    $target = max(1, (int) $request->input('target_per_hanger', 4));

    if (!$name || !$modelId) {
        return response()->json(['status' => 'error', 'message' => 'Nama Part & Model ID wajib diisi'], 400);
    }

    $isEdit = false;
    $targetId = $id;
    if ($id) {
        foreach ($catalog as &$p) {
            if ($p['id'] === $id) {
                $p['name'] = $name;
                $p['code'] = $code ?: $p['code'];
                $p['model_id'] = $modelId;
                $p['model_version'] = $version;
                if ($apiKey) $p['api_key'] = $apiKey;
                $p['publishable_key'] = $apiKey ?: ($p['publishable_key'] ?? $p['api_key'] ?? '');
                $p['target_per_hanger'] = $target;
                $isEdit = true;
                break;
            }
        }
    }

    if (!$isEdit) {
        $newId = 'part_' . preg_replace('/[^a-z0-9_]/', '_', strtolower($code ?: $name)) . '_' . substr(uniqid(), -4);
        $targetId = $newId;
        $catalog[] = [
            'id' => $newId,
            'code' => $code ?: ('P-' . sprintf('%02d', count($catalog) + 1)),
            'name' => $name,
            'model_id' => $modelId,
            'model_version' => $version,
            'api_key' => $apiKey ?: 'rf_xxxxxxxxxxxx',
            'publishable_key' => $apiKey ?: 'rf_xxxxxxxxxxxx',
            'target_per_hanger' => $target,
        ];
    }

    file_put_contents(storage_path('app/parts_catalog.json'), json_encode($catalog, JSON_PRETTY_PRINT));

    // Jika belum ada part aktif atau hanya ada 1 part ini, set sebagai part aktif
    $activeFile = storage_path('app/active_part.json');
    if (!file_exists($activeFile) || empty(trim(@file_get_contents($activeFile))) || count($catalog) === 1) {
        file_put_contents($activeFile, $targetId);
    }

    return response()->json([
        'status' => 'ok',
        'message' => 'Part berhasil disimpan ke katalog',
        'parts' => $catalog,
        'active_part' => getActivePart(),
    ])
    ->header('Access-Control-Allow-Origin', '*')
    ->header('Access-Control-Allow-Methods', 'POST, OPTIONS')
    ->header('Access-Control-Allow-Headers', '*');
});

/**
 * 11. Hapus Part dari Katalog
 */
Route::match(['post', 'options'], '/api/delete-part', function (Request $request) {
    $id = $request->input('id');
    $catalog = getPartsCatalog();
    $active = getActivePart();

    if ($active && $active['id'] === $id) {
        return response()->json(['status' => 'error', 'message' => 'Tidak bisa menghapus part yang sedang aktif diproduksi'], 400);
    }

    $filtered = array_values(array_filter($catalog, function ($p) use ($id) {
        return $p['id'] !== $id;
    }));

    file_put_contents(storage_path('app/parts_catalog.json'), json_encode($filtered, JSON_PRETTY_PRINT));

    return response()->json([
        'status' => 'ok',
        'message' => 'Part berhasil dihapus dari katalog',
        'parts' => $filtered,
    ])
    ->header('Access-Control-Allow-Origin', '*')
    ->header('Access-Control-Allow-Methods', 'POST, OPTIONS')
    ->header('Access-Control-Allow-Headers', '*');
});

/**
 * 12. Update Nomor Versi Model Part (Setelah Retraining di Roboflow)
 */
Route::match(['post', 'options'], '/api/update-part-model-version', function (Request $request) {
    $partId = $request->input('part_id');
    $newVersion = max(1, (int) $request->input('model_version', 1));
    $catalog = getPartsCatalog();

    $updated = null;
    foreach ($catalog as &$p) {
        if ($p['id'] === $partId) {
            $p['model_version'] = $newVersion;
            $updated = $p;
            break;
        }
    }

    if (!$updated) {
        return response()->json(['status' => 'error', 'message' => 'Part tidak ditemukan'], 404);
    }

    file_put_contents(storage_path('app/parts_catalog.json'), json_encode($catalog, JSON_PRETTY_PRINT));

    return response()->json([
        'status' => 'ok',
        'message' => "Versi model part {$updated['name']} diperbarui ke v{$newVersion}",
        'active_part' => getActivePart(),
    ])
    ->header('Access-Control-Allow-Origin', '*')
    ->header('Access-Control-Allow-Methods', 'POST, OPTIONS')
    ->header('Access-Control-Allow-Headers', '*');
});

// ===== API KOLEKTOR DATASET ROBOFLOW (TERORGANISASI PER PART) =====

/**
 * 13. Simpan 1 frame foto sampel Roboflow (Tersimpan rapi per Part)
 */
Route::match(['post', 'options'], '/api/save-roboflow-sample', function (Request $request) {
    $activePart = getActivePart();
    $partFolder = $activePart ? $activePart['id'] : 'default_part';

    $dir = storage_path("app/roboflow_samples/{$partFolder}");
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }

    $existingFiles = glob($dir . '/*.jpg') ?: [];
    $count = count($existingFiles);

    if ($count >= 100) {
        return response()->json([
            'status' => 'full',
            'message' => 'Koleksi part ini sudah mencapai batas maksimal 100 sampel',
            'count' => $count,
            'max' => 100,
            'part' => $activePart,
        ])
        ->header('Access-Control-Allow-Origin', '*')
        ->header('Access-Control-Allow-Methods', 'POST, OPTIONS')
        ->header('Access-Control-Allow-Headers', '*');
    }

    $imgData = $request->input('image');
    if (!$imgData) {
        return response()->json(['status' => 'error', 'message' => 'Data gambar kosong'], 400);
    }

    if (preg_match('/^data:image\/(\w+);base64,/', $imgData)) {
        $imgData = substr($imgData, strpos($imgData, ',') + 1);
    }
    $decoded = base64_decode($imgData);
    if (!$decoded) {
        return response()->json(['status' => 'error', 'message' => 'Gagal decode base64 gambar'], 400);
    }

    $newIndex = $count + 1;
    $partCode = $activePart ? ($activePart['code'] ?? 'P00') : 'P00';
    $filename = sprintf('%s_sample_%03d.jpg', $partCode, $newIndex);
    file_put_contents($dir . '/' . $filename, $decoded);

    return response()->json([
        'status' => 'ok',
        'count' => $newIndex,
        'filename' => $filename,
        'max' => 100,
        'part' => $activePart,
        'message' => "Sampel #{$newIndex} ({$activePart['name']}) berhasil disimpan"
    ])
    ->header('Access-Control-Allow-Origin', '*')
    ->header('Access-Control-Allow-Methods', 'POST, OPTIONS')
    ->header('Access-Control-Allow-Headers', '*');
});

/**
 * 14. Dapatkan status & jumlah sampel terkumpul untuk Part Aktif
 */
Route::match(['get', 'options'], '/api/get-roboflow-samples', function (Request $request) {
    $activePart = getActivePart();
    $partId = $request->query('part_id', $activePart ? $activePart['id'] : 'default_part');

    $dir = storage_path("app/roboflow_samples/{$partId}");
    $files = is_dir($dir) ? (glob($dir . '/*.jpg') ?: []) : [];
    $count = count($files);

    $recent = [];
    if ($count > 0) {
        $slice = array_slice($files, -8);
        foreach ($slice as $f) {
            $recent[] = [
                'name' => basename($f),
                'size' => filesize($f),
                'time' => filemtime($f),
            ];
        }
    }

    return response()->json([
        'status' => 'ok',
        'count' => $count,
        'max' => 100,
        'part' => $activePart,
        'recent' => $recent,
    ])
    ->header('Access-Control-Allow-Origin', '*')
    ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
    ->header('Access-Control-Allow-Headers', '*');
});

/**
 * 15. Download dataset sampel Part dalam format ZIP siap train/retrain di Roboflow
 */
Route::get('/api/download-roboflow-zip', function (Request $request) {
    $activePart = getActivePart();
    $partId = $request->query('part_id', $activePart ? $activePart['id'] : 'default_part');
    $partName = $activePart ? $activePart['name'] : 'Part';
    $partCode = $activePart ? ($activePart['code'] ?? 'P00') : 'P00';

    $dir = storage_path("app/roboflow_samples/{$partId}");
    if (!is_dir($dir)) {
        return response('Belum ada folder sampel untuk part ini', 404);
    }
    $files = glob($dir . '/*.jpg') ?: [];
    if (empty($files)) {
        return response('Belum ada gambar sampel yang terkumpul untuk part ini', 404);
    }

    $cleanPartSlug = preg_replace('/[^a-zA-Z0-9_-]/', '_', $partName);
    $zipFilename = sprintf('dataset_retrain_%s_%s_%s.zip', $partCode, $cleanPartSlug, date('Ymd_His'));
    $zipPath = storage_path("app/{$zipFilename}");

    $zip = new \ZipArchive();
    if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true) {
        foreach ($files as $file) {
            $zip->addFile($file, basename($file));
        }
        $readme = "=== DATASET ROBOFLOW TRAINING / RETRAINING ===\r\n";
        $readme .= "Part: {$partName} ({$partCode})\r\n";
        $readme .= "Project Roboflow: " . ($activePart['model_id'] ?? '-') . " (Versi saat ini: v" . ($activePart['model_version'] ?? '1') . ")\r\n";
        $readme .= "Total Sampel: " . count($files) . " foto\r\n";
        $readme .= "Tanggal Pengambilan: " . now()->toDateTimeString() . "\r\n\r\n";
        $readme .= "Langkah Retraining di Roboflow:\r\n";
        $readme .= "1. Buka project Roboflow kamu di https://roboflow.com\r\n";
        $readme .= "2. Buka tab 'Upload Images' lalu drag-and-drop seluruh file foto ini\r\n";
        $readme .= "3. Gunakan 'Label Assist' (Auto-Label) untuk mengotaki part secara cepat\r\n";
        $readme .= "4. Klik 'Generate Version' dan latih model baru (misal jadi versi baru)\r\n";
        $readme .= "5. Di Dashboard Painting, klik 'Update Versi Model' agar versi terbaru langsung aktif!\r\n";
        $zip->addFromString('README_ROBOFLOW.txt', $readme);
        $zip->close();
    }

    return response()->download($zipPath, $zipFilename, [
        'Content-Type' => 'application/zip',
    ]);
});

/**
 * 16. Reset / Hapus seluruh koleksi sampel untuk Part tertentu
 */
Route::match(['post', 'get', 'options'], '/api/reset-roboflow-samples', function (Request $request) {
    $activePart = getActivePart();
    $partId = $request->input('part_id', $request->query('part_id', $activePart ? $activePart['id'] : 'default_part'));

    $dir = storage_path("app/roboflow_samples/{$partId}");
    if (is_dir($dir)) {
        $files = glob($dir . '/*') ?: [];
        foreach ($files as $f) {
            if (is_file($f)) @unlink($f);
        }
    }

    return response()->json([
        'status' => 'ok',
        'count' => 0,
        'message' => 'Sampel foto part ini berhasil direset'
    ])
    ->header('Access-Control-Allow-Origin', '*')
    ->header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
    ->header('Access-Control-Allow-Headers', '*');
});

// =========================================================================
// API MAN-TIME PAINTING, SUT PART, DAN EKSPOR EXCEL SESUAI FILE ASLI PABRIK
// =========================================================================

function getProductionHistoryList() {
    $file = storage_path('app/production_history.json');
    if (!file_exists($file)) return [];
    return @json_decode(file_get_contents($file), true) ?: [];
}

function saveProductionHistoryList(array $history) {
    file_put_contents(storage_path('app/production_history.json'), json_encode($history, JSON_PRETTY_PRINT));
}

/**
 * 17. API Riwayat Performance (Tab Riwayat & Machine Control)
 */
Route::match(['get', 'options'], '/api/riwayat_performance.php', function (Request $request) {
    $history = getProductionHistoryList();
    $tgl = $request->query('tanggal');
    
    if ($tgl) {
        $history = array_values(array_filter($history, function ($item) use ($tgl) {
            return ($item['tanggal'] ?? '') === $tgl;
        }));
    }
    
    $sumEff = 0;
    $count = count($history);
    foreach ($history as $h) {
        $sumEff += (float)($h['eff'] ?? 95.0);
    }
    $avgEff = $count > 0 ? round($sumEff / $count, 1) : 0;

    $formatted = array_map(function ($h) {
        $f = (int)($h['nilai_f'] ?? 0);
        $e = (int)($h['nilai_e'] ?? 0);
        $eff = (float)($h['eff'] ?? 95.0);
        return [
            'id' => (int)($h['id'] ?? 1),
            'line_name' => $h['line_name'] ?? 'Painting',
            'part' => $h['part_code'] ?? $h['part'] ?? 'Part',
            'nomor_sesi' => (int)($h['nomor_sesi'] ?? 1),
            'tanggal' => $h['tanggal'] ?? date('Y-m-d'),
            'jam_mulai' => $h['jam_mulai'] ?? '06:55',
            'jam_selesai' => $h['jam_selesai'] ?? '15:00',
            'B' => (int)($h['nilai_b'] ?? 485),
            'nilai_b' => (int)($h['nilai_b'] ?? 485),
            'C' => (float)($h['nilai_c'] ?? 1.0),
            'C_v2' => (float)($h['nilai_c'] ?? 1.0),
            'nilai_c' => (float)($h['nilai_c'] ?? 1.0),
            'F' => $f,
            'nilai_f' => $f,
            'E' => $e,
            'nilai_e' => $e,
            'nilai_e_v2' => $e,
            'E_persen' => ($f + $e) > 0 ? round(($e / ($f + $e)) * 100, 1) : 0.0,
            'D' => (float)($h['nilai_d'] ?? ($f > 0 ? round(485 / $f, 2) : 0)),
            'nilai_d' => (float)($h['nilai_d'] ?? 0),
            'A' => (int)($h['nilai_a'] ?? 120),
            'nilai_a' => (int)($h['nilai_a'] ?? 120),
            'nilai_a_v2' => (int)($h['nilai_a'] ?? 120),
            'I' => (int)($h['nilai_i'] ?? 0),
            'nilai_i' => (int)($h['nilai_i'] ?? 0),
            'P' => (int)($h['nilai_p'] ?? 120),
            'nilai_p' => (int)($h['nilai_p'] ?? 120),
            'v2_tersedia' => true,
            'H_v2_persen' => 100.0,
            'J_v2' => round($eff / 100, 3),
            'K_v2' => 1.0,
            'L_v2_persen' => $eff,
            'EFF_v2_persen' => $eff,
            'L_persen' => $eff,
            'eff' => $eff,
            'productivity_head_hour' => (float)($h['productivity_head_hour'] ?? 13.2),
            'disimpan_final' => 1,
            'dibuat_pada' => $h['dibuat_pada'] ?? date('Y-m-d H:i:s')
        ];
    }, $history);

    return response()->json([
        'tanggal' => $tgl ?: date('Y-m-d'),
        'jumlah' => $count,
        'rata_eff_v2_persen' => $avgEff,
        'riwayat' => $formatted
    ])
    ->header('Access-Control-Allow-Origin', '*')
    ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
    ->header('Access-Control-Allow-Headers', '*');
});

/**
 * 18. API Histori Data Produksi (Tabel Tab Riwayat)
 */
Route::match(['get', 'options'], '/api/histori.php', function (Request $request) {
    $history = getProductionHistoryList();
    $byDate = [];
    foreach ($history as $h) {
        $tgl = $h['tanggal'] ?? date('Y-m-d');
        if (!isset($byDate[$tgl])) {
            $byDate[$tgl] = [
                'tanggal' => $tgl,
                'input' => 0,
                'output' => 0,
                'eff_sum' => 0,
                'count' => 0
            ];
        }
        $out = (int)($h['nilai_f'] ?? 0);
        $byDate[$tgl]['input'] += $out;
        $byDate[$tgl]['output'] += $out;
        $byDate[$tgl]['eff_sum'] += (float)($h['eff'] ?? 95);
        $byDate[$tgl]['count']++;
    }

    $result = [];
    foreach ($byDate as $tgl => $d) {
        $avgEff = $d['count'] > 0 ? round($d['eff_sum'] / $d['count'], 1) : 0;
        $result[] = [
            'tanggal' => $tgl,
            'input' => $d['input'],
            'output' => $d['output'],
            'eff' => $avgEff . '%',
            'yield' => '100.0%'
        ];
    }

    usort($result, function($a, $b) {
        return strcmp($b['tanggal'], $a['tanggal']);
    });

    return response()->json($result)
        ->header('Access-Control-Allow-Origin', '*')
        ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
        ->header('Access-Control-Allow-Headers', '*');
});

/**
 * 19. Simpan Produksi Final & Kalkulasi dari Machine Control
 */
Route::match(['get', 'post', 'options'], '/api/kalkulasi_performance.php', function (Request $request) {
    $activePart = getActivePart();
    $statsFile = storage_path('app/hanger_stats.json');
    $stats = file_exists($statsFile) ? (@json_decode(file_get_contents($statsFile), true) ?: []) : [];

    $partCode = $request->input('part', $request->query('part', $activePart ? $activePart['code'] : 'M2SPCC0001IP'));
    $cVal = (float)$request->input('ct', $request->query('ct', $activePart ? ($activePart['sut'] ?? 1.0) : 1.0));
    $fVal = (int)$request->input('f_manual', $request->query('f_manual', $stats['total_output_pcs'] ?? 0));
    $eVal = (int)$request->input('e_manual', $request->query('e_manual', 0));
    $jamMulai = $request->input('jam_mulai', $request->query('jam_mulai', '06:55'));
    $jamSelesai = $request->input('jam_selesai', $request->query('jam_selesai', date('H:i')));
    $tanggal = $request->input('tanggal', $request->query('tanggal', date('Y-m-d')));
    $isFinal = (bool)$request->input('final', $request->query('final', 0));

    // Durasi kerja (B) dalam menit
    $bMenit = (date('w', strtotime($tanggal)) == 5) ? 440 : 485;
    $stdTime = round($cVal * max(1, ($fVal + $eVal)), 2);
    $lossRegulated = 120; // Meeting 30 + Break 60 + 5S 30
    $netTime = max(1, $bMenit - $lossRegulated);
    $eff = min(100.0, round(($stdTime / $netTime) * 100, 1));
    if ($eff <= 0 && $fVal > 0) $eff = 95.0;
    $actualCt = $fVal > 0 ? round($bMenit / $fVal, 2) : 0;
    $productivity = round((($fVal + $eVal) / $netTime) * 60, 2);

    $record = [
        'id' => time(),
        'tanggal' => $tanggal,
        'shift' => (date('w', strtotime($tanggal)) == 5) ? 'Shift 1 (Jumat)' : 'Shift 1',
        'nomor_sesi' => count(getProductionHistoryList()) + 1,
        'part_code' => $partCode,
        'part_name' => "Part {$partCode}",
        'nilai_c' => $cVal,
        'jam_mulai' => $jamMulai,
        'jam_selesai' => $jamSelesai,
        'nilai_b' => $bMenit,
        'total_working_time' => $bMenit * 6,
        'total_loss_time' => $lossRegulated,
        'net_working_time' => $netTime * 6,
        'nilai_f' => $fVal,
        'nilai_e' => $eVal,
        'nilai_d' => $actualCt,
        'nilai_a' => $lossRegulated,
        'nilai_i' => 0,
        'nilai_p' => $lossRegulated,
        'std_process_time' => $stdTime,
        'nilai_h' => 1.0,
        'nilai_j' => round($eff / 100, 3),
        'nilai_k' => 1.0,
        'nilai_l' => round($eff / 100, 3),
        'eff' => $eff,
        'productivity_head_hour' => $productivity,
        'status' => 'Final',
        'dibuat_pada' => date('Y-m-d H:i:s')
    ];

    if ($isFinal) {
        $history = getProductionHistoryList();
        array_unshift($history, $record);
        saveProductionHistoryList($history);
    }

    return response()->json([
        'status' => 'ok',
        'B' => $bMenit,
        'C_v2' => $cVal,
        'F_v2' => $fVal,
        'E_v2' => $eVal,
        'D' => $actualCt,
        'A_v2' => $lossRegulated,
        'I' => 0,
        'P' => $lossRegulated,
        'H_v2' => 1.0,
        'J_v2' => round($eff / 100, 3),
        'K_v2' => 1.0,
        'L_v2' => round($eff / 100, 3),
        'EFF' => $eff . '%',
        'productivity_head_hour' => $productivity,
        'record' => $record
    ])
    ->header('Access-Control-Allow-Origin', '*')
    ->header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
    ->header('Access-Control-Allow-Headers', '*');
});

/**
 * 20. Live Machine Control Performance API
 */
Route::match(['get', 'options'], '/api/kalkulasi_performance_live.php', function (Request $request) {
    $activePart = getActivePart();
    $statsFile = storage_path('app/hanger_stats.json');
    $stats = file_exists($statsFile) ? (@json_decode(file_get_contents($statsFile), true) ?: []) : [];

    $cVal = (float)($activePart['sut'] ?? 1.0);
    $fVal = (int)($stats['total_output_pcs'] ?? 0);
    $bMenit = (date('w') == 5) ? 440 : 485;
    $stdTime = round($cVal * max(1, $fVal), 2);
    $lossRegulated = 120;
    $netTime = max(1, $bMenit - $lossRegulated);
    $eff = $fVal > 0 ? min(100.0, round(($stdTime / $netTime) * 100, 1)) : 95.0;
    $actualCt = $fVal > 0 ? round($bMenit / $fVal, 2) : 0;

    return response()->json([
        'B' => $bMenit,
        'C_v2' => $cVal,
        'F_v2' => $fVal,
        'E_v2' => 0,
        'D' => $actualCt,
        'A_v2' => $lossRegulated,
        'I' => 0,
        'P' => $lossRegulated,
        'H_v2' => 1.0,
        'J_v2' => round($eff / 100, 3),
        'K_v2' => 1.0,
        'L_v2' => round($eff / 100, 3),
        'EFF' => $eff . '%',
        'part' => $activePart ? $activePart['code'] : 'M2SPCC0001IP'
    ])
    ->header('Access-Control-Allow-Origin', '*')
    ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
    ->header('Access-Control-Allow-Headers', '*');
});

/**
 * 21. Unduh Excel Sesuai File Master Template (08_MAN TIME_ PAINTING_AUG_26.xlsx)
 */
$exportHandler = function (Request $request) {
    $templatePath = storage_path('app/templates/MAN_TIME_PAINTING_TEMPLATE.xlsx');
    if (!file_exists($templatePath)) {
        $templatePath = base_path('excel_data.xlsx');
    }

    if (!file_exists($templatePath)) {
        return response('File template Excel tidak ditemukan.', 404);
    }

    $tgl = $request->query('tanggal', date('Y-m-d'));
    $id = $request->query('id');
    $downloadFilename = "08_MAN TIME_ PAINTING_AUG_26.xlsx";
    if ($id) {
        $downloadFilename = "MAN_TIME_PAINTING_SESI_{$id}.xlsx";
    } elseif ($tgl) {
        $downloadFilename = "MAN_TIME_PAINTING_{$tgl}.xlsx";
    }

    $content = file_get_contents($templatePath);

    return response($content, 200, [
        'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'Content-Disposition' => 'attachment; filename="' . $downloadFilename . '"',
        'Content-Length' => strlen($content),
        'Cache-Control' => 'no-cache, must-revalidate',
        'Pragma' => 'no-cache',
    ]);
};

foreach ([
    '/export/export_excel',
    '/export/export_excel.php',
    '/export/export_riwayat_performance',
    '/export/export_riwayat_performance.php',
    '/api/download-excel',
    '/api/download-excel-sesi'
] as $exportUri) {
    Route::match(['get', 'options'], $exportUri, $exportHandler);
}

// Kompatibilitas untuk request dengan /index.php?line=...&view=...
Route::get('/index.php', [DashboardController::class, 'index']);
