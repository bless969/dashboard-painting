<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="alternate icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">

    <!-- CSS Global Dashboard -->
    <link rel="stylesheet" href="{{ asset('style.css') }}?v={{ time() }}">

    <!-- Chart.js -->
    <script src="{{ asset('assets/js/chart.umd.js') }}"></script>
    <script>
        // Fallback jika asset lokal bermasalah
        if (typeof Chart === 'undefined') {
            document.write('<script src="https://cdn.jsdelivr.net/npm/chart.js"><\/script>');
        }
    </script>
    <title>@yield('title', 'Dashboard Produksi — Line Painting')</title>

</head>
<body class="line-painting" data-line="Painting">

    <!-- ===== SIDEBAR HOVER TRIGGER & EDGE TAB ===== -->
    <div class="sidebar-edge-zone" id="sidebarEdgeZone" title="Arahkan kursor ke sisi kiri untuk membuka menu"></div>
    <div class="sidebar-edge-tab" id="sidebarEdgeIndicator" onclick="toggleSidebarDrawer()" title="Klik atau arahkan kursor untuk membuka menu navigasi">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
        <span class="edge-tab-label">MENU</span>
    </div>

    <!-- ===== SIDEBAR AUTO-REVEAL DRAWER ===== -->
    <div class="sidebar sidebar-drawer" id="mainSidebar">
        <div class="logo" style="margin-bottom:14px;">
            <img src="{{ asset('assets/images/logo-gobel.png') }}" class="logo-img" alt="Logo Gobel">
            <div class="text">
                <h3>PT Panasonic Gobel Indonesia</h3>
                <span id="logo-subtitle">
                    @if($view_aktif === 'ai-vision' || $view_aktif === 'machine-control') AI Vision & Kamera — Painting
                    @elseif($view_aktif === 'pengaturan') Pengaturan — Painting
                    @elseif($view_aktif === 'kwh-control') KWh Meter — Line Painting
                    @elseif($view_aktif === 'chart') Chart — Painting
                    @elseif(in_array($view_aktif, ['riwayat', 'detail', 'detail-shift'])) Riwayat — Painting
                    @else Dashboard Painting
                    @endif
                </span>
            </div>
        </div>

        <!-- ===== KARTU OPERATOR PIC & SHIFT (SIDEBAR) ===== -->
        <div class="sidebar-operator-card" onclick="bukaModalGantiShift()" title="Klik untuk Ganti PIC Operator / Serah Terima Shift" style="background:rgba(255,255,255,0.08); border:1px solid rgba(255,255,255,0.18); border-radius:10px; padding:10px 12px; margin-bottom:18px; cursor:pointer; transition:all 0.2s ease;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:5px;">
                <div style="display:flex; align-items:center; gap:5px; font-size:10px; font-weight:800; color:#93c5fd; text-transform:uppercase; letter-spacing:0.8px;">
                    <span style="display:inline-block; width:6px; height:6px; border-radius:50%; background:#22c55e; box-shadow:0 0 6px #22c55e;"></span>
                    <span>Operator PIC</span>
                </div>
                <span style="font-size:10px; color:#bfdbfe; background:rgba(255,255,255,0.12); padding:1px 6px; border-radius:4px; font-weight:700;">Ganti ⇄</span>
            </div>
            <div style="display:flex; align-items:center; gap:7px; margin-bottom:6px;">
                <div style="width:24px; height:24px; border-radius:6px; background:rgba(255,255,255,0.15); display:flex; align-items:center; justify-content:center; font-size:12px; flex-shrink:0;">
                    👤
                </div>
                <div id="sidebar-operator-name" style="font-size:13px; font-weight:800; color:#ffffff; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                    Mukhamad Nawwaf
                </div>
            </div>
            <div style="display:flex; align-items:center; gap:6px; flex-wrap:wrap;">
                <span id="sidebar-shift-badge" style="background:rgba(99,102,241,0.3); border:1px solid rgba(165,180,252,0.35); color:#e0e7ff; padding:2px 7px; border-radius:4px; font-size:10.5px; font-weight:700;">
                    Shift 1 (Pagi)
                </span>
                <span id="sidebar-mp-badge" style="background:rgba(255,255,255,0.1); color:#cbd5e1; padding:2px 6px; border-radius:4px; font-size:10.5px; font-weight:600;">
                    6 MP
                </span>
            </div>
        </div>

        <div class="nav-section-title">Menu Painting</div>
        <a class="nav-item {{ ($view_aktif === 'dashboard') ? 'active' : '' }}"
           data-view="dashboard"
           href="{{ route('dashboard', ['line' => 'Painting']) }}"
           onclick="return navigateSidebar(event, 'dashboard', 'Painting')">
            <span class="icon-nav"><svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg></span>
            <span class="nav-line-label">Dashboard</span>
        </a>
        <a class="nav-item {{ ($view_aktif === 'ai-vision') ? 'active' : '' }}"
           data-view="ai-vision"
           href="{{ route('ai-vision', ['line' => 'Painting']) }}"
           onclick="return navigateSidebar(event, 'ai-vision', 'Painting')">
            <span class="icon-nav"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg></span>
            <span class="nav-line-label">AI Vision &amp; Kamera</span>
        </a>
        <a class="nav-item {{ ($view_aktif === 'chart') ? 'active' : '' }}"
           data-view="chart"
           href="{{ route('chart', ['line' => 'Painting']) }}"
           onclick="return navigateSidebar(event, 'chart', 'Painting')">
            <span class="icon-nav"><svg viewBox="0 0 24 24"><path d="M3 3v18h18"/><path d="M18 17V9"/><path d="M13 17V5"/><path d="M8 17v-3"/></svg></span>
            <span class="nav-line-label">Chart</span>
        </a>
        <a class="nav-item {{ in_array($view_aktif, ['riwayat', 'detail', 'detail-shift']) ? 'active' : '' }}"
           data-view="riwayat"
           href="{{ route('riwayat', ['line' => 'Painting']) }}"
           onclick="return navigateSidebar(event, 'riwayat', 'Painting')">
            <span class="icon-nav"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></span>
            <span class="nav-line-label">Riwayat</span>
        </a>

        <div class="nav-section-title">Lainnya</div>
        <a class="nav-item {{ ($view_aktif === 'pengaturan') ? 'active' : '' }}"
           data-view="pengaturan"
           href="{{ route('pengaturan', ['line' => 'Painting']) }}"
           onclick="return navigateSidebarPengaturan(event)">
            <span class="icon-nav"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg></span>
            <span class="nav-line-label">Pengaturan</span>
        </a>
        <div class="sidebar-footer">
            <div class="sidebar-status">
                <span class="pulse-dot"></span> Line Painting Aktif
            </div>
        </div>
    </div>

    <!-- ===== MAIN CONTENT ===== -->
    <div class="main">
        <div class="topbar topbar-standalone">
            <!-- SISI KIRI: Card Menu Navigasi & Judul Halaman yang Rapi & Terintegrasi -->
            <div class="topbar-title-card">
                <button type="button" class="btn-sidebar-toggle" id="btnSidebarToggle" onclick="toggleSidebarDrawer()" title="Buka/Tutup Menu Navigasi (atau arahkan kursor ke tepi kiri layar)">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
                    <span>Menu</span>
                </button>
                <div class="topbar-card-divider"></div>
                <div class="topbar-title-content">
                    <h1 id="page-title">
                        @if($view_aktif === 'dashboard') Dashboard — Line Painting
                        @elseif($view_aktif === 'ai-vision' || $view_aktif === 'machine-control') AI Vision & Kamera — Line Painting
                        @elseif($view_aktif === 'chart') Chart — Line Painting
                        @elseif($view_aktif === 'riwayat') Riwayat — Line Painting
                        @elseif($view_aktif === 'detail') Detail Harian — Line Painting
                        @elseif($view_aktif === 'detail-shift') Detail Shift — Line Painting
                        @elseif($view_aktif === 'pengaturan') Pengaturan — Line Painting
                        @elseif($view_aktif === 'kwh-control') KWh Meter — Line Painting
                        @else Dashboard — Line Painting
                        @endif
                    </h1>
                    <p id="greeting-clock">-</p>
                </div>
            </div>

            <!-- SISI KANAN: Logo Gobel Solid & Tombol Fullscreen, serta Tombol Aksi di Bawahnya -->
            <div class="topbar-actions-column">
                <div class="topbar-actions-top">
                    <!-- Tombol Cepat Buka/Tutup Monitor Kamera ESP-CAM Live -->
                    <button type="button" class="btn-topbar-camera" id="btnTopbarCamera" onclick="toggleCameraPopup()" title="Buka / Sembunyikan Monitor Kamera ESP-CAM Live (Dapat Digeser & Diperbesar)">
                        <span class="pulse-dot-cam"></span>
                        <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.2">
                            <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/>
                            <circle cx="12" cy="13" r="4"/>
                        </svg>
                        <span>Kamera Live</span>
                    </button>

                    <div class="topbar-brand-badge-solid" title="PT Panasonic Gobel Indonesia — Painting Smart Factory">
                        <img src="{{ asset('assets/images/logo-gobel.png') }}" class="topbar-logo-solid-img" alt="Logo Gobel">
                        <div class="topbar-brand-solid-text">
                            <span class="brand-solid-company">PT PANASONIC GOBEL INDONESIA</span>
                            <span class="brand-solid-sub"><span class="pulse-dot-brand"></span> Painting Smart Factory</span>
                        </div>
                    </div>

                    <button id="btnFullscreen" class="btn-fullscreen" onclick="toggleFullscreen()" title="Fullscreen">
                        <span id="fs-icon"><svg viewBox="0 0 24 24"><path d="M8 3H5a2 2 0 0 0-2 2v3"/><path d="M21 8V5a2 2 0 0 0-2-2h-3"/><path d="M3 16v3a2 2 0 0 0 2 2h3"/><path d="M16 21h3a2 2 0 0 0 2-2v-3"/></svg></span>
                    </button>
                </div>

                <!-- Tombol aksi (Kembali ke Dashboard / Download Excel) ditaruh di bawah Logo agar rapi -->
                <div id="action-buttons" class="topbar-actions-bottom">
                    @if(in_array($view_aktif, ['detail', 'detail-shift']))
                        <a href="{{ route('riwayat', ['line' => 'Painting']) }}" class="btn-nav-action btn-light" data-nav data-line="Painting" data-view="riwayat">&larr; Kembali ke Riwayat</a>
                        @if($view_aktif === 'detail')
                            <a href="{{ url('export/export_excel.php?line=Painting&tanggal=' . ($tanggal ?? date('Y-m-d'))) }}" class="btn-nav-action btn-green">&#8681; Download Excel</a>
                        @endif
                    @elseif(in_array($view_aktif, ['machine-control', 'chart', 'kwh-control', 'ai-vision']))
                        <a href="{{ route('dashboard', ['line' => 'Painting']) }}" class="btn-nav-action btn-light" data-nav data-line="Painting" data-view="dashboard">&larr; Kembali ke Dashboard</a>
                    @elseif($view_aktif === 'riwayat')
                        <a href="{{ route('dashboard', ['line' => 'Painting']) }}" class="btn-nav-action btn-light" data-nav data-line="Painting" data-view="dashboard">&larr; Kembali ke Dashboard</a>
                        <button type="button" class="btn-nav-action btn-green" onclick="bukaModalExportExcel()">&#8681; Download Histori (Excel)</button>
                    @endif
                </div>
            </div>
        </div>

        @yield('content')
    </div>

    <!-- ===== FLOATING & DRAGGABLE CAMERA POPUP (ESP-CAM) ===== -->
    @include('partials.camera-popup')

    <!-- ===== MODALS & TOAST ===== -->
    @include('modals.alasan-stop')
    @include('modals.toast-standby')
    @include('modals.pilih-c')
    @include('modals.keterangan-durasi')
    @include('modals.detail-performance')
    @include('modals.detail-ct')
    @include('modals.manage-part')
    @include('modals.pilih-shift')
    @include('modals.export-excel')
    @include('modals.ganti-shift')
    @include('modals.input-ng')

    <!-- ===== JAVASCRIPT GLOBAL & CORE SCRIPTS ===== -->
    @include('layouts.scripts')
    @stack('scripts')

</body>
</html>
