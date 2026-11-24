<?php

use App\Http\Controllers\DashboardController;
use App\Models\HangerInput;
use App\Models\HangerOutput;
use App\Models\ProductionSummary;
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

// Proxy pembaca frekuensi noise / gangguan induksi motor dari ESP32 DevKit
Route::match(['get', 'options'], '/api/devkit-noise', function () {
    $noiseFile = storage_path('app/latest_noise.json');
    if (file_exists($noiseFile)) {
        $data = @json_decode(file_get_contents($noiseFile), true);
        if (is_array($data) && !empty($data['last_updated'])) {
            // Jika ada laporan aktif dari ESP32 dalam 10 detik terakhir, langsung kembalikan tanpa request HTTP
            if ((now()->timestamp - $data['last_updated']) <= 10) {
                return response()->json($data)->header('Access-Control-Allow-Origin', '*');
            }
        }
    }

    $ip = @file_get_contents(storage_path('app/devkit_ip.txt'));
    $ip = trim($ip ?: '');
    $failFile = storage_path('app/devkit_fail.txt');
    $lastFail = file_exists($failFile) ? (int) @file_get_contents($failFile) : 0;
    $shouldTryIp = (now()->timestamp - $lastFail) > 25; // Backoff: jangan spam ESP32 jika sedang offline

    if ($ip && $shouldTryIp) {
        try {
            $res = \Illuminate\Support\Facades\Http::connectTimeout(0.2)->timeout(0.3)->get("http://{$ip}/noise-meter");
            if ($res->successful()) {
                $espData = $res->json();
                $espData['last_updated'] = now()->timestamp;
                @file_put_contents($noiseFile, json_encode($espData, JSON_PRETTY_PRINT), LOCK_EX);
                return response()->json($espData)->header('Access-Control-Allow-Origin', '*');
            }
        } catch (\Throwable $e) {
            @file_put_contents($failFile, (string) now()->timestamp, LOCK_EX);
        }
    }

    if (file_exists($noiseFile)) {
        $data = @json_decode(file_get_contents($noiseFile), true);
        if (is_array($data)) {
            $data['status'] = 'cached';
            return response()->json($data)->header('Access-Control-Allow-Origin', '*');
        }
    }

    return response()->json([
        'status' => 'offline',
        'in_noise_hz' => 0.0,
        'out_noise_hz' => 0.0,
        'in_peak_hz' => 0.0,
        'out_peak_hz' => 0.0,
        'diagnosis' => 'Menunggu Data Frekuensi...',
        'total_rejected' => 0,
        'events' => []
    ])->header('Access-Control-Allow-Origin', '*');
});

/**
 * Endpoint laporan real-time frekuensi noise dari ESP32 DevKit
 */
Route::match(['get', 'post', 'options'], '/api/report-noise', function (Request $request) {
    $inHz = (float) $request->input('in_hz', $request->query('in_hz', 0));
    $outHz = (float) $request->input('out_hz', $request->query('out_hz', 0));
    $peakIn = (float) $request->input('peak_in', $request->query('peak_in', 0));
    $peakOut = (float) $request->input('peak_out', $request->query('peak_out', 0));
    $diag = trim((string) $request->input('diag', $request->query('diag', '')));
    if (!$diag) {
        $diag = trim((string) $request->input('diagnosis', $request->query('diagnosis', 'Normal')));
    }

    $noiseFile = storage_path('app/latest_noise.json');
    $current = file_exists($noiseFile) ? (@json_decode(file_get_contents($noiseFile), true) ?: []) : [];

    $peakIn = max($peakIn, (float)($current['in_peak_hz'] ?? 0), $inHz);
    $peakOut = max($peakOut, (float)($current['out_peak_hz'] ?? 0), $outHz);
    $maxHz = max($inHz, $outHz);

    $totalRejected = (int)($current['total_rejected'] ?? 0);
    if ($maxHz >= 1.5) {
        $totalRejected += (int) ceil($maxHz);
    }

    $events = $current['events'] ?? [];
    if ($maxHz >= 1.5) {
        array_unshift($events, [
            'time' => now()->format('H:i:s'),
            'hz' => round($maxHz, 1),
            'in_hz' => round($inHz, 1),
            'out_hz' => round($outHz, 1),
            'diagnosis' => $diag
        ]);
        $events = array_slice($events, 0, 15);
    }

    $data = [
        'status' => 'ok',
        'in_noise_hz' => $inHz,
        'out_noise_hz' => $outHz,
        'in_peak_hz' => $peakIn,
        'out_peak_hz' => $peakOut,
        'diagnosis' => $diag,
        'total_rejected' => $totalRejected,
        'last_updated' => now()->timestamp,
        'last_updated_time' => now()->format('H:i:s'),
        'events' => $events
    ];

    @file_put_contents($noiseFile, json_encode($data, JSON_PRETTY_PRINT), LOCK_EX);

    return response()->json($data)->header('Access-Control-Allow-Origin', '*');
});

Route::match(['get', 'post', 'options'], '/api/reset-noise-peak', function () {
    $noiseFile = storage_path('app/latest_noise.json');
    if (file_exists($noiseFile)) {
        $current = @json_decode(file_get_contents($noiseFile), true) ?: [];
        $current['in_peak_hz'] = 0.0;
        $current['out_peak_hz'] = 0.0;
        $current['total_rejected'] = 0;
        $current['events'] = [];
        @file_put_contents($noiseFile, json_encode($current, JSON_PRETTY_PRINT), LOCK_EX);
    }
    $ip = @file_get_contents(storage_path('app/devkit_ip.txt'));
    $ip = trim($ip ?: '');
    if ($ip) {
        try {
            \Illuminate\Support\Facades\Http::timeout(1.0)->get("http://{$ip}/reset-noise-peak");
        } catch (\Throwable $e) {}
    }
    return response()->json(['status' => 'ok'])->header('Access-Control-Allow-Origin', '*');
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
    $durationMs = (int) $request->input('duration_ms', $request->query('duration_ms', 0));
    $triggerId = uniqid('trig_', true);
    $timestamp = microtime(true);

    // Muat atau inisialisasi statistik hanger
    $statsFile = storage_path('app/hanger_stats.json');
    $stats = [
        'total_input_hangers' => 0,
        'total_output_hangers' => 0,
        'wip_hangers' => 0,
        'total_output_pcs' => 0,
        'total_ng_pcs' => 0,
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

    $stats['last_signal_at'] = now()->timestamp;
    $stats['last_updated'] = now()->toIso8601String();

    if ($type === 'btn_on') {
        $stats['is_produksi'] = 1;
        file_put_contents($statsFile, json_encode($stats, JSON_PRETTY_PRINT), LOCK_EX);
    } elseif ($type === 'btn_off') {
        $stats['is_produksi'] = 0;
        file_put_contents($statsFile, json_encode($stats, JSON_PRETTY_PRINT), LOCK_EX);
    } elseif ($type === 'heartbeat') {
        $stats['is_produksi'] = ($val == 1) ? 1 : 0;
        file_put_contents($statsFile, json_encode($stats, JSON_PRETTY_PRINT), LOCK_EX);
    } elseif ($type === 'input') {
        $stats['is_produksi'] = 1;
        $stats['total_input_hangers']++;
        $stats['wip_hangers'] = max(0, $stats['total_input_hangers'] - $stats['total_output_hangers']);
        file_put_contents($statsFile, json_encode($stats, JSON_PRETTY_PRINT), LOCK_EX);

        try {
            $activePart = function_exists('getActivePart') ? getActivePart() : null;
            HangerInput::create([
                'hanger_number' => $stats['total_input_hangers'],
                'part_code' => $activePart['code'] ?? null,
                'part_name' => $activePart['name'] ?? null,
                'sensor_pin' => 27,
                'wip_at_time' => $stats['wip_hangers'],
                'status' => 'in_line',
                'entered_at' => now(),
            ]);

            ProductionSummary::updateOrCreate(
                ['date' => today()->toDateString(), 'shift' => 'Shift 1'],
                [
                    'total_input_hangers' => $stats['total_input_hangers'],
                    'wip_hangers' => $stats['wip_hangers'],
                    'is_active' => true,
                ]
            );
        } catch (\Throwable $e) {
            // Abaikan jika database belum siap
        }
    } elseif ($type === 'output' || $type === 'output_trigger') {
        $stats['is_produksi'] = 1;
        $stats['total_output_hangers']++;

        // SENSOR 2 HANYA MENGHITUNG HANGER (Gantungan Besi), BUKAN PART (PCS).
        // Jumlah PART yang masuk/keluar dihitung secara mandiri & akurat oleh Roboflow Vision AI!
        $stats['wip_hangers'] = max(0, $stats['total_input_hangers'] - $stats['total_output_hangers']);
        file_put_contents($statsFile, json_encode($stats, JSON_PRETTY_PRINT), LOCK_EX);

        try {
            ProductionSummary::updateOrCreate(
                ['date' => today()->toDateString(), 'shift' => 'Shift 1'],
                [
                    'total_output_hangers' => $stats['total_output_hangers'],
                    'total_output_pcs' => $stats['total_output_pcs'] ?? 0,
                    'wip_hangers' => $stats['wip_hangers'],
                    'is_active' => true,
                ]
            );
        } catch (\Throwable $e) {
        }
    }

    // Simpan event trigger terakhir untuk dibaca listener UI
    $triggerData = [
        'trigger_id' => $triggerId,
        'type' => $type,
        'val' => $val,
        'duration_ms' => $durationMs,
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
    $detectedPartCode = trim((string) $request->input('detected_part_code', $request->query('detected_part_code', '')));

    // Otomatisasi: Jika Roboflow mendeteksi jenis part tertentu, cocokkan ke katalog dan aktifkan otomatis!
    $catalog = function_exists('getPartsCatalog') ? getPartsCatalog() : [];
    $activePart = function_exists('getActivePart') ? getActivePart() : null;

    if (!empty($detectedPartCode)) {
        foreach ($catalog as $p) {
            $pCode = $p['code'] ?? '';
            $pName = $p['name'] ?? '';
            $pId = $p['id'] ?? '';
            if (
                strcasecmp($pCode, $detectedPartCode) === 0 ||
                strcasecmp($pId, $detectedPartCode) === 0 ||
                strcasecmp($pName, $detectedPartCode) === 0 ||
                stripos($detectedPartCode, $pCode) !== false ||
                stripos($pCode, $detectedPartCode) !== false ||
                stripos($detectedPartCode, $pName) !== false ||
                stripos($pName, $detectedPartCode) !== false
            ) {
                $activePart = $p;
                file_put_contents(storage_path('app/active_part.json'), $p['id']);
                break;
            }
        }
    }

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

    // Total output hanger sudah dicatat oleh fotosel ESP32 di /api/sensor-trigger
    // Jika belum pernah ada hanger terdeteksi sama sekali, set minimal ke 1
    if ($stats['total_output_hangers'] < 1) {
        $stats['total_output_hangers'] = 1;
    }

    $stats['total_output_pcs'] += $pcsCount;
    if ($pcsCount === 0) {
        $stats['empty_hangers']++;
    }
    $stats['wip_hangers'] = max(0, $stats['total_input_hangers'] - $stats['total_output_hangers']);
    $stats['last_updated'] = now()->toIso8601String();

    file_put_contents($statsFile, json_encode($stats, JSON_PRETTY_PRINT), LOCK_EX);

    try {
        HangerOutput::create([
            'hanger_number' => $stats['total_output_hangers'],
            'part_code' => $activePart['code'] ?? null,
            'part_name' => $activePart['name'] ?? null,
            'target_pcs' => $activePart['target_per_hanger'] ?? 4,
            'detected_pcs' => $pcsCount,
            'is_empty' => ($pcsCount === 0),
            'sensor_pin' => 25,
            'exited_at' => now(),
        ]);

        ProductionSummary::updateOrCreate(
            ['date' => today()->toDateString(), 'shift' => 'Shift 1'],
            [
                'total_output_hangers' => $stats['total_output_hangers'],
                'total_output_pcs' => $stats['total_output_pcs'],
                'total_empty_hangers' => $stats['empty_hangers'],
                'wip_hangers' => $stats['wip_hangers'],
                'is_active' => true,
            ]
        );
    } catch (\Throwable $e) {
        // Abaikan jika database belum siap
    }

    // Catat log deteksi hanger untuk Audit Trail AI & Verifikasi Operator
    try {
        $activeShift = function_exists('getActiveShift') ? getActiveShift() : null;
        $detLogFile = storage_path('app/hanger_detections.json');
        $detLogs = file_exists($detLogFile) ? (@json_decode(file_get_contents($detLogFile), true) ?: []) : [];
        $targetPcs = $activePart['target_per_hanger'] ?? 4;
        $statusText = ($pcsCount === 0) ? 'Kosong' : (($pcsCount < $targetPcs) ? 'Kurang' : 'Normal');
        array_unshift($detLogs, [
            'id' => uniqid('h_'),
            'time' => date('H:i:s'),
            'tanggal' => date('Y-m-d'),
            'hanger_number' => $stats['total_output_hangers'],
            'part_code' => $activePart['code'] ?? ($detectedPartCode ?: 'Unknown'),
            'part_name' => $activePart['name'] ?? 'Part Painting',
            'detected_pcs' => $pcsCount,
            'target_pcs' => $targetPcs,
            'status' => $statusText,
            'operator' => $activeShift['operator_name'] ?? 'Mukhamad Nawwaf',
            'shift' => $activeShift['shift_name'] ?? 'Shift 1 (Pagi)'
        ]);
        if (count($detLogs) > 100)
            $detLogs = array_slice($detLogs, 0, 100);
        file_put_contents($detLogFile, json_encode($detLogs, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    } catch (\Throwable $e) {
    }

    // Otomatis akumulasikan ke riwayat produksi (Excel report) untuk part ini
    if ($pcsCount > 0 && $activePart) {
        try {
            $pCode = $activePart['code'];
            $history = getProductionHistoryList();
            $today = date('Y-m-d');
            $found = false;
            foreach ($history as &$h) {
                if (($h['tanggal'] ?? '') === $today && ($h['part_code'] ?? '') === $pCode) {
                    $h['nilai_f'] = (int) ($h['nilai_f'] ?? 0) + $pcsCount;
                    $cVal = (float) ($activePart['sut'] ?? 1.0);
                    $bMenit = (date('w') == 5) ? 440 : 485;
                    $lossReg = 120;
                    $netTime = max(1, $bMenit - $lossReg);
                    $stdTime = round($cVal * $h['nilai_f'], 2);
                    $h['std_time_output'] = $stdTime;
                    $h['eff'] = min(100.0, round(($stdTime / $netTime) * 100, 1));
                    if ($activeShift) {
                        $h['operator_name'] = $h['operator_name'] ?? ($activeShift['operator_name'] ?? 'Mukhamad Nawwaf');
                        $h['operator_id'] = $h['operator_id'] ?? ($activeShift['operator_id'] ?? 'OP-01');
                        $h['shift'] = $h['shift'] ?? ($activeShift['shift_name'] ?? 'Shift 1');
                        $h['total_manpower'] = $h['total_manpower'] ?? ($activeShift['total_manpower'] ?? 6);
                        $h['positions'] = $h['positions'] ?? ($activeShift['positions'] ?? []);
                    }
                    $found = true;
                    break;
                }
            }
            unset($h);

            if (!$found) {
                $cVal = (float) ($activePart['sut'] ?? 1.0);
                $bMenit = (date('w') == 5) ? 440 : 485;
                $lossReg = 120;
                $netTime = max(1, $bMenit - $lossReg);
                $stdTime = round($cVal * $pcsCount, 2);
                $activeShift = getActiveShift();
                $history[] = [
                    'id' => count($history) + 1,
                    'line_name' => 'Painting',
                    'shift' => $activeShift['shift_name'] ?? ((date('w') == 5) ? 'Shift 1 (Jumat)' : 'Shift 1'),
                    'operator_name' => $activeShift['operator_name'] ?? 'Mukhamad Nawwaf',
                    'operator_id' => $activeShift['operator_id'] ?? 'OP-01',
                    'total_manpower' => $activeShift['total_manpower'] ?? 6,
                    'positions' => $activeShift['positions'] ?? [],
                    'nomor_sesi' => count($history) + 1,
                    'part_code' => $pCode,
                    'part_name' => $activePart['name'] ?? "Part {$pCode}",
                    'nilai_c' => $cVal,
                    'jam_mulai' => '06:55',
                    'jam_selesai' => date('H:i'),
                    'tanggal' => $today,
                    'nilai_b' => $bMenit,
                    'total_working_time' => $bMenit * 6,
                    'loss_regulated' => $lossReg,
                    'actual_net_time' => $netTime,
                    'loss_unregulated' => 0,
                    'net_operating_time' => $netTime,
                    'nilai_f' => $pcsCount,
                    'nilai_e' => 0,
                    'std_time_output' => $stdTime,
                    'eff' => min(100.0, round(($stdTime / $netTime) * 100, 1)),
                    'actual_ct' => round($bMenit / max(1, $pcsCount), 2),
                    'productivity' => round(($pcsCount / $netTime) * 60, 2),
                    'created_at' => now()->toIso8601String()
                ];
            }
            saveProductionHistoryList($history);
        } catch (\Throwable $e) {
        }
    }

    return response()->json([
        'status' => 'ok',
        'message' => 'Hanger output berhasil dikonfirmasi secara otomatis',
        'confirmed_pcs' => $pcsCount,
        'part' => $activePart,
        'detected_part_code' => $detectedPartCode,
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
        'total_ng_pcs' => 0,
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

    // Periksa Sinyal dari ESP32 (Toleransi 35 detik agar aman dari latency jaringan)
    $lastSignalAt = $stats['last_signal_at'] ?? null;
    $now = now()->timestamp;
    $hasRecentSignal = $lastSignalAt && (($now - $lastSignalAt) <= 35);

    $stats['has_signal'] = $hasRecentSignal ? 1 : 0;
    $stats['last_signal_ago'] = $lastSignalAt ? ($now - $lastSignalAt) : null;

    return response()->json([
        'status' => 'ok',
        'stats' => $stats,
        'active_part' => function_exists('getActivePart') ? getActivePart() : null,
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
        'total_ng_pcs' => 0,
        'empty_hangers' => 0,
        'is_produksi' => 1,
        'last_updated' => now()->toIso8601String(),
    ];
    file_put_contents(storage_path('app/hanger_stats.json'), json_encode($stats, JSON_PRETTY_PRINT));

    // Reset database summary hari ini
    try {
        ProductionSummary::where('date', today()->toDateString())->update([
            'total_input_hangers' => 0,
            'total_output_hangers' => 0,
            'total_output_pcs' => 0,
            'wip_hangers' => 0,
        ]);
    } catch (\Throwable $e) {}

    // Reset trigger JSON
    try {
        file_put_contents(storage_path('app/latest_sensor_trigger.json'), json_encode([
            'trigger_id' => 'reset_' . time(),
            'type' => 'reset',
            'val' => 0,
            'duration_ms' => 0,
            'timestamp' => time(),
            'created_at' => now()->toIso8601String(),
        ], JSON_PRETTY_PRINT));
    } catch (\Throwable $e) {}

    // Panggil reset counter pada ESP32 jika sedang online
    try {
        $espIp = file_exists(storage_path('app/esp32_ip.txt')) ? trim(file_get_contents(storage_path('app/esp32_ip.txt'))) : '192.168.1.100';
        if ($espIp) {
            \Illuminate\Support\Facades\Http::timeout(1.0)->get("http://{$espIp}/reset-counter");
        }
    } catch (\Throwable $e) {}

    return response()->json([
        'status' => 'ok',
        'message' => 'Statistik hanger & counter berhasil direset ke 0',
        'stats' => $stats
    ])
        ->header('Access-Control-Allow-Origin', '*')
        ->header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
        ->header('Access-Control-Allow-Headers', '*');
});

/**
 * 5c. Pencatatan Part Cacat / NG Manual & Log Defect
 */
$catatNgHandler = function (Request $request) {
    $pcs = max(1, (int) $request->input('pcs', 1));
    $defectType = trim((string) $request->input('defect_type', 'Cat Bintik (Dust/Dirt)'));
    $notes = trim((string) $request->input('notes', ''));

    $statsFile = storage_path('app/hanger_stats.json');
    $stats = [
        'total_input_hangers' => 0,
        'total_output_hangers' => 0,
        'wip_hangers' => 0,
        'total_output_pcs' => 0,
        'total_ng_pcs' => 0,
        'empty_hangers' => 0,
        'last_updated' => now()->toIso8601String(),
    ];
    if (file_exists($statsFile)) {
        $loaded = @json_decode(file_get_contents($statsFile), true);
        if (is_array($loaded)) {
            $stats = array_merge($stats, $loaded);
        }
    }

    $currentNg = (int) ($stats['total_ng_pcs'] ?? 0);
    $stats['total_ng_pcs'] = $currentNg + $pcs;
    $stats['last_updated'] = now()->toIso8601String();
    file_put_contents($statsFile, json_encode($stats, JSON_PRETTY_PRINT), LOCK_EX);

    // Catat log riwayat defect
    $activePart = function_exists('getActivePart') ? getActivePart() : null;
    $activeShift = function_exists('getActiveShift') ? getActiveShift() : null;
    $ngLogFile = storage_path('app/ng_logs.json');
    $ngLogs = file_exists($ngLogFile) ? (@json_decode(file_get_contents($ngLogFile), true) ?: []) : [];

    $newEntry = [
        'id' => uniqid('ng_'),
        'time' => date('H:i:s'),
        'tanggal' => date('Y-m-d'),
        'part_code' => $activePart['code'] ?? 'PART-001',
        'part_name' => $activePart['name'] ?? 'Part Painting',
        'pcs' => $pcs,
        'defect_type' => $defectType,
        'notes' => $notes,
        'operator' => $activeShift['operator_name'] ?? 'Mukhamad Nawwaf',
        'shift' => $activeShift['shift_name'] ?? 'Shift 1'
    ];
    array_unshift($ngLogs, $newEntry);
    if (count($ngLogs) > 100) $ngLogs = array_slice($ngLogs, 0, 100);
    file_put_contents($ngLogFile, json_encode($ngLogs, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    $outputPcs = (int) ($stats['total_output_pcs'] ?? 0);
    $totalNg = $stats['total_ng_pcs'];
    $outputOk = max(0, $outputPcs - $totalNg);
    $yieldVal = ($outputPcs > 0) ? round(($outputOk / $outputPcs) * 100, 1) : 0.0;

    return response()->json([
        'status' => 'ok',
        'message' => "Berhasil mencatat {$pcs} pcs part NG ({$defectType})",
        'total_ng' => $totalNg,
        'output_ok' => $outputOk,
        'yield_val' => $yieldVal,
        'logs' => array_slice($ngLogs, 0, 10),
    ])
        ->header('Access-Control-Allow-Origin', '*')
        ->header('Access-Control-Allow-Methods', 'POST, OPTIONS')
        ->header('Access-Control-Allow-Headers', '*');
};
Route::match(['post', 'options'], '/api/add-ng', $catatNgHandler);
Route::match(['post', 'options'], '/api/catat-ng', $catatNgHandler);

Route::match(['post', 'options'], '/api/delete-ng-log', function (Request $request) {
    $id = $request->input('id');
    $ngLogFile = storage_path('app/ng_logs.json');
    $ngLogs = file_exists($ngLogFile) ? (@json_decode(file_get_contents($ngLogFile), true) ?: []) : [];

    $deletedPcs = 0;
    $filtered = [];
    foreach ($ngLogs as $log) {
        if ($log['id'] === $id) {
            $deletedPcs = (int) ($log['pcs'] ?? 0);
        } else {
            $filtered[] = $log;
        }
    }

    $statsFile = storage_path('app/hanger_stats.json');
    $stats = file_exists($statsFile) ? (@json_decode(file_get_contents($statsFile), true) ?: []) : [];

    if ($deletedPcs > 0) {
        $stats['total_ng_pcs'] = max(0, ((int)($stats['total_ng_pcs'] ?? 0)) - $deletedPcs);
        file_put_contents($statsFile, json_encode($stats, JSON_PRETTY_PRINT), LOCK_EX);
        file_put_contents($ngLogFile, json_encode($filtered, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    return response()->json([
        'status' => 'ok',
        'message' => 'Catatan NG berhasil dibatalkan',
        'total_ng' => $stats['total_ng_pcs'] ?? 0,
        'logs' => array_slice($filtered, 0, 10)
    ])
        ->header('Access-Control-Allow-Origin', '*')
        ->header('Access-Control-Allow-Methods', 'POST, OPTIONS')
        ->header('Access-Control-Allow-Headers', '*');
});

Route::match(['get', 'options'], '/api/get-ng-logs', function (Request $request) {
    $ngLogFile = storage_path('app/ng_logs.json');
    $ngLogs = file_exists($ngLogFile) ? (@json_decode(file_get_contents($ngLogFile), true) ?: []) : [];
    $statsFile = storage_path('app/hanger_stats.json');
    $stats = file_exists($statsFile) ? (@json_decode(file_get_contents($statsFile), true) ?: []) : [];

    $tgl = $request->query('tanggal');
    if ($tgl) {
        $ngLogs = array_values(array_filter($ngLogs, function ($item) use ($tgl) {
            return ($item['tanggal'] ?? '') === $tgl;
        }));
    }

    // Hitung ringkasan per jenis cacat (Pareto defect)
    $breakdown = [];
    $totalNgPcs = 0;
    foreach ($ngLogs as $l) {
        $pcs = (int) ($l['pcs'] ?? 1);
        $type = $l['defect_type'] ?? 'Lain-lain';
        $breakdown[$type] = ($breakdown[$type] ?? 0) + $pcs;
        $totalNgPcs += $pcs;
    }
    arsort($breakdown);

    return response()->json([
        'status' => 'ok',
        'total_ng' => $tgl ? $totalNgPcs : (int) ($stats['total_ng_pcs'] ?? $totalNgPcs),
        'breakdown' => $breakdown,
        'logs' => $ngLogs,
    ])
        ->header('Access-Control-Allow-Origin', '*')
        ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
        ->header('Access-Control-Allow-Headers', '*');
});

/**
 * 5b. Mengambil log database input, output, dan summary produksi
 */
Route::match(['get', 'options'], '/api/production-logs', function (Request $request) {
    $limit = min(100, max(1, (int) $request->input('limit', 20)));
    $type = $request->input('type', 'all'); // 'input', 'output', 'all'

    $inputs = ($type === 'all' || $type === 'input')
        ? HangerInput::orderBy('id', 'desc')->limit($limit)->get()
        : [];
    $outputs = ($type === 'all' || $type === 'output')
        ? HangerOutput::orderBy('id', 'desc')->limit($limit)->get()
        : [];
    $summary = ProductionSummary::orderBy('date', 'desc')->limit(7)->get();

    return response()->json([
        'status' => 'ok',
        'inputs' => $inputs,
        'outputs' => $outputs,
        'summary' => $summary,
    ])
        ->header('Access-Control-Allow-Origin', '*')
        ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
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

    // Periksa Sinyal dari ESP32 (Toleransi 35 detik agar stabil)
    $lastSignalAt = $stats['last_signal_at'] ?? null;
    $now = now()->timestamp;
    $hasRecentSignal = $lastSignalAt && (($now - $lastSignalAt) <= 35);

    // Ambil status produksi terbaru dari data tersimpan (1 = running, 0 = stop)
    $isProduksi = !empty($stats['is_produksi']);

    return response()->json([
        'status' => 'success',
        'is_produksi' => $isProduksi ? 1 : 0,
        'has_signal' => $hasRecentSignal ? 1 : 0,
        'last_signal_ago' => $lastSignalAt ? ($now - $lastSignalAt) : null,
        'status_mesin' => $isProduksi ? 'running' : 'stop',
        'warna_patlite' => $isProduksi ? 'Hijau' : 'Merah',
        'keterangan' => $isProduksi ? 'PRODUKSI AKTIF (Running)' : 'STOP / STANDBY (Mesin Mati)',
        'updated_at' => $stats['last_updated'] ?? now()->toIso8601String(),
    ])
        ->header('Access-Control-Allow-Origin', '*')
        ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
        ->header('Access-Control-Allow-Headers', '*');
});

Route::match(['get', 'options'], '/api/get-status', function (Request $request) {
    return redirect('/api/get_status.php');
});

/**
 * 7c. Data Metrik Produksi (PCS & Hanger) untuk Dashboard
 * Mengisi nilai 6 Kartu Metrik:
 * - Input Produksi (PCS), Output Produksi (PCS), Yield (%)
 * - Total Hanger (HANGER), Hanger Terisi Wadah, Hanger Kosong
 */
Route::match(['get', 'options'], '/api/data.php', function (Request $request) {
    $line = $request->query('line', 'Painting');
    $tanggal = $request->query('tanggal', date('Y-m-d'));
    $mode = $request->query('mode', 'shift');

    $statsFile = storage_path('app/hanger_stats.json');
    $stats = file_exists($statsFile) ? (@json_decode(file_get_contents($statsFile), true) ?: []) : [];

    // Tentukan shift saat ini
    $nowHour = (int) date('H');
    $nowMin = (int) date('i');
    $curMin = $nowHour * 60 + $nowMin;

    if ($curMin >= (6 * 60 + 55) && $curMin < (15 * 60 + 50)) {
        $shiftNama = 'Pagi';
        $rentangShift = '06:55 - 15:50';
    } elseif ($curMin >= (15 * 60 + 50) && $curMin < (23 * 60 + 20)) {
        $shiftNama = 'Sore';
        $rentangShift = '15:50 - 23:20';
    } else {
        $shiftNama = 'Malam';
        $rentangShift = '23:20 - 06:55';
    }

    $activePart = function_exists('getActivePart') ? getActivePart() : null;
    $pcsPerHanger = max(1, (int) ($activePart['pcs'] ?? 1));

    $totalInputHangers = (int) ($stats['total_input_hangers'] ?? 0);
    $totalOutputHangers = (int) ($stats['total_output_hangers'] ?? 0);
    $totalOutputPcs = (int) ($stats['total_output_pcs'] ?? 0);
    $emptyHangers = (int) ($stats['empty_hangers'] ?? 0);

    // Sinkronisasi dengan database jika ada
    try {
        if (class_exists('App\Models\HangerInput')) {
            $dbInput = \App\Models\HangerInput::whereDate('entered_at', $tanggal)->count();
            if ($dbInput > 0) {
                $totalInputHangers = max($totalInputHangers, $dbInput);
            }
        }
        if (class_exists('App\Models\HangerOutput')) {
            $dbOutputPcs = \App\Models\HangerOutput::whereDate('exited_at', $tanggal)->sum('detected_pcs');
            $dbOutputHangers = \App\Models\HangerOutput::whereDate('exited_at', $tanggal)->count();
            if ($dbOutputPcs > 0) {
                $totalOutputPcs = max($totalOutputPcs, (int) $dbOutputPcs);
            }
            if ($dbOutputHangers > 0) {
                $totalOutputHangers = max($totalOutputHangers, (int) $dbOutputHangers);
            }
        }
    } catch (\Throwable $e) {
    }

    // Hitung PCS
    $inputPcs = $totalInputHangers * $pcsPerHanger;
    // Keluaran Total Produksi (PCS) bertambah saat part dideteksi & dikonfirmasi oleh Kamera Vision AI
    $outputPcs = $totalOutputPcs;
    $totalNgPcs = (int) ($stats['total_ng_pcs'] ?? 0);
    $totalProcessedPcs = $outputPcs + $totalNgPcs;

    // Yield Produksi dihitung dari Output OK terhadap Total Part Masuk/Diproses (Output OK + NG)
    // Yield = (Output OK / Total Part) * 100%
    if ($totalProcessedPcs > 0) {
        $yieldPcs = round(($outputPcs / $totalProcessedPcs) * 100, 1);
    } elseif ($outputPcs > 0) {
        $yieldPcs = 100.0;
    } else {
        $yieldPcs = 100.0;
    }
    $orValue = $yieldPcs;

    // Hitung HANGER / WADAH
    $inputSheet = $totalInputHangers;
    $outputSheet = max(0, $totalOutputHangers - $emptyHangers);
    $yieldSheet = ($inputSheet > 0) ? round(($outputSheet / $inputSheet) * 100, 1) : 0.0;

    return response()->json([
        'status' => 'success',
        'line' => $line,
        'tanggal' => $tanggal,
        'mode' => $mode,
        'shift' => $shiftNama,
        'rentang_shift' => $rentangShift,
        'input' => $inputPcs,
        'output' => $outputPcs,
        'ng_pcs' => $totalNgPcs,
        'output_ok' => $outputOkPcs,
        'defect_rate' => (($outputPcs + $totalNgPcs) > 0) ? min(100.0, round(($totalNgPcs / ($outputPcs + $totalNgPcs)) * 100, 1)) : 0.0,
        'yield_val' => $yieldPcs,
        'or_value' => $orValue,
        'input_sheet' => $inputSheet,
        'output_sheet' => $outputSheet,
        'yield_sheet_val' => $emptyHangers,
        'total_hanger' => $totalInputHangers,
        'hanger_ok' => $outputSheet,
        'hanger_kosong' => $emptyHangers,
        'wip' => (int) ($stats['wip_hangers'] ?? max(0, $totalInputHangers - $totalOutputHangers)),
        'active_part' => $activePart,
    ])
        ->header('Access-Control-Allow-Origin', '*')
        ->header('Access-Control-Allow-Origin', '*')
        ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
        ->header('Access-Control-Allow-Headers', '*');
});

Route::match(['get', 'options'], '/api/data', function (Request $request) {
    return redirect('/api/data.php?' . http_build_query($request->all()));
});

/**
 * 7d. API Grafik Harian / Bulanan untuk Chart & Target Bulanan
 */
Route::match(['get', 'options'], '/api/grafik_harian.php', function (Request $request) {
    $statsFile = storage_path('app/hanger_stats.json');
    $stats = file_exists($statsFile) ? (@json_decode(file_get_contents($statsFile), true) ?: []) : [];

    $activePart = function_exists('getActivePart') ? getActivePart() : null;
    $pcsPerHanger = max(1, (int) ($activePart['pcs'] ?? 1));

    $totalInput = (int) ($stats['total_input_hangers'] ?? 0) * $pcsPerHanger;
    $totalOutput = (int) ($stats['total_output_pcs'] ?? 0);

    // Saw  xddddmpel titik data jam kerja (07:00 s.d 16:00)
    $jamList = ['07:00', '08:00', '09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00'];
    $titik = [];
    $accumIn = 0;
    $accumOut = 0;
    $stepIn = count($jamList) > 0 ? (int) ceil($totalInput / count($jamList)) : 0;
    $stepOut = count($jamList) > 0 ? (int) ceil($totalOutput / count($jamList)) : 0;

    foreach ($jamList as $idx => $jam) {
        $accumIn = min($totalInput, $accumIn + $stepIn);
        $accumOut = min($totalOutput, $accumOut + $stepOut);
        $titik[] = [
            'waktu' => $jam,
            'input' => $accumIn,
            'output' => $accumOut,
        ];
    }

    return response()->json([
        'status' => 'success',
        'total_input' => $totalInput,
        'total_output' => $totalOutput,
        'data' => $titik,
    ])
        ->header('Access-Control-Allow-Origin', '*')
        ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
        ->header('Access-Control-Allow-Headers', '*');
});

Route::match(['get', 'options'], '/api/grafik-harian', function (Request $request) {
    return redirect('/api/grafik_harian.php?' . http_build_query($request->all()));
});

Route::match(['get', 'options'], '/api/grafik_performance.php', function (Request $request) {
    $history = function_exists('getProductionHistoryList') ? getProductionHistoryList() : [];
    $tgl = $request->query('tanggal');
    if ($tgl) {
        $history = array_values(array_filter($history, function ($item) use ($tgl) {
            return ($item['tanggal'] ?? '') === $tgl;
        }));
    }

    $titik = [];
    foreach ($history as $idx => $h) {
        $sesiNo = $h['nomor_sesi'] ?? ($idx + 1);
        $part = $h['part_code'] ?? $h['part'] ?? 'Part';
        $titik[] = [
            'label' => "Sesi {$sesiNo} ({$part})",
            'I' => (float) ($h['nilai_i'] ?? 0),
            'D' => (float) ($h['nilai_d'] ?? 1.0),
            'eff' => (float) ($h['eff'] ?? 95.0),
        ];
    }

    return response()->json([
        'status' => 'success',
        'data' => $titik,
    ])
        ->header('Access-Control-Allow-Origin', '*')
        ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
        ->header('Access-Control-Allow-Headers', '*');
});

Route::match(['get', 'options'], '/api/get_part.php', function (Request $request) {
    $activePart = function_exists('getActivePart') ? getActivePart() : null;
    $partName = !empty($activePart['code']) ? "[{$activePart['code']}] " . ($activePart['name'] ?? '') : ($activePart['name'] ?? '-');
    return response()->json([
        'status' => 'success',
        'part' => $partName,
        'active_part' => $activePart,
    ])
        ->header('Access-Control-Allow-Origin', '*')
        ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
        ->header('Access-Control-Allow-Headers', '*');
});

// ===== API KATALOG PART & SESI CHANGEOVER =====

function getPartsCatalog()
{
    $file = storage_path('app/parts_catalog.json');
    if (!file_exists($file)) {
        file_put_contents($file, json_encode([], JSON_PRETTY_PRINT));
        return [];
    }
    $catalog = @json_decode(file_get_contents($file), true) ?: [];
    $modelsDir = storage_path('app/models');
    foreach ($catalog as &$p) {
        $code = $p['code'] ?? '';
        $modelFile = $p['model_file'] ?? '';
        
        // Auto-detect existing model file if not set or missing
        if (!$modelFile || !file_exists($modelsDir . '/' . $modelFile)) {
            $candidates = [
                $code . '_v1.pt',
                $code . '.pt',
                $code . '.onnx',
                'PART-001_v1.pt',
                'weights.pt',
            ];
            foreach ($candidates as $cand) {
                if (file_exists($modelsDir . '/' . $cand)) {
                    $modelFile = $cand;
                    break;
                }
            }
        }
        $fullPath = $modelsDir . '/' . $modelFile;
        $hasLocal = (!empty($modelFile) && file_exists($fullPath));
        $p['model_file'] = $modelFile;
        $p['has_local_model'] = $hasLocal;
        $p['model_size_mb'] = $hasLocal ? round(filesize($fullPath) / (1024 * 1024), 1) : 0;
    }
    return $catalog;
}

function getActiveShift()
{
    $file = storage_path('app/active_shift.json');
    if (file_exists($file)) {
        $data = @json_decode(file_get_contents($file), true);
        if (is_array($data) && !empty($data['operator_name'])) {
            return $data;
        }
    }
    $default = [
        'operator_name' => 'Mukhamad Nawwaf',
        'operator_id' => 'OP-01',
        'shift' => 'Shift 1',
        'shift_name' => 'Shift 1 (Pagi)',
        'manpower_tetap' => 4,
        'manpower_kwt' => 2,
        'manpower_daily' => 0,
        'total_manpower' => 6,
        'positions' => [
            ['name' => 'Loading / Hanging', 'count' => 2, 'status' => 'tetap', 'operators' => 'Budi, Ika'],
            ['name' => 'Sprayer / Painter', 'count' => 1, 'status' => 'tetap', 'operators' => 'Mukhamad Nawwaf'],
            ['name' => 'Unloading', 'count' => 2, 'status' => 'kwt', 'operators' => 'Ahmad, Yessy'],
            ['name' => 'QC & Packing', 'count' => 1, 'status' => 'tetap', 'operators' => 'Iin'],
        ],
        'started_at' => date('Y-m-d H:i:s'),
        'notes' => 'Line Painting Aktif'
    ];
    @file_put_contents($file, json_encode($default, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    return $default;
}

/**
 * 7.1. Dapatkan informasi Operator PIC & Shift Produksi Aktif
 */
Route::match(['get', 'options'], '/api/get-active-shift', function () {
    return response()->json([
        'status' => 'ok',
        'shift' => getActiveShift()
    ])
        ->header('Access-Control-Allow-Origin', '*')
        ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
        ->header('Access-Control-Allow-Headers', '*');
});

/**
 * 7.2. Perbarui / Serah Terima Operator PIC & Shift Aktif (Handover)
 */
Route::match(['post', 'options'], '/api/set-active-shift', function (Request $request) {
    $operatorName = trim($request->input('operator_name', 'Mukhamad Nawwaf'));
    $operatorId = trim($request->input('operator_id', 'OP-01'));
    $shift = trim($request->input('shift', 'Shift 1'));
    $tetap = max(0, (int) $request->input('manpower_tetap', 4));
    $kwt = max(0, (int) $request->input('manpower_kwt', 2));
    $daily = max(0, (int) $request->input('manpower_daily', 0));
    $totalMp = $tetap + $kwt + $daily;
    $notes = trim($request->input('notes', ''));
    $positions = $request->input('positions', []);
    if (!is_array($positions) || empty($positions)) {
        $positions = [
            ['name' => 'Loading / Hanging', 'count' => 2, 'status' => 'tetap', 'operators' => ''],
            ['name' => 'Sprayer / Painter', 'count' => 1, 'status' => 'tetap', 'operators' => ''],
            ['name' => 'Unloading', 'count' => 2, 'status' => 'kwt', 'operators' => ''],
            ['name' => 'QC & Packing', 'count' => 1, 'status' => 'tetap', 'operators' => ''],
        ];
    }

    $shiftNames = [
        'Shift 1' => 'Shift 1 (Pagi)',
        'Shift 2' => 'Shift 2 (Sore)',
        'Shift 3' => 'Shift 3 (Malam)',
    ];
    $shiftName = $shiftNames[$shift] ?? "{$shift} (Aktif)";

    $shiftData = [
        'operator_name' => $operatorName ?: 'Operator Painting',
        'operator_id' => $operatorId ?: 'OP-01',
        'shift' => $shift,
        'shift_name' => $shiftName,
        'manpower_tetap' => $tetap,
        'manpower_kwt' => $kwt,
        'manpower_daily' => $daily,
        'total_manpower' => $totalMp,
        'positions' => $positions,
        'started_at' => date('Y-m-d H:i:s'),
        'notes' => $notes
    ];

    $file = storage_path('app/active_shift.json');
    file_put_contents($file, json_encode($shiftData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    // Catat histori handover shift
    $logFile = storage_path('app/shift_handover_logs.json');
    $logs = file_exists($logFile) ? (@json_decode(file_get_contents($logFile), true) ?: []) : [];
    array_unshift($logs, array_merge($shiftData, ['logged_at' => date('Y-m-d H:i:s')]));
    if (count($logs) > 50)
        $logs = array_slice($logs, 0, 50);
    @file_put_contents($logFile, json_encode($logs, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    return response()->json([
        'status' => 'ok',
        'message' => "Sesi shift berhasil diperbarui: {$shiftName} oleh {$operatorName}",
        'shift' => $shiftData
    ])
        ->header('Access-Control-Allow-Origin', '*')
        ->header('Access-Control-Allow-Methods', 'POST, OPTIONS')
        ->header('Access-Control-Allow-Headers', '*');
});

/**
 * 7.3. Dapatkan Log Deteksi Hanger Real-time (Audit Trail AI Vision)
 */
Route::match(['get', 'options'], '/api/get-hanger-detections', function (Request $request) {
    $detLogFile = storage_path('app/hanger_detections.json');
    $logs = file_exists($detLogFile) ? (@json_decode(file_get_contents($detLogFile), true) ?: []) : [];
    return response()->json([
        'status' => 'ok',
        'count' => count($logs),
        'detections' => $logs
    ])
        ->header('Access-Control-Allow-Origin', '*')
        ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
        ->header('Access-Control-Allow-Headers', '*');
});

function getActivePart()
{
    $catalog = getPartsCatalog();
    $activeFile = storage_path('app/active_part.json');
    $activeId = file_exists($activeFile) ? trim(@file_get_contents($activeFile)) : '';

    foreach ($catalog as $p) {
        if ($p['id'] === $activeId)
            return $p;
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
        $activeShift = getActiveShift();
        $historyFile = storage_path('app/part_session_history.json');
        $history = file_exists($historyFile) ? (@json_decode(file_get_contents($historyFile), true) ?: []) : [];
        $history[] = [
            'part_id' => $currentPart['id'],
            'part_code' => $currentPart['code'] ?? '',
            'part_name' => $currentPart['name'],
            'model_id' => $currentPart['model_id'],
            'model_version' => $currentPart['model_version'],
            'shift' => $activeShift['shift_name'] ?? 'Shift 1',
            'operator_name' => $activeShift['operator_name'] ?? 'Mukhamad Nawwaf',
            'operator_id' => $activeShift['operator_id'] ?? 'OP-01',
            'total_manpower' => $activeShift['total_manpower'] ?? 6,
            'positions' => $activeShift['positions'] ?? [],
            'total_input_hangers' => $stats['total_input_hangers'] ?? 0,
            'total_output_hangers' => $stats['total_output_hangers'] ?? 0,
            'total_output_pcs' => $stats['total_output_pcs'] ?? 0,
            'total_ng_pcs' => $stats['total_ng_pcs'] ?? 0,
            'ended_at' => now()->toIso8601String(),
        ];
        file_put_contents($historyFile, json_encode($history, JSON_PRETTY_PRINT));
    }

    // 2. Reset counter batch untuk part baru
    $stats['total_input_hangers'] = 0;
    $stats['total_output_hangers'] = 0;
    $stats['wip_hangers'] = 0;
    $stats['total_output_pcs'] = 0;
    $stats['total_ng_pcs'] = 0;
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
 * 10. Tambah / Edit Part di Katalog (Mendukung Upload Model Offline)
 */
Route::match(['post', 'options'], '/api/save-part', function (Request $request) {
    $catalog = getPartsCatalog();
    $id = $request->input('id');
    $name = trim($request->input('name', ''));
    $code = trim($request->input('code', ''));
    $target = max(1, (int) $request->input('target_per_hanger', 4));

    if (!$name || !$code) {
        return response()->json(['status' => 'error', 'message' => 'Kode Part & Nama Part wajib diisi'], 400);
    }

    $modelsDir = storage_path('app/models');
    if (!file_exists($modelsDir)) {
        @mkdir($modelsDir, 0777, true);
    }

    $uploadedFile = $request->file('model_file');
    $savedModelFile = null;

    if ($uploadedFile && $uploadedFile->isValid()) {
        $ext = strtolower($uploadedFile->getClientOriginalExtension());
        if (!in_array($ext, ['pt', 'onnx', 'bin', 'engine'])) {
            $ext = 'pt';
        }
        $cleanCode = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $code);
        $savedModelFile = $cleanCode . '_v1.' . $ext;
        $uploadedFile->move($modelsDir, $savedModelFile);
    }

    $isEdit = false;
    $targetId = $id;
    if ($id) {
        foreach ($catalog as &$p) {
            if ($p['id'] === $id) {
                $p['name'] = $name;
                $p['code'] = $code ?: $p['code'];
                $p['target_per_hanger'] = $target;
                if ($savedModelFile) {
                    $p['model_file'] = $savedModelFile;
                }
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
            'model_id' => 'offline_' . strtolower($code),
            'model_version' => 1,
            'target_per_hanger' => $target,
            'model_file' => $savedModelFile ?: ($code . '_v1.pt'),
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
        'parts' => getPartsCatalog(),
        'active_part' => getActivePart(),
    ])
        ->header('Access-Control-Allow-Origin', '*')
        ->header('Access-Control-Allow-Methods', 'POST, OPTIONS')
        ->header('Access-Control-Allow-Headers', '*');
});

/**
 * 10a-2. Hapus Part dari Katalog
 * - Mendukung hapus part biasa maupun part aktif (jika aktif, dialihkan ke part lain atau dikosongkan)
 * - File model AI (.pt / .onnx) juga otomatis dihapus dari storage/app/models/
 */
Route::match(['post', 'options'], '/api/delete-part', function (Request $request) {
    if ($request->isMethod('options')) {
        return response('', 200)
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Access-Control-Allow-Methods', 'POST, OPTIONS')
            ->header('Access-Control-Allow-Headers', '*');
    }

    $id = $request->input('id');
    if (!$id) {
        return response()->json(['status' => 'error', 'message' => 'ID part tidak ditemukan'], 400)
            ->header('Access-Control-Allow-Origin', '*');
    }

    $catalogFile = storage_path('app/parts_catalog.json');
    $catalog = file_exists($catalogFile) ? (@json_decode(file_get_contents($catalogFile), true) ?: []) : [];

    // Cari part yang akan dihapus
    $partToDelete = null;
    $filtered = [];
    foreach ($catalog as $p) {
        if ($p['id'] === $id) {
            $partToDelete = $p;
        } else {
            $filtered[] = $p;
        }
    }

    if (!$partToDelete) {
        return response()->json(['status' => 'error', 'message' => 'Part tidak ditemukan di katalog'], 404)
            ->header('Access-Control-Allow-Origin', '*');
    }

    // Hapus file model AI dari disk jika ada
    $modelsDir = storage_path('app/models');
    $modelFile = $partToDelete['model_file'] ?? '';
    if ($modelFile) {
        $fullPath = $modelsDir . '/' . $modelFile;
        if (file_exists($fullPath)) {
            @unlink($fullPath);
        }
    }

    // Simpan katalog tanpa part yang dihapus
    $filtered = array_values($filtered);
    file_put_contents($catalogFile, json_encode($filtered, JSON_PRETTY_PRINT));

    // Kelola active_part: jika part yang dihapus adalah part aktif
    $activeFile = storage_path('app/active_part.json');
    $activeId = file_exists($activeFile) ? trim(@file_get_contents($activeFile)) : '';
    if ($id === $activeId) {
        if (!empty($filtered)) {
            file_put_contents($activeFile, $filtered[0]['id']);
        } else {
            file_put_contents($activeFile, '');
        }
    }

    return response()->json([
        'status' => 'ok',
        'message' => "Part '{$partToDelete['name']}' berhasil dihapus dari katalog" . ($modelFile ? " (file model '{$modelFile}' juga dihapus)" : ''),
        'parts' => getPartsCatalog(),
        'active_part' => getActivePart(),
    ])
        ->header('Access-Control-Allow-Origin', '*')
        ->header('Access-Control-Allow-Methods', 'POST, OPTIONS')
        ->header('Access-Control-Allow-Headers', '*');
});

/**
 * 10a-3. Hapus File Model AI (.pt / .onnx) Saja
 * - Menghapus file weights/model dari storage/app/models/ tanpa menghapus profil part dari katalog
 */
Route::match(['post', 'options'], '/api/delete-part-model', function (Request $request) {
    if ($request->isMethod('options')) {
        return response('', 200)
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Access-Control-Allow-Methods', 'POST, OPTIONS')
            ->header('Access-Control-Allow-Headers', '*');
    }

    $id = $request->input('id');
    if (!$id) {
        return response()->json(['status' => 'error', 'message' => 'ID part tidak ditemukan'], 400)
            ->header('Access-Control-Allow-Origin', '*');
    }

    $catalogFile = storage_path('app/parts_catalog.json');
    $catalog = file_exists($catalogFile) ? (@json_decode(file_get_contents($catalogFile), true) ?: []) : [];

    $found = false;
    $deletedFile = '';
    $modelsDir = storage_path('app/models');

    foreach ($catalog as &$p) {
        if ($p['id'] === $id) {
            $modelFile = $p['model_file'] ?? '';
            if ($modelFile) {
                $fullPath = $modelsDir . '/' . $modelFile;
                if (file_exists($fullPath)) {
                    @unlink($fullPath);
                }
                $deletedFile = $modelFile;
            }
            $p['model_file'] = '';
            $p['has_local_model'] = false;
            $p['model_size_mb'] = 0;
            $found = true;
            break;
        }
    }

    if (!$found) {
        return response()->json(['status' => 'error', 'message' => 'Part tidak ditemukan'], 404)
            ->header('Access-Control-Allow-Origin', '*');
    }

    file_put_contents($catalogFile, json_encode($catalog, JSON_PRETTY_PRINT));

    return response()->json([
        'status' => 'ok',
        'message' => "File model '{$deletedFile}' berhasil dihapus dari server",
        'parts' => getPartsCatalog(),
        'active_part' => getActivePart(),
    ])
        ->header('Access-Control-Allow-Origin', '*')
        ->header('Access-Control-Allow-Methods', 'POST, OPTIONS')
        ->header('Access-Control-Allow-Headers', '*');
});

/**
 * 10b. Endpoint Deteksi AI Offline (Lokal YOLO)
 */
Route::match(['post', 'options'], '/api/detect-local', function (Request $request) {
    $activePart = getActivePart();
    $modelsDir = storage_path('app/models');
    $modelFile = $request->input('model_file', $activePart['model_file'] ?? 'PART-001_v1.pt');
    $modelPath = $modelsDir . '/' . $modelFile;

    if (!file_exists($modelPath)) {
        // Fallback: cari file .pt atau .onnx apa saja di folder models
        $glob = glob($modelsDir . '/*.{pt,onnx}', GLOB_BRACE);
        if (!empty($glob)) {
            $modelPath = $glob[0];
            $modelFile = basename($modelPath);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => "File model '{$modelFile}' tidak ditemukan di storage/app/models/"
            ], 404);
        }
    }

    $imageBase64 = $request->input('image_base64', $request->input('image', ''));
    if (!$imageBase64 && $request->hasFile('image')) {
        $imageBase64 = base64_encode(file_get_contents($request->file('image')->getRealPath()));
    }

    if (!$imageBase64) {
        $camIpFile = storage_path('app/cam_ip.txt');
        $camIp = file_exists($camIpFile) ? trim(file_get_contents($camIpFile)) : '10.71.94.87';
        if ($camIp) {
            $ch = curl_init("http://{$camIp}/capture");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT_MS, 1000);
            $rawImg = curl_exec($ch);
            curl_close($ch);
            if ($rawImg && strlen($rawImg) > 500) {
                $imageBase64 = 'data:image/jpeg;base64,' . base64_encode($rawImg);
            }
        }
    }

    if (!$imageBase64) {
        return response()->json(['status' => 'error', 'message' => 'Gambar tidak ditemukan'], 400);
    }

    $conf = (float) $request->input('conf', 0.35);

    // 1. Coba panggil background daemon lokal (port 5001) jika sedang aktif
    $daemonUrl = 'http://127.0.0.1:5001/';
    $ch = curl_init($daemonUrl);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode([
            'model_path' => $modelPath,
            'image_base64' => $imageBase64,
            'conf' => $conf
        ]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT_MS => 1500,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json']
    ]);
    $resp = curl_exec($ch);
    $curlErr = curl_errno($ch);
    curl_close($ch);

    // Fungsi helper pencocokan & auto-switch part jika dikenali oleh AI
    $syncActivePartAI = function (&$payload) {
        $catalog = function_exists('getPartsCatalog') ? getPartsCatalog() : [];
        $activePart = function_exists('getActivePart') ? getActivePart() : null;
        if (!empty($payload['predictions']) && is_array($payload['predictions'])) {
            foreach ($payload['predictions'] as $pred) {
                $cls = trim((string) ($pred['class'] ?? $pred['name'] ?? ''));
                $confVal = (float) ($pred['confidence'] ?? 0);
                if ($confVal >= 0.35 && !empty($cls)) {
                    $clsLower = strtolower($cls);
                    if (!str_contains($clsLower, 'hanger') && !str_contains($clsLower, 'gantungan') && $clsLower !== 'part' && $clsLower !== 'object') {
                        foreach ($catalog as $p) {
                            $pCode = $p['code'] ?? '';
                            $pName = $p['name'] ?? '';
                            $pId = $p['id'] ?? '';
                            if (
                                strcasecmp($pCode, $cls) === 0 ||
                                strcasecmp($pId, $cls) === 0 ||
                                strcasecmp($pName, $cls) === 0 ||
                                stripos($cls, $pCode) !== false ||
                                stripos($pCode, $cls) !== false ||
                                stripos($cls, $pName) !== false ||
                                stripos($pName, $cls) !== false
                            ) {
                                if (!$activePart || $activePart['id'] !== $p['id']) {
                                    file_put_contents(storage_path('app/active_part.json'), $p['id']);
                                    $activePart = $p;
                                    $payload['part_switched'] = true;
                                }
                                break 2;
                            }
                        }
                    }
                }
            }
        }
        $payload['active_part'] = $activePart;
    };

    if (!$curlErr && $resp) {
        $json = @json_decode($resp, true);
        if ($json && isset($json['status']) && $json['status'] === 'ok') {
            $syncActivePartAI($json);
            return response()->json($json)->header('Access-Control-Allow-Origin', '*');
        }
    }

    // 2. Fallback: Eksekusi langsung script Python CLI
    $tempInput = storage_path('app/temp_detect_' . uniqid() . '.json');
    file_put_contents($tempInput, json_encode([
        'model_path' => $modelPath,
        'image_base64' => $imageBase64,
        'conf' => $conf
    ]));

    $pyScript = base_path('app/Services/local_ai_service.py');
    $cmd = 'python ' . escapeshellarg($pyScript) . ' --model ' . escapeshellarg($modelPath) . ' < ' . escapeshellarg($tempInput) . ' 2>&1';

    $output = shell_exec($cmd);
    @unlink($tempInput);

    $result = @json_decode($output, true);
    if (!$result || !isset($result['status'])) {
        return response()->json([
            'status' => 'error',
            'message' => 'Gagal menjalankan deteksi AI lokal',
            'raw_output' => $output
        ], 500)->header('Access-Control-Allow-Origin', '*');
    }

    $syncActivePartAI($result);
    return response()->json($result)->header('Access-Control-Allow-Origin', '*');
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
    $filename = sprintf('%s_sample_%04d.jpg', $partCode, $newIndex);
    file_put_contents($dir . '/' . $filename, $decoded);

    return response()->json([
        'status' => 'ok',
        'count' => $newIndex,
        'filename' => $filename,
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
    $catalog = function_exists('getPartsCatalog') ? getPartsCatalog() : [];
    $activePart = function_exists('getActivePart') ? getActivePart() : null;
    $partId = $request->query('part_id', $activePart ? $activePart['id'] : 'default_part');

    $partObj = $activePart;
    if ($partId && (!isset($partObj['id']) || $partObj['id'] !== $partId)) {
        foreach ($catalog as $p) {
            if (($p['id'] ?? '') === $partId) {
                $partObj = $p;
                break;
            }
        }
    }

    $partName = $partObj ? $partObj['name'] : 'Part';
    $partCode = $partObj ? ($partObj['code'] ?? 'P00') : 'P00';

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
        $readme .= "Project Roboflow: " . ($partObj['model_id'] ?? '-') . " (Versi saat ini: v" . ($partObj['model_version'] ?? '1') . ")\r\n";
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

    if (!file_exists($zipPath)) {
        return response('Gagal membuat file ZIP', 500);
    }

    return response()->streamDownload(function () use ($zipPath) {
        $stream = fopen($zipPath, 'rb');
        if ($stream) {
            fpassthru($stream);
            fclose($stream);
        }
        @unlink($zipPath);
    }, $zipFilename, [
        'Content-Type' => 'application/zip',
        'Content-Length' => filesize($zipPath),
    ]);
});

/**
 * 16. Reset / Hapus seluruh koleksi sampel untuk Part tertentu
 */
Route::match(['post', 'get', 'options'], '/api/reset-roboflow-samples', function (Request $request) {
    $activePart = getActivePart();
    $rawPartId = trim((string) ($request->input('part_id') ?? $request->query('part_id') ?? ''));
    $partId = ($rawPartId !== '' && $rawPartId !== 'all') ? $rawPartId : ($activePart ? $activePart['id'] : '');

    $targetDirs = [];
    if (!empty($partId)) {
        $targetDirs[] = storage_path("app/roboflow_samples/{$partId}");
    }
    // Jika partId kosong atau diminta all, sertakan seluruh subfolder di roboflow_samples
    if (empty($partId) || $rawPartId === 'all') {
        $baseDir = storage_path('app/roboflow_samples');
        if (is_dir($baseDir)) {
            $subdirs = glob($baseDir . '/*', GLOB_ONLYDIR) ?: [];
            $targetDirs = array_merge($targetDirs, $subdirs);
        }
    }

    $deletedCount = 0;
    foreach (array_unique($targetDirs) as $d) {
        if (is_dir($d)) {
            $files = glob($d . '/*') ?: [];
            foreach ($files as $f) {
                if (is_file($f)) {
                    if (@unlink($f)) {
                        $deletedCount++;
                    }
                }
            }
            @rmdir($d);
        }
    }

    // Bersihkan juga file zip download dataset lama
    $zipFiles = glob(storage_path('app/dataset_retrain_*.zip')) ?: [];
    foreach ($zipFiles as $zf) {
        if (is_file($zf)) {
            @unlink($zf);
        }
    }

    return response()->json([
        'status' => 'ok',
        'count' => 0,
        'deleted' => $deletedCount,
        'message' => 'Sampel foto berhasil direset'
    ])
        ->header('Access-Control-Allow-Origin', '*')
        ->header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
        ->header('Access-Control-Allow-Headers', '*');
});

// =========================================================================
// API MAN-TIME PAINTING, SUT PART, DAN EKSPOR EXCEL SESUAI FILE ASLI PABRIK
// =========================================================================

function getProductionHistoryList()
{
    $file = storage_path('app/production_history.json');
    if (!file_exists($file))
        return [];
    return @json_decode(file_get_contents($file), true) ?: [];
}

function saveProductionHistoryList(array $history)
{
    file_put_contents(storage_path('app/production_history.json'), json_encode($history, JSON_PRETTY_PRINT));
}

/**
 * 17. API Riwayat Performance (Tab Riwayat & Machine Control)
 */
Route::match(['get', 'options'], '/api/riwayat_performance.php', function (Request $request) {
    $history = getProductionHistoryList();
    $ngLogFile = storage_path('app/ng_logs.json');
    $allNgLogs = file_exists($ngLogFile) ? (@json_decode(file_get_contents($ngLogFile), true) ?: []) : [];

    $tgl = $request->query('tanggal');

    if ($tgl) {
        $history = array_values(array_filter($history, function ($item) use ($tgl) {
            return ($item['tanggal'] ?? '') === $tgl;
        }));
    }

    $sumEff = 0;
    $count = count($history);
    foreach ($history as $h) {
        $sumEff += (float) ($h['eff'] ?? 95.0);
    }
    $avgEff = $count > 0 ? round($sumEff / $count, 1) : 0;
    $activeShift = getActiveShift();

    $formatted = array_map(function ($h) use ($activeShift, $allNgLogs) {
        $f = (int) ($h['nilai_f'] ?? 0);
        $e = (int) ($h['nilai_e'] ?? 0);
        $eff = (float) ($h['eff'] ?? 95.0);
        $tglRecord = $h['tanggal'] ?? date('Y-m-d');
        $partCode = $h['part_code'] ?? $h['part'] ?? 'PART-001';

        // Filter NG logs yang cocok untuk sesi / tanggal ini
        $sesiNgLogs = array_values(array_filter($allNgLogs, function ($log) use ($tglRecord, $partCode) {
            return ($log['tanggal'] ?? '') === $tglRecord;
        }));

        // Hitung total NG dari log jika nilai_e di sesi adalah 0 tetapi log memiliki catatan
        $logNgSum = 0;
        $defectBreakdown = [];
        foreach ($sesiNgLogs as $nl) {
            $pcs = (int) ($nl['pcs'] ?? 1);
            $type = $nl['defect_type'] ?? 'Lain-lain';
            $logNgSum += $pcs;
            $defectBreakdown[$type] = ($defectBreakdown[$type] ?? 0) + $pcs;
        }

        $effectiveE = max($e, $logNgSum);
        $totalInput = $f + $effectiveE;
        $yieldPersen = ($totalInput > 0) ? round(($f / $totalInput) * 100, 1) : 0.0;
        $defectPersen = ($totalInput > 0) ? round(($effectiveE / $totalInput) * 100, 1) : 0.0;

        return [
            'id' => (int) ($h['id'] ?? 1),
            'line_name' => $h['line_name'] ?? 'Painting',
            'shift' => $h['shift'] ?? ($activeShift['shift_name'] ?? 'Shift 1 (Pagi)'),
            'operator_name' => $h['operator_name'] ?? ($activeShift['operator_name'] ?? 'Mukhamad Nawwaf'),
            'operator_id' => $h['operator_id'] ?? ($activeShift['operator_id'] ?? 'OP-01'),
            'total_manpower' => (int) ($h['total_manpower'] ?? ($activeShift['total_manpower'] ?? 6)),
            'positions' => $h['positions'] ?? ($activeShift['positions'] ?? []),
            'part' => $partCode,
            'nomor_sesi' => (int) ($h['nomor_sesi'] ?? 1),
            'tanggal' => $tglRecord,
            'jam_mulai' => $h['jam_mulai'] ?? '06:55',
            'jam_selesai' => $h['jam_selesai'] ?? '15:00',
            'jam_disimpan' => $h['jam_selesai'] ?? date('H:i'),
            'total_input' => $totalInput,
            'B' => (int) ($h['nilai_b'] ?? 485),
            'nilai_b' => (int) ($h['nilai_b'] ?? 485),
            'C' => (float) ($h['nilai_c'] ?? 1.0),
            'C_v2' => (float) ($h['nilai_c'] ?? 1.0),
            'nilai_c' => (float) ($h['nilai_c'] ?? 1.0),
            'F' => $f,
            'nilai_f' => $f,
            'E' => $effectiveE,
            'nilai_e' => $effectiveE,
            'nilai_e_v2' => $effectiveE,
            'E_persen' => $defectPersen,
            'yield_persen' => $yieldPersen,
            'defect_persen' => $defectPersen,
            'D' => (float) ($h['nilai_d'] ?? ($f > 0 ? round(485 / $f, 2) : 0)),
            'nilai_d' => (float) ($h['nilai_d'] ?? 0),
            'A' => (int) ($h['nilai_a'] ?? 120),
            'nilai_a' => (int) ($h['nilai_a'] ?? 120),
            'nilai_a_v2' => (int) ($h['nilai_a'] ?? 120),
            'I' => (int) ($h['nilai_i'] ?? 0),
            'nilai_i' => (int) ($h['nilai_i'] ?? 0),
            'P' => (int) ($h['nilai_p'] ?? 120),
            'nilai_p' => (int) ($h['nilai_p'] ?? 120),
            'v2_tersedia' => true,
            'H_v2_persen' => $yieldPersen,
            'J_v2' => round($eff / 100, 3),
            'K_v2' => 1.0,
            'L_v2_persen' => $eff,
            'EFF_v2_persen' => $eff,
            'L_persen' => $eff,
            'eff' => $eff,
            'productivity_head_hour' => (float) ($h['productivity_head_hour'] ?? 13.2),
            'disimpan_final' => 1,
            'dibuat_pada' => $h['dibuat_pada'] ?? date('Y-m-d H:i:s'),
            'defect_breakdown' => $defectBreakdown,
            'ng_logs' => $sesiNgLogs
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
    $ngLogFile = storage_path('app/ng_logs.json');
    $ngLogs = file_exists($ngLogFile) ? (@json_decode(file_get_contents($ngLogFile), true) ?: []) : [];
    $activeShift = getActiveShift();

    // Petakan NG log per tanggal
    $ngByDate = [];
    foreach ($ngLogs as $nl) {
        $t = $nl['tanggal'] ?? date('Y-m-d');
        if (!isset($ngByDate[$t])) {
            $ngByDate[$t] = [
                'total_ng' => 0,
                'breakdown' => [],
                'logs' => []
            ];
        }
        $pcs = (int) ($nl['pcs'] ?? 1);
        $type = $nl['defect_type'] ?? 'Lain-lain';
        $ngByDate[$t]['total_ng'] += $pcs;
        $ngByDate[$t]['breakdown'][$type] = ($ngByDate[$t]['breakdown'][$type] ?? 0) + $pcs;
        $ngByDate[$t]['logs'][] = $nl;
    }

    $byDate = [];
    foreach ($history as $h) {
        $tgl = $h['tanggal'] ?? date('Y-m-d');
        if (!isset($byDate[$tgl])) {
            $byDate[$tgl] = [
                'tanggal' => $tgl,
                'input' => 0,
                'output' => 0,
                'ng' => 0,
                'eff_sum' => 0,
                'count' => 0,
                'operator_name' => $h['operator_name'] ?? ($activeShift['operator_name'] ?? 'Mukhamad Nawwaf'),
                'shift' => $h['shift'] ?? ($activeShift['shift_name'] ?? 'Shift 1'),
                'total_manpower' => (int) ($h['total_manpower'] ?? ($activeShift['total_manpower'] ?? 6)),
                'sesi_list' => []
            ];
        }
        $out = (int) ($h['nilai_f'] ?? 0);
        $ng = (int) ($h['nilai_e'] ?? 0);

        if (!empty($h['operator_name'])) {
            $byDate[$tgl]['operator_name'] = $h['operator_name'];
        }
        if (!empty($h['shift'])) {
            $byDate[$tgl]['shift'] = $h['shift'];
        }
        if (!empty($h['total_manpower'])) {
            $byDate[$tgl]['total_manpower'] = (int) $h['total_manpower'];
        }

        $byDate[$tgl]['output'] += $out;
        $byDate[$tgl]['ng'] += $ng;
        $byDate[$tgl]['eff_sum'] += (float) ($h['eff'] ?? 95);
        $byDate[$tgl]['count']++;
        $byDate[$tgl]['sesi_list'][] = $h;
    }

    // Masukkan tanggal dari ng_logs jika ada catatan NG pada tanggal tanpa sesi
    foreach ($ngByDate as $tgl => $nd) {
        if (!isset($byDate[$tgl])) {
            $byDate[$tgl] = [
                'tanggal' => $tgl,
                'input' => 0,
                'output' => 0,
                'ng' => 0,
                'eff_sum' => 0,
                'count' => 0,
                'operator_name' => $nd['logs'][0]['operator'] ?? ($activeShift['operator_name'] ?? 'Mukhamad Nawwaf'),
                'shift' => $nd['logs'][0]['shift'] ?? ($activeShift['shift_name'] ?? 'Shift 1'),
                'total_manpower' => (int) ($activeShift['total_manpower'] ?? 6),
                'sesi_list' => []
            ];
        }
    }

    $result = [];
    foreach ($byDate as $tgl => $d) {
        $avgEff = $d['count'] > 0 ? round($d['eff_sum'] / $d['count'], 1) : 0;
        $extraNg = $ngByDate[$tgl]['total_ng'] ?? 0;
        $totalNgTgl = max($d['ng'], $extraNg);
        $totalOutTgl = $d['output'];
        $totalInTgl = $totalOutTgl + $totalNgTgl;

        $yieldVal = $totalInTgl > 0 ? round(($totalOutTgl / $totalInTgl) * 100, 1) : 0.0;
        $defectRate = $totalInTgl > 0 ? round(($totalNgTgl / $totalInTgl) * 100, 1) : 0.0;

        $defectBreakdown = $ngByDate[$tgl]['breakdown'] ?? [];
        if (empty($defectBreakdown) && $totalNgTgl > 0) {
            $defectBreakdown['Uncategorized NG'] = $totalNgTgl;
        }

        $result[] = [
            'tanggal' => $tgl,
            'input' => $totalInTgl,
            'output' => $totalOutTgl,
            'ng' => $totalNgTgl,
            'defect_rate' => $defectRate . '%',
            'defect_rate_val' => $defectRate,
            'eff' => $avgEff . '%',
            'eff_persen' => $avgEff,
            'yield' => $yieldVal . '%',
            'yield_val' => $yieldVal,
            'operator_name' => $d['operator_name'],
            'shift' => $d['shift'],
            'total_manpower' => $d['total_manpower'],
            'sesi_count' => $d['count'],
            'defect_breakdown' => $defectBreakdown,
            'ng_logs' => $ngByDate[$tgl]['logs'] ?? []
        ];
    }

    usort($result, function ($a, $b) {
        return strcmp($b['tanggal'], $a['tanggal']);
    });

    $filterTgl = $request->query('tanggal');
    if ($filterTgl) {
        $result = array_values(array_filter($result, function ($item) use ($filterTgl) {
            return ($item['tanggal'] ?? '') === $filterTgl;
        }));
    }

    return response()->json($result)
        ->header('Access-Control-Allow-Origin', '*')
        ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
        ->header('Access-Control-Allow-Headers', '*');
});

/**
 * 18b. API Detail Shift Produksi (View Detail Shift)
 */
Route::match(['get', 'options'], '/api/detail_shift_json.php', function (Request $request) {
    $line = $request->query('line', 'Painting');
    $tgl = $request->query('tanggal', date('Y-m-d'));
    $shiftReq = $request->query('shift', 'Pagi');

    $history = getProductionHistoryList();
    $ngLogFile = storage_path('app/ng_logs.json');
    $allNgLogs = file_exists($ngLogFile) ? (@json_decode(file_get_contents($ngLogFile), true) ?: []) : [];
    $activeShift = getActiveShift();

    // Normalisasi shift pencarian
    $sLow = strtolower(trim($shiftReq));
    $shiftFilter = function ($hShift) use ($sLow) {
        $hs = strtolower(trim($hShift));
        if ($sLow === 'pagi' || $sLow === 'shift 1' || $sLow === '1') {
            return str_contains($hs, 'pagi') || str_contains($hs, '1');
        } elseif ($sLow === 'sore' || $sLow === 'shift 2' || $sLow === '2') {
            return str_contains($hs, 'sore') || str_contains($hs, '2');
        } elseif ($sLow === 'malam' || $sLow === 'shift 3' || $sLow === '3') {
            return str_contains($hs, 'malam') || str_contains($hs, '3');
        }
        return str_contains($hs, $sLow);
    };

    // Filter berdasarkan tanggal & shift
    $matchingRecords = array_values(array_filter($history, function ($item) use ($tgl, $shiftFilter) {
        $sameDate = ($item['tanggal'] ?? '') === $tgl;
        if (!$sameDate) return false;
        $sh = $item['shift'] ?? 'Shift 1';
        return $shiftFilter($sh);
    }));

    // Fallback: Jika pada tanggal tersebut ada record tetapi nama shiftnya tidak persis, dan user memilih shift Pagi / 1
    if (empty($matchingRecords) && ($sLow === 'pagi' || $sLow === 'shift 1' || $sLow === '1')) {
        $matchingRecords = array_values(array_filter($history, function ($item) use ($tgl) {
            return ($item['tanggal'] ?? '') === $tgl;
        }));
    }

    // Ambil NG logs untuk tanggal ini
    $matchingNgLogs = array_values(array_filter($allNgLogs, function ($log) use ($tgl, $shiftFilter) {
        if (($log['tanggal'] ?? '') !== $tgl) return false;
        $sh = $log['shift'] ?? 'Shift 1';
        return $shiftFilter($sh);
    }));

    $totalOut = 0;
    $totalNg = 0;
    $effSum = 0;
    $sesiPerformance = [];
    $daftarStop = [];

    foreach ($matchingRecords as $h) {
        $f = (int) ($h['nilai_f'] ?? 0);
        $e = (int) ($h['nilai_e'] ?? 0);
        $c = (float) ($h['nilai_c'] ?? 1.0);
        $eff = (float) ($h['eff'] ?? 95.0);

        $totalOut += $f;
        $totalNg += $e;
        $effSum += $eff;

        $sesiIn = $f + $e;
        $sesiYield = $sesiIn > 0 ? round(($f / $sesiIn) * 100, 1) : 0.0;

        $sesiPerformance[] = [
            'id' => (int) ($h['id'] ?? 1),
            'part' => $h['part_code'] ?? $h['part_name'] ?? 'Part Painting',
            'jam_mulai' => $h['jam_mulai'] ?? '06:55',
            'jam_selesai' => $h['jam_selesai'] ?? '15:00',
            'c' => $c,
            'f' => $f,
            'e' => $e,
            'total_input' => $sesiIn,
            'yield_persen' => $sesiYield,
            'h_persen' => round((float) ($h['nilai_h'] ?? 1.0) * 100, 1),
            'j_persen' => round((float) ($h['nilai_j'] ?? 0.9) * 100, 1),
            'k_persen' => round((float) ($h['nilai_k'] ?? 1.0) * 100, 1),
            'l_persen' => $eff,
            'operator_name' => $h['operator_name'] ?? ($activeShift['operator_name'] ?? 'Mukhamad Nawwaf'),
            'shift' => $h['shift'] ?? ($activeShift['shift_name'] ?? 'Shift 1 (Pagi)'),
            'total_manpower' => (int) ($h['total_manpower'] ?? 6),
            'positions' => $h['positions'] ?? []
        ];

        // Ekstrak alasan stop dari record
        if (!empty($h['alasan_stop']) && is_array($h['alasan_stop'])) {
            foreach ($h['alasan_stop'] as $st) {
                $daftarStop[] = [
                    'status' => 'stop',
                    'kategori' => $st['kategori'] ?? 'Regulated',
                    'alasan' => $st['alasan'] ?? 'Stop Produksi',
                    'durasi_menit' => (float) ($st['durasi'] ?? 15),
                    'warna_patlite' => 'Merah',
                    'waktu_mulai' => $tgl . ' ' . ($h['jam_mulai'] ?? '06:55')
                ];
            }
        }
    }

    // Jika ada catatan stop standar bila daftar stop kosong
    if (empty($daftarStop) && !empty($matchingRecords)) {
        $daftarStop[] = [
            'status' => 'stop',
            'kategori' => 'Regulated',
            'alasan' => 'Meeting / Chokai Pagi',
            'durasi_menit' => 30,
            'warna_patlite' => 'Merah',
            'waktu_mulai' => $tgl . ' 06:55'
        ];
        $daftarStop[] = [
            'status' => 'stop',
            'kategori' => 'Regulated',
            'alasan' => 'Break Time',
            'durasi_menit' => 60,
            'warna_patlite' => 'Merah',
            'waktu_mulai' => $tgl . ' 11:45'
        ];
        $daftarStop[] = [
            'status' => 'stop',
            'kategori' => 'Regulated',
            'alasan' => 'Kebersihan 5S',
            'durasi_menit' => 30,
            'warna_patlite' => 'Merah',
            'waktu_mulai' => $tgl . ' 14:30'
        ];
    }

    // Tambahkan NG manual dari ng_logs
    $logNgSum = 0;
    foreach ($matchingNgLogs as $nl) {
        $logNgSum += (int) ($nl['pcs'] ?? 1);
    }
    $totalNg = max($totalNg, $logNgSum);
    $totalIn = $totalOut + $totalNg;
    $yieldVal = $totalIn > 0 ? round(($totalOut / $totalIn) * 100, 1) : 0.0;
    $countSesi = count($matchingRecords);
    $avgEff = $countSesi > 0 ? round($effSum / $countSesi, 1) : 0.0;

    $isJumat = (date('w', strtotime($tgl)) == 5);
    $durasiShift = $isJumat ? 440 : 485;

    $picName = !empty($matchingRecords[0]['operator_name']) ? $matchingRecords[0]['operator_name'] : ($activeShift['operator_name'] ?? 'Mukhamad Nawwaf');
    $shiftName = !empty($matchingRecords[0]['shift']) ? $matchingRecords[0]['shift'] : ($activeShift['shift_name'] ?? 'Shift 1');
    $mpCount = !empty($matchingRecords[0]['total_manpower']) ? (int) $matchingRecords[0]['total_manpower'] : 6;

    return response()->json([
        'status' => 'success',
        'line' => $line,
        'tanggal' => $tgl,
        'shift' => ucfirst($shiftReq),
        'operator_name' => $picName,
        'shift_name' => $shiftName,
        'total_manpower' => $mpCount,
        'total_input' => $totalIn,
        'total_output' => $totalOut,
        'ng' => $totalNg,
        'yield' => $yieldVal,
        'eff_avg' => $avgEff,
        'durasi_shift_menit' => $durasiShift,
        'sesi_performance' => $sesiPerformance,
        'daftar_stop' => $daftarStop,
        'ng_logs' => $matchingNgLogs
    ])
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
    $cVal = (float) $request->input('ct', $request->query('ct', $activePart ? ($activePart['sut'] ?? 1.0) : 1.0));
    $fVal = (int) $request->input('f_manual', $request->query('f_manual', $stats['total_output_pcs'] ?? 0));
    $eVal = (int) $request->input('e_manual', $request->query('e_manual', 0));
    $jamMulai = $request->input('jam_mulai', $request->query('jam_mulai', '06:55'));
    $jamSelesai = $request->input('jam_selesai', $request->query('jam_selesai', date('H:i')));
    $tanggal = $request->input('tanggal', $request->query('tanggal', date('Y-m-d')));
    $isFinal = (bool) $request->input('final', $request->query('final', 0));

    // Durasi kerja (B) dalam menit
    $bMenit = (date('w', strtotime($tanggal)) == 5) ? 440 : 485;
    $stdTime = round($cVal * max(1, ($fVal + $eVal)), 2);
    $lossRegulated = 120; // Meeting 30 + Break 60 + 5S 30
    $netTime = max(1, $bMenit - $lossRegulated);
    $eff = min(100.0, round(($stdTime / $netTime) * 100, 1));
    if ($eff <= 0 && $fVal > 0)
        $eff = 95.0;
    $actualCt = $fVal > 0 ? round($bMenit / $fVal, 2) : 0;
    $productivity = round((($fVal + $eVal) / $netTime) * 60, 2);
    $activeShift = getActiveShift();

    $record = [
        'id' => time(),
        'line_name' => 'Painting',
        'tanggal' => $tanggal,
        'shift' => $activeShift['shift_name'] ?? ((date('w', strtotime($tanggal)) == 5) ? 'Shift 1 (Jumat)' : 'Shift 1'),
        'operator_name' => $activeShift['operator_name'] ?? 'Mukhamad Nawwaf',
        'operator_id' => $activeShift['operator_id'] ?? 'OP-01',
        'total_manpower' => (int) ($activeShift['total_manpower'] ?? 6),
        'positions' => $activeShift['positions'] ?? [],
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

    $cVal = (float) ($activePart['sut'] ?? 1.0);
    $totalInput = (int) ($stats['total_input_hangers'] ?? 0);
    $totalOutput = (int) ($stats['total_output_hangers'] ?? 0);
    $fVal = (int) ($stats['total_output_pcs'] ?? $totalOutput);
    $eVal = 0; // Defect / NG produk

    // Hitung durasi sesi part jika ada
    $jamMulai = $request->input('jam_mulai', $request->query('jam_mulai', ''));
    $bMenit = 0;
    if ($jamMulai) {
        $mulaiTimestamp = strtotime(date('Y-m-d') . ' ' . $jamMulai);
        if ($mulaiTimestamp && time() > $mulaiTimestamp) {
            $bMenit = round((time() - $mulaiTimestamp) / 60, 1);
        }
    }

    $stdTime = round($cVal * max(1, $fVal), 2);
    $lossRegulated = 0; // Bebas durasi
    $netTime = max(1, $bMenit);
    $eff = $fVal > 0 ? min(100.0, round(($stdTime / $netTime) * 100, 1)) : 100.0;
    $actualCt = $fVal > 0 ? round($bMenit / $fVal, 2) : 0;
    $hPersen = ($totalInput > 0 && $totalOutput > 0) ? min(100.0, round(($totalOutput / $totalInput) * 100, 1)) : 100.0;

    return response()->json([
        'status' => 'success',
        'B' => $bMenit,
        'B_shift' => $bMenit,
        'C_v2' => $cVal,
        'F_v2' => $fVal,
        'E_v2' => $eVal,
        'total_input' => $totalInput,
        'total_output' => $totalOutput,
        'H_v2_persen' => $hPersen,
        'D' => $actualCt,
        'A_v2' => 0,
        'I' => 0,
        'I_shift' => 0,
        'P' => 0,
        'P_shift' => 0,
        'H_v2' => 1.0,
        'J_v2' => round($eff / 100, 3),
        'K_v2' => 1.0,
        'L_v2' => round($eff / 100, 3),
        'EFF' => $eff . '%',
        'waktu_kerja_shift' => max(1, $bMenit),
        'waktu_minimal_shift' => 0, // Bebas durasi / tidak ada batasan minimal shift
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
