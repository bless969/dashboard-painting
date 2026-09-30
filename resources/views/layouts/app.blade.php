<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Preload font lokal offline (Super cepat, 0ms latency, tanpa butuh internet di PC Server) -->
    <link rel="preload" href="{{ asset('assets/fonts/Inter-Variable.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="{{ asset('style.css') }}?v={{ @filemtime(public_path('style.css')) ?: time() }}">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
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
        <div class="logo">
            <img src="{{ asset('assets/images/logo-gobel.png') }}" class="logo-img" alt="Logo Gobel">
            <div class="text">
                <h3>PT Panasonic Gobel Indonesia</h3>
                <span id="logo-subtitle">
                    @if($view_aktif === 'machine-control') Machine Control — Painting
                    @elseif($view_aktif === 'pengaturan') Pengaturan — Painting
                    @elseif($view_aktif === 'kwh-control') KWh Meter — Painting
                    @elseif($view_aktif === 'chart') Chart — Painting
                    @elseif(in_array($view_aktif, ['riwayat', 'detail', 'detail-shift'])) Riwayat — Painting
                    @else Dashboard Painting
                    @endif
                </span>
            </div>
        </div>

        <div class="nav-section-title">Menu Utama</div>
        <a class="nav-item {{ ($view_aktif === 'dashboard') ? 'active' : '' }}"
           data-view="dashboard"
           href="{{ route('dashboard', ['line' => 'Painting']) }}"
           onclick="return navigateSidebar(event, 'dashboard', 'Painting')">
            <span class="icon-nav"><svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg></span>
            <span class="nav-line-label">Dashboard</span>
        </a>
        <a class="nav-item {{ ($view_aktif === 'machine-control') ? 'active' : '' }}"
           data-view="machine-control"
           href="{{ route('machine-control', ['line' => 'Painting']) }}"
           onclick="return navigateSidebar(event, 'machine-control', 'Painting')">
            <span class="icon-nav"><svg viewBox="0 0 24 24"><rect x="2" y="7" width="14" height="6" rx="1"/><path d="M9 13v4"/><rect x="7" y="17" width="4" height="4" rx="1"/><path d="M16 9h4a2 2 0 0 1 2 2v3"/></svg></span>
            <span class="nav-line-label">Machine Control</span>
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
        <a class="nav-item {{ ($view_aktif === 'kwh-control') ? 'active' : '' }}"
           data-view="kwh-control"
           href="{{ route('kwh-control', ['line' => 'Painting']) }}"
           onclick="return navigateSidebar(event, 'kwh-control', 'Painting')">
            <span class="icon-nav"><svg viewBox="0 0 24 24"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg></span>
            <span class="nav-line-label">KWh Meter (Painting)</span>
        </a>

        <div class="nav-section-title">Lainnya</div>
        <a class="nav-item {{ ($view_aktif === 'pengaturan') ? 'active' : '' }}"
           data-view="pengaturan"
           href="{{ route('pengaturan', ['line' => 'Painting']) }}"
           onclick="return navigateSidebarPengaturan(event)">
            <span class="icon-nav"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg></span>
            <span class="nav-line-label">Pengaturan &amp; Kamera</span>
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
                        @elseif($view_aktif === 'machine-control') Machine Control — Line Painting
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

            <!-- SISI KANAN: Tombol Kamera Live, Logo Gobel Solid & Tombol Fullscreen, serta Tombol Aksi di Bawahnya -->
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
                    @elseif(in_array($view_aktif, ['machine-control', 'chart', 'kwh-control']))
                        <a href="{{ route('dashboard', ['line' => 'Painting']) }}" class="btn-nav-action btn-light" data-nav data-line="Painting" data-view="dashboard">&larr; Kembali ke Dashboard</a>
                    @elseif($view_aktif === 'riwayat')
                        <a href="{{ route('dashboard', ['line' => 'Painting']) }}" class="btn-nav-action btn-light" data-nav data-line="Painting" data-view="dashboard">&larr; Kembali ke Dashboard</a>
                        <a href="{{ url('export/export_excel.php?line=Painting') }}" class="btn-nav-action btn-green">&#8681; Download Histori (Excel)</a>
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

    <!-- ===== JAVASCRIPT GLOBAL & CORE SCRIPTS ===== -->
    @include('layouts.scripts')
    @stack('scripts')

</body>
</html>
