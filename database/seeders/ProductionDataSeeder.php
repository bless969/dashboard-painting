<?php

namespace Database\Seeders;

use App\Models\HangerInput;
use App\Models\HangerOutput;
use App\Models\ProductionSummary;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductionDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Bersihkan data sebelumnya jika ada
        HangerInput::truncate();
        HangerOutput::truncate();
        ProductionSummary::truncate();

        $parts = [
            ['code' => 'NL78270NIP', 'name' => 'Part NL78270NIP', 'target' => 6],
            ['code' => 'M2SPCC0001IP', 'name' => 'Part M2SPCC0001IP', 'target' => 4],
            ['code' => 'NLP722106IP', 'name' => 'Part NLP722106IP', 'target' => 6],
            ['code' => 'NNP526001SIP', 'name' => 'Part NNP526001SIP', 'target' => 4],
            ['code' => 'L2SLHX0402AIP', 'name' => 'Part L2SLHX0402AIP', 'target' => 2],
        ];

        $today = Carbon::today();
        $startTime = $today->copy()->setHour(7)->setMinute(0)->setSecond(0);

        $totalInput = 50;
        $totalOutput = 45; // 5 hanger masih dalam proses (WIP) di jalur conveyor
        $totalPcs = 0;
        $totalEmpty = 0;

        $inputRecords = [];
        $outputRecords = [];

        for ($i = 1; $i <= $totalInput; $i++) {
            $part = $parts[($i - 1) % count($parts)];
            $enteredAt = $startTime->copy()->addSeconds(($i - 1) * 90); // Rata-rata 1 hanger per 90 detik

            $inputRecords[] = [
                'id' => $i,
                'hanger_number' => $i,
                'part_code' => $part['code'],
                'part_name' => $part['name'],
                'sensor_pin' => 25,
                'wip_at_time' => min($i, 5),
                'status' => ($i <= $totalOutput) ? 'completed' : 'in_line',
                'entered_at' => $enteredAt,
                'created_at' => $enteredAt,
                'updated_at' => $enteredAt,
            ];

            // Jika hanger sudah tiba di outlet (kamera / sensor 27) setelah durasi oven/conveyor ~15 menit
            if ($i <= $totalOutput) {
                $exitedAt = $enteredAt->copy()->addMinutes(15);
                
                // Variasi hanger kosong vs hanger isi
                $isEmpty = ($i % 15 === 0); // Hanger ke-15, 30, 45 sebagai hanger kosong
                $detectedPcs = $isEmpty ? 0 : $part['target'];
                
                if ($isEmpty) {
                    $totalEmpty++;
                } else {
                    $totalPcs += $detectedPcs;
                }

                $outputRecords[] = [
                    'id' => $i,
                    'hanger_number' => $i,
                    'part_code' => $part['code'],
                    'part_name' => $part['name'],
                    'target_pcs' => $part['target'],
                    'detected_pcs' => $detectedPcs,
                    'is_empty' => $isEmpty,
                    'sensor_pin' => 27,
                    'confidence' => $isEmpty ? null : round(0.92 + (($i % 7) * 0.01), 2),
                    'image_path' => null,
                    'exited_at' => $exitedAt,
                    'created_at' => $exitedAt,
                    'updated_at' => $exitedAt,
                ];
            }
        }

        // Simpan bulk data input & output
        HangerInput::insert($inputRecords);
        HangerOutput::insert($outputRecords);

        // 2. Buat Ringkasan Produksi (Production Summary)
        // Hari ini
        ProductionSummary::create([
            'date' => $today->toDateString(),
            'shift' => 'Shift 1',
            'total_input_hangers' => $totalInput,
            'total_output_hangers' => $totalOutput,
            'total_output_pcs' => $totalPcs,
            'total_empty_hangers' => $totalEmpty,
            'wip_hangers' => $totalInput - $totalOutput,
            'is_active' => true,
        ]);

        // Kemarin (Data histori lampau)
        ProductionSummary::create([
            'date' => $today->copy()->subDay()->toDateString(),
            'shift' => 'Shift 1',
            'total_input_hangers' => 240,
            'total_output_hangers' => 240,
            'total_output_pcs' => 960,
            'total_empty_hangers' => 8,
            'wip_hangers' => 0,
            'is_active' => false,
        ]);

        // 2 hari lalu
        ProductionSummary::create([
            'date' => $today->copy()->subDays(2)->toDateString(),
            'shift' => 'Shift 1',
            'total_input_hangers' => 235,
            'total_output_hangers' => 235,
            'total_output_pcs' => 940,
            'total_empty_hangers' => 6,
            'wip_hangers' => 0,
            'is_active' => false,
        ]);

        // 3. Sinkronkan juga file statistik lokal hanger_stats.json
        $statsFile = storage_path('app/hanger_stats.json');
        $liveStats = [
            'total_input_hangers' => $totalInput,
            'total_output_hangers' => $totalOutput,
            'wip_hangers' => $totalInput - $totalOutput,
            'total_output_pcs' => $totalPcs,
            'empty_hangers' => $totalEmpty,
            'is_produksi' => 1,
            'last_updated' => Carbon::now()->toIso8601String(),
        ];
        file_put_contents($statsFile, json_encode($liveStats, JSON_PRETTY_PRINT), LOCK_EX);

        $this->command->info("✓ Berhasil melakukan seeding database:");
        $this->command->info("  - Hanger Inputs: {$totalInput} record");
        $this->command->info("  - Hanger Outputs: {$totalOutput} record (Total PCS: {$totalPcs}, Empty: {$totalEmpty})");
        $this->command->info("  - Production Summaries: 3 hari");
    }
}

