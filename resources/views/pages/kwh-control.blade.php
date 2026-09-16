<div id="view-kwh-control" style="{{ $view_aktif !== 'kwh-control' ? 'display:none;' : '' }}">
    <!-- Topbar Info Header -->
    <div class="kwh-topbar-header">
        <div class="kwh-topbar-title">
            <span class="kwh-topbar-icon">⚡</span>
            <div>
                <h2 class="kwh-main-title">KWh Control — Line 3</h2>
                <p class="kwh-sub-title">Monitoring Konsumsi Energi Listrik &amp; Karakteristik Daya Real-Time</p>
            </div>
        </div>
        <div class="kwh-topbar-actions">
            <div class="kwh-date-picker-box">
                <label for="kwh-filter-tanggal" class="kwh-date-label">Pilih Tanggal:</label>
                <input type="date" id="kwh-filter-tanggal" class="kwh-date-input" value="{{ date('Y-m-d') }}" onchange="loadKwhControlData(this.value)">
            </div>
            <div class="kwh-sync-box">
                <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
                <span class="kwh-sync-indicator" id="kwh-last-updated">Memuat data...</span>
            </div>
            <button type="button" class="btn-refresh-kwh" onclick="loadKwhControlData()" title="Segarkan data KWh">
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path d="M23 4v6h-6M1 20v-6h6"></path>
                    <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
                </svg>
                Refresh
            </button>
        </div>
    </div>

    <!-- ===== ROW 1: 3 KARTU AWAL PERSIS SESUAI KWH ASLI ===== -->
    <div class="kwh-dashboard-grid">
        <!-- 1. Card 1: Line 3 Live Metrics -->
        <div class="kwh-card kwh-card-metrics">
            <div class="kwh-card-header">
                <h3 class="kwh-card-title">Line 3</h3>
                <span class="kwh-status-badge on" id="kwh-status-badge">
                    <span class="kwh-status-dot"></span>
                    <span id="kwh-status-text">ON</span>
                </span>
            </div>

            <div class="kwh-metrics-layout">
                <!-- Baris 1: Voltage, Current, Power -->
                <div class="kwh-metric-box">
                    <div class="kwh-metric-label">VOLTAGE</div>
                    <div class="kwh-metric-value" id="kwh-val-voltage">389 <span class="kwh-unit">V</span></div>
                </div>
                <div class="kwh-metric-box">
                    <div class="kwh-metric-label">CURRENT</div>
                    <div class="kwh-metric-value" id="kwh-val-current">26 <span class="kwh-unit">A</span></div>
                </div>
                <div class="kwh-metric-box">
                    <div class="kwh-metric-label">POWER</div>
                    <div class="kwh-metric-value" id="kwh-val-power">4.4 <span class="kwh-unit">kW</span></div>
                </div>

                <!-- Baris 2: Energy, Frequency -->
                <div class="kwh-metric-box">
                    <div class="kwh-metric-label">ENERGY</div>
                    <div class="kwh-metric-value" id="kwh-val-energy">13.3 <span class="kwh-unit">kWh</span></div>
                </div>
                <div class="kwh-metric-box">
                    <div class="kwh-metric-label">FREQUENCY</div>
                    <div class="kwh-metric-value" id="kwh-val-frequency">50 <span class="kwh-unit">Hz</span></div>
                </div>

                <!-- Baris 3: Power Factor, CO2 -->
                <div class="kwh-metric-box">
                    <div class="kwh-metric-label">POWER FACTOR</div>
                    <div class="kwh-metric-value" id="kwh-val-pf">0.77</div>
                </div>
                <div class="kwh-metric-box">
                    <div class="kwh-metric-label">CO2</div>
                    <div class="kwh-metric-value" id="kwh-val-co2">5.5 <span class="kwh-unit">kg</span></div>
                </div>
            </div>
        </div>

        <!-- 2. Card 2: Daily Line 3 Consumption (Bar Chart) -->
        <div class="kwh-card kwh-card-chart">
            <div class="kwh-card-header">
                <h3 class="kwh-card-title">Daily Line 3 Consumption</h3>
                <div class="kwh-date-badge" id="kwh-consumption-tgl-label">{{ date('d/m/Y') }}</div>
            </div>
            <div class="kwh-card-legend-center">
                <span class="kwh-legend-indicator blue-box"></span>
                <span class="kwh-legend-text">kWh / hour</span>
            </div>
            <div class="kwh-chart-wrapper">
                <canvas id="chart-kwh-consumption"></canvas>
            </div>
        </div>

        <!-- 3. Card 3: Daily Current Line 3 (Line Chart) -->
        <div class="kwh-card kwh-card-chart">
            <div class="kwh-card-header">
                <h3 class="kwh-card-title">Daily Current Line 3</h3>
                <div class="kwh-date-badge" id="kwh-current-tgl-label">{{ date('d/m/Y') }}</div>
            </div>
            <div class="kwh-card-legend-center">
                <span class="kwh-legend-indicator blue-box"></span>
                <span class="kwh-legend-text">Current / hour</span>
            </div>
            <div class="kwh-chart-wrapper">
                <canvas id="chart-kwh-current"></canvas>
            </div>
        </div>
    </div>

    <!-- ===== ROW 2: PCB PRODUCTION CONSUMPTION TOTAL (DAILY & MONTHLY) ===== -->
    <div class="kwh-section-title-group">
        <h4 class="kwh-section-heading">PCB Production Consumption Total — Line 3</h4>
    </div>
    <div class="kwh-charts-grid">
        <!-- Daily PCB Production Consumption Total -->
        <div class="kwh-card kwh-card-chart">
            <div class="kwh-card-header">
                <h3 class="kwh-card-title">Daily PCB Production Consumption Total</h3>
                <div class="kwh-date-badge" id="kwh-badge-daily-consumption">{{ date('d/m/Y') }}</div>
            </div>
            <div class="kwh-card-legend-center">
                <span class="kwh-legend-indicator blue-box"></span>
                <span class="kwh-legend-text">kWh Total / hour</span>
            </div>
            <div class="kwh-chart-wrapper">
                <canvas id="chart-daily-consumption"></canvas>
            </div>
        </div>

        <!-- Monthly PCB Production Consumption Total -->
        <div class="kwh-card kwh-card-chart">
            <div class="kwh-card-header">
                <h3 class="kwh-card-title">Monthly PCB Production Consumption Total</h3>
                <div class="kwh-date-badge" id="kwh-badge-monthly-consumption">{{ date('d/m/Y') }}</div>
            </div>
            <div class="kwh-card-legend-center">
                <span class="kwh-legend-indicator blue-box"></span>
                <span class="kwh-legend-text">kWh Total / day</span>
            </div>
            <div class="kwh-chart-wrapper">
                <canvas id="chart-monthly-consumption"></canvas>
            </div>
        </div>
    </div>

    <!-- ===== ROW 3: PCB PRODUCTION ENERGY COST TOTAL (DAILY & MONTHLY) ===== -->
    <div class="kwh-section-title-group">
        <h4 class="kwh-section-heading">PCB Production Energy Cost Total — Line 3</h4>
    </div>
    <div class="kwh-charts-grid">
        <!-- Daily PCB Production Energy Cost Total -->
        <div class="kwh-card kwh-card-chart">
            <div class="kwh-card-header">
                <h3 class="kwh-card-title">Daily PCB Production Energy Cost Total</h3>
                <div class="kwh-date-badge" id="kwh-badge-daily-cost">{{ date('d/m/Y') }}</div>
            </div>
            <div class="kwh-card-legend-center">
                <span class="kwh-legend-indicator orange-box"></span>
                <span class="kwh-legend-text" id="kwh-legend-peak">Beban Puncak ({{ date('d/m/Y') }})</span>
                <span class="kwh-legend-gap"></span>
                <span class="kwh-legend-indicator green-box"></span>
                <span class="kwh-legend-text" id="kwh-legend-offpeak">Luar Beban Puncak ({{ date('d/m/Y') }})</span>
            </div>
            <div class="kwh-chart-wrapper">
                <canvas id="chart-daily-cost"></canvas>
            </div>
        </div>

        <!-- Monthly PCB Production Energy Cost Total -->
        <div class="kwh-card kwh-card-chart">
            <div class="kwh-card-header">
                <h3 class="kwh-card-title">Monthly PCB Production Energy Cost Total</h3>
                <div class="kwh-date-badge" id="kwh-badge-monthly-cost">{{ date('d/m/Y') }}</div>
            </div>
            <div class="kwh-card-legend-center">
                <span class="kwh-legend-indicator green-box"></span>
                <span class="kwh-legend-text">Total Harga / day</span>
            </div>
            <div class="kwh-chart-wrapper">
                <canvas id="chart-monthly-cost"></canvas>
            </div>
        </div>
    </div>
</div>
