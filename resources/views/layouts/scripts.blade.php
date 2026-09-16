<script>
        // ===== DYNAMIC BASE URL (Mendukung Subfolder Laragon /dashboard-painting maupun Localhost) =====
        const APP_BASE_PATH = (function() {
            const p = window.location.pathname;
            if (p.startsWith('/dashboard-painting')) {
                return '/dashboard-painting';
            }
            if (p.startsWith('/dashboard-pcb')) {
                return '/dashboard-pcb';
            }
            const b = "{{ rtrim(request()->getBaseUrl(), '/') }}";
            return (b && b !== '/') ? b : '';
        })();
        const APP_BASE_URL = (function() {
            return window.location.origin + APP_BASE_PATH;
        })();

        function apiUrl(endpoint) {
            if (!endpoint || /^https?:\/\//i.test(endpoint)) return endpoint;
            const clean = endpoint.replace(/^\/+/, '');
            return `${APP_BASE_URL}/${clean}`;
        }

        // Interceptor fetch transparan agar seluruh pemanggilan api/... dan export/... otomatis diarahkan ke base subfolder yang tepat
        const _nativeFetch = window.fetch;
        window.fetch = function(resource, init) {
            if (typeof resource === 'string') {
                if (resource.startsWith('api/') || resource.startsWith('/api/') ||
                    resource.startsWith('export/') || resource.startsWith('/export/')) {
                    resource = apiUrl(resource);
                }
            }
            return _nativeFetch.call(this, resource, init);
        };

        // ===== STATE GLOBAL =====
        let currentLine = "<?= htmlspecialchars($line_aktif) ?>";
        let lastRenderedLine = currentLine;
        let currentView = "<?= htmlspecialchars($view_aktif) ?>";
        let currentTanggal = "<?= htmlspecialchars($tanggal_aktif) ?>";
        let DAFTAR_PART_CT = {!! json_encode($daftar_part_ct) !!};


        // State untuk view Detail Shift -- diisi saat "Lihat Detail" diklik dari tabel Riwayat
        // Produksi (lihat renderTabelHistori / tombol btn-detail-shift), lalu dibaca oleh
        // renderView() saat memanggil loadDetailShift().
        const _urlParams = new URLSearchParams(window.location.search);
        let detailShiftParams = {
            tanggal: _urlParams.get('tanggal') || currentTanggal,
            shift: _urlParams.get('shift') || 'Pagi'
        };
        let alasanStopSudahDitampilkanUntuk = null; // lacak "updated_at" event stop supaya modal alasan tidak muncul berulang
        let standbyKedipSudahDitampilkanUntuk = null; // sama, tapi untuk toast notifikasi standby_kedip
        let timerAutoTutupToastStandbyKedip = null;
        let warnaPatliteStopAktif = 'Merah'; // warna lampu Patlite saat mesin berhenti

        function renderBadgePatlite(warna) {
            const w = (warna || 'Merah').toLowerCase().trim();
            if (w.includes('kedip')) {
                return `<span class="badge-patlite badge-patlite-kuning-kedip" style="display:inline-flex; align-items:center; gap:5px; background:#fef9c3; color:#854d0e; border:1px solid #fef08a; padding:2px 8px; border-radius:6px; font-weight:600; font-size:11.5px;"><span style="display:inline-block; width:8px; height:8px; border-radius:50%; background:#ca8a04; animation:lampKedip 1s infinite;"></span> ${escapeHtml(warna || 'Kuning Kedip')}</span>`;
            } else if (w.includes('kuning')) {
                return `<span class="badge-patlite badge-patlite-kuning" style="display:inline-flex; align-items:center; gap:5px; background:#fef9c3; color:#854d0e; border:1px solid #fef08a; padding:2px 8px; border-radius:6px; font-weight:600; font-size:11.5px;"><span style="display:inline-block; width:8px; height:8px; border-radius:50%; background:#eab308;"></span> ${escapeHtml(warna || 'Kuning')}</span>`;
            } else if (w.includes('maintenance')) {
                return `<span class="badge-patlite badge-patlite-maintenance" style="display:inline-flex; align-items:center; gap:5px; background:#fee2e2; color:#991b1b; border:1px solid #fecaca; padding:2px 8px; border-radius:6px; font-weight:600; font-size:11.5px;"><span style="display:inline-block; width:8px; height:8px; border-radius:50%; background:#dc2626;"></span> ${escapeHtml(warna || 'Merah (Maintenance)')}</span>`;
            } else if (w.includes('mati') || w.includes('off')) {
                return `<span class="badge-patlite badge-patlite-mati" style="display:inline-flex; align-items:center; gap:5px; background:#f1f5f9; color:#475569; border:1px solid #e2e8f0; padding:2px 8px; border-radius:6px; font-weight:600; font-size:11.5px;"><span style="display:inline-block; width:8px; height:8px; border-radius:50%; background:#64748b;"></span> ${escapeHtml(warna || 'Mati / OFF')}</span>`;
            } else if (w.includes('hijau')) {
                return `<span class="badge-patlite badge-patlite-hijau" style="display:inline-flex; align-items:center; gap:5px; background:#dcfce7; color:#166534; border:1px solid #bbf7d0; padding:2px 8px; border-radius:6px; font-weight:600; font-size:11.5px;"><span style="display:inline-block; width:8px; height:8px; border-radius:50%; background:#22c55e;"></span> ${escapeHtml(warna || 'Hijau')}</span>`;
            } else {
                return `<span class="badge-patlite badge-patlite-merah" style="display:inline-flex; align-items:center; gap:5px; background:#fee2e2; color:#991b1b; border:1px solid #fecaca; padding:2px 8px; border-radius:6px; font-weight:600; font-size:11.5px;"><span style="display:inline-block; width:8px; height:8px; border-radius:50%; background:#ef4444;"></span> ${escapeHtml(warna || 'Merah')}</span>`;
            }
        }

        // Dipakai buat panggilan internal ke endpoint yang butuh api_key (mis. set_part.php)
        // dari halaman web ini sendiri. Nilainya sama dengan $kunci_rahasia di sisi PHP.
        const API_KEY_INTERNAL = "pcb101_rahasia_2026";

        // Format tanggal lokal (YYYY-MM-DD) yang aman dari pergeseran UTC
        function tanggalLokalYYYYMMDD() {
            const d = new Date();
            const pad = n => String(n).padStart(2, '0');
            return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
        }

        // ===== IKON FULLSCREEN =====
        const ikonExpand = '<svg viewBox="0 0 24 24"><path d="M8 3H5a2 2 0 0 0-2 2v3"/><path d="M21 8V5a2 2 0 0 0-2-2h-3"/><path d="M3 16v3a2 2 0 0 0 2 2h3"/><path d="M16 21h3a2 2 0 0 0 2-2v-3"/></svg>';
        const ikonCompress = '<svg viewBox="0 0 24 24"><path d="M8 3v3a2 2 0 0 1-2 2H3"/><path d="M21 8h-3a2 2 0 0 1-2-2V3"/><path d="M3 16h3a2 2 0 0 1 2 2v3"/><path d="M16 21v-3a2 2 0 0 1 2-2h3"/></svg>';

        function toggleFullscreen() {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen().catch(err => {
                    console.log("Gagal masuk fullscreen: " + err.message);
                });
            } else {
                document.exitFullscreen();
            }
        }

        document.addEventListener('fullscreenchange', () => {
            const iconEl = document.getElementById('fs-icon');
            iconEl.innerHTML = document.fullscreenElement ? ikonCompress : ikonExpand;
        });

        // ===== GREETING & JAM =====
        const namaHari = ["Minggu", "Senin", "Selasa", "Rabu", "Kamis", "Jumat", "Sabtu"];
        const namaBulan = ["Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"];

        function formatTanggalIndo(tanggalString) {
            const bagian = tanggalString.split('-');
            const tahun = bagian[0];
            const bulan = parseInt(bagian[1], 10) - 1;
            const tanggal = parseInt(bagian[2], 10);
            return `${tanggal} ${namaBulan[bulan]} ${tahun}`;
        }

        function updateGreetingClock() {
            const sekarang = new Date();
            const jam = sekarang.getHours();
            let sapaan;
            if (jam >= 4 && jam < 11) sapaan = "Selamat Pagi";
            else if (jam >= 11 && jam < 15) sapaan = "Selamat Siang";
            else if (jam >= 15 && jam < 18) sapaan = "Selamat Sore";
            else sapaan = "Selamat Malam";

            const tanggal = `${namaHari[sekarang.getDay()]}, ${sekarang.getDate()} ${namaBulan[sekarang.getMonth()]} ${sekarang.getFullYear()}`;
            const waktu = sekarang.toLocaleTimeString('id-ID');
            document.getElementById("greeting-clock").innerText = `${sapaan} · ${tanggal} · ${waktu}`;
        }

        // ===== FETCH DASHBOARD (kartu) =====
        let prevInput = 0, prevOutput = 0, prevYield = 0, prevOr = 0;
        let prevSheetInput = 0, prevSheetOutput = 0, prevSheetYield = 0;
        let sudahLoadPertama = false;

        // ===== KONTROL PRODUKSI PART (MACHINE CONTROL) =====
        // Melacak status mesin sebelumnya, buat deteksi transisi "mulai Running" dan "benar-benar berhenti".
        let statusMesinSebelumnya = null;
        let statusMesinAsliTerakhir = null; // status_mesin apa adanya dari server, dipakai tombol Maintenance
        let sesiProduksiAktif = false;   // true kalau ada sesi produksi part yang sedang berjalan
        // Jam saat mesin TERAKHIR berhenti selagi sesi produksi masih aktif (calon "jam selesai").
        // Dipakai kalau operator nanti mengisi alasan stop dengan kategori "Selesai Produksi" --
        // supaya (B) dihitung sampai jam mesin benar-benar berhenti, bukan sampai jam operator
        // selesai mengisi form alasannya (yang bisa telat beberapa saat).
        let calonJamSelesai = null;
        let timerLiveInterval = null;    // interval buat update tampilan durasi berjalan tiap detik

        function animateValue(id, start, end, duration, decimals, suffix) {
            const el = document.getElementById(id);
            if (!el) return;
            const startTime = performance.now();
            function langkah(t) {
                const progress = Math.min((t - startTime) / duration, 1);
                const nilai = start + (end - start) * progress;
                el.innerText = nilai.toFixed(decimals) + suffix;
                if (progress < 1) requestAnimationFrame(langkah);
            }
            requestAnimationFrame(langkah);
        }

        function getTanggalAktif() {
            const input = document.getElementById('filter-tanggal');
            if (input && input.value) return input.value;
            return new Date().toISOString().split('T')[0];
        }

        async function updateData(tanggal) {
            const tgl = tanggal || getTanggalAktif();
            let data = null;
            try {
                const response = await fetch(`api/data.php?line=${encodeURIComponent(currentLine)}&tanggal=${tgl}`, { cache: "no-store" });
                if (response.ok) {
                    data = await response.json();
                }
            } catch (e) {
                // UI standalone mode fallback
            }

            if (!sudahLoadPertama) {
                document.querySelectorAll('.metric-card .value').forEach(el => el.classList.remove('skeleton'));
                sudahLoadPertama = true;
            }

            if (!data || data.input === undefined) {
                return;
            }

            const newInput = isNaN(parseFloat(data.input)) ? 0 : parseFloat(data.input);
            const newOutput = isNaN(parseFloat(data.output)) ? 0 : parseFloat(data.output);
            const newYield = isNaN(parseFloat(data.yield_val)) ? 0 : parseFloat(data.yield_val);
            const newOr = isNaN(parseFloat(data.or_value)) ? 0 : parseFloat(data.or_value);

            // Satuan SHEET/PANEL (bukan PCS) -- lihat komentar di api/data.php
            const newSheetInput = isNaN(parseFloat(data.input_sheet)) ? 0 : parseFloat(data.input_sheet);
            const newSheetOutput = isNaN(parseFloat(data.output_sheet)) ? 0 : parseFloat(data.output_sheet);
            const newSheetYield = isNaN(parseFloat(data.yield_sheet_val)) ? 0 : parseFloat(data.yield_sheet_val);

            // Selalu simpan nilai terbaru biar kartu produksi bisa restore dengan benar
            prevInput = newInput; prevOutput = newOutput; prevYield = newYield; prevOr = newOr;
            prevSheetInput = newSheetInput; prevSheetOutput = newSheetOutput; prevSheetYield = newSheetYield;

            animateValue("input-value", prevInput, newInput, 600, 0, "");
            animateValue("output-value", prevOutput, newOutput, 600, 0, "");
            animateValue("yield-value", prevYield, newYield, 600, 1, "%");
            animateValue("or-value", prevOr, newOr, 600, 1, "%");

            animateValue("sheet-input-value", prevSheetInput, newSheetInput, 600, 0, "");
            animateValue("sheet-output-value", prevSheetOutput, newSheetOutput, 600, 0, "");
            animateValue("sheet-yield-value", prevSheetYield, newSheetYield, 600, 1, "%");

            const hariIni = new Date().toISOString().split('T')[0];
            const labelTgl = (tgl === hariIni) ? 'Hari Ini' : formatTanggalIndo(tgl);
            document.querySelectorAll('.metric-tanggal-label').forEach(el => el.innerText = labelTgl);
        }

        // ===== TOWER LIGHT STATUS MESIN (SELARAS DENGAN ESP32) =====
        async function updateStatusMesin() {
            let status = 'running';
            try {
                const response = await fetch(`api/get_status.php?line=${encodeURIComponent(currentLine)}`, { cache: "no-store" });
                if (response.ok) {
                    const hasil = await response.json();
                    if (hasil && hasil.status_mesin) {
                        status = hasil.status_mesin;
                    }
                }
            } catch (e) {
                // UI standalone mode fallback
            }

            // Matiin semua lampu & lepas class kedap-kedip dulu, baru nyalain yang sesuai
            const lampMerah = document.getElementById("lamp-merah");
            const lampKuning = document.getElementById("lamp-kuning");
            const lampHijau = document.getElementById("lamp-hijau");
            if (lampMerah) lampMerah.classList.remove("aktif");
            if (lampKuning) lampKuning.classList.remove("aktif", "lamp-berkedip");
            if (lampHijau) lampHijau.classList.remove("aktif", "lamp-berkedip");

            const petaTeks = {
                stop: "STOP (BERHENTI)",
                running: "RUNNING (PRODUKSI)",
                running_kedip: "PRODUKSI (KUNING KEDIP)",
                maintenance: "MAINTENANCE",
                standby: "STANDBY (BERHENTI)",
                standby_kedip: "STANDBY KEDIP (BERHENTI)",
                mati: "MATI / PATLITE OFF"
            };

            const teksStatusEl = document.getElementById("teks-status-mesin");
            if (teksStatusEl) teksStatusEl.innerText = petaTeks[status] || "STOP";

            // Visualisasi Patlite fisik 6 kondisi:
            // 1. Kondisi 1 (1 + 2,0 + 3,0): Mesin produksi normal (Hijau Nyala)
            if (status === 'running') {
                if (lampHijau) lampHijau.classList.add("aktif");
            }
            // 2. Kondisi 2 (1 + 2" + 3,0): Mesin produksi tapi tetap jalan (Hijau Nyala + Kuning Kedip)
            else if (status === 'running_kedip') {
                if (lampHijau) lampHijau.classList.add("aktif");
                if (lampKuning) lampKuning.classList.add("aktif", "lamp-berkedip");
            }
            // 3. Kondisi 3 (1,0 + 2 + 3,0): Mesin berhenti (Kuning Nyala solid)
            else if (status === 'standby') {
                if (lampKuning) lampKuning.classList.add("aktif");
            }
            // 4. Kondisi 4 (1,0 + 2" + 3,0): Mesin berhenti (Kuning Kedip)
            else if (status === 'standby_kedip') {
                if (lampKuning) lampKuning.classList.add("aktif", "lamp-berkedip");
            }
            // 5. Kondisi 5 (1,0 + 2,0 + 3): Mesin berhenti (Merah Nyala solid) / Maintenance
            else if (status === 'stop' || status === 'maintenance') {
                if (lampMerah) lampMerah.classList.add("aktif");
            }
            // 6. Kondisi 6 (1,0 + 2,0 + 3,0): Mati (Semua lampu tower padam)
            else if (status === 'mati') {
                // Semua lampu tetap padam
            }

            // Sinkronkan tampilan tombol Maintenance dengan status asli dari server
            const btnMaint = document.getElementById("btn-toggle-maintenance");
            if (btnMaint) {
                btnMaint.style.display = "inline-block";
                if (status === 'maintenance') {
                    btnMaint.innerText = "✅ Selesai Maintenance";
                    btnMaint.classList.add("aktif");
                } else {
                    btnMaint.innerText = "🔧 Mode Maintenance";
                    btnMaint.classList.remove("aktif");
                }
            }
            statusMesinAsliTerakhir = status;
            waktuMatiStopAktif = hasil.waktu_mati || hasil.updated_at;
            warnaPatliteStopAktif = hasil.warna_patlite || (status === 'standby' ? 'Kuning' : (status === 'standby_kedip' ? 'Kuning Kedip' : (status === 'mati' ? 'Mati / OFF' : (status === 'maintenance' ? 'Merah (Maintenance)' : 'Merah'))));
            infoStopSelesaiTerakhir = hasil.info_stop_selesai || null;

            const teksUpdateStatus = document.getElementById("teks-update-status");
            if (teksUpdateStatus) {
                if (hasil.updated_at) {
                    teksUpdateStatus.innerText = "Update terakhir: " + hasil.updated_at;
                } else {
                    teksUpdateStatus.innerText = "Belum ada data status";
                }
            }

            // Klasifikasi kondisi mesin:
            // Mesin Berhenti: Stop (Merah), Standby (Kuning), Standby Kedip (Kuning Kedip), Mati (OFF)
            const isMesinBerhenti = (status === 'stop' || status === 'standby' || status === 'standby_kedip' || status === 'mati');
            const isSedangProduksi = (status === 'running' || status === 'running_kedip');

            // Peringatan: mesin sedang berhenti (Stop / Standby / Standby Kedip / Mati) dan alasannya belum diisi.
            // Khusus line PCB: line lain belum menggunakan sistem notifikasi ini dan punya settingan tersendiri.
            const warningAlasanEl = document.getElementById("stop-alasan-warning");
            if (hasil.perlu_alasan_stop && currentLine === 'PCB') {
                const jumlahBelum = hasil.jumlah_belum_diisi || 1;
                let teksPeringatan = "";
                let teksJudul = "";

                if (isSedangProduksi || status === 'maintenance') {
                    const infoRingkas = hasil.info_stop_selesai ? ` [${hasil.info_stop_selesai.rentang} (${hasil.info_stop_selesai.durasi})]` : '';
                    teksPeringatan = `Ada ${jumlahBelum} riwayat stop${infoRingkas} — klik untuk isi susulan`;
                    teksJudul = `Alasan Stop (Susulan - sisa ${jumlahBelum})`;
                } else {
                    let infoKondisi = "Lampu Merah";
                    if (status === 'standby') infoKondisi = "Lampu Kuning";
                    if (status === 'standby_kedip') infoKondisi = "Kuning Kedip";
                    if (status === 'mati') infoKondisi = "Mesin Mati / Patlite OFF";

                    teksPeringatan = `Mesin berhenti (${infoKondisi}) — klik untuk isi alasan (sisa ${jumlahBelum})`;
                    teksJudul = `Alasan Mesin Berhenti (${infoKondisi} - sisa ${jumlahBelum})`;
                }

                const teksPeringatanEl = document.getElementById("stop-alasan-warning-teks");
                if (teksPeringatanEl) teksPeringatanEl.innerText = teksPeringatan;
                const judulModalEl = document.getElementById("alasan-stop-judul");
                if (judulModalEl) judulModalEl.innerText = teksJudul;
                if (warningAlasanEl) warningAlasanEl.style.display = "flex";

                // Jika mesin berhenti: Buka modal popup, tampilkan toast notifikasi & bunyikan alarm suara
                if (alasanStopSudahDitampilkanUntuk !== hasil.updated_at) {
                    alasanStopSudahDitampilkanUntuk = hasil.updated_at;
                    if (isMesinBerhenti) {
                        bukaModalAlasanStop();
                        let labelNotif = status === 'stop' ? 'Lampu Merah (Stop)' : (status === 'standby_kedip' ? 'Kuning Kedip (Standby)' : (status === 'mati' ? 'Mesin Mati / Patlite OFF' : 'Lampu Kuning (Standby)'));
                        tampilkanToastNotification(`Mesin ${currentLine} berhenti! (${labelNotif})`, 'danger');
                        putarSuaraNotifikasiMesinBerhenti();
                    }
                }
            } else {
                if (warningAlasanEl) warningAlasanEl.style.display = "none";
                alasanStopSudahDitampilkanUntuk = null;
                const modalAlasan = document.getElementById('modal-alasan-stop');
                if (modalAlasan && modalAlasan.style.display !== 'none' && !sedangProsesMaintenance) {
                    tutupModalAlasanStop();
                }
            }

            // ===== Deteksi transisi status buat sesi otomatis Kalkulator Performance =====
            const wasSedangProduksi = (statusMesinSebelumnya === 'running' || statusMesinSebelumnya === 'running_kedip');
            if (statusMesinSebelumnya !== null) {
                if (isSedangProduksi && !wasSedangProduksi) {
                    // Mesin menyala lagi (Patlite jadi hijau / hijau+kuning kedip). Mulai/lanjutkan sesi.
                    if (!sesiProduksiAktif) {
                        mulaiSesiPerformance();
                    }
                    calonJamSelesai = null; // batalkan calon jam selesai yang sempat tersimpan waktu jeda

                    if (currentView === 'machine-control') {
                        perbaruiHasilPerformanceLive();
                        loadRingkasanAlasanStop();
                    }
                } else if (wasSedangProduksi && !isSedangProduksi) {
                    // Mesin baru berhenti. Simpan jam saat ini sebagai CALON jam selesai
                    if (sesiProduksiAktif) {
                        calonJamSelesai = jamSekarangHHMM();
                    }
                }
            }
            statusMesinSebelumnya = status;

            // Selaraskan tampilan kotak status "Kontrol Produksi Part" dengan kondisi sesi & mesin saat ini.
            if (sesiProduksiAktif) {
                updateStatusBoxMC(isSedangProduksi ? 'berjalan' : 'jeda');
                if (!timerLiveInterval) {
                    mulaiTimerLive();
                }
            } else {
                hentikanTimerLive();
                const ct = document.getElementById('perf-ct-pilihan')?.value;
                if (isSedangProduksi && !ct) {
                    updateStatusBoxMC('menunggu-part');
                } else {
                    updateStatusBoxMC('idle');
                }
            }
        }

        // ===== TOMBOL MANUAL: MODE MAINTENANCE =====
        // Satu-satunya status yang masih perlu dipicu manual (tidak ada sensornya).
        // Status lain (running/stop) sekarang murni otomatis dari arduino_bridge.py.
        let sedangProsesMaintenance = false;
        async function toggleMaintenanceManual() {
            if (statusMesinAsliTerakhir === 'maintenance') {
                // Selesaikan mode maintenance dan kembalikan status mesin ke running
                if (sedangProsesMaintenance) return;
                sedangProsesMaintenance = true;
                const btnMaint = document.getElementById("btn-toggle-maintenance");
                if (btnMaint) btnMaint.disabled = true;

                try {
                    await fetch('api/update_status.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `api_key=${encodeURIComponent(API_KEY_INTERNAL)}&line=${encodeURIComponent(currentLine)}&status_mesin=running&manual_selesai=1`
                    });
                    await updateStatusMesin();
                } catch (e) {
                    alert("Gagal mengubah status mesin. Coba lagi.");
                } finally {
                    if (btnMaint) btnMaint.disabled = false;
                    sedangProsesMaintenance = false;
                }
            } else {
                // Mesin sedang tidak maintenance: langsung buka modal agar operator memilih kategori & mengisi note
                bukaModalAlasanStop();
                pilihKategoriAlasan('Maintenance');
            }
        }

        // Format jam saat ini jadi "HH:MM", sesuai jam real-time perangkat
        function jamSekarangHHMM() {
            const d = new Date();
            return String(d.getHours()).padStart(2, '0') + ':' + String(d.getMinutes()).padStart(2, '0');
        }

        // Dipanggil otomatis saat status mesin berubah JADI Running.
        // Kalau part sudah dipilih sebelumnya, sesi produksi langsung dimulai.
        // Kalau belum, tampilkan status "menunggu part" -- sesi baru dimulai begitu part dipilih.
        function mulaiSesiPerformance() {
            const ct = document.getElementById('perf-ct-pilihan')?.value;
            if (!ct) {
                updateStatusBoxMC('menunggu-part');
                return;
            }
            mulaiSesiDenganPartTerpilih();
        }

        // Memulai sesi produksi part yang sedang dipilih: catat jam mulai menyesuaikan
        // Run Time awal shift yang sedang aktif (Pagi 06:55, Sore 15:50, Malam 23:20) jika belum ada sesi di shift ini,
        // aktifkan timer live, dan aktifkan tombol Matikan Mesin.
        async function mulaiSesiDenganPartTerpilih(jamMulaiOverride = null) {
            const inputTanggal = document.getElementById('perf-tanggal');
            const inputMulai = document.getElementById('perf-jam-mulai');
            const inputSelesai = document.getElementById('perf-jam-selesai');
            if (!inputTanggal || !inputMulai || !inputSelesai) return;

            const shift = dapatkanShiftSekarang();
            inputTanggal.value = shift.tanggal;

            let jamMulaiFinal = jamMulaiOverride;
            if (!jamMulaiFinal) {
                try {
                    const resp = await fetch(`api/riwayat_performance.php?line=${encodeURIComponent(currentLine)}&tanggal=${shift.tanggal}`, { cache: 'no-store' });
                    const d = await resp.json();
                    const riwayat = Array.isArray(d?.riwayat) ? d.riwayat : [];
                    const adaSesiDiShiftIni = riwayat.some(r => {
                        const jm = r.jam_mulai;
                        if (!jm) return false;
                        if (shift.jamMulai > shift.jamSelesai) {
                            return jm >= shift.jamMulai || jm < shift.jamSelesai;
                        }
                        return jm >= shift.jamMulai && jm < shift.jamSelesai;
                    });

                    if (!adaSesiDiShiftIni) {
                        // Belum ada sesi sama sekali di shift ini -> ikuti jam mulai shift
                        jamMulaiFinal = shift.jamMulai;
                    } else {
                        // Sudah ada sesi sebelumnya di shift ini -> pakai jam sekarang saat part dipilih
                        jamMulaiFinal = jamSekarangHHMM();
                    }
                } catch (e) {
                    jamMulaiFinal = jamSekarangHHMM();
                }
            }

            inputMulai.value = jamMulaiFinal;
            inputSelesai.value = '';
            sesiProduksiAktif = true;

            // Sembunyikan notifikasi status simpan sesi sebelumnya
            const statusSimpanEl = document.getElementById('simpan-produksi-status');
            if (statusSimpanEl) statusSimpanEl.style.display = 'none';

            // Catat nama part sesi ini, dipakai untuk mendeteksi ganti-part-langsung
            // (lihat isiDropdownCtPerformance()).
            const labelPart = document.getElementById('perf-part-label');
            namaPartSesiAktifSebelumnya = labelPart ? labelPart.innerText : null;

            const hasilEl = document.getElementById('perf-hasil-wrapper');
            if (hasilEl) hasilEl.style.display = ''; // tampil dari awal sesi -- nilainya diisi live oleh perbaruiHasilPerformanceLive()
            const kosongEl = document.getElementById('perf-hasil-kosong');
            if (kosongEl) kosongEl.style.display = 'none';

            mulaiTimerLive();
            updateStatusBoxMC('berjalan');

            perbaruiHasilPerformanceLive(); // isi angka live segera, jangan tunggu polling interval berikutnya

            // Simpan jam mulai & part sesi ini ke server, supaya kalau halaman di-refresh,
            // sesi ini bisa dipulihkan dengan jam mulai yang SAMA (lihat pulihkanSesiAktif()).
            simpanSesiAktifKeServer();
        }

        // Mengakhiri sesi produksi karena operator mengonfirmasi kategori stop "Selesai Produksi".
        // Dipanggil dari simpanAlasanStop(). Pakai jam mesin berhenti yang tersimpan di calonJamSelesai
        // (bukan jam sekarang), supaya (B) tidak ikut kebawa telatnya operator mengisi form alasan.
        async function selesaikanSesiKarenaSelesaiProduksi() {
            if (sesiProduksiAktif) {
                await matikanMesin(calonJamSelesai);
            }
            calonJamSelesai = null;
        }

        // Dipanggil saat operator mengisi alasan stop dengan kategori "Selesai Produksi"
        // (lihat selesaikanSesiKarenaSelesaiProduksi()). Mengisi Jam Selesai, menghitung & menyimpan
        // hasil performance sesi produksi part ini, lalu mengakhiri sesi (data dipisah dari sesi berikutnya).
        async function matikanMesin(jamSelesaiOverride) {
            const inputMulai = document.getElementById('perf-jam-mulai');
            const inputSelesai = document.getElementById('perf-jam-selesai');
            if (!sesiProduksiAktif || !inputMulai || !inputMulai.value) return; // tidak ada sesi aktif buat diakhiri

            hentikanTimerLive();
            inputSelesai.value = jamSelesaiOverride || jamSekarangHHMM();

            updateStatusBoxMC('menghitung');

            const ct = document.getElementById('perf-ct-pilihan')?.value;
            if (!ct) {
                // Belum ada part/C yang dipilih -- tampilkan modal pilih C dulu
                hentikanTimerLive();
                updateStatusBoxMC('idle');
                bukaModalPilihC();
                return; // jangan lanjut sampai C dipilih
            }
            // Modal keterangan penyimpangan durasi HANYA muncul kalau sesi ini adalah sesi
            // terakhir shift (jam selesainya sudah mencapai/lewat jam akhir shift). Kalau
            // sesi diakhiri karena ganti part di tengah shift yang sama, modal di-skip --
            // keterangan diisi otomatis oleh server (lihat kalkulasi_performance.php).
            const sudahAkhirShift = apakahSudahAkhirShift(inputSelesai.value);

            // Reset kartu SEBELUM hitung -- supaya tidak ada angka sesi lama yang tampil
            // setelah sesi ini selesai. hitungPerformanceShift() akan mengisi ulang nilai
            // ke kartu dengan data final, tidak perlu khawatir kartu jadi kosong sesaat.
            resetKartuMetrik();

            await hitungPerformanceShift(!sudahAkhirShift);

            // Sesi selesai -> kosongkan jam biar sesi produksi berikutnya tidak numpuk sama sesi ini
            sesiProduksiAktif = false;
            inputMulai.value = '';
            inputSelesai.value = '';
            const inputTgl = document.getElementById('perf-tanggal');
            if (inputTgl) inputTgl.value = '';
            updateStatusBoxMC('idle');

            // Hentikan timer dan set ke 00:00:00
            const elTimer = document.getElementById('mc-live-timer');
            if (elTimer) {
                elTimer.innerText = '00:00:00';
                elTimer.style.display = 'none';
            }

            // Reset kartu metrik ke '-' dan sembunyikan grid
            resetKartuMetrik();

            document.getElementById('ct-part-search').value = '';
            document.getElementById('ct-part-select').value = '';
            tampilkanDetailCt(''); // kosongkan tampilan detail CT & kotak "-- Pilih Part --"
            isiDropdownCtPerformance(null); // WAJIB dipanggil terpisah -- tampilkanDetailCt('') return lebih awal dan tidak memanggil ini; inilah yang benar-benar mengosongkan perf-ct-pilihan yang dicek mulaiSesiPerformance()
            localStorage.removeItem('selected_part_' + currentLine);
            await simpanPartTerpilih(''); // kosongkan juga di server (selected_part)

            const boxC = document.getElementById('mc-nilai-c-box');
            const boxKosong = document.getElementById('mc-nilai-c-kosong');
            if (boxC) boxC.style.display = 'none';
            if (boxKosong) boxKosong.style.display = 'flex';

            // Sesi sudah ditutup & dihitung -- hapus catatan sesi aktif di server juga,
            // supaya kalau halaman di-refresh setelah ini, tidak ada sesi lama yang
            // "kepulihkan" lagi secara keliru.
            await hapusSesiAktifDiServer();
        }

        // Cek apakah jam selesai sesi (format "HH:MM") sudah mencapai/lewat jam akhir shift
        // saat ini. Dipakai untuk menentukan apakah sesi yang diakhiri ini adalah sesi
        // TERAKHIR di shift (ganti shift), atau baru ganti part di tengah shift yang sama.
        function apakahSudahAkhirShift(jamSelesaiHHMM) {
            const shift = dapatkanShiftSekarang();
            // Shift Malam lintas tengah malam (mis. 23:20 - 06:55) -- jam selesai lebih kecil dari
            // jam mulai secara angka, jadi bandingkan sebagai "belum sampai" hanya kalau jam
            // selesai sesi ini juga di rentang dini hari (lebih kecil dari jam mulai shift).
            if (shift.jamMulai > shift.jamSelesai) {
                if (jamSelesaiHHMM >= shift.jamMulai) return false; // masih di sore/malam hari yg sama, belum lewat tengah malam
                return jamSelesaiHHMM >= shift.jamSelesai;
            }
            return jamSelesaiHHMM >= shift.jamSelesai;
        }

        // ===== TIMER LIVE DURASI PRODUKSI =====
        function mulaiTimerLive() {
            hentikanTimerLive();
            perbaruiTimerLive();
            timerLiveInterval = setInterval(perbaruiTimerLive, 1000);
        }

        function hentikanTimerLive() {
            if (timerLiveInterval) {
                clearInterval(timerLiveInterval);
                timerLiveInterval = null;
            }
        }

        function perbaruiTimerLive() {
            const inputMulai = document.getElementById('perf-jam-mulai');
            const inputTanggal = document.getElementById('perf-tanggal');
            const el = document.getElementById('mc-live-timer');
            if (!inputMulai || !inputMulai.value || !el) return;

            const timeParts = inputMulai.value.split(':').map(Number);
            const jam = timeParts[0] || 0;
            const menit = timeParts[1] || 0;
            const detik = timeParts[2] || 0;

            let mulai = new Date();
            if (inputTanggal && inputTanggal.value) {
                const dParts = inputTanggal.value.split('-').map(Number);
                if (dParts.length === 3) {
                    mulai = new Date(dParts[0], dParts[1] - 1, dParts[2], jam, menit, detik, 0);
                } else {
                    mulai.setHours(jam, menit, detik, 0);
                }
            } else {
                mulai.setHours(jam, menit, detik, 0);
            }

            let selisihDetik = Math.floor((new Date() - mulai) / 1000);
            if (selisihDetik < 0) selisihDetik = Math.max(0, selisihDetik + 86400); // sesi lewat tengah malam

            // Cegah anomali durasi > 24 jam akibat perbedaan tanggal anchor shift
            if (selisihDetik >= 86400) {
                selisihDetik = selisihDetik % 86400;
            }

            // Cek apakah shift sesi ini sudah selesai/berganti saat halaman dibiarkan terbuka
            const shiftSekarang = dapatkanShiftSekarang();
            const tglSesi = inputTanggal && inputTanggal.value ? inputTanggal.value : tanggalLokalYYYYMMDD();
            const sesiMasihValidShift = (() => {
                if (tglSesi !== shiftSekarang.tanggal) return false;
                if (shiftSekarang.jamMulai > shiftSekarang.jamSelesai) {
                    return inputMulai.value >= shiftSekarang.jamMulai || inputMulai.value < shiftSekarang.jamSelesai;
                }
                return inputMulai.value >= shiftSekarang.jamMulai && inputMulai.value < shiftSekarang.jamSelesai;
            })();

            if (!sesiMasihValidShift && apakahSudahAkhirShift(jamSekarangHHMM())) {
                // Shift telah berganti -- panggil finalisasi di server dan reset tampilan
                hentikanTimerLive();
                hapusSesiAktifDiServer().then(() => {
                    sesiProduksiAktif = false;
                    resetKartuMetrik();
                    updateStatusBoxMC('idle');
                    const statusEl = document.getElementById('simpan-produksi-status');
                    if (statusEl) {
                        statusEl.textContent = `ℹ️ Pergantian shift terdeteksi. Sesi shift sebelumnya telah otomatis difinalisasi dan disimpan ke riwayat.`;
                        statusEl.style.display = '';
                        statusEl.style.color = '#0d3b8c';
                    }
                });
                return;
            }

            // Jika sesi sudah berjalan tidak wajar (> 16 jam, mustahil dalam 1 shift), reset otomatis
            if (selisihDetik >= 57600) {
                hentikanTimerLive();
                sesiProduksiAktif = false;
                updateStatusBoxMC('idle');
                return;
            }

            const j = String(Math.floor(selisihDetik / 3600)).padStart(2, '0');
            const m = String(Math.floor((selisihDetik % 3600) / 60)).padStart(2, '0');
            const s = String(selisihDetik % 60).padStart(2, '0');
            el.innerText = `${j}:${m}:${s}`;
        }

        // ===== HASIL PERFORMANCE LIVE (grid B/C/F/E/D/A/I/P/H/J/K/L di Machine Control) =====
        // Dulu grid ini kosong (disembunyikan) sampai sesi produksi benar-benar SELESAI dan
        // kalkulasiPerformance() menyimpan hasil final ke riwayat_performance. Sekarang grid
        // tampil sejak sesi MULAI, dan angkanya di-refresh live pakai endpoint read-only
        // api/kalkulasi_performance_live.php (SAMA yang sudah dipakai loadMachineRatio() di
        // Dashboard) -- endpoint ini TIDAK menyimpan apapun, jadi aman dipanggil berkali-kali
        // tanpa membuat baris riwayat duplikat. Begitu sesi benar-benar berakhir (Selesai
        // Produksi/Ganti Type), kalkulasiPerformance() tetap yang mengisi nilai FINAL ke grid
        // ini (menimpa angka live terakhir), jadi hasil akhir yang tersimpan ke riwayat tetap
        // sumber kebenaran, bukan hasil live yang sempat tampil.

        // ===== RESET KARTU METRIK =====
        // Dipanggil setiap kali sesi produksi berakhir (Selesai Produksi, Ganti Type, atau
        // Simpan Produksi) agar nilai B/C/D/E/F/H/I/J/K/P/EFF tidak menampilkan angka sesi
        // lama saat sesi berikutnya belum menghasilkan data apapun.
        function resetKartuMetrik() {
            const ids = ['perf-val-B','perf-val-C','perf-val-F','perf-val-E',
                         'perf-val-D','perf-val-A','perf-val-I','perf-val-P',
                         'perf-val-H','perf-val-J','perf-val-K','perf-val-L'];
            ids.forEach(id => {
                const el = document.getElementById(id);
                if (el) el.innerText = '-';
            });
            // Sembunyikan tag "(dikoreksi manual)"
            ['perf-val-C-manual-tag','perf-val-F-manual-tag','perf-val-E-manual-tag']
                .forEach(id => {
                    const el = document.getElementById(id);
                    if (el) el.style.display = 'none';
                });
            // Sembunyikan wrapper hasil sampai ada data sesi baru
            const hasilEl = document.getElementById('perf-hasil-wrapper');
            const kosongEl = document.getElementById('perf-hasil-kosong');
            if (hasilEl) hasilEl.style.display = 'none';
            if (kosongEl) {
                kosongEl.style.display = 'block';
                kosongEl.innerText = 'Belum ada sesi produksi. Mulai mesin dan pilih part untuk melihat hasil perhitungan live.';
            }
        }

        async function perbaruiHasilPerformanceLive() {
            if (currentView !== 'machine-control') return; // hemat request kalau operator lagi buka halaman lain


            const inputTanggal = document.getElementById('perf-tanggal');
            const inputMulai = document.getElementById('perf-jam-mulai');
            const tanggal = inputTanggal && inputTanggal.value ? inputTanggal.value : (currentTanggal || tanggalLokalYYYYMMDD());
            let jamMulai = inputMulai ? inputMulai.value : '';
            let ct = document.getElementById('perf-ct-pilihan')?.value;
            let namaPartUntukLive = document.getElementById('perf-part-label')?.innerText || '';

            if (!namaPartUntukLive || namaPartUntukLive === 'Belum ada') {
                namaPartUntukLive = localStorage.getItem('selected_part_' + currentLine) || '';
                const lbl = document.getElementById('perf-part-label');
                if (lbl && namaPartUntukLive) lbl.innerText = namaPartUntukLive;
            }

            // Pastikan CT terisi otomatis jika ada nama part
            if ((!ct || ct === '') && namaPartUntukLive && namaPartUntukLive !== 'Belum ada') {
                const found = (Array.isArray(DAFTAR_PART_CT) ? DAFTAR_PART_CT : []).find(p => p.part === namaPartUntukLive);
                if (found && Array.isArray(found.ct) && found.ct.length > 0) {
                    ct = found.ct[0];
                    const sel = document.getElementById('perf-ct-pilihan');
                    if (sel) {
                        sel.innerHTML = found.ct.map(n => `<option value="${n}">CT Standar — ${n} menit</option>`).join('');
                        sel.value = ct;
                    }
                }
            }

            if (!jamMulai) {
                // Gunakan default shift start jika jam mulai belum terisi di DOM
                const shiftSekarang = dapatkanShiftSekarang();
                jamMulai = shiftSekarang.jamMulai || '06:55';
                if (inputMulai) inputMulai.value = jamMulai;
            }

            // Fallback CT agar kalkulasi tidak macet
            if (!ct) ct = '0.5';

            const jamSekarang = jamSekarangHHMM();
            // Kirim juga koreksi manual F/E/C yang SEDANG AKTIF (kalau ada), supaya polling
            // live ini tidak menimpa balik ke nilai otomatis sensor selagi operator sedang
            // mengoreksi lewat form "Koreksi F/E/C" -- lihat form-koreksi-fe. Kosong/field
            // tidak ada = tidak dikirim = live tetap pakai nilai otomatis seperti biasa.
            const fManualAktif = document.getElementById('input-koreksi-f')?.value?.trim();
            const eManualAktif = document.getElementById('input-koreksi-e')?.value?.trim();
            const cManualAktif = document.getElementById('input-koreksi-c')?.value?.trim();
            const url = `api/kalkulasi_performance_live.php?line=${encodeURIComponent(currentLine)}`
                + `&tanggal=${encodeURIComponent(tanggal)}&jam_mulai=${encodeURIComponent(jamMulai)}`
                + `&jam_selesai=${encodeURIComponent(jamSekarang)}&ct=${encodeURIComponent(ct)}&part=${encodeURIComponent(namaPartUntukLive)}`
                + (fManualAktif ? `&f_manual=${encodeURIComponent(fManualAktif)}` : '')
                + (eManualAktif ? `&e_manual=${encodeURIComponent(eManualAktif)}` : '')
                + (cManualAktif ? `&c_manual=${encodeURIComponent(cManualAktif)}` : '');

            try {
                const res = await fetch(url, { cache: "no-store" });
                const data = await res.json();
                if (data.status !== 'success') {
                    console.warn('perbaruiHasilPerformanceLive: respons bukan success', data);
                    return; // biarkan tampilan sebelumnya, coba lagi di polling berikutnya
                }

                document.getElementById('perf-val-B').innerText = data.B ?? '-';
                document.getElementById('perf-val-C').innerText = data.C_v2 ?? ct;
                const cTag = document.getElementById('perf-val-C-manual-tag');
                if (cTag) cTag.style.display = data.C_v2_manual ? '' : 'none';
                document.getElementById('perf-val-F').innerText = data.F_v2 ?? 0;
                const fTag = document.getElementById('perf-val-F-manual-tag');
                if (fTag) fTag.style.display = data.F_v2_manual ? '' : 'none';
                document.getElementById('perf-val-E').innerText = data.E_v2 ?? 0;
                const eTag = document.getElementById('perf-val-E-manual-tag');
                if (eTag) eTag.style.display = data.E_v2_manual ? '' : 'none';
                document.getElementById('perf-val-D').innerText = data.D ?? 0;
                document.getElementById('perf-val-A').innerText = data.A_v2 ?? 0;
                document.getElementById('perf-val-I').innerText = data.I ?? 0;
                document.getElementById('perf-val-P').innerText = data.P ?? 0;
                document.getElementById('perf-val-H').innerText = `${data.H_v2 ?? 0} (${data.H_v2_persen ?? 0}%)`;
                document.getElementById('perf-val-J').innerText = data.J_v2 ?? 0;
                document.getElementById('perf-val-K').innerText = `${data.K_v2 ?? 0}`;
                document.getElementById('perf-val-L').innerText = data.tidak_ada_data
                    ? `0% (Menunggu data produksi)`
                    : `${data.EFF_v2_persen}%`;

                const kosongEl = document.getElementById('perf-hasil-kosong');
                const hasilEl = document.getElementById('perf-hasil-wrapper');
                if (kosongEl) kosongEl.style.display = 'none';
                if (hasilEl) hasilEl.style.display = '';
            } catch (e) {
                console.error('perbaruiHasilPerformanceLive: gagal fetch', e);
            }
        }

        // ===== STATUS BOX KONTROL PRODUKSI PART =====
        function updateStatusBoxMC(state) {
            const box = document.getElementById('mc-status-box');
            const icon = document.getElementById('mc-status-icon');
            const judul = document.getElementById('mc-status-judul');
            const sub = document.getElementById('mc-status-sub');
            const timerEl = document.getElementById('mc-live-timer');
            if (!box || !icon || !judul || !sub || !timerEl) return;

            const partLabel = document.getElementById('perf-part-label');
            const namaPart = partLabel ? partLabel.innerText : 'Belum ada';
            const jamMulai = document.getElementById('perf-jam-mulai')?.value || '-';

            box.className = 'mc-status-box';

            if (state === 'menunggu-part') {
                box.classList.add('mc-status-waiting');
                icon.innerText = '⏳';
                judul.innerText = 'Mesin Running — tunggu pilih part';
                sub.innerText = 'Pilih part dan nilai (C) di atas dulu — sesi produksi akan mulai otomatis setelah part dipilih.';
                timerEl.style.display = 'none';
            } else if (state === 'berjalan') {
                box.classList.add('mc-status-running');
                icon.innerText = '▶';
                judul.innerText = `Sedang produksi: ${namaPart}`;
                sub.innerText = `Mulai jam ${jamMulai} — durasi terus berjalan sampai sesi ditutup dengan kategori "Selesai Produksi"`;
                timerEl.style.display = 'block';
            } else if (state === 'jeda') {
                box.classList.add('mc-status-paused');
                icon.innerText = '⏸';
                judul.innerText = `Jeda: ${namaPart}`;
                sub.innerText = `Mesin sedang berhenti, sesi sejak jam ${jamMulai} masih berjalan — isi alasan stop, pilih "Selesai Produksi" untuk mengakhiri sesi ini.`;
                timerEl.style.display = 'block';
            } else if (state === 'menghitung') {
                box.classList.add('mc-status-running');
                icon.innerText = '⏳';
                judul.innerText = 'Menghitung & menyimpan hasil sesi...';
                sub.innerText = namaPart;
                timerEl.style.display = 'none';
            } else {
                box.classList.add('mc-status-idle');
                icon.innerText = '⏸';
                judul.innerText = 'Belum ada sesi produksi';
                sub.innerText = 'Pilih part di atas untuk memulai sesi produksi.';
                timerEl.style.display = 'none';
            }
        }

        // ===== GRAFIK =====
        let chartProduksi = null;
        let chartProduksiTipeAktif = null; // bentuk chart yang lagi aktif, buat tahu perlu recreate atau cukup update data
        let chartProduksiLabelsAktif = []; // dipakai callback tooltip scatter biar selalu baca label terbaru

        // ===== 2 CHART PERFORMANCE: Loss Time & Product Time =====
        // Sumbernya per SESI (dari riwayat_performance lewat api/grafik_performance.php),
        // bukan per jam seperti chart Produksi -- jadi state-nya dipisah sendiri-sendiri.
        // Bentuk (bar/line/area/scatter) tetap ikut dropdown "chart-type-select" yang sama
        // dengan chart Produksi, supaya seragam.
        const stateLossTime    = { chart: null, tipe: null };
        const stateProductTime = { chart: null, tipe: null };
        let labelSesiPerformanceAktif = []; // label sesi terbaru, dipakai callback tooltip scatter

        // Dipanggil dari auto-refresh/navigasi untuk refresh Grafik Produksi
        function updateChartAktif() {
            updateChart();
        }

        function simpanBentukGrafik(tipe) {
            localStorage.setItem('chartType', tipe);
            updateChartAktif();
        }

        // ===== SHIFT SAAT INI (Pagi/Sore/Malam) =====
        // Jam kerja normal sesuai form "Pengawasan Waktu Kerja Machine Control":
        //   Pagi  : 06:55 - 15:50
        //   Sore  : 15:50 - 23:20
        //   Malam : 23:20 - 06:55 (lanjut ke hari berikutnya)
        function dapatkanShiftSekarang() {
            const sekarang = new Date();
            const menitSekarang = sekarang.getHours() * 60 + sekarang.getMinutes();
            const keMenit = (h, m) => h * 60 + m;

            const batasPagiMulai   = keMenit(6, 55);
            const batasPagiSelesai = keMenit(15, 50);
            const batasSoreSelesai = keMenit(23, 20);

            let nama, jamMulai, jamSelesai;
            const tglAcuan = new Date(sekarang);

            if (menitSekarang >= batasPagiMulai && menitSekarang < batasPagiSelesai) {
                nama = 'Pagi';  jamMulai = '06:55'; jamSelesai = '15:50';
            } else if (menitSekarang >= batasPagiSelesai && menitSekarang < batasSoreSelesai) {
                nama = 'Sore';  jamMulai = '15:50'; jamSelesai = '23:20';
            } else {
                nama = 'Malam'; jamMulai = '23:20'; jamSelesai = '06:55';
                // Kalau sekarang masih dini hari (00:00 - 06:55), shift Malam ini
                // sebenarnya MULAI KEMARIN, jadi tanggal acuannya dimundurkan 1 hari.
                if (menitSekarang < batasPagiMulai) {
                    tglAcuan.setDate(tglAcuan.getDate() - 1);
                }
            }

            const tanggal = tglAcuan.getFullYear() + '-'
                + String(tglAcuan.getMonth() + 1).padStart(2, '0') + '-'
                + String(tglAcuan.getDate()).padStart(2, '0');

            return { nama, jamMulai, jamSelesai, tanggal };
        }

        // Batas rentang date picker chart Produksi: dari 5 tahun lalu (tanggal yang sama) sampai hari ini.
        // Dipisah jadi fungsi sendiri supaya "5 tahun" tetap dihitung relatif terhadap hari ini
        // setiap kali dipanggil (bukan di-hardcode), dan gampang diubah kalau rentangnya mau diganti.
        function setRentangTanggalChart() {
            const input = document.getElementById('filter-tanggal');
            if (!input) return;

            const hariIni = new Date();
            const limaTahunLalu = new Date(hariIni);
            limaTahunLalu.setFullYear(hariIni.getFullYear() - 5);

            const keFormatISO = (d) => d.getFullYear() + '-'
                + String(d.getMonth() + 1).padStart(2, '0') + '-'
                + String(d.getDate()).padStart(2, '0');

            input.max = keFormatISO(hariIni);
            input.min = keFormatISO(limaTahunLalu);
        }

        function bukaPickerTanggal() {
            const input = document.getElementById('filter-tanggal');
            setRentangTanggalChart(); // pastikan min/max selalu up-to-date sebelum picker dibuka
            if (input.showPicker) {
                input.showPicker(); // method modern, didukung Chrome/Edge terbaru
            } else {
                input.focus();
                input.click();
            }
        }

        // Isi dropdown #filter-tahun dengan tahun sekarang s/d 5 tahun ke belakang (samakan
        // dengan batas JUMLAH_TAHUN_KEBELAKANG di api/grafik_harian.php & api/grafik_performance.php).
        // Cukup dipanggil sekali; tidak perlu diisi ulang tiap updateChart() karena isinya statis
        // selama halaman terbuka (kecuali user menunggu lewat tengah malam pergantian tahun).
        const JUMLAH_TAHUN_KEBELAKANG = 5;
        function isiDropdownTahun() {
            const select = document.getElementById('filter-tahun');
            if (!select || select.options.length > 0) return; // sudah pernah diisi

            const tahunSekarang = new Date().getFullYear();
            for (let t = tahunSekarang; t >= tahunSekarang - JUMLAH_TAHUN_KEBELAKANG; t--) {
                const opt = document.createElement('option');
                opt.value = t;
                opt.textContent = t;
                select.appendChild(opt);
            }
        }

        // Isi dropdown #filter-bulan-highlight dengan Jan-Des (opsi "Semua bulan" sudah ada di HTML).
        // Sama seperti dropdown tahun, isinya statis jadi cukup diisi sekali.
        const NAMA_BULAN = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        function isiDropdownBulan() {
            const select = document.getElementById('filter-bulan-highlight');
            if (!select || select.options.length > 1) return; // sudah pernah diisi (lebih dari opsi "Semua bulan")

            NAMA_BULAN.forEach((nama, i) => {
                const opt = document.createElement('option');
                opt.value = i; // index 0-11, cocok dengan index titik data bulanan di chart
                opt.textContent = nama;
                select.appendChild(opt);
            });
        }

        // Catatan: dulu bulanHighlightAktif (0-11) dipakai plugin highlightBulanPlugin di bawah
        // untuk menggambar pita warna di atas 12 titik bulan -- sekarang digantikan
        // bulanTerpilihDrilldown (di bawah), yang benar-benar fetch ulang data harian, bukan
        // cuma highlight visual. Variabelnya sengaja dibiarkan (selalu null, tidak pernah
        // di-set lagi) supaya highlightBulanPlugin tetap aman jadi no-op, bukan dihapus, biar
        // tidak perlu bongkar pastikanPluginTerdaftar()/Chart.register() di bawah.
        let bulanHighlightAktif = null;

        // Bulan (1-12) yang lagi dipilih untuk drill-down di mode Tahunan, atau null kalau
        // "Semua bulan" (tampilkan 12 titik agregat per bulan seperti biasa). Beda dari
        // bulanHighlightAktif dulu: sekarang memicu FETCH ULANG ke mode "bulanan" (detail
        // harian dalam bulan itu), bukan cuma menggambar pita warna di atas data yang sama.
        let bulanTerpilihDrilldown = null;

        // Dipanggil saat dropdown "Lihat detail bulan" diganti (hanya aktif di mode Tahunan).
        // "Semua bulan" -> balik ke 12 titik agregat per bulan (tahun penuh).
        // Bulan tertentu -> fetch mode "bulanan" supaya chart tampilkan tiap HARI dalam bulan itu.
        function ubahBulanTerpilih() {
            const nilai = document.getElementById('filter-bulan-highlight').value;
            bulanTerpilihDrilldown = (nilai === '') ? null : parseInt(nilai, 10);
            updateChart();
        }

        // Dipanggil saat dropdown "Per Jam / Per Hari / ..." diganti.
        // Tampilkan kontrol input yang sesuai dan refresh chart.
        function ubahModePeriode() {
            const mode = document.getElementById('periode-mode-select').value;

            // Sembunyikan semua wrapper kontrol dulu
            const wrapTgl = document.getElementById('wrapper-tanggal-perjam'); if (wrapTgl) wrapTgl.style.display = 'none';
            document.getElementById('wrapper-minggu').style.display           = 'none';
            document.getElementById('wrapper-bulan').style.display            = 'none';
            document.getElementById('wrapper-tahun').style.display            = 'none';

            // Tampilkan yang relevan
            if (mode === 'perjam' || mode === 'perhari') document.getElementById('wrapper-tanggal-perjam').style.display = '';
            if (mode === 'perminggu') document.getElementById('wrapper-minggu').style.display = '';
            if (mode === 'perbulan')  document.getElementById('wrapper-bulan').style.display  = '';
            if (mode === 'pertahun' || mode === '5tahun') document.getElementById('wrapper-tahun').style.display = '';

            // Reset drill-down bulan saat keluar dari pertahun
            if (mode !== 'pertahun') {
                document.getElementById('filter-bulan-highlight').value = '';
                bulanTerpilihDrilldown = null;
            }

            const deskripsiPerjam = {
                'deskripsi-chart-ng-product': 'Rasio NG (%) dan jumlah Product/Output (pcs) per jam pada tanggal terpilih.',
                'deskripsi-chart-loss-time': 'Total durasi stop kategori Loss (menit) per jam pada tanggal terpilih.',
                'deskripsi-chart-product-time': '(D) Actual Cycle Time — waktu rata-rata menghasilkan 1 pcs per jam.',
                'deskripsi-chart-machine-rate': '(K) Operation Time Ratio — rasio waktu mesin beroperasi per jam.'
            };
            const deskripsiPerhari = {
                'deskripsi-chart-ng-product': 'Rasio NG (%) dan jumlah Product/Output (pcs) per hari pada bulan terpilih.',
                'deskripsi-chart-loss-time': 'Total durasi stop kategori Loss (menit) per hari pada bulan terpilih.',
                'deskripsi-chart-product-time': '(D) Actual Cycle Time — waktu rata-rata menghasilkan 1 pcs per hari.',
                'deskripsi-chart-machine-rate': '(K) Operation Time Ratio — rasio waktu mesin beroperasi per hari.'
            };
            const deskripsiPerminggu = {
                'deskripsi-chart-ng-product': 'Rasio NG (%) dan jumlah Product/Output (pcs) per hari dalam minggu terpilih.',
                'deskripsi-chart-loss-time': 'Total durasi stop kategori Loss (menit) per hari dalam minggu terpilih.',
                'deskripsi-chart-product-time': '(D) Actual Cycle Time — waktu rata-rata menghasilkan 1 pcs per hari.',
                'deskripsi-chart-machine-rate': '(K) Operation Time Ratio — rasio waktu mesin beroperasi per hari.'
            };
            const deskripsiPerbulan = {
                'deskripsi-chart-ng-product': 'Rata-rata Rasio NG (%) dan total Product/Output (pcs) per bulan untuk tahun terpilih.',
                'deskripsi-chart-loss-time': 'Total durasi stop kategori Loss (menit) per bulan untuk tahun terpilih.',
                'deskripsi-chart-product-time': '(D) Actual Cycle Time — rata-rata waktu menghasilkan 1 pcs per bulan.',
                'deskripsi-chart-machine-rate': '(K) Operation Time Ratio — rata-rata rasio waktu mesin beroperasi per bulan.'
            };
            const deskripsiPertahun = {
                'deskripsi-chart-ng-product': 'Rata-rata Rasio NG (%) dan total Product/Output (pcs) per tahun.',
                'deskripsi-chart-loss-time': 'Total durasi stop kategori Loss (menit) per tahun.',
                'deskripsi-chart-product-time': '(D) Actual Cycle Time — rata-rata waktu menghasilkan 1 pcs per tahun.',
                'deskripsi-chart-machine-rate': '(K) Operation Time Ratio — rata-rata rasio waktu mesin beroperasi per tahun.'
            };
            const deskripsi5Tahun = {
                'deskripsi-chart-ng-product': 'Rata-rata Rasio NG (%) dan total Product/Output (pcs) per bulan, 5 tahun terakhir.',
                'deskripsi-chart-loss-time': 'Total durasi stop kategori Loss (menit) per bulan, 5 tahun terakhir.',
                'deskripsi-chart-product-time': '(D) Actual Cycle Time — rata-rata waktu menghasilkan 1 pcs per bulan, 5 tahun terakhir.',
                'deskripsi-chart-machine-rate': '(K) Operation Time Ratio — rata-rata rasio waktu mesin beroperasi per bulan, 5 tahun terakhir.'
            };
        const peta = { perjam: deskripsiPerjam, perhari: deskripsiPerhari, perminggu: deskripsiPerminggu,
                           perbulan: deskripsiPerbulan, pertahun: deskripsiPertahun, '5tahun': deskripsi5Tahun };
            const sumber = peta[mode] || deskripsiPerjam;
            for (const id in sumber) {
                const el = document.getElementById(id);
                if (el) el.innerText = sumber[id];
            }

            updateChart();
        }

        async function updateChart() {
            const canvasProduksi = document.getElementById('chartProduksi');
            if (!canvasProduksi) return;

            pastikanPluginTerdaftar();
            isiDropdownTahun();
            isiDropdownBulan();
            setRentangTanggalChart();

            const modeSelect = document.getElementById('periode-mode-select');
            const mode = modeSelect ? modeSelect.value : 'perjam';
            const tipeChart = document.getElementById('chart-type-select')?.value || localStorage.getItem('chartType') || 'bar';
            const labelPart = document.getElementById('label-part-produksi');
            if (labelPart) labelPart.style.display = 'none';

            if (mode === '5tahun') {
                const response = await fetch(`api/grafik_harian.php?line=${encodeURIComponent(currentLine)}&mode=5tahun`, { cache: "no-store" });
                const hasil = await response.json();
                const titik = hasil.data;
                renderChart(tipeChart, titik.map(p => p.waktu), titik.map(p => p.input), titik.map(p => p.output), hasil.total_input, hasil.total_output);
                updatePerformanceCharts(null, tipeChart, null, '5tahun');
                document.getElementById('judul-chart-produksi').textContent = 'Produksi (5 Tahun Terakhir)';
                return;
            }

            if (mode === 'pertahun') {
                // Agregat per bulan untuk tahun terpilih (sama dengan mode tahunan lama)
                isiDropdownTahun();
                const tahun = parseInt(document.getElementById('filter-tahun').value) || new Date().getFullYear();

                if (bulanTerpilihDrilldown !== null) {
                    // Drill-down: detail harian dalam bulan tertentu
                    const bulanFormat = `${tahun}-${String(bulanTerpilihDrilldown).padStart(2,'0')}`;
                    const response = await fetch(`api/grafik_harian.php?line=${encodeURIComponent(currentLine)}&mode=bulanan&bulan=${bulanFormat}`, { cache: "no-store" });
                    const hasil = await response.json();
                    const titik = hasil.data;
                    renderChart(tipeChart, titik.map(p => p.waktu), titik.map(p => p.input), titik.map(p => p.output), hasil.total_input, hasil.total_output);
                    updatePerformanceCharts(null, tipeChart, tahun, 'bulanan', bulanFormat);
                    document.getElementById('judul-chart-produksi').textContent = `Produksi (${NAMA_BULAN[bulanTerpilihDrilldown-1]} ${tahun})`;
                } else {
                    const response = await fetch(`api/grafik_harian.php?line=${encodeURIComponent(currentLine)}&mode=tahunan&tahun=${tahun}`, { cache: "no-store" });
                    const hasil = await response.json();
                    const titik = hasil.data;
                    renderChart(tipeChart, titik.map(p => p.waktu), titik.map(p => p.input), titik.map(p => p.output), hasil.total_input, hasil.total_output);
                    updatePerformanceCharts(null, tipeChart, tahun, null);
                    document.getElementById('judul-chart-produksi').textContent = `Produksi Tahun ${tahun}`;
                }
                return;
            }

            if (mode === 'perminggu') {
                // Per hari dalam 1 minggu ISO
                const inputMinggu = document.getElementById('filter-minggu');
                if (!inputMinggu.value) {
                    // Set ke minggu sekarang sebagai default
                    const now = new Date();
                    const tahunIso = now.getFullYear();
                    const noMinggu = String(getISOWeek(now)).padStart(2,'0');
                    inputMinggu.value = `${tahunIso}-W${noMinggu}`;
                }
                const minggu = inputMinggu.value; // format: "2026-W35"
                const response = await fetch(`api/grafik_harian.php?line=${encodeURIComponent(currentLine)}&mode=mingguan&minggu=${encodeURIComponent(minggu)}`, { cache: "no-store" });
                const hasil = await response.json();
                const titik = hasil.data;
                renderChart(tipeChart, titik.map(p => p.waktu), titik.map(p => p.input), titik.map(p => p.output), hasil.total_input, hasil.total_output);
                updatePerformanceCharts(null, tipeChart, null, 'mingguan', minggu);
                document.getElementById('judul-chart-produksi').textContent = `Produksi Minggu ${minggu}`;
                return;
            }

            if (mode === 'perbulan') {
                // Per hari dalam 1 bulan
                const inputBulan = document.getElementById('filter-bulan-input');
                if (!inputBulan.value) {
                    inputBulan.value = new Date().toISOString().slice(0,7); // YYYY-MM
                }
                const bulanFormat = inputBulan.value;
                const response = await fetch(`api/grafik_harian.php?line=${encodeURIComponent(currentLine)}&mode=bulanan&bulan=${encodeURIComponent(bulanFormat)}`, { cache: "no-store" });
                const hasil = await response.json();
                const titik = hasil.data;
                renderChart(tipeChart, titik.map(p => p.waktu), titik.map(p => p.input), titik.map(p => p.output), hasil.total_input, hasil.total_output);
                updatePerformanceCharts(null, tipeChart, null, 'bulanan', bulanFormat);
                const [thn, bln] = bulanFormat.split('-');
                document.getElementById('judul-chart-produksi').textContent = `Produksi ${NAMA_BULAN[parseInt(bln)-1]} ${thn}`;
                return;
            }

            // mode 'perjam' atau 'perhari': tampilkan per-jam untuk tanggal terpilih
            const tanggalInput = document.getElementById('filter-tanggal');
            if (tanggalInput && !tanggalInput.value) {
                tanggalInput.value = new Date().toISOString().split('T')[0];
            }
            const tanggal = tanggalInput ? tanggalInput.value : getTanggalAktif();
            const teksTgl = document.getElementById('teks-tanggal');
            if (teksTgl) teksTgl.innerText = formatTanggalIndo(tanggal);
            if (document.getElementById('input-value')) {
                updateData(tanggal);
            }

            const response = await fetch(`api/grafik_harian.php?line=${encodeURIComponent(currentLine)}&tanggal=${tanggal}`, { cache: "no-store" });
            const hasil = await response.json();
            const titik = hasil.data;
            renderChart(tipeChart, titik.map(p => p.waktu), titik.map(p => p.input), titik.map(p => p.output), hasil.total_input, hasil.total_output);
            updatePerformanceCharts(tanggal, tipeChart, null);
            ambilPartAktif();
            document.getElementById('judul-chart-produksi').textContent = `Produksi ${formatTanggalIndo(tanggal)}`;
        }

        // Helper: hitung nomor minggu ISO dari sebuah Date object
        function getISOWeek(date) {
            const d = new Date(Date.UTC(date.getFullYear(), date.getMonth(), date.getDate()));
            d.setUTCDate(d.getUTCDate() + 4 - (d.getUTCDay() || 7));
            const yearStart = new Date(Date.UTC(d.getUTCFullYear(), 0, 1));
            return Math.ceil((((d - yearStart) / 86400000) + 1) / 7);
        }

        // Ambil data performance (NG Ratio, Product, Loss Time, Product Time, Machine Rate) lalu
        // render ke 4 chart. Mode harian: per-sesi untuk `tanggal`. Mode tahunan: agregat per-bulan
        // untuk `tahun`. Mode "5tahun": agregat per-bulan lintas 5 tahun terakhir. Mode "bulanan":
        // drill-down per-hari untuk `bulanFormat` (YYYY-MM). Dipanggil dari updateChart() supaya
        // selalu ikut ter-refresh tiap ganti tanggal/tahun/bulan, ganti bentuk chart, ganti mode,
        // dan auto-refresh.
        async function updatePerformanceCharts(tanggal, tipeChart, tahun, modeKhusus, bulanFormat) {
            let url;
            if (modeKhusus === '5tahun') {
                url = `api/grafik_performance.php?line=${encodeURIComponent(currentLine)}&mode=5tahun`;
            } else if (modeKhusus === 'bulanan') {
                url = `api/grafik_performance.php?line=${encodeURIComponent(currentLine)}&mode=bulanan&bulan=${bulanFormat}`;
            } else if (modeKhusus === 'mingguan') {
                url = `api/grafik_performance.php?line=${encodeURIComponent(currentLine)}&mode=mingguan&minggu=${bulanFormat}`;
            } else if (tahun) {
                url = `api/grafik_performance.php?line=${encodeURIComponent(currentLine)}&mode=tahunan&tahun=${tahun}`;
            } else {
                url = `api/grafik_performance.php?line=${encodeURIComponent(currentLine)}&tanggal=${tanggal}`;
            }

            try {
                const response = await fetch(url, { cache: "no-store" });
                if (!response.ok) throw new Error('HTTP ' + response.status);
                const hasil = await response.json();

                const titik = hasil.data || [];
                const labels = titik.map(p => p.label);
                labelSesiPerformanceAktif = labels;

                const elEmptyLoss = document.getElementById('empty-chartLossTime');
                const elCanvasLoss = document.getElementById('chartLossTime');
                const elEmptyProd = document.getElementById('empty-chartProductTime');
                const elCanvasProd = document.getElementById('chartProductTime');

                if (titik.length === 0) {
                    if (elEmptyLoss) elEmptyLoss.style.display = 'block';
                    if (elCanvasLoss) elCanvasLoss.style.display = 'none';
                    if (elEmptyProd) elEmptyProd.style.display = 'block';
                    if (elCanvasProd) elCanvasProd.style.display = 'none';

                    if (stateLossTime.chart) {
                        stateLossTime.chart.destroy();
                        stateLossTime.chart = null;
                    }
                    if (stateProductTime.chart) {
                        stateProductTime.chart.destroy();
                        stateProductTime.chart = null;
                    }
                    return;
                }

                if (elEmptyLoss) elEmptyLoss.style.display = 'none';
                if (elCanvasLoss) elCanvasLoss.style.display = 'block';
                if (elEmptyProd) elEmptyProd.style.display = 'none';
                if (elCanvasProd) elCanvasProd.style.display = 'block';

                renderSingleMetricChart(stateLossTime, 'chartLossTime', tipeChart, labels, titik.map(p => p.I), WARNA_LOSS, 'Loss Time (menit)');
                renderSingleMetricChart(stateProductTime, 'chartProductTime', tipeChart, labels, titik.map(p => p.D), WARNA_INPUT, 'Product Time (menit/pcs)');
            } catch (err) {
                console.error('updatePerformanceCharts: gagal memuat grafik', err);
            }
        }

        // Ambil nama part yang lagi aktif/dipilih di Machine Control, disimpan ke variabel
        // (TIDAK langsung ditampilkan). Baru ditampilkan saat salah satu bar di chart diklik.
        let partProduksiAktif = '';
        async function ambilPartAktif() {
            try {
                const resp = await fetch(`api/get_part.php?line=${encodeURIComponent(currentLine)}`, { cache: 'no-store' });
                const d = await resp.json();
                partProduksiAktif = d.part || '';
            } catch (e) {
                partProduksiAktif = '';
            }
        }

        // Dipanggil saat user klik salah satu bar di chart Produksi Per Jam
        function tampilkanPartSaatDiklik(indexJam) {
            const labelEl = document.getElementById('label-part-produksi');
            if (!partProduksiAktif) {
                labelEl.style.display = 'none';
                return;
            }
            const jam = chartProduksiLabelsAktif[indexJam] || '';
            labelEl.innerText = jam ? `Part: ${partProduksiAktif} (jam ${jam})` : `Part: ${partProduksiAktif}`;
            labelEl.style.display = '';
        }

        // ===== PLUGIN: SOROT BULAN TERPILIH (mode Tahunan) =====
        // Menggambar pita vertikal semi-transparan di belakang bar/titik data pada index bulan
        // yang aktif di dropdown "Sorot bulan" (bulanHighlightAktif, 0-11). Dipasang sebagai
        // plugin global Chart.js supaya bisa dipakai di semua chart (Produksi, NG Ratio+Product,
        // Loss Time, Product Time, Machine Rate) tanpa mengubah konfigurasi dataset masing-masing.
        // Otomatis tidak melakukan apa-apa kalau chart bukan lagi menampilkan 12 titik bulan
        // (mis. saat kembali ke mode Harian), karena bulanHighlightAktif di-reset ke null.
        const highlightBulanPlugin = {
            id: 'highlightBulanPlugin',
            beforeDatasetsDraw(chart) {
                if (bulanHighlightAktif === null || bulanHighlightAktif === undefined) return;
                const xScale = chart.scales.x;
                if (!xScale) return;

                const { ctx, chartArea } = chart;
                if (!chartArea) return;

                // xScale category (bar/line) pakai getPixelForTick; xScale linear (scatter) pakai getPixelForValue.
                const pusatPixel = xScale.getPixelForTick
                    ? xScale.getPixelForTick(bulanHighlightAktif)
                    : xScale.getPixelForValue(bulanHighlightAktif);
                if (pusatPixel === undefined || isNaN(pusatPixel)) return;

                // Lebar pita kira-kira selebar 1 slot kategori, supaya menutupi bar/titik bulan itu saja.
                const jumlahTitik = (chart.data.labels && chart.data.labels.length) || 12;
                const lebarPita = (chartArea.right - chartArea.left) / jumlahTitik;

                ctx.save();
                ctx.fillStyle = 'rgba(124, 58, 237, 0.10)'; // ungu lembut, netral terhadap semua palet warna metrik
                ctx.fillRect(pusatPixel - lebarPita / 2, chartArea.top, lebarPita, chartArea.bottom - chartArea.top);
                ctx.restore();
            }
        };

        // Chart.js CDN dimuat dengan `defer`, sedangkan script ini TIDAK, jadi `window.Chart`
        // bisa saja belum ada di titik ini. Plugin baru benar-benar didaftarkan (sekali saja)
        // saat chart pertama kali dibuat lewat pastikanPluginTerdaftar(), dipanggil dari tiap
        // fungsi render sebelum membuat instance Chart.js baru.
        let pluginHighlightSudahTerdaftar = false;
        function pastikanPluginTerdaftar() {
            if (pluginHighlightSudahTerdaftar || !window.Chart) return;
            Chart.register(highlightBulanPlugin);
            pluginHighlightSudahTerdaftar = true;
        }

        // Palet warna konsisten untuk semua jenis grafik
        const WARNA_INPUT = { solid: '#0d3b8c', soft: 'rgba(13, 59, 140, 0.75)', fill: 'rgba(13, 59, 140, 0.18)' };
        const WARNA_OUTPUT = { solid: '#16a34a', soft: 'rgba(22, 163, 74, 0.75)', fill: 'rgba(22, 163, 74, 0.18)' };
        const WARNA_NG = { solid: '#dc2626', soft: 'rgba(220, 38, 38, 0.75)', fill: 'rgba(220, 38, 38, 0.18)' };
        const WARNA_LOSS = { solid: '#ea580c', soft: 'rgba(234, 88, 12, 0.75)', fill: 'rgba(234, 88, 12, 0.18)' };
        const WARNA_RATE = { solid: '#7c3aed', soft: 'rgba(124, 58, 237, 0.75)', fill: 'rgba(124, 58, 237, 0.18)' };

        const LEGEND_STYLE = { position: 'top', align: 'end', labels: { color: '#6b6b80', usePointStyle: true, pointStyle: 'circle', boxWidth: 8, padding: 16, font: { family: 'Inter', size: 12 } } };
        const TOOLTIP_STYLE = { backgroundColor: '#ffffff', titleColor: '#2b2b3a', bodyColor: '#2b2b3a', borderColor: '#eaeaf2', borderWidth: 1, padding: 12, cornerRadius: 10, titleFont: { family: 'Inter', weight: '600' }, bodyFont: { family: 'Inter' }, boxPadding: 6 };

        function renderChart(tipe, labels, inputData, outputData, totalInput, totalOutput) {
            const ctx = document.getElementById('chartProduksi').getContext('2d');
            chartProduksiLabelsAktif = labels;

            // Kalau chart sudah ada & bentuknya sama seperti sebelumnya, cukup ganti datanya
            // saja tanpa hancurkan & bikin ulang chart-nya -- ini yang bikin refresh tiap 5 detik
            // gak lagi "berkedip". Chart cuma dihancurkan & dibuat ulang kalau bentuknya beneran ga
            if (chartProduksi !== null && chartProduksiTipeAktif === tipe) {
                if (tipe === 'pie') {
                    chartProduksi.data.datasets[0].data = [totalInput, totalOutput];
                } else if (tipe === 'scatter') {
                    chartProduksi.data.datasets[0].data = inputData.map((v, i) => ({ x: i, y: v }));
                    chartProduksi.data.datasets[1].data = outputData.map((v, i) => ({ x: i, y: v }));
                } else {
                    chartProduksi.data.labels = labels;
                    chartProduksi.data.datasets[0].data = inputData;
                    chartProduksi.data.datasets[1].data = outputData;
                }
                chartProduksi.update('none'); // 'none' = tanpa animasi transisi, biar mulus
                return;
            }

            // Chart.js tidak bisa "update" antar tipe berbeda (mis. bar -> pie),
            // jadi grafik lama dihancurkan dulu lalu dibuat ulang cuma kalau bentuknya berubah.
            if (chartProduksi !== null) {
                chartProduksi.destroy();
                chartProduksi = null;
            }
            chartProduksiTipeAktif = tipe;

            let config;

            if (tipe === 'pie') {
                // Pie tidak cocok untuk 24 titik per jam, jadi ditampilkan total Input vs Output harian
                config = {
                    type: 'pie',
                    data: {
                        labels: ['Input', 'Output'],
                        datasets: [{
                            data: [totalInput, totalOutput],
                            backgroundColor: [WARNA_INPUT.soft, WARNA_OUTPUT.soft],
                            hoverBackgroundColor: [WARNA_INPUT.solid, WARNA_OUTPUT.solid],
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: { legend: LEGEND_STYLE, tooltip: TOOLTIP_STYLE }
                    }
                };
            } else if (tipe === 'scatter') {
                config = {
                    type: 'scatter',
                    data: {
                        datasets: [
                            { label: 'Input', data: inputData.map((v, i) => ({ x: i, y: v })), backgroundColor: WARNA_INPUT.soft, pointRadius: 5, pointHoverRadius: 7 },
                            { label: 'Output', data: outputData.map((v, i) => ({ x: i, y: v })), backgroundColor: WARNA_OUTPUT.soft, pointRadius: 5, pointHoverRadius: 7 }
                        ]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: LEGEND_STYLE,
                            tooltip: { ...TOOLTIP_STYLE, callbacks: { title: (items) => chartProduksiLabelsAktif[items[0].raw.x] || '' } }
                        },
                        scales: {
                            x: { type: 'linear', ticks: { color: '#9a9ab0', font: { family: 'Inter', size: 11 }, stepSize: 1, callback: (val) => chartProduksiLabelsAktif[val] || '' }, grid: { display: false } },
                            y: { ticks: { color: '#9a9ab0', font: { family: 'Inter', size: 12 } }, grid: { color: '#f0f1f7' }, beginAtZero: true }
                        }
                    }
                };
            } else {
                // Bar, Line, dan Area memakai bentuk data yang sama (per jam), beda di styling saja
                const isLineFamily = (tipe === 'line' || tipe === 'area');
                const buatDataset = (label, data, warna) => ({
                    label: label,
                    data: data,
                    backgroundColor: tipe === 'area' ? warna.fill : warna.soft,
                    borderColor: warna.solid,
                    hoverBackgroundColor: warna.solid,
                    borderRadius: isLineFamily ? 0 : 4,
                    borderSkipped: false,
                    maxBarThickness: 28,
                    fill: tipe === 'area',
                    tension: 0.3,
                    pointRadius: isLineFamily ? 3 : 0,
                    borderWidth: isLineFamily ? 2 : 0
                });

                config = {
                    type: isLineFamily ? 'line' : 'bar',
                    data: {
                        labels: labels,
                        datasets: [
                            buatDataset('Input', inputData, WARNA_INPUT),
                            buatDataset('Output', outputData, WARNA_OUTPUT)
                        ]
                    },
                    options: {
                        responsive: true,
                        animation: { duration: 400 },
                        interaction: { mode: 'index', intersect: false },
                        plugins: { legend: LEGEND_STYLE, tooltip: TOOLTIP_STYLE },
                        onClick: (evt, elements) => {
                            if (elements.length > 0) {
                                tampilkanPartSaatDiklik(elements[0].index);
                            }
                        },
                        scales: {
                            x: { ticks: { color: '#9a9ab0', font: { family: 'Inter', size: 11 }, maxRotation: 0, autoSkip: true, maxTicksLimit: 10 }, grid: { display: false } },
                            y: { ticks: { color: '#9a9ab0', font: { family: 'Inter', size: 12 } }, grid: { color: '#f0f1f7' }, beginAtZero: true }
                        }
                    }
                };
            }

            chartProduksi = new Chart(ctx, config);
        }

        // ===== CHART GENERIK: 1 METRIK PER SESI (Loss Time, Product Time, Machine Rate) =====
        // `state` = { chart, tipe } -- objek kecil biar instance Chart.js-nya bisa dipakai ulang
        // (update data) atau dihancurkan & dibuat ulang (ganti bentuk), sama seperti chartProduksi.
        function renderSingleMetricChart(state, canvasId, tipe, labels, data, warna, labelSeries) {
            const canvas = document.getElementById(canvasId);
            if (!canvas) return;
            const ctx = canvas.getContext('2d');

            if (state.chart !== null && state.tipe === tipe) {
                if (tipe === 'pie') {
                    state.chart.data.labels = labels;
                    state.chart.data.datasets[0].data = data;
                } else if (tipe === 'scatter') {
                    state.chart.data.datasets[0].data = data.map((v, i) => ({ x: i, y: v }));
                } else {
                    state.chart.data.labels = labels;
                    state.chart.data.datasets[0].data = data;
                }
                state.chart.update('none');
                return;
            }

            if (state.chart !== null) {
                state.chart.destroy();
                state.chart = null;
            }
            state.tipe = tipe;

            let config;

            if (tipe === 'pie') {
                config = {
                    type: 'pie',
                    data: {
                        labels: labels,
                        datasets: [{ data: data, backgroundColor: labels.map((_, i) => i % 2 === 0 ? warna.soft : warna.fill), hoverBackgroundColor: warna.solid, borderWidth: 0 }]
                    },
                    options: { responsive: true, plugins: { legend: LEGEND_STYLE, tooltip: TOOLTIP_STYLE } }
                };
            } else if (tipe === 'scatter') {
                config = {
                    type: 'scatter',
                    data: { datasets: [{ label: labelSeries, data: data.map((v, i) => ({ x: i, y: v })), backgroundColor: warna.soft, pointRadius: 5, pointHoverRadius: 7 }] },
                    options: {
                        responsive: true,
                        plugins: { legend: LEGEND_STYLE, tooltip: { ...TOOLTIP_STYLE, callbacks: { title: (items) => labels[items[0].raw.x] || '' } } },
                        scales: {
                            x: { type: 'linear', ticks: { color: '#9a9ab0', font: { family: 'Inter', size: 11 }, stepSize: 1, callback: (val) => labels[val] || '' }, grid: { display: false } },
                            y: { ticks: { color: '#9a9ab0', font: { family: 'Inter', size: 12 } }, grid: { color: '#f0f1f7' }, beginAtZero: true }
                        }
                    }
                };
            } else {
                const isLineFamily = (tipe === 'line' || tipe === 'area');
                config = {
                    type: isLineFamily ? 'line' : 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: labelSeries,
                            data: data,
                            backgroundColor: tipe === 'area' ? warna.fill : warna.soft,
                            borderColor: warna.solid,
                            hoverBackgroundColor: warna.solid,
                            borderRadius: isLineFamily ? 0 : 4,
                            borderSkipped: false,
                            maxBarThickness: 28,
                            fill: tipe === 'area',
                            tension: 0.3,
                            pointRadius: isLineFamily ? 3 : 0,
                            borderWidth: isLineFamily ? 2 : 0
                        }]
                    },
                    options: {
                        responsive: true,
                        animation: { duration: 400 },
                        interaction: { mode: 'index', intersect: false },
                        plugins: { legend: LEGEND_STYLE, tooltip: TOOLTIP_STYLE },
                        scales: {
                            x: { ticks: { color: '#9a9ab0', font: { family: 'Inter', size: 11 }, maxRotation: 0, autoSkip: true, maxTicksLimit: 10 }, grid: { display: false } },
                            y: { ticks: { color: '#9a9ab0', font: { family: 'Inter', size: 12 } }, grid: { color: '#f0f1f7' }, beginAtZero: true }
                        }
                    }
                };
            }

            state.chart = new Chart(ctx, config);
        }

        // State tanggal filter histori aktif
        let tanggalFilterHistoriAktif = null;

        // ===== TABEL RIWAYAT =====
        async function updateHistoriTable(filterTanggal = undefined) {
            if (filterTanggal !== undefined) {
                tanggalFilterHistoriAktif = filterTanggal;
            }
            // Simpan posisi scroll dulu, sebelum tabel dibangun ulang
            const posisiScroll = window.scrollY;

            try {
                let url = `api/histori.php?line=${encodeURIComponent(currentLine)}`;
                if (tanggalFilterHistoriAktif) {
                    url += `&tanggal=${encodeURIComponent(tanggalFilterHistoriAktif)}`;
                }
                const response = await fetch(url, { cache: "no-store" });
                const rawData = await response.json();
                const data = Array.isArray(rawData) ? rawData : (rawData.data || []);

                const totalInput = data.reduce((jumlah, baris) => jumlah + (Number(baris.input) || 0), 0);
                const totalOutput = data.reduce((jumlah, baris) => jumlah + (Number(baris.output) || 0), 0);
                const yieldKeseluruhan = totalInput > 0 ? ((totalOutput / totalInput) * 100).toFixed(1) : "0.0";

                const elInput = document.getElementById("rw-total-input");
                const elOutput = document.getElementById("rw-total-output");
                const elYield = document.getElementById("rw-yield");
                const tbody = document.getElementById("isi-tabel-histori");

                if (elInput) elInput.innerText = totalInput;
                if (elOutput) elOutput.innerText = totalOutput;
                if (elYield) elYield.innerText = yieldKeseluruhan + "%";
                if (!tbody) return;
                tbody.innerHTML = "";

                if (data.length === 0) {
                    const tr = document.createElement("tr");
                    const pesan = tanggalFilterHistoriAktif
                        ? `Belum ada catatan data riwayat produksi untuk tanggal ${formatTanggalIndo(tanggalFilterHistoriAktif)}.`
                        : `Belum ada catatan data riwayat produksi.`;
                    tr.innerHTML = `<td colspan="6" style="text-align:center; color:#9a9ab0; padding:20px;">${pesan}</td>`;
                    tbody.appendChild(tr);
                } else {
                    data.forEach(baris => {
                        const tr = document.createElement("tr");
                        const urlDownload = `${APP_BASE_PATH}/export/export_riwayat_performance.php?line=${encodeURIComponent(currentLine)}&tanggal=${baris.tanggal}&shift=${encodeURIComponent(baris.shift)}`;


                        tr.innerHTML = `
                            <td class="td-toggle"><a class="btn-toggle-detail" href="index.php?line=${encodeURIComponent(currentLine)}&view=detail&tanggal=${baris.tanggal}" data-nav data-line="${currentLine}" data-view="detail" data-tanggal="${baris.tanggal}" title="Lihat detail per jam">&#9656;</a></td>
                            <td class="td-tanggal-klik" onclick="cariSesiProduksiTanggal('${baris.tanggal}');" title="Klik untuk menampilkan sesi produksi tanggal ini">${formatTanggalIndo(baris.tanggal)}</td>
                            <td><span class="badge-shift badge-shift-${(baris.shift || '').toLowerCase()}">${baris.shift}</span></td>
                            <td><a href="index.php?line=${encodeURIComponent(currentLine)}&view=detail-shift" class="btn-detail-shift" data-nav data-line="${currentLine}" data-view="detail-shift" data-tanggal="${baris.tanggal}" data-shift="${baris.shift}">Lihat Detail</a></td>
                            <td><strong>${baris.eff_persen}%</strong></td>
                            <td><a href="${urlDownload}" class="btn-download-row" title="Download Excel Hasil Produksi (${baris.shift})">&#8681;</a></td>
                        `;
                        tbody.appendChild(tr);
                    });
                }
            } catch (err) {
                console.error("Gagal memuat histori data:", err);
            }

            // Balikin posisi scroll ke tempat semula, setelah browser selesai gambar ulang
            requestAnimationFrame(() => {
                window.scrollTo(0, posisiScroll);
            });
        }

        // ===== PENCARIAN SESI PRODUKSI (RIWAYAT PRODUKSI) =====
        async function cariSesiProduksiTanggal(tanggalInput) {
            const inputEl = document.getElementById('filter-tanggal-produksi');
            let tgl = tanggalInput;
            if (!tgl && inputEl) {
                tgl = inputEl.value;
            }
            if (!tgl) {
                tgl = new Date().toISOString().split('T')[0];
            }
            if (inputEl) {
                inputEl.value = tgl;
            }

            const labelTgl = formatTanggalIndo(tgl);
            const labelAktifEl = document.getElementById('rw-label-tanggal-aktif');
            const sesiTglLabel = document.getElementById('rw-sesi-tgl-label');
            const containerEl = document.getElementById('rw-hasil-sesi-container');
            const listEl = document.getElementById('rw-daftar-sesi-list');
            const kosongEl = document.getElementById('rw-sesi-kosong');
            const countBadge = document.getElementById('rw-sesi-count-badge');
            const rataEffEl = document.getElementById('rw-sesi-rata-eff');

            if (labelAktifEl) labelAktifEl.innerText = labelTgl;
            if (sesiTglLabel) sesiTglLabel.innerText = labelTgl;
            if (containerEl) containerEl.style.display = '';

            // Update tabel histori untuk tanggal yang dicari
            updateHistoriTable(tgl);

            // Fetch data sesi produksi dari api/riwayat_performance
            try {
                if (listEl) {
                    listEl.style.display = '';
                    listEl.innerHTML = '<div style="color:#6b6b80; font-size:12.5px; padding:12px 0;">Memuat sesi produksi...</div>';
                }
                const resp = await fetch(`api/riwayat_performance.php?line=${encodeURIComponent(currentLine)}&tanggal=${tgl}`, { cache: 'no-store' });
                const d = await resp.json();

                if (countBadge) countBadge.innerText = `${d.jumlah || 0} sesi`;
                if (rataEffEl) rataEffEl.innerText = (d.rata_eff_v2_persen || 0) + '%';

                if (listEl) listEl.innerHTML = '';

                if (!d.riwayat || d.riwayat.length === 0) {
                    if (kosongEl) {
                        kosongEl.innerText = `Belum ada sesi produksi yang tercatat pada tanggal ${labelTgl}.`;
                        kosongEl.style.display = '';
                    }
                    if (listEl) listEl.style.display = 'none';
                } else {
                    if (kosongEl) kosongEl.style.display = 'none';
                    if (listEl) listEl.style.display = '';

                    d.riwayat.forEach(r => {
                        riwayatPerformanceById[r.id] = r;

                        const effUntukKartu = r.v2_tersedia ? r.EFF_v2_persen : r.L_persen;
                        const labelRumusLama = r.v2_tersedia ? '' : ' <span style="color:#b45309; font-size:10px;">(rumus lama)</span>';

                        const kartu = document.createElement('div');
                        kartu.className = 'riwayat-ph-card';
                        kartu.innerHTML = `
                            <div class="riwayat-ph-card-main">
                                <div class="riwayat-ph-card-title">
                                    Sesi #${r.nomor_sesi} — ${escapeHtml(r.part)}
                                    <span class="riwayat-ph-card-id">${r.jam_mulai} - ${r.jam_selesai}</span>
                                </div>
                                <div class="riwayat-ph-card-sub">
                                    Output: <strong>${r.F} pcs</strong> | NG: <strong>${r.E_persen}%</strong> | Waktu Kerja (B): <strong>${r.B}m</strong> | Stop: <strong>${r.A}m</strong>
                                </div>
                            </div>
                            <div class="riwayat-ph-card-eff">
                                <div class="riwayat-ph-card-eff-val">${effUntukKartu}%${labelRumusLama}</div>
                                <div class="riwayat-ph-card-eff-lbl">EFF Sesi</div>
                            </div>
                            <div class="riwayat-ph-card-actions">
                                <button type="button" class="btn-detail-shift" onclick="bukaDetailPerformance(${r.id})">Lihat Detail</button>
                                <a href="${APP_BASE_PATH}/export/export_riwayat_performance.php?id=${r.id}" class="btn-download-row" title="Download Excel sesi ini">&#8681;</a>

                            </div>
                        `;
                        listEl.appendChild(kartu);
                    });
                }
            } catch (err) {
                console.error("Gagal memuat sesi produksi:", err);
                if (listEl) listEl.innerHTML = '<div style="color:#e05252; font-size:12.5px; padding:10px 0;">Gagal memuat sesi produksi. Silakan coba lagi.</div>';
            }
        }

        function setTanggalProduksiCari(mode) {
            const inputEl = document.getElementById('filter-tanggal-produksi');
            let tglTarget = new Date();
            if (mode === 'kemarin') {
                tglTarget.setDate(tglTarget.getDate() - 1);
            }
            const yyyy = tglTarget.getFullYear();
            const mm = String(tglTarget.getMonth() + 1).padStart(2, '0');
            const dd = String(tglTarget.getDate()).padStart(2, '0');
            const strTgl = `${yyyy}-${mm}-${dd}`;
            if (inputEl) inputEl.value = strTgl;
            cariSesiProduksiTanggal(strTgl);
        }

        function resetTanggalProduksiCari() {
            const inputEl = document.getElementById('filter-tanggal-produksi');
            const labelAktifEl = document.getElementById('rw-label-tanggal-aktif');
            const containerEl = document.getElementById('rw-hasil-sesi-container');

            if (inputEl) inputEl.value = '';
            if (labelAktifEl) labelAktifEl.innerText = 'Semua Tanggal';
            if (containerEl) containerEl.style.display = 'none';

            updateHistoriTable(null);
        }

        function setTanggalPh(mode) {
            const input = document.getElementById('filter-tanggal-ph');
            if (!input) return;
            let tglTarget = new Date();
            if (mode === 'kemarin') {
                tglTarget.setDate(tglTarget.getDate() - 1);
            }
            const yyyy = tglTarget.getFullYear();
            const mm = String(tglTarget.getMonth() + 1).padStart(2, '0');
            const dd = String(tglTarget.getDate()).padStart(2, '0');
            const strTgl = `${yyyy}-${mm}-${dd}`;
            input.value = strTgl;
            loadRiwayatPerformance(strTgl);
        }

        // ===== VIEW DETAIL HARIAN (Input & Output terpisah) =====
        function renderTabelJam(daftarEvent) {
            if (daftarEvent.length === 0) {
                return `<p style="color:#9a9ab0; font-size:13px; padding:8px 0;">Tidak ada catatan pada tanggal ini.</p>`;
            }
            let html = `<table class="tabel-detail-jam"><thead><tr><th>Jam</th><th>Jumlah</th></tr></thead><tbody>`;
            daftarEvent.forEach(ev => {
                html += `<tr><td>${ev.jam}</td><td>${ev.jumlah}</td></tr>`;
            });
            html += `</tbody></table>`;
            return html;
        }

        async function loadDetailHarian(line, tanggal) {
            const elInput = document.getElementById("dt-isi-input");
            const elOutput = document.getElementById("dt-isi-output");
            if (!elInput || !elOutput) return;

            const response = await fetch(`api/detail_harian.php?line=${encodeURIComponent(line)}&tanggal=${tanggal}`, { cache: "no-store" });
            const hasil = await response.json();

            const daftarInput = [];
            const daftarOutput = [];
            (hasil.data || []).forEach(ev => {
                const item = { jam: ev.jam, jumlah: ev.jumlah ?? "" };
                if (ev.tipe === "input") daftarInput.push(item);
                else daftarOutput.push(item);
            });

            const elTotalIn = document.getElementById("dt-total-input");
            const elTotalOut = document.getElementById("dt-total-output");
            const elTotalYield = document.getElementById("dt-yield");
            if (elTotalIn) elTotalIn.innerText = hasil.total_input;
            if (elTotalOut) elTotalOut.innerText = hasil.total_output;
            if (elTotalYield) elTotalYield.innerText = hasil.yield + "%";

            // Simpan posisi scroll dulu, sebelum tabel diganti - biar gak lompat ke atas tiap auto-refresh
            const posisiScroll = window.scrollY;

            elInput.innerHTML = renderTabelJam(daftarInput);
            elOutput.innerHTML = renderTabelJam(daftarOutput);

            requestAnimationFrame(() => {
                window.scrollTo(0, posisiScroll);
            });
        }

        function escapeHtml(teks) {
            const div = document.createElement('div');
            div.innerText = teks;
            return div.innerHTML;
        }

        // ===== VIEW: DETAIL SHIFT =====
        // Jam mulai/selesai tiap shift -- dipakai untuk navigasi Prev/Next & filter manual.
        const DS_JAM_SHIFT = {
            Pagi:  { mulai: '06:55', selesai: '15:50' },
            Sore:  { mulai: '15:50', selesai: '23:20' },
            Malam: { mulai: '23:20', selesai: '06:55' },
        };

        function dsOffsetTanggal(tgl, hari) {
            const d = new Date(tgl);
            d.setDate(d.getDate() + hari);
            return d.toISOString().split('T')[0];
        }

        // Dipanggil oleh renderView() saat pertama masuk ke view ini (dari tombol "Lihat Detail"
        // atau dari Back/Forward browser). tanggal & shift datang dari detailShiftParams.
        function loadDetailShift(line, tanggal, shift) {
            const elFilterTgl = document.getElementById('ds-filter-tanggal');
            const elFilterShift = document.getElementById('ds-filter-shift');
            if (!elFilterTgl || !elFilterShift) return;
            if (!tanggal) tanggal = currentTanggal;
            if (!shift) shift = 'Pagi';
            elFilterTgl.value = tanggal;
            elFilterShift.value = shift;
            dsUpdateNavShift(shift, tanggal);
            dsFetchDanRender(line, tanggal, shift);
        }

        // Dipanggil saat tombol "Tampilkan" di filter bar diklik (ganti tanggal/shift manual).
        function muatUlangDetailShift() {
            const tanggal = document.getElementById('ds-filter-tanggal').value;
            const shift   = document.getElementById('ds-filter-shift').value;
            if (!tanggal) return;
            detailShiftParams = { tanggal, shift };
            dsUpdateNavShift(shift, tanggal);
            dsFetchDanRender(currentLine, tanggal, shift);
        }

        async function dsFetchDanRender(line, tanggal, shift) {
            const btn     = document.getElementById('ds-btn-tampilkan');
            const loading = document.getElementById('ds-filter-loading');
            btn.disabled  = true;
            loading.style.display = '';

            try {
                const url = `api/detail_shift_json.php?line=${encodeURIComponent(line)}&tanggal=${encodeURIComponent(tanggal)}&shift=${encodeURIComponent(shift)}`;
                const res  = await fetch(url, { cache: 'no-store' });
                const data = await res.json();
                if (data.status !== 'success') throw new Error();
                renderHasilDetailShift(data);
            } catch (e) {
                document.getElementById('ds-konten-performance').innerHTML = '<div class="ds-kosong">Gagal memuat data. Coba lagi.</div>';
                document.getElementById('ds-konten-stop').innerHTML = '';
            } finally {
                btn.disabled = false;
                loading.style.display = 'none';
            }
        }

        function dsUpdateNavShift(shiftAktif, tgl) {
            const urutan = ['Pagi', 'Sore', 'Malam'];
            const idx    = urutan.indexOf(shiftAktif);
            const prev   = urutan[(idx - 1 + 3) % 3];
            const next   = urutan[(idx + 1) % 3];

            // Shift Malam lintas tengah malam -- kalau shift aktif Pagi, shift Malam sebelumnya
            // itu tanggalnya kemarin; kalau shift aktif Malam, shift Pagi berikutnya itu besok.
            let tglPrev = tgl;
            if (shiftAktif === 'Pagi') tglPrev = dsOffsetTanggal(tgl, -1);
            let tglNext = tgl;
            if (shiftAktif === 'Malam') tglNext = dsOffsetTanggal(tgl, 1);

            const linkPrev = document.getElementById('ds-link-prev');
            const linkNext = document.getElementById('ds-link-next');
            linkPrev.innerHTML = `&#8592; Shift ${prev}`;
            linkNext.innerHTML = `Shift ${next} &#8594;`;
            linkPrev.onclick = () => { navigateTo(currentLine, 'detail-shift', tglPrev, { tanggal: tglPrev, shift: prev }); return false; };
            linkNext.onclick = () => { navigateTo(currentLine, 'detail-shift', tglNext, { tanggal: tglNext, shift: next }); return false; };
        }

        function renderHasilDetailShift(data) {
            document.getElementById('ds-judul-shift-performance').innerText = `Shift ${data.shift}`;
            document.getElementById('ds-judul-shift-stop').innerText        = `Shift ${data.shift}`;

            document.getElementById('ds-val-input').innerText  = data.total_input;
            document.getElementById('ds-val-output').innerText = data.total_output;
            document.getElementById('ds-val-ng').innerText     = data.ng;
            document.getElementById('ds-val-yield').innerText  = `${data.yield}%`;

            // EFF card
            const effCard = document.getElementById('ds-eff-card');
            const effVal  = data.eff_avg || 0;
            document.getElementById('ds-val-eff').innerText = effVal > 0 ? effVal + '%' : '-';
            const warna = effVal >= 80 ? {bg:'#f0fdf4',border:'#bbf7d0',text:'#16a34a'}
                        : effVal >= 60 ? {bg:'#fffbeb',border:'#fde68a',text:'#d97706'}
                        :                {bg:'#fef2f2',border:'#fecaca',text:'#dc2626'};
            effCard.style.background   = warna.bg;
            effCard.style.border       = `1.5px solid ${warna.border}`;
            document.getElementById('ds-val-eff').style.color = warna.text;
            document.getElementById('ds-eff-unit').innerText = effVal >= 80 ? '✓ Target tercapai'
                : effVal >= 60 ? '⚠ Perlu perhatian' : (effVal > 0 ? '✗ Di bawah target' : 'Belum ada data');

            // Tabel performance
            const kontenPerf = document.getElementById('ds-konten-performance');
            document.getElementById('ds-meta-performance').innerText = `${(data.sesi_performance || []).length} sesi tercatat`;
            if (!data.sesi_performance || data.sesi_performance.length === 0) {
                kontenPerf.innerHTML = `<div class="ds-kosong">Belum ada data performance untuk shift ini.</div>`;
            } else {
                let baris = data.sesi_performance.map(s => {
                    const l = s.l_persen;
                    const cls = l >= 80 ? 'hijau' : l >= 60 ? 'kuning' : 'merah';
                    return `<tr>
                        <td><strong>${escapeHtml(s.part)}</strong></td>
                        <td>${s.jam_mulai} – ${s.jam_selesai}</td>
                        <td>${s.c} mnt</td>
                        <td>${s.h_persen}%</td>
                        <td>${s.j_persen}%</td>
                        <td>${s.k_persen}%</td>
                        <td><span class="ds-eff-chip ${cls}">${l}%</span></td>
                    </tr>`;
                }).join('');
                kontenPerf.innerHTML = `<table><thead><tr>
                    <th>Part</th><th>Jam</th><th>CT Standar</th><th>Finish Good</th><th>Ability</th><th>Op. Time</th><th>EFF</th>
                </tr></thead><tbody>${baris}</tbody></table>`;
            }

            // Tabel stop
            const kontenStop = document.getElementById('ds-konten-stop');
            const totalMnt   = (data.daftar_stop || []).reduce((a, s) => a + (s.durasi_menit || 0), 0);
            const durShift   = data.durasi_shift_menit || 0;
            const pctStop    = durShift > 0 ? ((totalMnt / durShift) * 100).toFixed(1) : 0;
            document.getElementById('ds-meta-total-stop').innerText
                = `${totalMnt} mnt stop dari ${durShift} mnt shift (${pctStop}%)`;

            if (!data.daftar_stop || data.daftar_stop.length === 0) {
                kontenStop.innerHTML = `<div class="ds-kosong">Tidak ada catatan stop di shift ini.</div>`;
            } else {
                const labelStatus = { stop: 'Stop', standby: 'Standby', standby_kedip: 'Standby (Kedip)', mati: 'Mati / OFF' };
                let baris = data.daftar_stop.map(s => {
                    const kat  = (s.kategori || '').toLowerCase();
                    const cls  = kat === 'loss' ? 'ds-badge-loss' : kat === 'p' ? 'ds-badge-p' : 'ds-badge-selesai';
                    const pct  = durShift > 0 ? Math.min(100, Math.round(s.durasi_menit / durShift * 100)) : 0;
                    const jam  = s.waktu_mulai ? new Date(s.waktu_mulai.replace(' ','T')).toLocaleTimeString('id-ID',{hour:'2-digit',minute:'2-digit'}) : '-';
                    const badgePatlite = renderBadgePatlite(s.warna_patlite || s.status);
                    return `<tr>
                        <td>${escapeHtml(labelStatus[s.status] || 'Stop')}</td>
                        <td><span class="ds-badge ${cls}">${escapeHtml(s.kategori||'-')}</span></td>
                        <td>${escapeHtml(s.alasan||'-')}</td>
                        <td>${badgePatlite}</td>
                        <td><div class="ds-durasi-wrap">
                            <div class="ds-durasi-bar-bg"><div class="ds-durasi-bar-fill" style="width:${pct}%"></div></div>
                            <span class="ds-durasi-label">${s.durasi_menit ? s.durasi_menit+' mnt' : '-'}</span>
                        </div></td>
                        <td>${jam}</td>
                    </tr>`;
                }).join('');
                kontenStop.innerHTML = `<table><thead><tr>
                    <th>Status</th><th>Kategori</th><th>Alasan</th><th>Warna Patlite</th><th>Durasi</th><th>Waktu Mulai</th>
                </tr></thead><tbody>${baris}</tbody></table>`;
            }
        }

        // ===== MODAL: ALASAN MESIN BERHENTI (P / LOSS) =====
        const ALASAN_STOP_KATEGORI = {
            'P': ['Break/Meeting', 'Inventory', 'Maintenance', 'Trial Run', 'Not Planning'],
            'Loss': [
                'Trouble', 'Ganti Material', 'Ganti Type', 'Teaching',
                'Tidak Ada Material Eksternal', 'Tidak Ada Material Internal', 'Tidak Ada Material Inhouse',
                'Listrik Padam', 'Angin Turun', 'Gas Trouble', 'Shorting', 'Mold', 'Dies Tersendat', 'Speed Down Loss',
                'Loss Time'
            ],
            'Selesai': ['Selesai Produksi']
        };

        let kategoriAlasanTerpilih = null;

        function simulasiPopupStop(statusSimulasi = 'stop') {
            const d = new Date();
            const pad = n => String(n).padStart(2, '0');
            waktuMatiStopAktif = `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`;
            const judulEl = document.getElementById('alasan-stop-judul');
            let infoLabel = "Stop - Merah";
            if (statusSimulasi === 'standby') {
                infoLabel = "Standby - Kuning";
                warnaPatliteStopAktif = "Kuning";
            } else if (statusSimulasi === 'standby_kedip') {
                infoLabel = "Standby - Kuning Kedip";
                warnaPatliteStopAktif = "Kuning Kedip";
            } else if (statusSimulasi === 'mati') {
                infoLabel = "Mesin Mati / Patlite OFF";
                warnaPatliteStopAktif = "Mati / OFF";
            } else {
                warnaPatliteStopAktif = "Merah";
            }
            if (judulEl) judulEl.innerText = `Alasan Mesin Berhenti (${infoLabel} - Simulasi)`;
            bukaModalAlasanStop();
            tampilkanToastNotification(`Mesin ${currentLine} berhenti! (${infoLabel} - Simulasi)`, 'danger');
            putarSuaraNotifikasiMesinBerhenti();
        }

        function bukaModalAlasanStop() {
            document.getElementById('alasan-stop-line').innerText = currentLine;
            resetPilihanAlasanStop();
            const labelWaktu = document.getElementById('maint-waktu-mati-label');
            const valWaktu = document.getElementById('maint-waktu-mati-val');
            if (labelWaktu && valWaktu) {
                labelWaktu.innerText = waktuMatiStopAktif || 'Saat mesin berhenti (sekarang)';
                valWaktu.value = waktuMatiStopAktif || '';
            }

            // Update keterangan warna Patlite di banner modal
            const patliteLabelEl = document.getElementById('alasan-stop-patlite-label');
            const patliteBadgeEl = document.getElementById('alasan-stop-patlite-badge');
            const patliteDotEl = document.getElementById('alasan-stop-patlite-dot');
            const patliteValEl = document.getElementById('alasan-stop-warna-patlite-val');
            const warnaModal = warnaPatliteStopAktif || 'Merah';
            if (patliteValEl) patliteValEl.value = warnaModal;
            if (patliteLabelEl) patliteLabelEl.innerText = `Lampu ${warnaModal}`;
            if (patliteBadgeEl) patliteBadgeEl.innerHTML = renderBadgePatlite(warnaModal);
            if (patliteDotEl) {
                const w = warnaModal.toLowerCase();
                if (w.includes('kedip')) {
                    patliteDotEl.style.background = '#ca8a04';
                    patliteDotEl.style.boxShadow = '0 0 8px rgba(202,138,4,0.8)';
                } else if (w.includes('kuning')) {
                    patliteDotEl.style.background = '#eab308';
                    patliteDotEl.style.boxShadow = '0 0 8px rgba(234,179,8,0.8)';
                } else if (w.includes('mati') || w.includes('off')) {
                    patliteDotEl.style.background = '#64748b';
                    patliteDotEl.style.boxShadow = 'none';
                } else {
                    patliteDotEl.style.background = '#ef4444';
                    patliteDotEl.style.boxShadow = '0 0 8px rgba(239,68,68,0.8)';
                }
            }

            const infoSelesaiEl = document.getElementById('alasan-stop-info-selesai');
            const ringkasanTeksEl = document.getElementById('alasan-stop-ringkasan-teks');
            if (infoSelesaiEl && ringkasanTeksEl) {
                if (infoStopSelesaiTerakhir && infoStopSelesaiTerakhir.is_selesai) {
                    ringkasanTeksEl.innerText = `${infoStopSelesaiTerakhir.rentang} (Loss: ${infoStopSelesaiTerakhir.durasi})`;
                    infoSelesaiEl.style.display = 'inline-flex';
                } else {
                    infoSelesaiEl.style.display = 'none';
                }
            }

            document.getElementById('modal-alasan-stop').style.display = 'flex';
        }

        function tutupModalAlasanStop() {
            document.getElementById('modal-alasan-stop').style.display = 'none';
        }

        function tutupModalJikaDiLuarAlasanStop(e) {
            if (e.target.id === 'modal-alasan-stop') tutupModalAlasanStop();
        }

        // ===== AUDIO ALERT & TOAST NOTIFIKASI MESIN BERHENTI =====
        function putarSuaraNotifikasiMesinBerhenti() {
            try {
                const AudioCtx = window.AudioContext || window.webkitAudioContext;
                if (!AudioCtx) return;
                const ctx = new AudioCtx();
                if (ctx.state === 'suspended') {
                    ctx.resume();
                }
                const now = ctx.currentTime;

                // Nada 1: 587.33 Hz (D5)
                const osc1 = ctx.createOscillator();
                const gain1 = ctx.createGain();
                osc1.type = 'triangle';
                osc1.frequency.setValueAtTime(587.33, now);
                gain1.gain.setValueAtTime(0.25, now);
                gain1.gain.exponentialRampToValueAtTime(0.001, now + 0.22);
                osc1.connect(gain1);
                gain1.connect(ctx.destination);
                osc1.start(now);
                osc1.stop(now + 0.22);

                // Nada 2: 880 Hz (A5)
                const osc2 = ctx.createOscillator();
                const gain2 = ctx.createGain();
                osc2.type = 'triangle';
                osc2.frequency.setValueAtTime(880, now + 0.25);
                gain2.gain.setValueAtTime(0.35, now + 0.25);
                gain2.gain.exponentialRampToValueAtTime(0.001, now + 0.65);
                osc2.connect(gain2);
                gain2.connect(ctx.destination);
                osc2.start(now + 0.25);
                osc2.stop(now + 0.65);
            } catch (e) {
                console.log("Audio alert blocked or unsupported:", e);
            }
        }

        function tampilkanToastStandbyKedip() {
            tampilkanToastNotification(`Mesin ${currentLine} standby (Kuning berkedip)`, 'warning');
        }

        function tutupToastStandbyKedip() {
            const el = document.getElementById('toast-standby-kedip');
            if (el) el.style.display = 'none';
            clearTimeout(timerAutoTutupToastStandbyKedip);
        }

        function tampilkanToastNotification(pesan, tipe = 'warning') {
            const el = document.getElementById('toast-standby-kedip');
            if (!el) return;
            const teksEl = document.getElementById('toast-standby-kedip-teks');
            const iconEl = document.getElementById('toast-icon');
            if (teksEl) teksEl.innerText = pesan;
            if (iconEl) {
                iconEl.innerText = (tipe === 'danger') ? '🚨' : ((tipe === 'info') ? 'ℹ️' : '⚠️');
            }
            if (tipe === 'danger') {
                el.style.background = '#fef2f2';
                el.style.color = '#991b1b';
                el.style.borderColor = '#f87171';
            } else if (tipe === 'info') {
                el.style.background = '#eff6ff';
                el.style.color = '#1e40af';
                el.style.borderColor = '#60a5fa';
            } else {
                el.style.background = '#fff8e1';
                el.style.color = '#92620a';
                el.style.borderColor = '#ffc93b';
            }
            el.style.display = 'flex';

            clearTimeout(timerAutoTutupToastStandbyKedip);
            timerAutoTutupToastStandbyKedip = setTimeout(tutupToastStandbyKedip, 7000);
        }

        function resetPilihanAlasanStop() {
            kategoriAlasanTerpilih = null;
            document.getElementById('btn-kategori-P')?.classList.remove('aktif');
            document.getElementById('btn-kategori-Loss')?.classList.remove('aktif');
            document.getElementById('btn-kategori-Selesai')?.classList.remove('aktif');
            document.getElementById('btn-kategori-Maintenance')?.classList.remove('aktif');
            document.getElementById('alasan-detail-wrapper').style.display = 'none';
            document.getElementById('alasan-detail-select').innerHTML = '';
            document.getElementById('maintenance-detail-wrapper').style.display = 'none';
            const namaMesinMaint = document.getElementById('maint-nama-mesin');
            if (namaMesinMaint) namaMesinMaint.value = '';
            const selMaint = document.getElementById('maint-kategori-masalah');
            if (selMaint) selMaint.value = '';
            const noteMaint = document.getElementById('maint-note-error');
            if (noteMaint) noteMaint.value = '';
            const statusBox = document.getElementById('maint-kirim-status');
            if (statusBox) statusBox.style.display = 'none';
        }

        function pilihKategoriAlasan(kategori) {
            kategoriAlasanTerpilih = kategori;
            document.getElementById('btn-kategori-P')?.classList.toggle('aktif', kategori === 'P');
            document.getElementById('btn-kategori-Loss')?.classList.toggle('aktif', kategori === 'Loss');
            document.getElementById('btn-kategori-Selesai')?.classList.toggle('aktif', kategori === 'Selesai');
            document.getElementById('btn-kategori-Maintenance')?.classList.toggle('aktif', kategori === 'Maintenance');

            if (kategori === 'Maintenance') {
                document.getElementById('alasan-detail-wrapper').style.display = 'none';
                document.getElementById('maintenance-detail-wrapper').style.display = 'block';
                setTimeout(() => document.getElementById('maint-nama-mesin')?.focus(), 100);
                return;
            }

            document.getElementById('maintenance-detail-wrapper').style.display = 'none';
            const select = document.getElementById('alasan-detail-select');
            if (kategori === 'Selesai') {
                select.innerHTML = '<option value="Selesai Produksi" selected>Selesai Produksi</option>';
            } else {
                select.innerHTML = '<option value="">-- Pilih Alasan --</option>' +
                    ALASAN_STOP_KATEGORI[kategori].map(a => `<option value="${a}">${a}</option>`).join('');
            }

            document.getElementById('alasan-detail-wrapper').style.display = 'block';
        }

        async function simpanAlasanStop() {
            const alasan = document.getElementById('alasan-detail-select').value;
            if (!kategoriAlasanTerpilih || !alasan) return;

            const tombolSimpan = document.querySelector('#alasan-detail-wrapper .btn-simpan-keterangan');
            tombolSimpan.disabled = true;
            tombolSimpan.innerText = 'Menyimpan...';

            const formData = new FormData();
            formData.append('line', currentLine);
            formData.append('kategori', kategoriAlasanTerpilih);
            formData.append('alasan', alasan);
            const warnaKirim = document.getElementById('alasan-stop-warna-patlite-val')?.value || warnaPatliteStopAktif || 'Merah';
            formData.append('warna_patlite', warnaKirim);

            try {
                const res = await fetch('api/alasan_stop2.php', { method: 'POST', body: formData });
                await res.json().catch(() => ({ status: 'success' }));

                tutupModalAlasanStop();
                updateStatusMesin();
                if (currentView === 'machine-control') loadRingkasanAlasanStop();
                if (currentView === 'riwayat' && riwayatTabAktif === 'stop') loadRiwayatStop();

                const ALASAN_PEMICU_TUTUP_SESI = ['Ganti Type'];
                if (kategoriAlasanTerpilih === 'Selesai' || ALASAN_PEMICU_TUTUP_SESI.includes(alasan)) {
                    bukaModalPilihC();
                }
            } catch (err) {
                console.error('simpanAlasanStop: error', err);
                tutupModalAlasanStop();
                updateStatusMesin();
            } finally {

                tombolSimpan.disabled = false;
                tombolSimpan.innerText = 'Simpan Alasan';
            }
        }

        async function kirimFormMaintenance() {
            const namaMesin = document.getElementById('maint-nama-mesin').value.trim();
            const katMasalah = document.getElementById('maint-kategori-masalah').value;
            const note = document.getElementById('maint-note-error').value.trim();
            const waktuMati = document.getElementById('maint-waktu-mati-val').value;

            if (!namaMesin) {
                alert("Silakan masukkan Nama Mesin yang sedang maintenance.");
                document.getElementById('maint-nama-mesin').focus();
                return;
            }
            if (!katMasalah) {
                alert("Silakan pilih Kategori Permasalahan (Konveyor atau Kabel Putus).");
                document.getElementById('maint-kategori-masalah').focus();
                return;
            }
            if (!note) {
                alert("Silakan tuliskan Note Error / detail kendala kerusakan mesin.");
                document.getElementById('maint-note-error').focus();
                return;
            }

            const btnKirim = document.getElementById('btn-kirim-maint');
            const statusBox = document.getElementById('maint-kirim-status');
            btnKirim.disabled = true;
            btnKirim.innerText = "Mengirim...";
            statusBox.style.display = "block";
            statusBox.style.background = "#e0f2fe";
            statusBox.style.color = "#0369a1";
            statusBox.innerText = "⏳ Menyimpan ke Loss Time dan meneruskan data...";

            const formData = new FormData();
            formData.append('line', currentLine);
            formData.append('nama_mesin', namaMesin);
            formData.append('kategori_masalah', katMasalah);
            formData.append('note', note);
            if (waktuMati) formData.append('waktu_mati', waktuMati);

            try {
                const res = await fetch('api/maintenance/kirim', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();

                if (data.status === 'success') {
                    statusBox.style.background = "#dcfce7";
                    statusBox.style.color = "#15803d";
                    let pesan = "✅ Status mesin beralih ke Mode Maintenance & dicatat ke Loss Time.";
                    if (data.status_kirim_teman === 'sukses') {
                        pesan += " Data berhasil terkirim ke website tujuan.";
                    } else if (data.status_kirim_teman === 'gagal') {
                        pesan += " (Info: Server IP tujuan offline/tidak merespon, data lokal tetap aman).";
                    }
                    statusBox.innerText = pesan;

                    setTimeout(() => {
                        tutupModalAlasanStop();
                        updateStatusMesin();
                        if (currentView === 'machine-control') loadRingkasanAlasanStop();
                    }, 1200);
                } else {
                    statusBox.style.background = "#fee2e2";
                    statusBox.style.color = "#b91c1c";
                    statusBox.innerText = "❌ Gagal: " + (data.message || "Terjadi kesalahan.");
                }
            } catch (err) {
                statusBox.style.background = "#fee2e2";
                statusBox.style.color = "#b91c1c";
                statusBox.innerText = "❌ Gagal koneksi ke server lokal. Coba lagi.";
            } finally {
                btnKirim.disabled = false;
                btnKirim.innerText = "🚀 Kirim";
            }
        }

        // ===== MODAL: PILIH NILAI (C) SAAT SELESAI PRODUKSI =====
        // Muncul setelah operator mengonfirmasi kategori "Selesai Produksi" di modal alasan stop.
        // Sesi produksi baru benar-benar difinalisasi & dihitung setelah nilai (C) dikonfirmasi di sini.
        //
        // Opsi radio yang ditampilkan gabungan dari 2 sumber:
        //   1. CT standar dari daftar_part.php (item.ct) -- seperti sebelumnya
        //   2. Riwayat CT MANUAL yang pernah diisi operator untuk part ini (ct_manual_history.php)
        //      -- ditambahkan sebagai opsi radio TAMBAHAN, supaya operator tidak perlu ketik
        //      ulang nilai yang sama tiap kali part yang sama diproduksi lagi.
        async function bukaModalPilihC() {
            if (!sesiProduksiAktif) return; // tidak ada sesi yang perlu difinalisasi, tidak perlu tanya (C)

            const namaPartSekarang = document.getElementById('perf-part-label').innerText;
            const item = DAFTAR_PART_CT.find(p => p.part === namaPartSekarang);

            const wadah = document.getElementById('pilih-c-opsi-wrapper');
            wadah.innerHTML = '<div class="ct-detail-empty">Memuat opsi CT...</div>';
            document.getElementById('pilih-c-part-label').innerText = namaPartSekarang || 'Belum ada part dipilih';
            document.getElementById('modal-pilih-c').style.display = 'flex';

            // Riwayat manual disimpan per PART SAJA (bukan per line), tapi tetap dikirim
            // line supaya siap kalau nanti mau dipisah per line juga.
            let riwayatManual = [];
            if (namaPartSekarang) {
                try {
                    const resRiwayat = await fetch(
                        `api/ct_manual_history.php?line=${encodeURIComponent(currentLine)}&part=${encodeURIComponent(namaPartSekarang)}`,
                        { cache: "no-store" }
                    );
                    const dataRiwayat = await resRiwayat.json();
                    if (dataRiwayat.status === 'success') riwayatManual = dataRiwayat.riwayat;
                } catch (e) {
                    // Gagal ambil riwayat manual bukan alasan untuk gagal total -- lanjut
                    // dengan opsi standar saja, operator masih bisa isi manual dari nol.
                    console.warn('Gagal ambil riwayat CT manual:', e);
                }
            }

            wadah.innerHTML = '';
            let adaOpsi = false;
            let opsiKe = 0;

            if (item) {
                item.ct.forEach((nilai) => {
                    const idRadio = `pilih-c-opsi-${opsiKe++}`;
                    wadah.insertAdjacentHTML('beforeend', `
                        <label class="pilih-c-opsi" for="${idRadio}">
                            <input type="radio" name="pilih-c-radio" id="${idRadio}" value="${nilai}" ${!adaOpsi ? 'checked' : ''}>
                            <span>${escapeHtml(item.part)} — <strong>${nilai} menit</strong> <em style="color:#9a9ab0; font-weight:400;">(CT standar)</em></span>
                        </label>
                    `);
                    adaOpsi = true;
                });
            }

            riwayatManual.forEach((r) => {
                const idRadio = `pilih-c-opsi-${opsiKe++}`;
                wadah.insertAdjacentHTML('beforeend', `
                    <label class="pilih-c-opsi" for="${idRadio}">
                        <input type="radio" name="pilih-c-radio" id="${idRadio}" value="${r.nilai_ct}" ${!adaOpsi ? 'checked' : ''}>
                        <span>${escapeHtml(namaPartSekarang)} — <strong>${r.nilai_ct} menit</strong> <em style="color:#9a9ab0; font-weight:400;">(pernah diisi manual)</em></span>
                    </label>
                `);
                adaOpsi = true;
            });

            if (!adaOpsi) {
                wadah.innerHTML = '<div class="ct-detail-empty">Belum ada CT standar maupun riwayat manual untuk part ini. Isi manual di bawah.</div>';
            }

            const inputManual = document.getElementById('pilih-c-manual');
            inputManual.value = '';
            inputManual.dataset.otomatis = '';
            const hasilOtomatis = document.getElementById('hasil-c-otomatis');
            hasilOtomatis.style.display = 'none';
            hasilOtomatis.innerText = '';
        }

        function tutupModalPilihC() {
            document.getElementById('modal-pilih-c').style.display = 'none';
        }

        function tutupModalJikaDiLuarPilihC(e) {
            if (e.target.id === 'modal-pilih-c') tutupModalPilihC();
        }

        // Opsi ke-3 buat nilai (C): hitung otomatis dari data produksi -- rata-rata waktu antara
        // barang masuk ke input dan barang masuk ke output, pada rentang jam sesi produksi ini
        // (jam mulai sesi s/d jam mesin berhenti sekarang). Hasilnya diisikan ke kolom manual
        // supaya bisa dicek/diedit dulu oleh operator sebelum dikonfirmasi.
        async function hitungNilaiCOtomatisDariData() {
            const tanggal = document.getElementById('perf-tanggal').value || currentTanggal;
            const jamMulai = document.getElementById('perf-jam-mulai').value;
            const jamSelesai = calonJamSelesai || jamSekarangHHMM();
            const hasilEl = document.getElementById('hasil-c-otomatis');
            const inputManual = document.getElementById('pilih-c-manual');

            if (!jamMulai) {
                alert('Jam mulai sesi belum ada, tidak bisa menghitung rata-rata otomatis dari data.');
                return;
            }

            hasilEl.style.display = 'block';
            hasilEl.innerText = 'Menghitung dari data...';

            const btn = document.getElementById('btn-hitung-c-otomatis');
            btn.disabled = true;

            try {
                const url = `api/hitung_c_otomatis.php?line=${encodeURIComponent(currentLine)}&tanggal=${encodeURIComponent(tanggal)}`
                    + `&jam_mulai=${encodeURIComponent(jamMulai)}&jam_selesai=${encodeURIComponent(jamSelesai)}`;
                const res = await fetch(url, { cache: "no-store" });
                const data = await res.json();

                if (data.status !== 'success') {
                    hasilEl.innerText = data.message || 'Gagal menghitung (C) otomatis dari data.';
                    return;
                }

                inputManual.value = data.C;
                inputManual.dataset.otomatis = '1';
                hasilEl.innerText = `Rata-rata dari ${data.jumlah_pasangan} pasang barang (input → output): ${data.C} menit/pcs. `
                    + `Sudah diisi ke kolom manual di bawah -- bisa diedit dulu kalau perlu sebelum dikonfirmasi.`;
            } catch (err) {
                hasilEl.innerText = 'Gagal menghubungi server: ' + err.message;
            } finally {
                btn.disabled = false;
            }
        }

        // Dipanggil saat operator klik "Konfirmasi & Selesaikan Sesi". Nilai (C) yang dipilih
        // (dari opsi radio, isian manual, atau hasil hitung otomatis dari data) diterapkan ke
        // dropdown perf-ct-pilihan, baru setelah itu sesi produksi benar-benar difinalisasi
        // (dihitung & disimpan ke riwayat).
        async function konfirmasiNilaiC() {
            const inputManual = document.getElementById('pilih-c-manual');
            const manualInput = inputManual.value;
            const otomatisDariData = inputManual.dataset.otomatis === '1' && manualInput !== '';
            const radioTerpilih = document.querySelector('input[name="pilih-c-radio"]:checked');

            // Isian manual/otomatis diprioritaskan kalau diisi, supaya operator bisa override CT standar kalau perlu
            const nilaiC = manualInput !== '' ? parseFloat(manualInput) : (radioTerpilih ? parseFloat(radioTerpilih.value) : NaN);

            if (!nilaiC || nilaiC <= 0 || isNaN(nilaiC)) {
                alert('Pilih salah satu opsi, isi nilai (C) manual, atau hitung otomatis dari data yang valid (lebih dari 0).');
                return;
            }

            const label = otomatisDariData ? 'otomatis dari data' : 'manual';

            // Simpan ke riwayat CT manual HANYA kalau nilai ini benar-benar diisi/dihasilkan
            // baru lewat kolom manual (bukan sekadar pilih radio opsi yang sudah ada) --
            // supaya nilai ini bisa dipilih lagi tanpa ketik ulang saat part ini diproduksi lagi.
            if (manualInput !== '') {
                const namaPartUntukRiwayat = document.getElementById('perf-part-label').innerText;
                if (namaPartUntukRiwayat) {
                    try {
                        await fetch('api/ct_manual_history.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ line: currentLine, part: namaPartUntukRiwayat, nilai_ct: nilaiC })
                        });
                    } catch (e) {
                        // Gagal simpan riwayat bukan alasan untuk membatalkan penyelesaian sesi --
                        // operator cukup ketik ulang manual lagi lain kali kalau riwayat ini hilang.
                        console.warn('Gagal simpan riwayat CT manual:', e);
                    }
                }
            }

            const select = document.getElementById('perf-ct-pilihan');
            const sudahAdaOpsi = Array.from(select.options).some(o => parseFloat(o.value) === nilaiC);
            if (!sudahAdaOpsi) {
                const opt = document.createElement('option');
                opt.value = nilaiC;
                opt.textContent = `CT Standar — ${nilaiC} menit (${label})`;
                select.appendChild(opt);
            }
            select.value = nilaiC;

            tutupModalPilihC();
            await selesaikanSesiKarenaSelesaiProduksi();

            // PENTING: bersihkan CT & label part SETELAH sesi lama selesai dihitung & disimpan.
            // Tanpa ini, perf-ct-pilihan & perf-part-label masih "nyangkut" pakai part LAMA --
            // kalau operator menyalakan mesin lagi SEBELUM sempat pilih part baru di dropdown,
            // mulaiSesiPerformance() akan lolos cek CT (karena masih keisi part lama) dan sesi
            // baru mulai dengan CT & nama part yang salah, walaupun jam_mulai-nya sendiri sudah
            // benar ter-reset. Dengan dikosongkan di sini, mulaiSesiPerformance() akan menunggu
            // (status 'menunggu-part') sampai operator benar-benar pilih part baru.
            const selectCt = document.getElementById('perf-ct-pilihan');
            const labelPart = document.getElementById('perf-part-label');
            if (selectCt) selectCt.value = '';
            if (labelPart) labelPart.innerText = 'Belum ada';
        }

        function formatJamMenitDariDetik(detik) {
            const totalMenit = Math.round(detik / 60);
            const jam = Math.floor(totalMenit / 60);
            const sisaMenit = totalMenit % 60;
            return jam > 0 ? `${jam}j ${sisaMenit}m` : `${sisaMenit}m`;
        }

        // ===== Format angka MENIT (desimal, dari API) jadi "Xm Ys" ala tampilan HMI mesin =====
        function formatMenitJadiMenitDetik(menitDesimal) {
            const totalDetik = Math.round(menitDesimal * 60);
            const menit = Math.floor(totalDetik / 60);
            const detik = totalDetik % 60;
            return `${menit}m ${detik}s`;
        }

        // ===== MACHINE RATIO: live-view sesi part yang SEDANG berjalan (read-only, tidak nyimpan riwayat) =====
        async function loadMachineRatio() {
            const kosongEl = document.getElementById('mr-tidak-ada-sesi');
            const kontenEl = document.getElementById('mr-konten');
            if (!kosongEl || !kontenEl) return;

            const inputMulai = document.getElementById('perf-jam-mulai');
            const jamMulai = inputMulai ? inputMulai.value : '';
            const ct = document.getElementById('perf-ct-pilihan')?.value;
            const namaPart = document.getElementById('perf-part-label')?.innerText || '-';

            const inValEl = document.getElementById('input-value');
            const outValEl = document.getElementById('output-value');

            // Kalau belum ada sesi aktif, tetap tampilkan data input/output dari dashboard
            if (!sesiProduksiAktif || !jamMulai || !ct) {
                const totalInput = inValEl ? (parseInt(inValEl.innerText) || 0) : 0;
                const totalOutput = outValEl ? (parseInt(outValEl.innerText) || 0) : 0;
                const ngProduct = Math.max(totalInput - totalOutput, 0);
                const hPersen = totalInput > 0 ? Math.round((totalOutput / totalInput) * 1000) / 10 : 0;
                const ePersen = totalInput > 0 ? Math.round((ngProduct / totalInput) * 1000) / 10 : 0;
                kosongEl.style.display = 'none';
                kontenEl.style.display = '';

                const elMrPart = document.getElementById('mr-nama-part');
                const elMrMulai = document.getElementById('mr-jam-mulai');
                if (elMrPart) elMrPart.innerText = '-';
                if (elMrMulai) elMrMulai.innerText = 'Sesi belum dimulai';

                const elTotProd = document.getElementById('mr-total-product');
                const elOkProd = document.getElementById('mr-ok-product');
                const elNgRatio = document.getElementById('mr-ng-ratio');
                const elNgProd = document.getElementById('mr-ng-product');
                const elProdRate = document.getElementById('mr-product-rate');
                const elMachRate = document.getElementById('mr-machine-rate');
                const elProdTime = document.getElementById('mr-product-time');
                const elRunTime = document.getElementById('mr-run-time');
                const elWaitLoss = document.getElementById('mr-waiting-loss');
                const elMachTrouble = document.getElementById('mr-machine-trouble');

                if (elTotProd) elTotProd.innerText = `${totalInput} PCS`;
                if (elOkProd) elOkProd.innerText = `${totalOutput} PCS`;
                if (elNgRatio) elNgRatio.innerText = `${ePersen}%`;
                if (elNgProd) elNgProd.innerText = `${ngProduct} PCS`;
                if (elProdRate) elProdRate.innerText = `${hPersen}%`;
                if (elMachRate) elMachRate.innerText = '-%';
                if (elProdTime) {
                    elProdTime.innerText = '-';
                    elProdTime.classList.remove('mr-card-green', 'mr-card-red');
                    elProdTime.classList.add('mr-card-dark');
                }
                if (elRunTime) elRunTime.innerText = '-';
                if (elWaitLoss) elWaitLoss.innerText = '-';
                if (elMachTrouble) elMachTrouble.innerText = '-';
                return;
            }

            const jamSekarang = jamSekarangHHMM();
            const url = `api/kalkulasi_performance_live.php?line=${encodeURIComponent(currentLine)}`
                + `&tanggal=${encodeURIComponent(currentTanggal)}&jam_mulai=${encodeURIComponent(jamMulai)}`
                + `&jam_selesai=${encodeURIComponent(jamSekarang)}&ct=${encodeURIComponent(ct)}&part=${encodeURIComponent(namaPart)}`;

            try {
                const res = await fetch(url, { cache: "no-store" });
                const data = await res.json();
                if (data.status !== 'success') return; // biarkan tampilan sebelumnya, coba lagi di polling berikutnya

                kosongEl.style.display = 'none';
                kontenEl.style.display = '';

                document.getElementById('mr-nama-part').innerText = namaPart;
                document.getElementById('mr-jam-mulai').innerText = `Mulai ${jamMulai}`;

                // Ambil input & output dari kartu dashboard biar sinkron
                const totalInput = parseInt(document.getElementById('input-value').innerText) || 0;
                const totalOutput = parseInt(document.getElementById('output-value').innerText) || 0;
                const ngProduct = Math.max(totalInput - totalOutput, 0);
                const hPersen = totalInput > 0 ? Math.round((totalOutput / totalInput) * 1000) / 10 : 0;
                const ePersen = totalInput > 0 ? Math.round((ngProduct / totalInput) * 1000) / 10 : 0;

                document.getElementById('mr-total-product').innerText = `${totalInput} PCS`;
                document.getElementById('mr-ok-product').innerText = `${totalOutput} PCS`;
                document.getElementById('mr-ng-ratio').innerText = `${ePersen}%`;
                document.getElementById('mr-ng-product').innerText = `${ngProduct} PCS`;

                document.getElementById('mr-product-rate').innerText = `${hPersen}%`;

                // Machine Rate = persentase Working Time Shift -- seberapa besar Product Time
                // (B_shift, waktu berjalan sesi sejak awal shift) dibanding Working Time standar
                // shift (waktu_kerja_shift: 535 Pagi / 450 Sore / 455 Malam), BUKAN lagi terhadap
                // Run Time/waktu minimal (waktu_minimal_shift). Diubah 2026-08-28 supaya konsisten
                // dengan rumus (K) backend yang juga sudah dibandingkan ke Working Time Shift.
                // Rumus (L) EFF (H x J x K) sendiri TIDAK dihapus, datanya tetap dikirim backend
                // (data.L_persen) dan masih dipakai di Riwayat/Detail Shift.
                const machineRatePersen = data.waktu_kerja_shift > 0
                    ? Math.round((data.B_shift / data.waktu_kerja_shift) * 1000) / 10
                    : 0;
                // Machine Rate (K) murni rasio waktu sesi berjalan (B_shift) terhadap waktu kerja
                // standar shift -- TIDAK bergantung pada jumlah barang input/output. Jadi tidak boleh
                // disembunyikan jadi '-%' hanya karena belum ada barang lewat sensor (mis. saat
                // maintenance/idle padahal sesi part sudah berjalan). '-%' cuma ditampilkan kalau
                // waktu_kerja_shift sendiri tidak valid (<= 0).
                document.getElementById('mr-machine-rate').innerText = data.waktu_kerja_shift > 0 ? `${machineRatePersen}%` : '-%';

                // Kartu Product Time berubah HIJAU begitu B_shift (waktu berjalan sesi sejak
                // awal shift) sudah mencapai/melewati waktu_minimal_shift (target minimal shift
                // yang sedang berlangsung -- Pagi/Sore/Malam beda-beda, sudah otomatis dari
                // backend, tidak di-hardcode di sini). Sebelum tercapai, kartu MERAH (mr-card-red)
                // sebagai penanda waktu kerja masih di bawah standar minimal shift -- konsisten
                // dengan modal "Waktu Kerja Di Bawah Standar" yang muncul di Machine Control saat
                // sesi terakhir shift ditutup dengan (B) di bawah waktu_minimal_shift ini juga.
                const elProductTime = document.getElementById('mr-product-time');
                elProductTime.innerText = formatMenitJadiMenitDetik(data.B_shift);
                const sudahPenuhiWaktuMinimal = !data.tidak_ada_data && data.waktu_minimal_shift > 0
                    && data.B_shift >= data.waktu_minimal_shift;
                elProductTime.classList.remove('mr-card-green', 'mr-card-red', 'mr-card-dark');
                if (sudahPenuhiWaktuMinimal) {
                    elProductTime.classList.add('mr-card-green');
                } else {
                    elProductTime.classList.add('mr-card-dark');
                }
                document.getElementById('mr-run-time').innerText = formatMenitJadiMenitDetik(data.waktu_minimal_shift);
                document.getElementById('mr-waiting-loss').innerText = formatMenitJadiMenitDetik(data.P_shift);
                document.getElementById('mr-machine-trouble').innerText = formatMenitJadiMenitDetik(data.I_shift);
            } catch (err) {
                // Diamkan -- polling berikutnya (5 detik lagi) akan coba ulang otomatis
            }
        }

        async function loadRingkasanAlasanStop() {
            const labelLine = document.getElementById('alasan-ringkasan-line-label');
            const tbody = document.getElementById('tbody-ringkasan-alasan-stop');
            if (!labelLine || !tbody) return;

            const hariIni = new Date().toISOString().split('T')[0];
            labelLine.innerText = currentLine;

            try {
                const response = await fetch(`api/alasan_stop2.php?line=${encodeURIComponent(currentLine)}&tanggal=${hariIni}`, { cache: "no-store" });
                if (!response.ok) throw new Error("HTTP " + response.status);
                const hasil = await response.json();

                if (!hasil || !hasil.ringkasan || hasil.ringkasan.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="4" class="ct-detail-empty">Belum ada catatan stop hari ini.</td></tr>';
                    return;
                }

                tbody.innerHTML = hasil.ringkasan.map(r => {
                    const kelasBadge = r.kategori === 'P' ? 'badge-kategori-p' : (r.kategori === 'Loss' ? 'badge-kategori-loss' : (r.kategori === 'Selesai' ? 'badge-kategori-selesai' : 'badge-kategori-kosong'));
                    const badgePatlite = renderBadgePatlite(r.warna_patlite || r.status);
                    return `
                        <tr>
                            <td><span class="badge-kategori ${kelasBadge}">${escapeHtml(r.kategori)}</span></td>
                            <td>${escapeHtml(r.alasan)}</td>
                            <td>${badgePatlite}</td>
                            <td>${formatJamMenitDariDetik(r.durasi_detik)}</td>
                        </tr>
                    `;
                }).join('');
            } catch (err) {
                console.warn('loadRingkasanAlasanStop: error', err);
                tbody.innerHTML = '<tr><td colspan="4" class="ct-detail-empty">Belum ada catatan stop hari ini.</td></tr>';
            }
        }


        // ===== NAVIGASI TANPA RELOAD (inti dari fitur fullscreen tetap nyala) =====
        function renderView(line, view, tanggal) {
            currentLine = line;
            currentView = view;
            if (tanggal) currentTanggal = tanggal;

            document.body.classList.add('has-bg-factory');
            document.body.dataset.line = 'PCB';

            // Update sidebar navigation active state
            document.querySelectorAll('.sidebar .nav-item[data-view]').forEach(item => {
                const itemV = item.dataset.view;
                let isActive = false;
                if (itemV === 'riwayat') {
                    isActive = ['riwayat', 'detail', 'detail-shift'].includes(view);
                } else {
                    isActive = (itemV === view);
                }
                item.classList.toggle('active', isActive);
            });

            // Update judul halaman
            let judul = 'Dashboard — Line PCB';
            if (view === 'riwayat') judul = 'Riwayat — Line PCB';
            if (view === 'chart') judul = 'Chart — Line PCB';
            if (view === 'detail') judul = `Detail Harian — Line PCB (${formatTanggalIndo(currentTanggal)})`;
            if (view === 'detail-shift') judul = 'Detail Shift — Line PCB';
            if (view === 'machine-control') judul = 'Machine Control — Line PCB';
            if (view === 'pengaturan') judul = 'Pengaturan — Line PCB';
            if (view === 'kwh-control') judul = 'KWh Meter — Line 3 (PCB)';
            const titleEl = document.getElementById('page-title');
            if (titleEl) titleEl.innerText = judul;

            // Ubah teks di bawah logo Gobel mengikuti halaman yang sedang aktif
            const subTitleEl = document.getElementById('logo-subtitle');
            if (subTitleEl) {
                subTitleEl.innerText =
                    (view === 'machine-control') ? 'Machine Control — PCB' :
                    (view === 'pengaturan') ? 'Pengaturan — PCB' :
                    (view === 'kwh-control') ? 'KWh Meter — Line 3' :
                    (view === 'chart') ? 'Chart — PCB' :
                    (['riwayat', 'detail', 'detail-shift'].includes(view)) ? 'Riwayat — PCB' :
                    'Dashboard PCB';
            }

            // Tampilkan/sembunyikan view yang sesuai (hanya jika elemen ada)
            const vd = document.getElementById('view-dashboard'); if (vd) vd.style.display = (view === 'dashboard') ? '' : 'none';
            const vr = document.getElementById('view-riwayat'); if (vr) vr.style.display = (view === 'riwayat') ? '' : 'none';
            const vc = document.getElementById('view-chart'); if (vc) vc.style.display = (view === 'chart') ? '' : 'none';
            const vdet = document.getElementById('view-detail'); if (vdet) vdet.style.display = (view === 'detail') ? '' : 'none';
            const vds = document.getElementById('view-detail-shift'); if (vds) vds.style.display = (view === 'detail-shift') ? '' : 'none';
            const vmc = document.getElementById('view-machine-control'); if (vmc) vmc.style.display = (view === 'machine-control') ? '' : 'none';
            const vp = document.getElementById('view-pengaturan'); if (vp) vp.style.display = (view === 'pengaturan') ? '' : 'none';
            const vkwh = document.getElementById('view-kwh-control'); if (vkwh) vkwh.style.display = (view === 'kwh-control') ? '' : 'none';
            const statusBadgeEl = document.getElementById('status-badge');
            if (statusBadgeEl) statusBadgeEl.style.display = (view === 'dashboard') ? '' : 'none';
            const simBtn = document.getElementById('btn-simulasi-stop');
            if (simBtn) simBtn.style.display = (view === 'kwh-control' || view === 'pengaturan') ? 'none' : 'inline-flex';

            // Update tombol aksi di topbar
            const actionEl = document.getElementById('action-buttons');
            if (actionEl) {
                if (view === 'dashboard') {
                    actionEl.innerHTML = '';
                } else if (view === 'detail') {
                    actionEl.innerHTML = `
                        <a href="${APP_BASE_PATH}/riwayat?line=${encodeURIComponent(line)}" class="btn-nav-action btn-light" data-nav data-line="${line}" data-view="riwayat">&larr; Kembali ke Riwayat</a>
                        <a href="${APP_BASE_PATH}/export/export_excel.php?line=${encodeURIComponent(line)}&tanggal=${currentTanggal}" class="btn-nav-action btn-green">&#8681; Download Excel</a>
                    `;
                } else if (view === 'detail-shift') {
                    actionEl.innerHTML = `
                        <a href="${APP_BASE_PATH}/riwayat?line=${encodeURIComponent(line)}" class="btn-nav-action btn-light" data-nav data-line="${line}" data-view="riwayat">&larr; Kembali ke Riwayat</a>
                    `;
                } else if (view === 'machine-control') {
                    actionEl.innerHTML = `
                        <a href="${APP_BASE_PATH}/dashboard?line=${encodeURIComponent(line)}" class="btn-nav-action btn-light" data-nav data-line="${line}" data-view="dashboard">&larr; Kembali ke Dashboard</a>
                    `;
                } else if (view === 'chart') {
                    actionEl.innerHTML = `
                        <a href="${APP_BASE_PATH}/dashboard?line=${encodeURIComponent(line)}" class="btn-nav-action btn-light" data-nav data-line="${line}" data-view="dashboard">&larr; Kembali ke Dashboard</a>
                    `;
                } else if (view === 'pengaturan' || view === 'kwh-control') {
                    actionEl.innerHTML = '';
                } else {
                    actionEl.innerHTML = `
                        <a href="${APP_BASE_PATH}/dashboard?line=${encodeURIComponent(line)}" class="btn-nav-action btn-light" data-nav data-line="${line}" data-view="dashboard">&larr; Kembali ke Dashboard</a>
                        <a href="${APP_BASE_PATH}/export/export_excel.php?line=${encodeURIComponent(line)}" class="btn-nav-action btn-green">&#8681; Download Histori (Excel)</a>
                    `;
                }
            }

            // Ambil data sesuai view yang lagi aktif
            if (view === 'dashboard') {
                if (line !== lastRenderedLine) {
                    prevInput = 0; prevOutput = 0; prevYield = 0; prevOr = 0;
                    prevSheetInput = 0; prevSheetOutput = 0; prevSheetYield = 0;
                    const elIn = document.getElementById("input-value"); if (elIn) elIn.innerText = "0";
                    const elOut = document.getElementById("output-value"); if (elOut) elOut.innerText = "0";
                    const elYd = document.getElementById("yield-value"); if (elYd) elYd.innerText = "0.0%";
                    const elOr = document.getElementById("or-value"); if (elOr) elOr.innerText = "0.0%";
                    const elSIn = document.getElementById("sheet-input-value"); if (elSIn) elSIn.innerText = "0";
                    const elSOut = document.getElementById("sheet-output-value"); if (elSOut) elSOut.innerText = "0";
                    const elSYd = document.getElementById("sheet-yield-value"); if (elSYd) elSYd.innerText = "0.0%";
                    lastRenderedLine = line;
                }
                updateData();
                updateStatusMesin();
                loadMachineRatio();
            } else if (view === 'chart') {
                updateChartAktif();
            } else if (view === 'detail') {
                loadDetailHarian(line, currentTanggal);
            } else if (view === 'detail-shift') {
                loadDetailShift(line, detailShiftParams.tanggal, detailShiftParams.shift);
            } else if (view === 'riwayat') {
                pilihTabRiwayat('produksi');
            } else if (view === 'kwh-control') {
                loadKwhControlData();
            } else if (view === 'machine-control') {
                loadRingkasanAlasanStop();
                if (sesiProduksiAktif) {
                    if (!timerLiveInterval) mulaiTimerLive();
                    updateStatusBoxMC(statusMesinSebelumnya === 'running' ? 'berjalan' : 'jeda');
                } else {
                    hentikanTimerLive();
                    updateStatusBoxMC('idle');
                }
                perbaruiHasilPerformanceLive();
            }
        }




        // ===== NAVIGASI SIDEBAR: menu "Pengaturan" (global, bukan per-line) =====
        function navigateSidebarPengaturan(event) {
            event.preventDefault();
            navigateTo(currentLine, 'pengaturan');
            return false;
        }

        // ===== NAVIGASI SIDEBAR: klik sub-item (Dashboard/Machine Control/Riwayat) di bawah sebuah line =====
        function navigateSidebar(event, view, line) {
            event.preventDefault();
            navigateTo(line || currentLine, view);
            return false;
        }

        // ===== OPSI BAR RIWAYAT: toggle antara tab Produksi, Machine Control, Shift, dan Stop & Patlite =====
        let riwayatTabAktif = 'produksi';

        function pilihTabRiwayat(tab) {
            riwayatTabAktif = tab;

            const btnProd = document.getElementById('opsi-produksi');
            const btnMc = document.getElementById('opsi-mc');
            const btnShift = document.getElementById('opsi-shift');
            const btnStop = document.getElementById('opsi-stop');
            const tabProd = document.getElementById('riwayat-tab-produksi');
            const tabMc = document.getElementById('riwayat-tab-mc');
            const tabShift = document.getElementById('riwayat-tab-shift');
            const tabStop = document.getElementById('riwayat-tab-stop');

            if (btnProd) btnProd.classList.toggle('aktif', tab === 'produksi');
            if (btnMc) btnMc.classList.toggle('aktif', tab === 'mc');
            if (btnShift) btnShift.classList.toggle('aktif', tab === 'shift');
            if (btnStop) btnStop.classList.toggle('aktif', tab === 'stop');

            if (tabProd) tabProd.style.display = (tab === 'produksi') ? '' : 'none';
            if (tabMc) tabMc.style.display = (tab === 'mc') ? '' : 'none';
            if (tabShift) tabShift.style.display = (tab === 'shift') ? '' : 'none';
            if (tabStop) tabStop.style.display = (tab === 'stop') ? '' : 'none';

            // Muat data on-demand tiap kali pindah tab, biar selalu up to date
            if (tab === 'produksi') {
                updateHistoriTable();
            } else if (tab === 'mc') {
                loadRiwayatPerformance();
            } else if (tab === 'shift') {
                loadRiwayatShift();
            } else if (tab === 'stop') {
                loadRiwayatStop();
            }
        }

        // ===== TAB RIWAYAT STOP & PATLITE =====
        function bukaPickerRiwayatStop() {
            const input = document.getElementById('filter-tanggal-rstop');
            if (!input) return;
            if (input.showPicker) { input.showPicker(); } else { input.focus(); input.click(); }
        }

        function setTanggalRStop(tipe) {
            const input = document.getElementById('filter-tanggal-rstop');
            if (!input) return;
            if (tipe === 'hari_ini') {
                input.value = new Date().toISOString().split('T')[0];
            } else if (tipe === 'kemarin') {
                const kem = new Date();
                kem.setDate(kem.getDate() - 1);
                input.value = kem.toISOString().split('T')[0];
            } else if (tipe === 'semua') {
                input.value = '';
            }
            loadRiwayatStop();
        }

        async function loadRiwayatStop(tanggal) {
            const input = document.getElementById('filter-tanggal-rstop');
            const elTeks = document.getElementById('teks-tanggal-rstop');
            const elLabel = document.getElementById('rstop-tgl-label');
            const elTotalKejadian = document.getElementById('rstop-total-kejadian');
            const elTotalDurasi = document.getElementById('rstop-total-durasi');
            const elTotalLoss = document.getElementById('rstop-total-loss');
            const tbody = document.getElementById('tbody-riwayat-stop-lengkap');
            if (!tbody) return;

            if (tanggal !== undefined) {
                if (input) input.value = tanggal;
            } else if (input && !input.value && input.value !== '') {
                input.value = new Date().toISOString().split('T')[0];
            }

            const tgl = input ? input.value : '';
            const hariIni = new Date().toISOString().split('T')[0];
            const kem = new Date();
            kem.setDate(kem.getDate() - 1);
            const tglKemarin = kem.toISOString().split('T')[0];

            let labelTgl = 'Semua Data';
            if (tgl === hariIni) {
                labelTgl = 'Hari Ini';
            } else if (tgl === tglKemarin) {
                labelTgl = 'Kemarin';
            } else if (tgl) {
                labelTgl = formatTanggalIndo(tgl);
            }

            if (elTeks) elTeks.innerText = labelTgl;
            if (elLabel) elLabel.innerText = labelTgl;

            tbody.innerHTML = '<tr><td colspan="6" class="ct-detail-empty">Memuat data riwayat stop...</td></tr>';

            const url = `api/riwayat_stop.php?line=${encodeURIComponent(currentLine)}${tgl ? '&tanggal=' + encodeURIComponent(tgl) : ''}`;
            try {
                const resp = await fetch(url, { cache: 'no-store' });
                if (!resp.ok) throw new Error('HTTP ' + resp.status);
                const d = await resp.json();

                if (elTotalKejadian) elTotalKejadian.innerText = d.total_kejadian || 0;
                if (elTotalDurasi) elTotalDurasi.innerText = (d.total_durasi_menit || 0) + ' mnt';
                if (elTotalLoss) elTotalLoss.innerText = (d.total_loss_menit || 0) + ' mnt';

                const list = d.daftar || [];
                if (list.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="6" class="ct-detail-empty">Tidak ada riwayat kejadian stop pada filter tanggal ini.</td></tr>';
                    return;
                }

                tbody.innerHTML = list.map((item, idx) => {
                    const kelasBadge = item.kategori === 'P' ? 'badge-kategori-p' : (item.kategori === 'Loss' ? 'badge-kategori-loss' : (item.kategori === 'Selesai' ? 'badge-kategori-selesai' : 'badge-kategori-kosong'));
                    const badgePatlite = renderBadgePatlite(item.warna_patlite || item.status);
                    const waktuFormatted = item.waktu ? item.waktu : '-';

                    return `
                        <tr>
                            <td style="text-align:center; color:#64748b; font-weight:600;">${idx + 1}</td>
                            <td style="font-family:monospace; font-size:12.5px; color:#1e293b;">${escapeHtml(waktuFormatted)}</td>
                            <td><span class="badge-kategori ${kelasBadge}">${escapeHtml(item.kategori || '-')}</span></td>
                            <td style="font-weight:600; color:#0f172a;">${escapeHtml(item.alasan || '-')}</td>
                            <td>${badgePatlite}</td>
                            <td style="font-weight:600; color:#1d4ed8;">${escapeHtml(item.durasi_teks || (item.durasi_menit + ' mnt'))}</td>
                        </tr>
                    `;
                }).join('');
            } catch (err) {
                console.error('loadRiwayatStop: error', err);
                tbody.innerHTML = '<tr><td colspan="6" class="ct-detail-empty">Gagal memuat data riwayat stop.</td></tr>';
            }
        }

        // ===== RIWAYAT KALKULATOR CYCLE TIME & PERFORMANCE (terintegrasi di dalam Riwayat) =====
        function bukaPickerPerformance() {
            const input = document.getElementById('filter-tanggal-ph');
            if (!input) return;
            if (input.showPicker) { input.showPicker(); } else { input.focus(); input.click(); }
        }

        // Simpan riwayat sesi yang terakhir dimuat, di-key berdasarkan id -- dipakai supaya
        // tombol "Lihat Detail" tidak perlu fetch ulang ke server, cukup ambil dari sini.
        let riwayatPerformanceById = {};
        let tanggalRiwayatPerformanceSedangDilihat = null;

        async function loadRiwayatPerformance(tanggal) {
            const input = document.getElementById('filter-tanggal-ph');
            if (!input) return;
            if (tanggal) {
                input.value = tanggal;
            } else if (!input.value) {
                input.value = new Date().toISOString().split('T')[0];
            }
            const tgl = input.value;
            const labelTgl = formatTanggalIndo(tgl);
            const elTeks = document.getElementById('teks-tanggal-ph');
            const elLabel = document.getElementById('ph-tgl-label');
            if (elTeks) elTeks.innerText = labelTgl;
            if (elLabel) elLabel.innerText = labelTgl;

            const resp = await fetch(`api/riwayat_performance.php?line=${encodeURIComponent(currentLine)}&tanggal=${tgl}`, { cache: 'no-store' });
            const d = await resp.json();
            tanggalRiwayatPerformanceSedangDilihat = d.tanggal || tgl;

            const elJumlah = document.getElementById('ph-jumlah');
            const elRataEff = document.getElementById('ph-rata-eff');
            if (elJumlah) elJumlah.innerText = d.jumlah || 0;
            if (elRataEff) elRataEff.innerText = (d.rata_eff_v2_persen || 0) + '%';

            const daftar   = document.getElementById('daftar-riwayat-ph');
            const elKosong = document.getElementById('ph-kosong');
            if (!daftar) return;
            daftar.innerHTML = '';
            riwayatPerformanceById = {};

            if (!d.riwayat || d.riwayat.length === 0) {
                if (elKosong) elKosong.style.display = '';
                daftar.style.display  = 'none';
                return;
            }

            if (elKosong) elKosong.style.display = 'none';
            daftar.style.display  = '';

            d.riwayat.forEach(r => {
                riwayatPerformanceById[r.id] = r;

                const effUntukKartu = r.v2_tersedia ? r.EFF_v2_persen : r.L_persen;
                const labelRumusLama = r.v2_tersedia ? '' : ' <span style="color:#b45309; font-size:10px;">(rumus lama)</span>';

                const kartu = document.createElement('div');
                kartu.className = 'riwayat-ph-card';
                kartu.innerHTML = `
                    <div class="riwayat-ph-card-main">
                        <div class="riwayat-ph-card-title">
                            Sesi #${r.nomor_sesi} — ${escapeHtml(r.part)}
                            <span class="riwayat-ph-card-id">${r.jam_mulai} - ${r.jam_selesai}</span>
                        </div>
                        <div class="riwayat-ph-card-sub">
                            Output: <strong>${r.F} pcs</strong> | NG: <strong>${r.E_persen}%</strong> | Waktu Kerja (B): <strong>${r.B}m</strong> | Stop: <strong>${r.A}m</strong>
                        </div>
                    </div>
                    <div class="riwayat-ph-card-eff">
                        <div class="riwayat-ph-card-eff-val">${effUntukKartu}%${labelRumusLama}</div>
                        <div class="riwayat-ph-card-eff-lbl">EFF Sesi</div>
                    </div>
                    <div class="riwayat-ph-card-actions">
                        <button type="button" class="btn-detail-shift" onclick="bukaDetailPerformance(${r.id})">Lihat Detail</button>
                        <a href="${APP_BASE_PATH}/export/export_riwayat_performance.php?id=${r.id}" class="btn-download-row" title="Download Excel sesi ini">&#8681;</a>

                    </div>
                `;
                daftar.appendChild(kartu);
            });
        }

        // ===== RIWAYAT PER SHIFT (di bawah kartu live "Machine Ratio" di Dashboard) =====
        function bukaPickerRiwayatShift() {
            const input = document.getElementById('filter-tanggal-rs');
            if (input.showPicker) { input.showPicker(); } else { input.focus(); input.click(); }
        }

        function formatDurasiSingkat(menit) {
            const total = Math.round(menit * 60);
            const m = Math.floor(total / 60);
            const s = total % 60;
            return `${m}m ${s}s`;
        }

        async function loadRiwayatShift(tanggal) {
            const input = document.getElementById('filter-tanggal-rs');
            if (!input) return;
            if (tanggal) {
                input.value = tanggal;
            } else if (!input.value) {
                input.value = new Date().toISOString().split('T')[0];
            }
            const tgl = input.value;
            const labelTgl = formatTanggalIndo(tgl);
            const elTeks = document.getElementById('teks-tanggal-rs');
            const elLabel = document.getElementById('rs-tgl-label');
            if (elTeks) elTeks.innerText = labelTgl;
            if (elLabel) elLabel.innerText = labelTgl;

            const resp = await fetch(`api/riwayat_shift.php?line=${encodeURIComponent(currentLine)}&tanggal=${tgl}`, { cache: 'no-store' });
            const d = await resp.json();

            const daftar = document.getElementById('daftar-riwayat-shift');
            if (!daftar) return;
            daftar.innerHTML = '';

            (d.shift || []).forEach(s => {
                const kartu = document.createElement('div');
                kartu.className = 'riwayat-ph-card';

                if (s.belum_dimulai) {
                    kartu.innerHTML = `
                        <div class="riwayat-ph-card-main">
                            <div class="riwayat-ph-card-title">Shift ${s.shift} <span class="riwayat-ph-card-id">${s.jam_mulai} - ${s.jam_selesai}</span></div>
                            <div class="riwayat-ph-card-sub">Belum berjalan</div>
                        </div>
                    `;
                    daftar.appendChild(kartu);
                    return;
                }

                const totalInput = s.total_input !== undefined ? s.total_input : (s.total_product ?? 0);
                const totalOutput = s.total_output !== undefined ? s.total_output : (s.ok_product ?? 0);
                const totalNg = s.ng !== undefined ? s.ng : (s.ng_product ?? 0);
                const hPersen = s.h_persen !== undefined ? s.h_persen : (s.product_rate ?? 0);
                const lPersen = s.l_persen !== undefined ? s.l_persen : (s.machine_rate ?? (s.eff ?? 0));
                const jumlahSesi = s.jumlah_sesi_part !== undefined ? s.jumlah_sesi_part : 0;
                const hasEff = s.ada_data_eff ?? (s.eff !== undefined && s.eff !== null);
                const labelEff = hasEff ? `${lPersen}%` : '-';
                const subJudul = s.sedang_berjalan
                    ? `Sedang berjalan &middot; ${jumlahSesi} sesi part tercatat`
                    : `${jumlahSesi} sesi part tercatat`;

                kartu.innerHTML = `
                    <div class="riwayat-ph-card-main">
                        <div class="riwayat-ph-card-title">Shift ${s.shift} <span class="riwayat-ph-card-id">${s.jam_mulai} - ${s.jam_selesai}</span></div>
                        <div class="riwayat-ph-card-sub">${subJudul}</div>
                    </div>
                    <div class="riwayat-ph-card-metrics">
                        <div class="riwayat-ph-metric"><span>Total Product</span><strong>${totalInput}</strong></div>
                        <div class="riwayat-ph-metric"><span>OK Product</span><strong>${totalOutput}</strong></div>
                        <div class="riwayat-ph-metric"><span>NG Product</span><strong>${totalNg}</strong></div>
                        <div class="riwayat-ph-metric"><span>Product Rate (H)</span><strong>${hPersen}%</strong></div>
                        <div class="riwayat-ph-metric highlight"><span>Machine Rate (L)</span><strong>${labelEff}</strong></div>
                    </div>
                `;
                daftar.appendChild(kartu);
            });
        }
        let idSesiRiwayatSedangDibuka = null;

        function bukaDetailPerformance(id) {
            const r = riwayatPerformanceById[id];
            if (!r) return;
            idSesiRiwayatSedangDibuka = id;

            document.getElementById('dp-part-label').innerText = r.part;
            document.getElementById('dp-id').innerText = r.nomor_sesi;
            document.getElementById('dp-jam').innerText = `${r.jam_mulai} - ${r.jam_selesai}`;
            document.getElementById('dp-disimpan').innerText = r.jam_disimpan;

            const valC = (r.C_manual !== null && r.C_manual !== undefined) ? r.C_manual : r.C;
            const valF = (r.F_manual !== null && r.F_manual !== undefined) ? r.F_manual : r.F;
            const valE = (r.E_manual !== null && r.E_manual !== undefined) ? r.E_manual : r.E_v2;

            document.getElementById('dp-val-B').innerText = r.B;
            document.getElementById('dp-val-C').innerText = valC;
            document.getElementById('dp-val-I').innerText = r.I;
            document.getElementById('dp-val-P').innerText = r.P;

            if (r.v2_tersedia) {
                // Rumus baru (v2) -- sesi dibuat setelah "Card Tabel Hasil Produksi" diperbarui.
                document.getElementById('dp-catatan-rumus-lama').style.display = 'none';
                document.getElementById('dp-val-C-manual-tag').style.display = (r.C_manual !== null && r.C_manual !== undefined) ? '' : 'none';
                document.getElementById('dp-val-F').innerText = valF;
                document.getElementById('dp-val-F-manual-tag').style.display = (r.F_manual !== null && r.F_manual !== undefined) ? '' : 'none';
                document.getElementById('dp-val-E').innerText = valE;
                document.getElementById('dp-val-E-manual-tag').style.display = (r.E_manual !== null && r.E_manual !== undefined) ? '' : 'none';
                document.getElementById('dp-val-D').innerText = r.D;
                document.getElementById('dp-val-A').innerText = r.A_v2;
                document.getElementById('dp-val-H').innerText = `${r.H_v2_persen}%`;
                document.getElementById('dp-val-J').innerText = r.J_v2;
                document.getElementById('dp-val-K').innerText = r.K_v2;
                document.getElementById('dp-val-L').innerText = `${r.EFF_v2_persen}%`;

                // Prefill form koreksi riwayat dengan nilai yang sedang aktif (sehingga tidak kosong dan tidak ter-reset).
                document.getElementById('dp-input-koreksi-f').value = (r.F_manual !== null && r.F_manual !== undefined) ? r.F_manual : valF;
                document.getElementById('dp-input-koreksi-e').value = (r.E_manual !== null && r.E_manual !== undefined) ? r.E_manual : (valE !== null && valE !== undefined ? valE : '0');
                document.getElementById('dp-input-koreksi-c').value = (r.C_manual !== null && r.C_manual !== undefined) ? r.C_manual : valC;
                document.getElementById('dp-koreksi-wrapper').style.display = '';
            } else {
                // Sesi LAMA (sebelum kolom v2 ada) -- tampilkan apa adanya dengan rumus lama,
                // dengan catatan supaya tidak disalahartikan sama seperti sesi baru. Koreksi
                // manual TIDAK tersedia untuk sesi lama (tidak punya kolom v2 utk dihitung ulang).
                document.getElementById('dp-catatan-rumus-lama').style.display = '';
                document.getElementById('dp-val-C-manual-tag').style.display = 'none';
                document.getElementById('dp-val-F-manual-tag').style.display = 'none';
                document.getElementById('dp-val-E-manual-tag').style.display = 'none';
                document.getElementById('dp-val-F').innerText = r.F;
                document.getElementById('dp-val-E').innerText = `${r.E_persen}% (rumus lama)`;
                document.getElementById('dp-val-D').innerText = r.D;
                document.getElementById('dp-val-A').innerText = r.A;
                document.getElementById('dp-val-H').innerText = `${r.H_persen}%`;
                document.getElementById('dp-val-J').innerText = r.J;
                document.getElementById('dp-val-K').innerText = r.K;
                document.getElementById('dp-val-L').innerText = `${r.L_persen}%`;
                document.getElementById('dp-koreksi-wrapper').style.display = 'none';
            }
            document.getElementById('form-koreksi-riwayat').style.display = 'none';

            document.getElementById('dp-download-link').href = `${APP_BASE_PATH}/export/export_riwayat_performance.php?id=${r.id}`;


            document.getElementById('modal-detail-performance').style.display = 'flex';
        }
        window.bukaModalDetailPerformance = bukaDetailPerformance;

        // ===== KOREKSI MANUAL F/E/C DARI RIWAYAT (sesi yang SUDAH tersimpan) =====
        function bukaFormKoreksiRiwayat() {
            document.getElementById('form-koreksi-riwayat').style.display = '';
        }

        function batalKoreksiRiwayat() {
            document.getElementById('form-koreksi-riwayat').style.display = 'none';
        }

        async function simpanKoreksiRiwayat() {
            if (!idSesiRiwayatSedangDibuka) {
                alert('Sesi riwayat tidak ditemukan.');
                return;
            }
            const fManual = document.getElementById('dp-input-koreksi-f').value.trim();
            const eManual = document.getElementById('dp-input-koreksi-e').value.trim();
            const cManual = document.getElementById('dp-input-koreksi-c').value.trim();

            try {
                const res = await fetch('api/update_koreksi_riwayat.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: idSesiRiwayatSedangDibuka, f_manual: fManual, e_manual: eManual, c_manual: cManual })
                });
                const data = await res.json();
                if (data.status !== 'success') {
                    alert(data.message || 'Gagal menyimpan koreksi.');
                    return;
                }

                // Ekstraksi nilai dengan dukungan kedua format respons (top-level / nested data)
                const valF = (data.F_v2 !== undefined) ? data.F_v2 : (data.data?.F_v2 ?? data.data?.f_v2);
                const isFManual = (data.F_v2_manual !== undefined) ? data.F_v2_manual : (data.data?.f_manual !== null && data.data?.f_manual !== undefined);

                const valE = (data.E_v2 !== undefined) ? data.E_v2 : (data.data?.E_v2 ?? data.data?.e_v2);
                const isEManual = (data.E_v2_manual !== undefined) ? data.E_v2_manual : (data.data?.e_manual !== null && data.data?.e_manual !== undefined);

                const valC = (data.C_v2 !== undefined) ? data.C_v2 : (data.data?.C_v2 ?? data.data?.c_v2);
                const isCManual = (data.C_v2_manual !== undefined) ? data.C_v2_manual : (data.data?.c_manual !== null && data.data?.c_manual !== undefined);

                const valH = (data.H_v2_persen !== undefined) ? data.H_v2_persen : (data.data?.H_v2_persen ?? data.data?.h_v2_persen ?? 0);
                const valJ = (data.J_v2 !== undefined) ? data.J_v2 : (data.data?.J_v2 ?? data.data?.j_v2 ?? 0);
                const valK = (data.K_v2 !== undefined) ? data.K_v2 : (data.data?.K_v2 ?? data.data?.k_v2 ?? 0);
                const valL = (data.EFF_v2_persen !== undefined) ? data.EFF_v2_persen : (data.data?.EFF_v2_persen ?? data.data?.eff_v2_persen ?? data.eff_v2_persen ?? 0);

                // Update tampilan modal langsung dengan hasil baru dari server.
                document.getElementById('dp-val-F').innerText = valF;
                document.getElementById('dp-val-F-manual-tag').style.display = isFManual ? '' : 'none';
                document.getElementById('dp-val-E').innerText = valE;
                document.getElementById('dp-val-E-manual-tag').style.display = isEManual ? '' : 'none';
                document.getElementById('dp-val-C').innerText = valC;
                document.getElementById('dp-val-C-manual-tag').style.display = isCManual ? '' : 'none';
                document.getElementById('dp-val-H').innerText = `${valH}%`;
                document.getElementById('dp-val-J').innerText = valJ;
                document.getElementById('dp-val-K').innerText = valK;
                document.getElementById('dp-val-L').innerText = `${valL}%`;

                // Sinkronkan cache riwayatPerformanceById supaya kalau modal dibuka lagi tanpa
                // reload daftar, datanya tetap konsisten dengan hasil koreksi barusan.
                if (riwayatPerformanceById[idSesiRiwayatSedangDibuka]) {
                    const r = riwayatPerformanceById[idSesiRiwayatSedangDibuka];
                    r.F = valF; r.F_manual = isFManual ? Number(fManual) : null;
                    r.E_v2 = valE; r.E_manual = isEManual ? Number(eManual) : null;
                    r.C = valC; r.C_manual = isCManual ? Number(cManual) : null;
                    r.H_v2_persen = valH; r.J_v2 = valJ; r.K_v2 = valK; r.EFF_v2_persen = valL;
                }

                document.getElementById('form-koreksi-riwayat').style.display = 'none';

                // Muat ulang daftar riwayat di belakang modal, biar kartu ringkasan (EFF dsb) ikut update.
                if (typeof loadRiwayatPerformance === 'function') loadRiwayatPerformance();
            } catch (err) {
                alert('Gagal menghubungi server: ' + err.message);
            }
        }

        function tutupModalDetailPerformance() {
            document.getElementById('modal-detail-performance').style.display = 'none';
        }

        function tutupModalJikaDiLuarDetailPerformance(e) {
            if (e.target.id === 'modal-detail-performance') tutupModalDetailPerformance();
        }

        // ===== MODAL: RINCIAN CYCLE TIME PER SHEET =====
        // Dipanggil dari 2 tempat: card live (sesi berjalan) & modal riwayat (sesi selesai).
        // Keduanya memanggil fungsi bersama renderDetailCt() setelah menyiapkan parameter
        // rentang jam yang sesuai konteksnya masing-masing.
        async function renderDetailCt(line, tanggal, jamMulai, jamSelesai, part) {
            document.getElementById('dct-part-label').innerText = part || '-';
            document.getElementById('modal-detail-ct').style.display = 'flex';

            const tabelWrapper = document.getElementById('dct-tabel-wrapper');
            const kosongEl = document.getElementById('dct-kosong');
            const isiTabel = document.getElementById('dct-isi-tabel');
            isiTabel.innerHTML = '';
            tabelWrapper.style.display = 'none';
            kosongEl.style.display = 'none';
            kosongEl.innerText = 'Memuat rincian cycle time...';
            kosongEl.style.display = '';

            if (!jamMulai || !jamSelesai || !part) {
                kosongEl.innerText = 'Data sesi belum lengkap (jam mulai/selesai/part), tidak bisa menampilkan rincian.';
                return;
            }

            try {
                const url = `api/detail_cycle_time.php?line=${encodeURIComponent(line)}&tanggal=${encodeURIComponent(tanggal)}`
                    + `&jam_mulai=${encodeURIComponent(jamMulai)}&jam_selesai=${encodeURIComponent(jamSelesai)}&part=${encodeURIComponent(part)}`;
                const res = await fetch(url, { cache: "no-store" });
                const data = await res.json();

                if (data.status !== 'success') {
                    kosongEl.innerText = data.message || 'Gagal memuat rincian cycle time.';
                    return;
                }

                document.getElementById('dct-jumlah').innerText = `${data.jumlah_event} sheet`;
                document.getElementById('dct-rata').innerText = `${data.rata_rata_ct_per_pcs} menit`;
                document.getElementById('dct-median').innerText = `${data.median_ct_per_pcs} menit`;
                document.getElementById('dct-minmax').innerText = `${data.min_ct_per_pcs} / ${data.max_ct_per_pcs} menit`;

                if (data.jumlah_event === 0) {
                    kosongEl.innerText = 'Belum ada event output pada rentang ini.';
                    kosongEl.style.display = '';
                    tabelWrapper.style.display = 'none';
                    return;
                }

                kosongEl.style.display = 'none';
                tabelWrapper.style.display = '';

                // Tandai baris dengan nilai jauh di atas median (>1.5x) sebagai kemungkinan
                // outlier -- indikasi visual cepat tanpa operator harus bandingkan manual
                // satu-satu ke angka median di ringkasan atas.
                const ambangOutlier = data.median_ct_per_pcs * 1.5;

                data.event.forEach(ev => {
                    const kemungkinanOutlier = ambangOutlier > 0 && ev.ct_per_pcs_menit > ambangOutlier;
                    const baris = document.createElement('tr');
                    if (kemungkinanOutlier) baris.style.background = '#fff3e0';
                    baris.innerHTML = `
                        <td>${ev.nomor}</td>
                        <td>${ev.jam}</td>
                        <td>${ev.cycle_time_detik_mentah}</td>
                        <td>${ev.ct_per_sheet_menit}</td>
                        <td>${ev.ct_per_pcs_menit}${kemungkinanOutlier ? ' <span style="color:#b45309; font-weight:700;" title="Jauh di atas median, kemungkinan sheet ini kena jeda saat mesin berhenti">⚠️</span>' : ''}</td>
                    `;
                    isiTabel.appendChild(baris);
                });
            } catch (err) {
                kosongEl.innerText = 'Gagal menghubungi server: ' + err.message;
                kosongEl.style.display = '';
                tabelWrapper.style.display = 'none';
            }
        }

        // Konteks LIVE -- sesi sedang berjalan di card "Tabel Hasil Produksi".
        function bukaDetailCtLive() {
            const tanggal = document.getElementById('perf-tanggal').value || currentTanggal;
            const jamMulai = document.getElementById('perf-jam-mulai').value;
            const jamSelesai = jamSekarangHHMM();
            const part = document.getElementById('perf-part-label').innerText;
            renderDetailCt(currentLine, tanggal, jamMulai, jamSelesai, part);
        }

        // Konteks RIWAYAT -- sesi yang sudah selesai & tersimpan, dibuka dari modal Detail Sesi Produksi.
        function bukaDetailCtRiwayat() {
            const r = riwayatPerformanceById[idSesiRiwayatSedangDibuka];
            if (!r) return;
            const tanggal = tanggalRiwayatPerformanceSedangDilihat || currentTanggal;
            renderDetailCt(currentLine, tanggal, r.jam_mulai, r.jam_selesai, r.part);
        }

        function tutupModalDetailCt() {
            document.getElementById('modal-detail-ct').style.display = 'none';
        }

        function tutupModalJikaDiLuarDetailCt(e) {
            if (e.target.id === 'modal-detail-ct') tutupModalDetailCt();
        }

        function navigateTo(line, view, tanggal, extra) {
            let targetPath = (view === 'dashboard') ? 'dashboard' : view;
            let url = `${APP_BASE_PATH}/${targetPath}?line=${encodeURIComponent(line)}`;
            if (view === 'detail' && tanggal) url += `&tanggal=${encodeURIComponent(tanggal)}`;
            if (view === 'detail-shift' && extra && extra.shift) {
                url += `&tanggal=${encodeURIComponent(extra.tanggal || tanggal)}&shift=${encodeURIComponent(extra.shift)}`;
            }
            history.pushState({ line, view, tanggal, extra }, '', url);
            if (view === 'detail-shift' && extra && extra.shift) {
                detailShiftParams = { tanggal: extra.tanggal || tanggal, shift: extra.shift };
            }
            renderView(line, view, tanggal);
        }


        // Klik pada elemen manapun yang punya atribut data-nav (tombol aksi di topbar, dibuat lewat
        // innerHTML) ditangkap di sini biar gak reload halaman. Item sidebar (header line & sub-item)
        // sudah punya onclick sendiri masing-masing (bukaGrupLine / navigateSidebar / navigateSidebarPengaturan),
        // jadi TIDAK diikutsertakan di selector ini supaya navigasinya tidak double-handled.
        document.addEventListener('click', (e) => {
            const target = e.target.closest('[data-nav]');
            if (!target || target.classList.contains('disabled')) return;

            e.preventDefault();
            const line = target.dataset.line || currentLine;
            const view = target.dataset.view || 'dashboard';
            const tanggal = target.dataset.tanggal || null;
            const shift = target.dataset.shift || null;
            navigateTo(line, view, tanggal, shift ? { tanggal, shift } : null);
        });

        // Dukung tombol Back/Forward browser
        window.addEventListener('popstate', (e) => {
            if (e.state) {
                if (e.state.view === 'detail-shift' && e.state.extra && e.state.extra.shift) {
                    detailShiftParams = { tanggal: e.state.extra.tanggal || e.state.tanggal, shift: e.state.extra.shift };
                }
                renderView(e.state.line, e.state.view, e.state.tanggal);
            }
        });

        // ===== KWH CONTROL (LINE 3 MONITORING) =====
        let chartKwhConsumptionInstance = null;
        let chartKwhCurrentInstance = null;
        let chartDailyConsumptionInstance = null;
        let chartDailyCostInstance = null;
        let chartMonthlyConsumptionInstance = null;
        let chartMonthlyCostInstance = null;
        let isKwhLoading = false;

        // Plugin kustom untuk merender label teks vertikal di dalam bar (seperti pada screenshot asli)
        const verticalBarLabelsPlugin = {
            id: 'verticalBarLabels',
            afterDatasetsDraw(chart) {
                const { ctx } = chart;
                chart.data.datasets.forEach((dataset, datasetIndex) => {
                    const meta = chart.getDatasetMeta(datasetIndex);
                    if (!meta || !meta.visible) return;

                    meta.data.forEach((bar, index) => {
                        const rawVal = dataset.data[index];
                        if (rawVal === null || rawVal === undefined || rawVal <= 0) return;

                        const barHeight = Math.abs(bar.base - bar.y);
                        if (barHeight < 24) return;

                        let text = '';
                        if (chart.canvas.id.includes('cost')) {
                            text = new Intl.NumberFormat('id-ID', {
                                minimumFractionDigits: 2,
                                maximumFractionDigits: 2
                            }).format(rawVal);
                        } else {
                            text = new Intl.NumberFormat('id-ID', {
                                minimumFractionDigits: 2,
                                maximumFractionDigits: 2
                            }).format(rawVal);
                        }

                        ctx.save();
                        ctx.translate(bar.x, (bar.y + bar.base) / 2);
                        ctx.rotate(-Math.PI / 2);
                        ctx.textAlign = 'center';
                        ctx.textBaseline = 'middle';
                        ctx.font = 'bold 9px "Inter", sans-serif';

                        // Outline tipis agar terbaca jelas
                        ctx.strokeStyle = 'rgba(0, 0, 0, 0.55)';
                        ctx.lineWidth = 2.5;
                        ctx.strokeText(text, 0, 0);

                        // Teks warna kuning emas cerah persis seperti di screenshot
                        ctx.fillStyle = '#fde047';
                        ctx.fillText(text, 0, 0);

                        ctx.restore();
                    });
                });
            }
        };

        async function loadKwhControlData(tanggalParam = null) {
            if (isKwhLoading) return;
            isKwhLoading = true;

            const inputTanggal = document.getElementById('kwh-filter-tanggal');
            let tgl = tanggalParam;
            if (!tgl && inputTanggal) {
                tgl = inputTanggal.value;
            }
            if (!tgl) {
                tgl = new Date().toISOString().split('T')[0];
            }
            if (inputTanggal) {
                inputTanggal.value = tgl;
            }

            // Format tanggal untuk label badge (DD/MM/YYYY)
            let tglDisplay = tgl;
            const parts = tgl.split('-');
            if (parts.length === 3) {
                tglDisplay = `${parts[2]}/${parts[1]}/${parts[0]}`;
            }

            const badgeCons = document.getElementById('kwh-consumption-tgl-label');
            const badgeCurrent = document.getElementById('kwh-current-tgl-label');
            const badgeDailyCons = document.getElementById('kwh-badge-daily-consumption');
            const badgeDailyCost = document.getElementById('kwh-badge-daily-cost');
            const badgeMonthlyCons = document.getElementById('kwh-badge-monthly-consumption');
            const badgeMonthlyCost = document.getElementById('kwh-badge-monthly-cost');
            const legendPeak = document.getElementById('kwh-legend-peak');
            const legendOffpeak = document.getElementById('kwh-legend-offpeak');

            if (badgeCons) badgeCons.innerText = tglDisplay;
            if (badgeCurrent) badgeCurrent.innerText = tglDisplay;
            if (badgeDailyCons) badgeDailyCons.innerText = tglDisplay;
            if (badgeDailyCost) badgeDailyCost.innerText = tglDisplay;
            if (badgeMonthlyCons) badgeMonthlyCons.innerText = tglDisplay;
            if (badgeMonthlyCost) badgeMonthlyCost.innerText = tglDisplay;
            if (legendPeak) legendPeak.innerText = `Beban Puncak (${tglDisplay})`;
            if (legendOffpeak) legendOffpeak.innerText = `Luar Beban Puncak (${tglDisplay})`;

            try {
                const res = await fetch(`/api/kwh/data?device=pcb_line_3&tanggal=${encodeURIComponent(tgl)}`, { cache: 'no-store' });
                const json = await res.json();

                if (json.status === 'success') {
                    // 1. Update Live Metric Cards
                    if (json.latest) {
                        const l = json.latest;
                        const elV = document.getElementById('kwh-val-voltage');
                        const elI = document.getElementById('kwh-val-current');
                        const elP = document.getElementById('kwh-val-power');
                        const elE = document.getElementById('kwh-val-energy');
                        const elF = document.getElementById('kwh-val-frequency');
                        const elPf = document.getElementById('kwh-val-pf');
                        const elCo2 = document.getElementById('kwh-val-co2');

                        if (elV) elV.innerHTML = `${l.voltage} <span class="kwh-unit">V</span>`;
                        if (elI) elI.innerHTML = `${l.current} <span class="kwh-unit">A</span>`;
                        if (elP) elP.innerHTML = `${l.active_power} <span class="kwh-unit">kW</span>`;
                        if (elE) elE.innerHTML = `${l.energy} <span class="kwh-unit">kWh</span>`;
                        if (elF) elF.innerHTML = `${l.frequency} <span class="kwh-unit">Hz</span>`;
                        if (elPf) elPf.innerText = l.power_factor;
                        if (elCo2) elCo2.innerHTML = `${l.co2} <span class="kwh-unit">kg</span>`;
                    }

                    // 2. Update Status Badge (ON / OFF)
                    const statusBadge = document.getElementById('kwh-status-badge');
                    const statusText = document.getElementById('kwh-status-text');
                    if (statusBadge && statusText) {
                        if (json.is_online) {
                            statusBadge.className = 'kwh-status-badge on';
                            statusText.innerText = 'ON';
                        } else {
                            statusBadge.className = 'kwh-status-badge off';
                            statusText.innerText = 'OFF';
                        }
                    }

                    // 3. Update Indicator Jam Update
                    const indicator = document.getElementById('kwh-last-updated');
                    if (indicator) {
                        const now = new Date();
                        const timeStr = now.toTimeString().split(' ')[0];
                        indicator.innerText = `Terakhir diperbarui: ${timeStr}`;
                    }

                    // 4. Render 3 Kartu Awal (Daily Line 3 Consumption & Daily Current Line 3)
                    if (json.hourly) {
                        renderKwhConsumptionChart(json.hourly);
                        renderKwhCurrentChart(json.hourly);
                        renderDailyConsumptionChart(json.hourly, tglDisplay);
                    }
                    if (json.daily_cost) {
                        renderDailyCostChart(json.daily_cost, tglDisplay);
                    }
                    if (json.monthly_consumption) {
                        renderMonthlyConsumptionChart(json.monthly_consumption, tglDisplay);
                    }
                    if (json.monthly_cost) {
                        renderMonthlyCostChart(json.monthly_cost, tglDisplay);
                    }
                }
            } catch (err) {
                console.error('Gagal mengambil data KWh control:', err);
                const indicator = document.getElementById('kwh-last-updated');
                if (indicator) indicator.innerText = 'Koneksi error';
            } finally {
                isKwhLoading = false;
            }
        }

        // Render Grafik Bar: Daily Line 3 Consumption (Kartu Awal)
        function renderKwhConsumptionChart(hourlyData) {
            const canvas = document.getElementById('chart-kwh-consumption');
            if (!canvas) return;

            const labels = hourlyData.map(h => h.hour);
            const values = hourlyData.map(h => h.energy);

            if (chartKwhConsumptionInstance) {
                chartKwhConsumptionInstance.data.labels = labels;
                chartKwhConsumptionInstance.data.datasets[0].data = values;
                chartKwhConsumptionInstance.update('none');
                return;
            }

            const ctx = canvas.getContext('2d');
            chartKwhConsumptionInstance = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'kWh / hour',
                        data: values,
                        backgroundColor: '#2563eb',
                        borderRadius: 3,
                        barPercentage: 0.85,
                        categoryPercentage: 0.9,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    layout: { padding: { left: 0, right: 2, top: 8, bottom: 0 } },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: (ctx) => ` Konsumsi: ${new Intl.NumberFormat('id-ID', { minimumFractionDigits: 1, maximumFractionDigits: 2 }).format(ctx.raw)} kWh`
                            }
                        }
                    },
                    scales: {
                        x: {
                            title: { display: true, text: 'Hour', font: { size: 10, weight: 'bold' }, color: '#475569', padding: { top: 2 } },
                            ticks: { autoSkip: false, font: { size: 8.5, weight: '600' }, color: '#64748b', padding: 2 },
                            grid: { display: false }
                        },
                        y: {
                            title: { display: true, text: 'kWh', font: { size: 10, weight: 'bold' }, color: '#475569' },
                            ticks: { font: { size: 9 }, color: '#64748b', stepSize: 2 },
                            grid: { color: '#f1f5f9', borderDash: [2, 2] },
                            beginAtZero: true,
                            suggestedMax: 16
                        }
                    }
                }
            });
        }

        // Render Grafik Line: Daily Current Line 3 (Kartu Awal)
        function renderKwhCurrentChart(hourlyData) {
            const canvas = document.getElementById('chart-kwh-current');
            if (!canvas) return;

            const labels = hourlyData.map(h => h.hour);
            const values = hourlyData.map(h => h.current);

            if (chartKwhCurrentInstance) {
                chartKwhCurrentInstance.data.labels = labels;
                chartKwhCurrentInstance.data.datasets[0].data = values;
                chartKwhCurrentInstance.update('none');
                return;
            }

            const ctx = canvas.getContext('2d');
            chartKwhCurrentInstance = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Current / hour',
                        data: values,
                        borderColor: '#2563eb',
                        backgroundColor: 'transparent',
                        pointBackgroundColor: '#2563eb',
                        pointBorderColor: '#2563eb',
                        pointBorderWidth: 1,
                        pointRadius: 3,
                        pointHoverRadius: 5,
                        tension: 0.1,
                        fill: false
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    layout: { padding: { left: 0, right: 2, top: 8, bottom: 0 } },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: (ctx) => ` Arus: ${new Intl.NumberFormat('id-ID', { minimumFractionDigits: 1, maximumFractionDigits: 2 }).format(ctx.raw)} A`
                            }
                        }
                    },
                    scales: {
                        x: {
                            title: { display: true, text: 'Hour', font: { size: 10, weight: 'bold' }, color: '#475569', padding: { top: 2 } },
                            ticks: { autoSkip: false, font: { size: 8.5, weight: '600' }, color: '#64748b', padding: 2 },
                            grid: { color: '#f1f5f9', borderDash: [2, 2] }
                        },
                        y: {
                            title: { display: true, text: 'A', font: { size: 10, weight: 'bold' }, color: '#475569' },
                            ticks: { font: { size: 9 }, color: '#64748b', stepSize: 5 },
                            grid: { color: '#f1f5f9', borderDash: [2, 2] },
                            beginAtZero: true,
                            suggestedMax: 30
                        }
                    }
                }
            });
        }

        function renderDailyConsumptionChart(hourlyData, tglDisplay) {
            const canvas = document.getElementById('chart-daily-consumption');
            if (!canvas) return;

            const labels = hourlyData.map(h => h.hour);
            const values = hourlyData.map(h => h.energy);

            if (chartDailyConsumptionInstance) {
                chartDailyConsumptionInstance.data.labels = labels;
                chartDailyConsumptionInstance.data.datasets[0].data = values;
                chartDailyConsumptionInstance.update('none');
                return;
            }

            const ctx = canvas.getContext('2d');
            chartDailyConsumptionInstance = new Chart(ctx, {
                type: 'bar',
                plugins: [verticalBarLabelsPlugin],
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'kWh Total / hour',
                        data: values,
                        backgroundColor: '#2563eb',
                        borderRadius: 3,
                        barPercentage: 0.85,
                        categoryPercentage: 0.9,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    layout: { padding: { left: 0, right: 4, top: 10, bottom: 0 } },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: (ctx) => ` Konsumsi: ${new Intl.NumberFormat('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(ctx.raw)} kWh`
                            }
                        }
                    },
                    scales: {
                        x: {
                            title: { display: true, text: 'Hour', font: { size: 10, weight: 'bold' }, color: '#475569', padding: { top: 2 } },
                            ticks: { autoSkip: false, font: { size: 8.5, weight: '600' }, color: '#64748b' },
                            grid: { display: false }
                        },
                        y: {
                            title: { display: true, text: 'kWh', font: { size: 10, weight: 'bold' }, color: '#475569' },
                            ticks: { font: { size: 9 }, color: '#64748b' },
                            grid: { color: '#f1f5f9', borderDash: [2, 2] },
                            beginAtZero: true,
                            suggestedMax: 35
                        }
                    }
                }
            });
        }

        function renderDailyCostChart(dailyCostData, tglDisplay) {
            const canvas = document.getElementById('chart-daily-cost');
            if (!canvas) return;

            const labels = dailyCostData.map(d => d.hour);
            const peakValues = dailyCostData.map(d => d.peak_cost > 0 ? d.peak_cost : null);
            const offpeakValues = dailyCostData.map(d => d.offpeak_cost > 0 ? d.offpeak_cost : null);

            if (chartDailyCostInstance) {
                chartDailyCostInstance.data.labels = labels;
                chartDailyCostInstance.data.datasets[0].label = `Beban Puncak (${tglDisplay})`;
                chartDailyCostInstance.data.datasets[0].data = peakValues;
                chartDailyCostInstance.data.datasets[1].label = `Luar Beban Puncak (${tglDisplay})`;
                chartDailyCostInstance.data.datasets[1].data = offpeakValues;
                chartDailyCostInstance.update('none');
                return;
            }

            const ctx = canvas.getContext('2d');
            chartDailyCostInstance = new Chart(ctx, {
                type: 'bar',
                plugins: [verticalBarLabelsPlugin],
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: `Beban Puncak (${tglDisplay})`,
                            data: peakValues,
                            backgroundColor: '#ffa200',
                            borderRadius: 3,
                            barPercentage: 0.85,
                            categoryPercentage: 0.9,
                            stack: 'cost'
                        },
                        {
                            label: `Luar Beban Puncak (${tglDisplay})`,
                            data: offpeakValues,
                            backgroundColor: '#15803d',
                            borderRadius: 3,
                            barPercentage: 0.85,
                            categoryPercentage: 0.9,
                            stack: 'cost'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    layout: { padding: { left: 0, right: 4, top: 10, bottom: 0 } },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: (ctx) => ` ${ctx.dataset.label}: Rp ${new Intl.NumberFormat('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(ctx.raw)}`
                            }
                        }
                    },
                    scales: {
                        x: {
                            title: { display: true, text: 'Hour', font: { size: 10, weight: 'bold' }, color: '#475569', padding: { top: 2 } },
                            ticks: { autoSkip: false, font: { size: 8.5, weight: '600' }, color: '#64748b' },
                            grid: { display: false }
                        },
                        y: {
                            title: { display: true, text: 'Rp', font: { size: 10, weight: 'bold' }, color: '#475569' },
                            ticks: {
                                font: { size: 9 },
                                color: '#64748b',
                                callback: (v) => 'Rp ' + new Intl.NumberFormat('id-ID').format(v)
                            },
                            grid: { color: '#f1f5f9', borderDash: [2, 2] },
                            beginAtZero: true,
                            suggestedMax: 35000
                        }
                    }
                }
            });
        }

        function renderMonthlyConsumptionChart(monthlyConsumptionData, tglDisplay) {
            const canvas = document.getElementById('chart-monthly-consumption');
            if (!canvas) return;

            const labels = monthlyConsumptionData.map(m => m.day);
            const values = monthlyConsumptionData.map(m => m.energy);

            if (chartMonthlyConsumptionInstance) {
                chartMonthlyConsumptionInstance.data.labels = labels;
                chartMonthlyConsumptionInstance.data.datasets[0].data = values;
                chartMonthlyConsumptionInstance.update('none');
                return;
            }

            const ctx = canvas.getContext('2d');
            chartMonthlyConsumptionInstance = new Chart(ctx, {
                type: 'bar',
                plugins: [verticalBarLabelsPlugin],
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'kWh Total / day',
                        data: values,
                        backgroundColor: '#2563eb',
                        borderRadius: 3,
                        barPercentage: 0.85,
                        categoryPercentage: 0.9,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    layout: { padding: { left: 0, right: 4, top: 10, bottom: 0 } },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: (ctx) => ` Konsumsi: ${new Intl.NumberFormat('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(ctx.raw)} kWh`
                            }
                        }
                    },
                    scales: {
                        x: {
                            title: { display: true, text: 'Day', font: { size: 10, weight: 'bold' }, color: '#475569', padding: { top: 2 } },
                            ticks: { autoSkip: false, font: { size: 8, weight: '600' }, color: '#64748b' },
                            grid: { display: false }
                        },
                        y: {
                            title: { display: true, text: 'kWh', font: { size: 10, weight: 'bold' }, color: '#475569' },
                            ticks: { font: { size: 9 }, color: '#64748b' },
                            grid: { color: '#f1f5f9', borderDash: [2, 2] },
                            beginAtZero: true,
                            suggestedMax: 800
                        }
                    }
                }
            });
        }

        function renderMonthlyCostChart(monthlyCostData, tglDisplay) {
            const canvas = document.getElementById('chart-monthly-cost');
            if (!canvas) return;

            const labels = monthlyCostData.map(m => m.day);
            const values = monthlyCostData.map(m => m.total_cost);

            if (chartMonthlyCostInstance) {
                chartMonthlyCostInstance.data.labels = labels;
                chartMonthlyCostInstance.data.datasets[0].data = values;
                chartMonthlyCostInstance.update('none');
                return;
            }

            const ctx = canvas.getContext('2d');
            chartMonthlyCostInstance = new Chart(ctx, {
                type: 'bar',
                plugins: [verticalBarLabelsPlugin],
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Total Harga / day',
                        data: values,
                        backgroundColor: '#15803d',
                        borderRadius: 3,
                        barPercentage: 0.85,
                        categoryPercentage: 0.9,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    layout: { padding: { left: 0, right: 4, top: 10, bottom: 0 } },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: (ctx) => ` Total Harga: Rp ${new Intl.NumberFormat('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(ctx.raw)}`
                            }
                        }
                    },
                    scales: {
                        x: {
                            title: { display: true, text: 'Day', font: { size: 10, weight: 'bold' }, color: '#475569', padding: { top: 2 } },
                            ticks: { autoSkip: false, font: { size: 8, weight: '600' }, color: '#64748b' },
                            grid: { display: false }
                        },
                        y: {
                            title: { display: true, text: 'Rp', font: { size: 10, weight: 'bold' }, color: '#475569' },
                            ticks: {
                                font: { size: 9 },
                                color: '#64748b',
                                callback: (v) => 'Rp ' + new Intl.NumberFormat('id-ID').format(v)
                            },
                            grid: { color: '#f1f5f9', borderDash: [2, 2] },
                            beginAtZero: true,
                            suggestedMax: 800000
                        }
                    }
                }
            });
        }

        // ===== JALANKAN SEMUA =====
        setInterval(() => { if (currentView === 'dashboard') { updateData(); loadMachineRatio(); } }, 3000);
        // Polling status mesin selalu jalan di semua halaman (bukan cuma dashboard), supaya transisi
        // Running <-> berhenti tetap terdeteksi buat sesi otomatis Kalkulator Performance meski user
        // sedang membuka halaman lain (mis. Machine Control).
        setInterval(updateStatusMesin, 3000);
        setInterval(() => {
            if (currentView === 'chart') updateChartAktif();
            else if (currentView === 'riwayat') {
                if (riwayatTabAktif === 'produksi') updateHistoriTable();
                else if (riwayatTabAktif === 'mc') loadRiwayatPerformance();
                else if (riwayatTabAktif === 'shift') loadRiwayatShift();
            }
            else if (currentView === 'detail') loadDetailHarian(currentLine, currentTanggal);
            else if (currentView === 'kwh-control') loadKwhControlData();
            else if (currentView === 'machine-control') {
                loadRingkasanAlasanStop();
                perbaruiHasilPerformanceLive();
            }
        }, 5000);
        setInterval(updateGreetingClock, 1000);

        // ===== PART AKTIF: simpan & pulihkan pilihan part di Machine Control =====
        // Dulu bagian ini polling ke get_part.php setiap 2 detik buat "nangkep" part yang
        // dipilih dari aplikasi simulasi Visual Studio (sudah tidak dipakai lagi -- sekarang
        // data produksi & status murni dari Arduino). Sekarang dibalik: part yang dipilih
        // MANUAL oleh operator di halaman ini yang disimpan ke server (lewat set_part.php),
        // supaya kalau halaman di-refresh atau dibuka dari perangkat lain, pilihan part
        // terakhir tetap kepakai -- tanpa bergantung pada aplikasi simulasi apapun.
        async function simpanPartTerpilih(namaPart) {
            try {
                if (namaPart) {
                    localStorage.setItem('selected_part_' + currentLine, namaPart);
                } else {
                    localStorage.removeItem('selected_part_' + currentLine);
                }
                await fetch('api/set_part.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `api_key=${encodeURIComponent(API_KEY_INTERNAL)}&line=${encodeURIComponent(currentLine)}&part=${encodeURIComponent(namaPart || '')}`
                });
            } catch (e) {
                // Diabaikan -- kalau gagal simpan, paling cuma gak ke-restore pas refresh nanti
            }
        }

        async function pulihkanPartTersimpan() {
            try {
                const resp = await fetch(`api/get_part.php?line=${encodeURIComponent(currentLine)}`, { cache: 'no-store' });
                const d = await resp.json();
                if (d && d.status === 'success') {
                    if (d.part) {
                        const searchInput = document.getElementById('ct-part-search');
                        const select = document.getElementById('ct-part-select');
                        if (searchInput) searchInput.value = d.part;
                        if (select) {
                            isiDropdownPartCt(DAFTAR_PART_CT);
                            select.value = d.part;
                        }
                        localStorage.setItem('selected_part_' + currentLine, d.part);
                        tampilkanDetailCt(d.part, false); // false = jangan simpan ulang, ini cuma restore
                    } else {
                        // Server mengonfirmasi part kosong/direset -- bersihkan localStorage & UI
                        localStorage.removeItem('selected_part_' + currentLine);
                        const searchInput = document.getElementById('ct-part-search');
                        const select = document.getElementById('ct-part-select');
                        if (searchInput) searchInput.value = '';
                        if (select) select.value = '';
                        tampilkanDetailCt('', false);
                        isiDropdownCtPerformance(null);
                    }
                }
            } catch (e) {
                const localPart = localStorage.getItem('selected_part_' + currentLine);
                if (localPart) tampilkanDetailCt(localPart, false);
            }
        }

        // ===== SESI PRODUKSI AKTIF: persist jam mulai & part ke server =====
        // Dulu status "sesi produksi sedang berjalan" (sesiProduksiAktif, perf-jam-mulai)
        // cuma hidup di variabel JavaScript browser -- begitu halaman di-refresh, semua
        // reset ke kosong walaupun sesi di lapangan masih berjalan, sehingga operator
        // dianggap "belum ada sesi" dan harus pilih part lagi (jam mulai ikut reset ke
        // jam refresh, bukan jam mulai sesi yang sebenarnya). Fungsi-fungsi di bawah ini
        // menyimpan & memulihkan status itu lewat api/sesi_aktif.php supaya refresh
        // halaman tidak lagi memutus sesi yang sedang berjalan.
        async function simpanSesiAktifKeServer() {
            const inputTanggal = document.getElementById('perf-tanggal');
            const inputMulai = document.getElementById('perf-jam-mulai');
            const labelPart = document.getElementById('perf-part-label');
            if (!inputMulai || !inputMulai.value) return;

            try {
                await fetch('api/sesi_aktif.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `api_key=${encodeURIComponent(API_KEY_INTERNAL)}&line=${encodeURIComponent(currentLine)}`
                        + `&aktif=1&part=${encodeURIComponent(labelPart ? labelPart.innerText : '')}`
                        + `&tanggal=${encodeURIComponent(inputTanggal ? inputTanggal.value : '')}`
                        + `&jam_mulai=${encodeURIComponent(inputMulai.value)}`
                });
            } catch (e) {
                // Diabaikan -- kalau gagal simpan, paling cuma gak ke-restore pas refresh nanti
            }
        }

        async function hapusSesiAktifDiServer() {
            try {
                await fetch('api/sesi_aktif.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `api_key=${encodeURIComponent(API_KEY_INTERNAL)}&line=${encodeURIComponent(currentLine)}&aktif=0`
                });
            } catch (e) {
                // Diabaikan
            }
        }

        // Dipanggil sekali saat halaman dibuka/refresh. Cek ke server apakah ada sesi
        // produksi yang MASIH AKTIF untuk line ini (belum ditutup lewat "Selesai Produksi"
        // / "Ganti Type"). Kalau ada, pulihkan jam mulai & part ASLINYA (bukan jam sekarang)
        // supaya timer & sesi lanjut dari titik terakhir, bukan mulai dari nol lagi.
        async function pulihkanSesiAktif() {
            try {
                const resp = await fetch(`api/sesi_aktif.php?line=${encodeURIComponent(currentLine)}`, { cache: 'no-store' });
                const d = await resp.json();
                if (!d || !d.aktif || !d.jam_mulai) {
                    sesiProduksiAktif = false;
                    hentikanTimerLive();
                    resetKartuMetrik();
                    updateStatusBoxMC('idle');
                    return;
                }

                // Cek apakah sesi masih dalam shift yang sedang berjalan saat ini
                const shiftSekarang = dapatkanShiftSekarang();
                const sesiMasihDalamShiftIni = (() => {
                    if (d.tanggal !== shiftSekarang.tanggal) return false;
                    // Shift Malam lintas tengah malam (23:20 - 06:55): jam_mulai sesi valid
                    // kalau >= 23:20 ATAU < 06:55 (dimulai malam itu atau dini hari).
                    if (shiftSekarang.jamMulai > shiftSekarang.jamSelesai) {
                        return d.jam_mulai >= shiftSekarang.jamMulai || d.jam_mulai < shiftSekarang.jamSelesai;
                    }
                    return d.jam_mulai >= shiftSekarang.jamMulai && d.jam_mulai < shiftSekarang.jamSelesai;
                })();

                if (!sesiMasihDalamShiftIni) {
                    // Sesi basi dari shift sebelumnya / hari lalu -- tutup di server & jangan pulihkan
                    await hapusSesiAktifDiServer();
                    sesiProduksiAktif = false;
                    hentikanTimerLive();
                    resetKartuMetrik();
                    updateStatusBoxMC('idle');
                    return;
                }

                const inputTanggal = document.getElementById('perf-tanggal');
                const inputMulai = document.getElementById('perf-jam-mulai');
                const inputSelesai = document.getElementById('perf-jam-selesai');
                const labelPart = document.getElementById('perf-part-label');
                if (inputTanggal) inputTanggal.value = d.tanggal || tanggalLokalYYYYMMDD();
                if (inputMulai) inputMulai.value = d.jam_mulai;
                if (inputSelesai) inputSelesai.value = '';
                sesiProduksiAktif = true;
                if (labelPart && d.part) labelPart.innerText = d.part;
                namaPartSesiAktifSebelumnya = labelPart ? labelPart.innerText : d.part;

                // Pastikan detail CT & nilai C untuk part aktif terisi di UI
                if (d.part) {
                    const searchInput = document.getElementById('ct-part-search');
                    const selectPart = document.getElementById('ct-part-select');
                    if (searchInput) searchInput.value = d.part;
                    if (selectPart) selectPart.value = d.part;
                    localStorage.setItem('selected_part_' + currentLine, d.part);
                    tampilkanDetailCt(d.part, false);
                }

                const boxC = document.getElementById('mc-nilai-c-box');
                const boxKosong = document.getElementById('mc-nilai-c-kosong');
                if (boxC) boxC.style.display = 'flex';
                if (boxKosong) boxKosong.style.display = 'none';

                const hasilEl = document.getElementById('perf-hasil-wrapper');
                if (hasilEl) hasilEl.style.display = '';
                const kosongEl = document.getElementById('perf-hasil-kosong');
                if (kosongEl) kosongEl.style.display = 'none';

                mulaiTimerLive();
                updateStatusBoxMC('berjalan');
                perbaruiHasilPerformanceLive();
            } catch (e) {
                resetKartuMetrik();
            }
        }

        function isiDropdownPartCt(daftar) {
            const select = document.getElementById('ct-part-select');
            if (!select) return;
            const nilaiTerpilih = select.value;
            select.innerHTML = '<option value="">-- Pilih Part --</option>';
            const list = Array.isArray(daftar) ? daftar : (Array.isArray(DAFTAR_PART_CT) ? DAFTAR_PART_CT : []);
            list.forEach(item => {
                const opt = document.createElement('option');
                opt.value = item.part;
                const pcs = parseInt(item.pcs, 10) || 1;
                opt.textContent = `${item.part} (${pcs} pcs/sheet)`;
                select.appendChild(opt);
            });
            if (nilaiTerpilih) select.value = nilaiTerpilih;
        }

        // Inisialisasi dropdown part & pulihkan data
        isiDropdownPartCt(DAFTAR_PART_CT);

        // Pulihkan segera dari localStorage jika ada, agar tidak ada kedipan/reset part saat refresh
        const localSavedPart = localStorage.getItem('selected_part_' + currentLine);
        if (localSavedPart) {
            const searchInput = document.getElementById('ct-part-search');
            const select = document.getElementById('ct-part-select');
            if (searchInput) searchInput.value = localSavedPart;
            if (select) select.value = localSavedPart;
            tampilkanDetailCt(localSavedPart, false);
        }

        (async () => {
            await pulihkanPartTersimpan();
            await pulihkanSesiAktif();
            if (currentView === 'machine-control') {
                if (sesiProduksiAktif) {
                    if (!timerLiveInterval) mulaiTimerLive();
                    updateStatusBoxMC(statusMesinSebelumnya === 'running' ? 'berjalan' : 'jeda');
                }
                perbaruiHasilPerformanceLive();
            }
        })();


        updateGreetingClock();
        renderView(currentLine, currentView, currentTanggal);

        function filterPartCt() {
            const inputSearch = document.getElementById('ct-part-search');
            if (!inputSearch) return;
            const kata = inputSearch.value.trim().toUpperCase();
            const kotakSaran = document.getElementById('ct-search-suggestions');
            if (!kotakSaran) return;

            const list = Array.isArray(DAFTAR_PART_CT) ? DAFTAR_PART_CT : [];
            const hasil = kata === ''
                ? list
                : list.filter(item => item.part.toUpperCase().includes(kata));

            isiDropdownPartCt(hasil);

            if (kata === '') {
                kotakSaran.style.display = 'none';
                kotakSaran.innerHTML = '';
                return;
            }

            if (hasil.length === 0) {
                kotakSaran.innerHTML = '<div class="ct-suggestion-empty">Part tidak ditemukan</div>';
                kotakSaran.style.display = 'block';
                return;
            }

            kotakSaran.innerHTML = hasil.slice(0, 8).map(item => {
                const pcs = parseInt(item.pcs, 10) || 1;
                const ctVal = Array.isArray(item.ct) ? item.ct[0] : (item.ct || 0);
                return `
                    <div class="ct-suggestion-item" onclick="pilihPartDariSaran('${item.part}')" style="display:flex; justify-content:space-between; align-items:center; padding:10px 14px;">
                        <span style="font-weight:600; color:#0d3b8c;">${item.part}</span>
                        <div style="display:flex; align-items:center; gap:8px;">
                            <span class="mp-badge-pcs">${pcs} pcs/sheet</span>
                            <span style="font-size:12px; color:#64748b; font-weight:500;">${ctVal} m</span>
                        </div>
                    </div>
                `;
            }).join('');
            kotakSaran.style.display = 'block';
        }

        function pilihPartDariSaran(namaPart) {
            const inputSearch = document.getElementById('ct-part-search');
            if (inputSearch) inputSearch.value = namaPart;
            const kotakSaran = document.getElementById('ct-search-suggestions');
            if (kotakSaran) kotakSaran.style.display = 'none';
            isiDropdownPartCt(DAFTAR_PART_CT);
            const select = document.getElementById('ct-part-select');
            if (select) select.value = namaPart;
            tampilkanDetailCt(namaPart);
        }

        // simpanKeServer default true: dipanggil true saat operator benar-benar memilih part
        // (lewat dropdown/pencarian), false saat cuma memulihkan pilihan tersimpan pas halaman dibuka.
        function tampilkanDetailCt(namaPart, simpanKeServer = true) {
            const kotakDetail = document.getElementById('ct-detail-box');
            const kotakKosong = document.getElementById('ct-detail-empty');

            if (!namaPart) {
                if (kotakDetail) kotakDetail.style.display = 'none';
                if (kotakKosong) kotakKosong.style.display = 'block';
                return;
            }

            const item = (Array.isArray(DAFTAR_PART_CT) ? DAFTAR_PART_CT : []).find(p => p.part === namaPart);
            if (!item) {
                if (kotakDetail) kotakDetail.style.display = 'none';
                if (kotakKosong) kotakKosong.style.display = 'block';
                isiDropdownCtPerformance(null, simpanKeServer);
                return;
            }

            const detailPartEl = document.getElementById('ct-detail-part');
            if (detailPartEl) detailPartEl.innerText = item.part;

            const wadahNilai = document.getElementById('ct-detail-values');
            if (wadahNilai && Array.isArray(item.ct)) {
                const ctVal = item.ct[0] || 0;
                const detik = Math.round(ctVal * 60 * 100) / 100;
                const pcs = parseInt(item.pcs, 10) || 1;
                wadahNilai.innerHTML = `
                    <div class="ct-value-row">
                        <span class="ct-value-label">CT Standar</span>
                        <span class="ct-value-number ct-value-primary">${ctVal} menit <span style="font-size:12px; font-weight:500; color:#64748b; margin-left:4px;">(${detik} detik)</span></span>
                    </div>
                    <div class="ct-value-row">
                        <span class="ct-value-label">Pcs per Sheet</span>
                        <span class="ct-value-number ct-value-secondary" style="color:#0284c7; font-weight:700;">${pcs} pcs / sheet</span>
                    </div>
                `;
            }

            if (kotakKosong) kotakKosong.style.display = 'none';
            if (kotakDetail) kotakDetail.style.display = 'block';

            // Sinkronkan pilihan part ke Kalkulator Cycle Time & Performance (nilai C)
            isiDropdownCtPerformance(item, simpanKeServer);

            // Simpan ke server SUPAYA persisten (cuma kalau ini benar-benar aksi pilih
            // manual dari operator, bukan sekadar restore pas halaman baru dibuka)
            if (simpanKeServer) simpanPartTerpilih(namaPart);
        }

        // ===== KONTROL PRODUKSI PART: SINKRONISASI PART TERPILIH =====
        // Mengisi "Nilai CT yang Dipakai" dengan CT part yang baru dipilih di atas
        // (biar nilai C otomatis ngikut part, sesuai alur: pilih part -> CT otomatis kepakai).
        function isiDropdownCtPerformance(item, aksiManualOperator = true) {
            const select = document.getElementById('perf-ct-pilihan');
            const labelPart = document.getElementById('perf-part-label');
            const boxC     = document.getElementById('mc-nilai-c-box');
            const boxKosong = document.getElementById('mc-nilai-c-kosong');
            const angkaC   = document.getElementById('mc-nilai-c-angka');
            const partC    = document.getElementById('mc-nilai-c-part');
            if (!select || !labelPart) return;

            if (!item) {
                select.innerHTML = '<option value="">-- Pilih part dulu --</option>';
                labelPart.innerText = 'Belum ada';
                if (boxC)      { boxC.style.display = 'none'; }
                if (boxKosong) { boxKosong.style.display = 'flex'; }
                const hasilEl = document.getElementById('perf-hasil-wrapper');
                const kosongEl = document.getElementById('perf-hasil-kosong');
                if (hasilEl)   { hasilEl.style.display = 'none'; }
                if (kosongEl)  { kosongEl.style.display = ''; }
                return;
            }

            // PENTING: kalau ini kasus ganti-part-langsung (mesin masih Running, sesi masih
            // aktif, part beda dari sesi berjalan), simpan CT & jam mulai sesi LAMA dulu
            // SEBELUM overwrite select/label di bawah -- matikanMesin() baca dari elemen DOM
            // yang sama, jadi kalau tidak disimpan lebih dulu, sesi lama akan salah dihitung
            // pakai CT part BARU.
            const iniGantiPartLangsung = (aksiManualOperator && statusMesinSebelumnya === 'running' && sesiProduksiAktif && item.part !== namaPartSesiAktifSebelumnya);
            let ctSesiLama = null;
            let jamSelesaiSesiLama = null;
            if (iniGantiPartLangsung) {
                ctSesiLama = select.value;
                jamSelesaiSesiLama = jamSekarangHHMM();
            }

            labelPart.innerText = item.part;
            select.innerHTML = item.ct.map((nilai, idx) =>
                `<option value="${nilai}">CT Standar — ${nilai} menit</option>`
            ).join('');

            // Tampilkan kotak nilai C yang terpilih
            if (boxC)      { boxC.style.display = 'flex'; }
            if (boxKosong) { boxKosong.style.display = 'none'; }
            if (angkaC)    { angkaC.innerText = item.ct[0]; }
            if (partC) {
                const pcsVal = parseInt(item.pcs, 10) || 1;
                partC.innerText = `${item.part} (${pcsVal} pcs/sheet)`;
            }

            // Tampilkan Card Tabel Hasil Produksi di Machine Control
            const hasilEl = document.getElementById('perf-hasil-wrapper');
            const kosongEl = document.getElementById('perf-hasil-kosong');
            if (hasilEl) hasilEl.style.display = '';
            if (kosongEl) kosongEl.style.display = 'none';

            // Isi nilai C ke kartu Machine Control
            const valCEl = document.getElementById('perf-val-C');
            if (valCEl) valCEl.innerText = item.ct[0];

            if (iniGantiPartLangsung) {
                aktifkanOverrideMerah(true);
                tutupSesiOtomatisKarenaGantiPartLangsung(ctSesiLama, jamSelesaiSesiLama).then(() => {
                    mulaiSesiDenganPartTerpilih(jamSelesaiSesiLama);
                    aktifkanOverrideMerah(false);
                });
            } else if (aksiManualOperator && !sesiProduksiAktif) {
                // Selalu mulai sesi saat part dipilih MANUAL oleh operator agar card Machine Control langsung aktif & live
                mulaiSesiDenganPartTerpilih();
            } else {
                perbaruiHasilPerformanceLive();
            }
        }

        // Nama part yang sedang berjalan di sesi aktif SEKARANG (sebelum part baru dipilih).
        // Diperbarui tiap kali sesi baru mulai, dipakai buat mendeteksi ganti part langsung.
        let namaPartSesiAktifSebelumnya = null;

        // Kirim flag override_merah ke server -- ESP32 membaca ini lewat get_status.php
        // (fungsi ambilStatusSekarang() di firmware) dan menyalakan relay tower light merah
        // secara paksa selama flag ini true, terlepas dari sinyal tower light asli mesin.
        async function aktifkanOverrideMerah(aktif) {
            try {
                await fetch('api/set_override_merah.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `api_key=${encodeURIComponent(API_KEY_INTERNAL)}&line=${encodeURIComponent(currentLine)}&aktif=${aktif ? '1' : '0'}`
                });
            } catch (e) {
                console.log('Gagal mengubah override_merah: ' + e.message);
            }
        }

        // Menutup sesi yang sedang berjalan memakai CT & jam selesai yang SUDAH DISIMPAN
        // sebelum dropdown/label di-overwrite ke part baru (lihat isiDropdownCtPerformance()).
        // Dipicu otomatis oleh deteksi ganti-part-langsung, BUKAN dari klik tombol operator,
        // jadi tidak bisa memakai matikanMesin() apa adanya (fungsi itu baca CT dari DOM yang
        // di titik ini sudah berisi part BARU, bukan part lama yang sesinya mau ditutup).
        async function tutupSesiOtomatisKarenaGantiPartLangsung(ctSesiLama, jamSelesaiSesiLama) {
            const inputMulai = document.getElementById('perf-jam-mulai');
            const inputSelesai = document.getElementById('perf-jam-selesai');
            if (!sesiProduksiAktif || !inputMulai || !inputMulai.value || !ctSesiLama) return;

            hentikanTimerLive();
            inputSelesai.value = jamSelesaiSesiLama;
            updateStatusBoxMC('menghitung');

            // Override sementara nilai perf-ct-pilihan & perf-part-label ke CT/nama part LAMA
            // supaya hitungPerformanceShift() menghitung & menyimpan sesi ini dengan angka
            // DAN label yang benar, terlepas dari dropdown/label yang sudah berubah ke part baru.
            const select = document.getElementById('perf-ct-pilihan');
            const labelPart = document.getElementById('perf-part-label');
            const innerHTMLSaatIni = select ? select.innerHTML : '';
            const nilaiSelectSaatIni = select ? select.value : null;
            const labelPartSaatIni = labelPart ? labelPart.innerText : null;

            if (select) {
                select.innerHTML = `<option value="${ctSesiLama}" selected>CT Lama</option>`;
                select.value = ctSesiLama;
            }
            if (labelPart) labelPart.innerText = namaPartSesiAktifSebelumnya;

            // Sama seperti matikanMesin(): modal keterangan penyimpangan HANYA muncul kalau
            // sesi lama ini kebetulan sesi terakhir shift. Ganti part di tengah shift (kasus
            // paling umum untuk fungsi ini) selalu skip modal.
            const sudahAkhirShift = apakahSudahAkhirShift(jamSelesaiSesiLama);

            // Reset kartu sebelum hitung sesi lama ditutup
            resetKartuMetrik();

            await hitungPerformanceShift(!sudahAkhirShift);

            // Kembalikan dropdown & label ke pilihan part baru
            if (select) {
                select.innerHTML = innerHTMLSaatIni;
                if (nilaiSelectSaatIni !== null) select.value = nilaiSelectSaatIni;
            }
            if (labelPart && labelPartSaatIni !== null) labelPart.innerText = labelPartSaatIni;
            namaPartSesiAktifSebelumnya = labelPartSaatIni;

            sesiProduksiAktif = false;
            inputMulai.value = '';
            inputSelesai.value = '';
            updateStatusBoxMC('idle');

            // Hapus catatan sesi aktif (part LAMA) di server SEBELUM mulaiSesiDenganPartTerpilih()
            // (dipanggil oleh caller setelah promise ini selesai) menyimpan sesi part BARU --
            // supaya tidak ada jeda di mana server masih menyimpan part lama sebagai "aktif".
            await hapusSesiAktifDiServer();
        }

        // ===== MODAL: KETERANGAN PENYIMPANGAN DURASI SHIFT =====
        // (B) Waktu Kerja di bawah standar minimal shift -> tampilkan modal ini, minta
        // operator isi alasan sebelum sesi bisa dihitung & disimpan (menggantikan prompt()
        // browser bawaan yang terlihat kurang profesional untuk dashboard produksi).
        let _resolveKeteranganDurasi = null; // simpan resolver Promise supaya bisa dipanggil dari tombol

        function bukaModalKeteranganDurasi(pesan) {
            document.getElementById('keterangan-durasi-pesan').innerText = pesan;
            const input = document.getElementById('keterangan-durasi-input');
            input.value = '';
            document.getElementById('keterangan-durasi-error').style.display = 'none';
            document.getElementById('modal-keterangan-durasi').style.display = 'flex';
            setTimeout(() => input.focus(), 50);

            // Return sebuah Promise yang di-resolve saat operator konfirmasi (dengan teks
            // keterangannya) atau membatalkan (dengan null) -- dipakai via await di pemanggil.
            return new Promise((resolve) => {
                _resolveKeteranganDurasi = resolve;
            });
        }

        function tutupModalKeteranganDurasi() {
            document.getElementById('modal-keterangan-durasi').style.display = 'none';
            if (_resolveKeteranganDurasi) {
                _resolveKeteranganDurasi(null); // dibatalkan -- pemanggil akan urungkan penyimpanan
                _resolveKeteranganDurasi = null;
            }
        }

        function tutupModalJikaDiLuarKeteranganDurasi(event) {
            if (event.target.id === 'modal-keterangan-durasi') tutupModalKeteranganDurasi();
        }

        function konfirmasiKeteranganDurasi() {
            const input = document.getElementById('keterangan-durasi-input');
            const teks = input.value.trim();
            if (!teks) {
                document.getElementById('keterangan-durasi-error').style.display = 'block';
                input.focus();
                return;
            }
            document.getElementById('modal-keterangan-durasi').style.display = 'none';
            if (_resolveKeteranganDurasi) {
                _resolveKeteranganDurasi(teks);
                _resolveKeteranganDurasi = null;
            }
        }

        // Simpan parameter request terakhir supaya tombol "Koreksi F/E/C" & "Simpan Produksi"
        // bisa hitung ulang tanpa perlu operator isi ulang jam/part -- cukup kirim ulang
        // dengan f_manual/e_manual/c_manual (dan final=1 khusus utk Simpan Produksi).
        let _perfTerakhirParams = null;

        async function hitungPerformanceShift(skipModal, fManual, eManual, cManual, final) {
            const line = currentLine;
            const tanggal = document.getElementById('perf-tanggal').value || currentTanggal;
            const jamMulai = document.getElementById('perf-jam-mulai').value;
            const jamSelesai = document.getElementById('perf-jam-selesai').value;
            const ct = document.getElementById('perf-ct-pilihan').value;
            const part = document.getElementById('perf-part-label').innerText;
            if (!jamMulai || !jamSelesai) {
                alert('Isi jam mulai dan jam selesai dulu.');
                return;
            }
            if (!ct) {
                alert('Pilih part di "Cek Cycle Time (CT) Part" dulu — nilai (C) Cycle Time Standar akan otomatis terisi dari daftar part.');
                return;
            }

            try {
                let url = `api/kalkulasi_performance.php?line=${encodeURIComponent(line)}&tanggal=${encodeURIComponent(tanggal)}`
                    + `&jam_mulai=${encodeURIComponent(jamMulai)}&jam_selesai=${encodeURIComponent(jamSelesai)}&ct=${encodeURIComponent(ct)}&part=${encodeURIComponent(part)}`
                    + (skipModal ? '&skip_keterangan=1' : '')
                    + (fManual !== undefined && fManual !== null && fManual !== '' ? `&f_manual=${encodeURIComponent(fManual)}` : '')
                    + (eManual !== undefined && eManual !== null && eManual !== '' ? `&e_manual=${encodeURIComponent(eManual)}` : '')
                    + (cManual !== undefined && cManual !== null && cManual !== '' ? `&c_manual=${encodeURIComponent(cManual)}` : '')
                    + (final ? '&final=1' : '');
                let res = await fetch(url, { cache: "no-store" });
                let data = await res.json();

                // Waktu kerja (B) di bawah standar minimal shift -- server minta keterangan
                // alasan penyimpangan dulu sebelum sesi ini bisa dihitung & disimpan.
                // (Kalau skipModal true, server sudah handle otomatis dan status ini tidak akan
                // muncul -- lihat skip_keterangan di kalkulasi_performance.php.)
                if (data.status === 'perlu_keterangan' || data.status === 'warning_durasi') {
                    const keterangan = await bukaModalKeteranganDurasi(data.message);
                    if (!keterangan) {
                        // Modal dibatalkan (klik X / klik di luar modal / Cancel) -- sesi belum disimpan.
                        return;
                    }
                    url += `&keterangan_durasi=${encodeURIComponent(keterangan)}`;
                    res = await fetch(url, { cache: "no-store" });
                    data = await res.json();
                }

                if (data.status !== 'success') {
                    alert(data.message || 'Gagal menghitung performance.');
                    return null;
                }

                // Simpan supaya "Koreksi F/E/C" & "Simpan Produksi" bisa dipanggil ulang nanti
                // dari card yang sudah jadi.
                _perfTerakhirParams = { skipModal };

                document.getElementById('perf-val-B').innerText = data.B;
                document.getElementById('perf-val-C').innerText = data.C_v2;
                document.getElementById('perf-val-C-manual-tag').style.display = data.C_v2_manual ? '' : 'none';
                document.getElementById('perf-val-F').innerText = data.F_v2;
                document.getElementById('perf-val-F-manual-tag').style.display = data.F_v2_manual ? '' : 'none';
                document.getElementById('perf-val-E').innerText = data.E_v2;
                document.getElementById('perf-val-E-manual-tag').style.display = data.E_v2_manual ? '' : 'none';
                document.getElementById('perf-val-D').innerText = data.D;
                document.getElementById('perf-val-A').innerText = data.A_v2;
                document.getElementById('perf-val-I').innerText = data.I;
                document.getElementById('perf-val-P').innerText = data.P;
                document.getElementById('perf-val-H').innerText = `${data.H_v2} (${data.H_v2_persen}%)`;
                document.getElementById('perf-val-J').innerText = data.J_v2;
                document.getElementById('perf-val-K').innerText = `${data.K_v2}`;
                document.getElementById('perf-val-L').innerText = data.tidak_ada_data
                    ? `Tidak ada data produksi di rentang ini`
                    : `${data.EFF_v2_persen}%`;

                // Prefill form koreksi dengan nilai yang sedang dipakai, biar operator lihat
                // nilai berjalan saat mau koreksi.
                document.getElementById('input-koreksi-f').value = data.F_v2_manual ? data.F_v2 : '';
                document.getElementById('input-koreksi-e').value = data.E_v2_manual ? data.E_v2 : '';
                document.getElementById('input-koreksi-c').value = data.C_v2_manual ? data.C_v2 : '';

                document.getElementById('perf-hasil-kosong').style.display = 'none';
                document.getElementById('perf-hasil-wrapper').style.display = '';

                return data;
            } catch (err) {
                alert('Gagal menghubungi server: ' + err.message);
                return null;
            }
        }

        // ===== KOREKSI MANUAL (F) Finished Good, (E) NG & (C) Cycle Time Standar =====
        function bukaFormKoreksiFE() {
            document.getElementById('form-koreksi-fe').style.display = '';
        }

        function batalKoreksiFE() {
            document.getElementById('form-koreksi-fe').style.display = 'none';
        }

        async function simpanKoreksiFE() {
            if (!_perfTerakhirParams) {
                alert('Belum ada hasil kalkulasi untuk dikoreksi.');
                return;
            }
            const fManual = document.getElementById('input-koreksi-f').value.trim();
            const eManual = document.getElementById('input-koreksi-e').value.trim();
            const cManual = document.getElementById('input-koreksi-c').value.trim();
            await hitungPerformanceShift(true, fManual, eManual, cManual); // skipModal=true, sesi sudah pernah lolos validasi durasi sebelumnya
            document.getElementById('form-koreksi-fe').style.display = 'none';
        }

        // ===== TOMBOL "SIMPAN PRODUKSI" =====
        // Hitung ulang sesi yang sedang tampil dan tandai baris riwayat baru sebagai final (disimpan_final=1),
        // kemudian MENGAKHIRI SESI PRODUKSI dan MERESET KESELURUHAN tampilan agar siap mulai sesi baru.
        async function simpanProduksiFinal() {
            const inputMulai = document.getElementById('perf-jam-mulai');
            const inputSelesai = document.getElementById('perf-jam-selesai');
            if (!sesiProduksiAktif && (!inputMulai || !inputMulai.value)) {
                alert('Tidak ada sesi produksi aktif yang perlu disimpan.');
                return;
            }

            const btnSimpan = document.getElementById('btn-simpan-produksi');
            if (btnSimpan) {
                btnSimpan.disabled = true;
                btnSimpan.innerText = 'Menyimpan...';
            }

            try {
                const fManual = document.getElementById('input-koreksi-f')?.value?.trim();
                const eManual = document.getElementById('input-koreksi-e')?.value?.trim();
                const cManual = document.getElementById('input-koreksi-c')?.value?.trim();
                const skipModal = true; // Selalu skip pengecekan waktu minimal saat simpan manual
                
                // Jika sesi masih berjalan (belum ada jam selesai)
                if (!inputSelesai.value) {
                    inputSelesai.value = jamSekarangHHMM();
                }

                hentikanTimerLive();

                // Hitung & simpan sesi ke database dengan tanda final=1
                const data = await hitungPerformanceShift(skipModal, fManual, eManual, cManual, true);
                if (!data || data.status !== 'success') {
                    return;
                }
                
                // Sesi produksi resmi berakhir
                sesiProduksiAktif = false;
                if (inputMulai) inputMulai.value = '';
                if (inputSelesai) inputSelesai.value = '';
                const inputTanggal = document.getElementById('perf-tanggal');
                if (inputTanggal) inputTanggal.value = '';

                // Hentikan timer dan kembalikan ke 00:00:00
                hentikanTimerLive();
                const elTimer = document.getElementById('mc-live-timer');
                if (elTimer) {
                    elTimer.innerText = '00:00:00';
                    elTimer.style.display = 'none';
                }

                // Reset kartu metrik ke '-' dan sembunyikan grid hasil
                resetKartuMetrik();

                // Kosongkan seluruh elemen pilihan part
                const searchInput = document.getElementById('ct-part-search');
                const selectPart = document.getElementById('ct-part-select');
                if (searchInput) searchInput.value = '';
                if (selectPart) selectPart.value = '';
                tampilkanDetailCt('');
                isiDropdownCtPerformance(null);
                localStorage.removeItem('selected_part_' + currentLine);
                await simpanPartTerpilih('');

                // Sembunyikan box nilai C hijau, tampilkan box peringatan pilih part
                const boxC = document.getElementById('mc-nilai-c-box');
                const boxKosong = document.getElementById('mc-nilai-c-kosong');
                if (boxC) boxC.style.display = 'none';
                if (boxKosong) boxKosong.style.display = 'flex';

                // Reset form koreksi
                const inF = document.getElementById('input-koreksi-f');
                const inE = document.getElementById('input-koreksi-e');
                const inC = document.getElementById('input-koreksi-c');
                if (inF) inF.value = '';
                if (inE) inE.value = '';
                if (inC) inC.value = '';
                const formKoreksi = document.getElementById('form-koreksi-fe');
                if (formKoreksi) formKoreksi.style.display = 'none';
                _perfTerakhirParams = null;

                // Hapus sesi aktif di server agar tidak dipulihkan lagi
                await hapusSesiAktifDiServer();

                // Kembalikan kotak status Machine Control ke kondisi idle
                updateStatusBoxMC('idle');

                // Tutup modal alasan stop jika sedang terbuka
                tutupModalAlasanStop();

                // Tampilkan notifikasi bahwa sesi berhasil disimpan final dan layar direset
                const statusEl = document.getElementById('simpan-produksi-status');
                if (statusEl) {
                    statusEl.textContent = `✅ Produksi tersimpan sebagai Sesi #${data.nomor_sesi} (final) pukul ${jamSekarangHHMM()}. Sesi selesai & siap mulai awal lagi.`;
                    statusEl.style.display = '';
                    statusEl.style.color = '#1a7d3a';
                }
            } finally {
                if (btnSimpan) {
                    btnSimpan.disabled = false;
                    btnSimpan.innerText = '💾 Simpan Produksi';
                }
            }
        }

        // ===== MODAL: MANAGE PART (CYCLE TIME & PCS PER SHEET) =====
        function bukaModalManagePart(filterPart = '') {
            const modal = document.getElementById('modal-manage-part');
            if (!modal) return;
            modal.style.display = 'flex';
            batalEditPart();
            const searchInput = document.getElementById('mp-search-input');
            if (searchInput) {
                searchInput.value = filterPart || '';
            }
            muatTabelManagePart(filterPart || '');
        }

        function tutupModalManagePart() {
            const modal = document.getElementById('modal-manage-part');
            if (modal) modal.style.display = 'none';
            batalEditPart();
            sembunyikanMpAlert();
        }

        function tutupModalJikaDiLuarManagePart(e) {
            if (e.target && e.target.id === 'modal-manage-part') {
                tutupModalManagePart();
            }
        }

        function bukaEditPartIni() {
            const select = document.getElementById('ct-part-select');
            const namaPart = select ? select.value : '';
            if (!namaPart) {
                alert('Pilih part terlebih dahulu untuk mengedit detailnya.');
                return;
            }
            bukaModalManagePart(namaPart);
            const item = (Array.isArray(DAFTAR_PART_CT) ? DAFTAR_PART_CT : []).find(p => p.part === namaPart);
            if (item) {
                mulaiEditPart(item.id || '', item.part, item.ct[0] || 0, item.pcs || 1);
            }
        }

        function updateCtDetikHint(val) {
            const hint = document.getElementById('mp-ct-hint');
            if (!hint) return;
            const menit = parseFloat(val);
            if (!isNaN(menit) && menit >= 0) {
                const detik = Math.round(menit * 60 * 100) / 100;
                hint.textContent = `= ${detik} detik / pcs`;
            } else {
                hint.textContent = `= 0 detik / pcs`;
            }
        }

        function fokusTambahPart() {
            batalEditPart();
            const inputKode = document.getElementById('mp-kode-part');
            if (inputKode) {
                inputKode.focus();
                inputKode.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }

        function mulaiEditPart(id, partName, ct, pcs) {
            document.getElementById('mp-part-id').value = id || partName;
            document.getElementById('mp-kode-part').value = partName;
            document.getElementById('mp-cycle-time').value = ct;
            updateCtDetikHint(ct);
            document.getElementById('mp-pcs-sheet').value = pcs || 1;

            const title = document.getElementById('mp-form-title');
            const subtitle = document.getElementById('mp-form-subtitle');
            const btnText = document.getElementById('btn-mp-text');
            const btnIcon = document.getElementById('btn-mp-icon');
            const btnBatal = document.getElementById('btn-mp-batal');

            if (title) title.textContent = `✏️ Edit Part: ${partName}`;
            if (subtitle) subtitle.textContent = `Ubah nilai Cycle Time Standar dan Pcs per Sheet untuk part ini.`;
            if (btnText) btnText.textContent = `Perbarui Part`;
            if (btnIcon) btnIcon.textContent = `💾`;
            if (btnBatal) btnBatal.style.display = 'inline-flex';

            const formCard = document.getElementById('mp-form-card');
            if (formCard) formCard.classList.add('mp-form-editing');

            const inputCt = document.getElementById('mp-cycle-time');
            if (inputCt) {
                inputCt.focus();
                inputCt.select();
            }
        }

        function batalEditPart() {
            const form = document.getElementById('form-manage-part');
            if (form) form.reset();
            document.getElementById('mp-part-id').value = '';
            updateCtDetikHint(0);

            const title = document.getElementById('mp-form-title');
            const subtitle = document.getElementById('mp-form-subtitle');
            const btnText = document.getElementById('btn-mp-text');
            const btnIcon = document.getElementById('btn-mp-icon');
            const btnBatal = document.getElementById('btn-mp-batal');

            if (title) title.textContent = `➕ Tambah Part Baru`;
            if (subtitle) subtitle.textContent = `Lengkapi kode part, cycle time standar, dan jumlah pcs per sheet`;
            if (btnText) btnText.textContent = `Simpan Part Baru`;
            if (btnIcon) btnIcon.textContent = `➕`;
            if (btnBatal) btnBatal.style.display = 'none';

            const formCard = document.getElementById('mp-form-card');
            if (formCard) formCard.classList.remove('mp-form-editing');
        }

        function tampilkanMpAlert(tipe, pesan) {
            const alertEl = document.getElementById('mp-alert');
            if (!alertEl) return;
            alertEl.className = `mp-alert mp-alert-${tipe}`;
            alertEl.innerHTML = `<span>${tipe === 'success' ? '✅' : '⚠️'}</span> <div>${pesan}</div>`;
            alertEl.style.display = 'flex';
            setTimeout(() => {
                if (alertEl) alertEl.style.display = 'none';
            }, 4000);
        }

        function sembunyikanMpAlert() {
            const alertEl = document.getElementById('mp-alert');
            if (alertEl) alertEl.style.display = 'none';
        }

        function muatTabelManagePart(filter = '') {
            const tbody = document.getElementById('mp-table-body');
            const badge = document.getElementById('mp-count-badge');
            if (!tbody) return;

            const listSemua = Array.isArray(DAFTAR_PART_CT) ? DAFTAR_PART_CT : [];
            const query = (filter || '').trim().toUpperCase();
            const hasil = query === ''
                ? listSemua
                : listSemua.filter(item => item.part.toUpperCase().includes(query));

            if (badge) badge.textContent = `${listSemua.length} Part`;

            if (hasil.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="5" style="text-align:center; padding:32px 16px; color:#9a9ab0;">
                            ${query ? `Part dengan kata kunci "<strong>${filter}</strong>" tidak ditemukan.` : 'Belum ada part terdaftar.'}
                        </td>
                    </tr>
                `;
                return;
            }

            tbody.innerHTML = hasil.map((item, idx) => {
                const ctVal = Array.isArray(item.ct) ? item.ct[0] : (item.ct || 0);
                const detik = Math.round(ctVal * 60 * 100) / 100;
                const pcs = parseInt(item.pcs, 10) || 1;
                const itemId = item.id || '';

                return `
                    <tr>
                        <td style="text-align:center; color:#9a9ab0; font-size:12px;">${idx + 1}</td>
                        <td>
                            <strong style="color:#0d3b8c; font-size:13.5px;">${item.part}</strong>
                        </td>
                        <td>
                            <span style="font-weight:700; color:#2b2b3a;">${ctVal}</span>
                            <span style="font-size:12px; color:#64748b;">menit (${detik}s)</span>
                        </td>
                        <td style="text-align:center;">
                            <span class="mp-badge-pcs">${pcs} pcs/sheet</span>
                        </td>
                        <td style="text-align:center;">
                            <div style="display:inline-flex; align-items:center; gap:6px;">
                                <button type="button" class="btn-mp-edit" onclick="mulaiEditPart('${itemId}', '${item.part}', ${ctVal}, ${pcs})" title="Edit part ${item.part}">
                                    ✏️
                                </button>
                                <button type="button" class="btn-mp-delete" onclick="hapusPartDariModal('${itemId}', '${item.part}')" title="Hapus part ${item.part}">
                                    🗑️
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');
        }

        function filterTabelManagePart(val) {
            muatTabelManagePart(val);
        }

        async function simpanPartDariModal(e) {
            e.preventDefault();
            const id = document.getElementById('mp-part-id').value;
            const kodePart = document.getElementById('mp-kode-part').value.trim().toUpperCase();
            const ct = parseFloat(document.getElementById('mp-cycle-time').value);
            const pcs = parseInt(document.getElementById('mp-pcs-sheet').value, 10);

            if (!kodePart) {
                tampilkanMpAlert('error', 'Kode / Nama Part wajib diisi.');
                return;
            }
            if (isNaN(ct) || ct < 0) {
                tampilkanMpAlert('error', 'Cycle Time Standar harus berupa angka >= 0.');
                return;
            }
            if (isNaN(pcs) || pcs < 1) {
                tampilkanMpAlert('error', 'Pcs per Sheet minimal 1.');
                return;
            }

            const btnSimpan = document.getElementById('btn-mp-simpan');
            if (btnSimpan) btnSimpan.disabled = true;

            try {
                const isEdit = Boolean(id);
                const url = isEdit ? `api/parts/${encodeURIComponent(id)}` : 'api/parts';
                const bodyData = {
                    part: kodePart,
                    cycle_time: ct,
                    pcs_per_sheet: pcs
                };
                if (isEdit) {
                    bodyData._method = 'PUT';
                }

                const res = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(bodyData)
                });

                const data = await res.json();
                if (data.status === 'success') {
                    tampilkanMpAlert('success', data.message || 'Part berhasil disimpan.');

                    // Update memory DAFTAR_PART_CT
                    if (Array.isArray(data.formatted)) {
                        DAFTAR_PART_CT = data.formatted;
                    } else {
                        const idx = DAFTAR_PART_CT.findIndex(p => p.part === (isEdit ? (document.getElementById('mp-part-id').value || kodePart) : kodePart));
                        if (idx >= 0) {
                            DAFTAR_PART_CT[idx] = { id: id || DAFTAR_PART_CT[idx].id, part: kodePart, ct: [ct], pcs: pcs };
                        } else {
                            DAFTAR_PART_CT.push({ id: data.part ? data.part.id : '', part: kodePart, ct: [ct], pcs: pcs });
                            DAFTAR_PART_CT.sort((a, b) => a.part.localeCompare(b.part));
                        }
                    }

                    // Sinkronkan dropdown & filter
                    const select = document.getElementById('ct-part-select');
                    const partTerpilihSebelumnya = select ? select.value : '';
                    isiDropdownPartCt(DAFTAR_PART_CT);

                    // Jika part yang diedit adalah yang sedang aktif dipilih, perbarui tampilan detailnya langsung
                    if (partTerpilihSebelumnya === kodePart || (isEdit && partTerpilihSebelumnya === id)) {
                        if (select) select.value = kodePart;
                        tampilkanDetailCt(kodePart, false);
                    } else if (partTerpilihSebelumnya && select) {
                        select.value = partTerpilihSebelumnya;
                    }

                    batalEditPart();
                    const searchInput = document.getElementById('mp-search-input');
                    muatTabelManagePart(searchInput ? searchInput.value : '');
                } else {
                    tampilkanMpAlert('error', data.message || 'Gagal menyimpan part.');
                }
            } catch (err) {
                tampilkanMpAlert('error', 'Terjadi kesalahan jaringan atau server.');
            } finally {
                if (btnSimpan) btnSimpan.disabled = false;
            }
        }

        async function hapusPartDariModal(id, partName) {
            if (!confirm(`Hapus part "${partName}" dari sistem?\nData part ini tidak akan muncul lagi di pilihan part.`)) {
                return;
            }

            try {
                const targetId = id || partName;
                const res = await fetch(`api/parts/${encodeURIComponent(targetId)}/delete`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ _method: 'DELETE' })
                });

                const data = await res.json();
                if (data.status === 'success') {
                    tampilkanMpAlert('success', data.message || `Part "${partName}" berhasil dihapus.`);

                    if (Array.isArray(data.formatted)) {
                        DAFTAR_PART_CT = data.formatted;
                    } else {
                        DAFTAR_PART_CT = DAFTAR_PART_CT.filter(p => p.part !== partName && p.id !== id);
                    }

                    const select = document.getElementById('ct-part-select');
                    const partTerpilih = select ? select.value : '';
                    isiDropdownPartCt(DAFTAR_PART_CT);

                    if (partTerpilih === partName) {
                        if (select) select.value = '';
                        tampilkanDetailCt('');
                    } else if (partTerpilih && select) {
                        select.value = partTerpilih;
                    }

                    const searchInput = document.getElementById('mp-search-input');
                    muatTabelManagePart(searchInput ? searchInput.value : '');
                } else {
                    tampilkanMpAlert('error', data.message || 'Gagal menghapus part.');
                }
            } catch (err) {
                tampilkanMpAlert('error', 'Terjadi kesalahan saat menghapus part.');
            }
        }

        document.addEventListener('click', (e) => {
            const wrapper = document.querySelector('.ct-search-wrapper');
            if (wrapper && !wrapper.contains(e.target)) {
                document.getElementById('ct-search-suggestions').style.display = 'none';
            }
        });

        isiDropdownPartCt(DAFTAR_PART_CT);

        const inputPerfTanggal = document.getElementById('perf-tanggal');
        if (inputPerfTanggal) inputPerfTanggal.value = currentTanggal;

        const elChartType = document.getElementById('chart-type-select');
        if (elChartType) elChartType.value = localStorage.getItem('chartType') || 'bar';

        if (currentView === 'kwh-control') {
            loadKwhControlData();
        }
    </script>