<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Helper untuk menyiapkan data umum yang dibutuhkan setiap halaman UI.
     */
    private function prepareCommonData(Request $request, string $viewNama): array
    {
        $line_aktif = 'Painting';

        $tanggal_param = $request->query('tanggal');
        $tanggal_aktif = ($tanggal_param && preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal_param))
            ? $tanggal_param
            : date('Y-m-d');

        // DAFTAR PART & CT dari config (UI standalone tanpa perlu database backend)
        $daftar_part_ct = config('daftar_part', []);

        return [
            'line_aktif' => $line_aktif,
            'view_aktif' => $viewNama,
            'tanggal_aktif' => $tanggal_aktif,
            'daftar_part_ct' => $daftar_part_ct,
        ];
    }

    private function renderPageView(string $pageBlade, array $data)
    {
        return response()->view('dashboard.index', $data)
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    /**
     * Halaman Dashboard Utama
     */
    public function dashboard(Request $request)
    {
        $data = $this->prepareCommonData($request, 'dashboard');

        return $this->renderPageView('pages.dashboard', $data);
    }

    /**
     * Halaman Machine Control
     */
    public function machineControl(Request $request)
    {
        $data = $this->prepareCommonData($request, 'machine-control');

        return $this->renderPageView('pages.machine-control', $data);
    }

    /**
     * Halaman Chart
     */
    public function chart(Request $request)
    {
        $data = $this->prepareCommonData($request, 'chart');

        return $this->renderPageView('pages.chart', $data);
    }

    /**
     * Halaman Riwayat
     */
    public function riwayat(Request $request)
    {
        $data = $this->prepareCommonData($request, 'riwayat');

        return $this->renderPageView('pages.riwayat', $data);
    }

    /**
     * Halaman Detail Harian
     */
    public function detail(Request $request)
    {
        $data = $this->prepareCommonData($request, 'detail');

        return $this->renderPageView('pages.detail', $data);
    }

    /**
     * Halaman Detail Shift
     */
    public function detailShift(Request $request)
    {
        $data = $this->prepareCommonData($request, 'detail-shift');

        return $this->renderPageView('pages.detail-shift', $data);
    }

    /**
     * Halaman Pengaturan
     */
    public function pengaturan(Request $request)
    {
        $data = $this->prepareCommonData($request, 'pengaturan');

        return $this->renderPageView('pages.pengaturan', $data);
    }

    /**
     * Halaman KWh Control
     */
    public function kwhControl(Request $request)
    {
        $data = $this->prepareCommonData($request, 'kwh-control');

        return $this->renderPageView('pages.kwh-control', $data);
    }

    /**
     * Halaman AI Vision & Konfigurasi ESP-CAM
     */
    public function aiVision(Request $request)
    {
        $data = $this->prepareCommonData($request, 'ai-vision');

        return $this->renderPageView('pages.ai-vision', $data);
    }

    /**
     * Fallback / Compatibility router untuk query param ?view=... dan /index.php
     */
    public function index(Request $request)
    {
        $view = $request->query('view', 'dashboard');

        return match ($view) {
            'machine-control' => $this->machineControl($request),
            'chart' => $this->chart($request),
            'riwayat' => $this->riwayat($request),
            'detail' => $this->detail($request),
            'detail-shift' => $this->detailShift($request),
            'pengaturan' => $this->pengaturan($request),
            'kwh-control' => $this->kwhControl($request),
            'ai-vision' => $this->aiVision($request),
            default => $this->dashboard($request),
        };
    }
}
