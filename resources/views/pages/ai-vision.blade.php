<div id="view-ai-vision" style="{{ $view_aktif !== 'ai-vision' ? 'display:none;' : '' }}">
    <div class="panel">
        <div class="panel-header-row">
            <div class="panel-title">Pengaturan ESP32-CAM & AI Vision</div>
        </div>
        <div class="settings-row-desc" style="margin-bottom:16px;">
            Halaman ini digunakan untuk melihat stream langsung dari ESP32-CAM dan menjalankan AI (Teachable Machine) untuk mendeteksi barang produksi.
        </div>

        <div style="background:#f8fafc; border:1.5px solid #dbe4f3; border-radius:10px; padding:16px; margin-bottom:20px;">
            <h3 style="margin-top:0; margin-bottom:10px; font-size:15px; color:#333;">Konfigurasi Master (ESP32)</h3>
            <p style="font-size:13px; color:#555; margin-bottom:12px;">
                Untuk mengatur jaringan (WiFi), IP Address, dan target stream dari kamera ESP32-CAM, silakan buka dashboard master-painting.
            </p>
            <!-- Tombol ke Master Painting dengan /cctv -->
            <a href="http://master-painting.test/cctv" target="_blank" class="btn-simpan-produksi" style="text-decoration:none; display:inline-block; padding:10px 16px; background:#0d3b8c; color:white; border-radius:6px; font-weight:600; font-size:13px;">
                ⚙️ Buka Konfigurasi Master Painting &rarr;
            </a>
        </div>

        <div style="display:flex; gap:20px; flex-wrap:wrap;">
            <!-- Kolom Stream Video -->
            <div style="flex:1; min-width:300px; background:#000; border-radius:10px; overflow:hidden; position:relative; min-height:350px; display:flex; align-items:center; justify-content:center; flex-direction:column;">
                <span id="ai-video-placeholder" style="color:#666; font-size:14px; position:absolute; z-index:1;">[ Menunggu Stream Video dari Kamera... ]</span>
                <img id="ai-mjpeg-stream" crossorigin="anonymous" style="display:none; position:relative; z-index:2; width:100%; height:auto;" alt="ESP32-CAM Stream">
            </div>
            
            <!-- Kolom Pengaturan Stream -->
            <div style="flex:1; min-width:300px;">
                <label style="display:block; font-size:13px; font-weight:600; color:#333; margin-bottom:6px;">Teachable Machine Model URL</label>
                <div style="display:flex; gap:10px; margin-bottom:16px;">
                    <input type="text" id="tm-url-input" class="filter-select" style="flex:1; padding:10px;" placeholder="https://teachablemachine.withgoogle.com/models/xxxxxx/">
                    <button type="button" class="btn-simpan-produksi" id="btn-load-model" style="padding:10px 16px; min-width:120px;">Muat Model</button>
                </div>
                
                <label style="display:block; font-size:13px; font-weight:600; color:#333; margin-bottom:6px;">URL Stream ESP32-CAM</label>
                <div style="display:flex; gap:10px; margin-bottom:12px;">
                    <input type="text" id="stream-url-input" class="filter-select" style="flex:1; padding:10px;" placeholder="http://192.168.1.100:81/stream">
                    <button type="button" class="btn-simpan-produksi" id="btn-start-stream" style="padding:10px 16px; min-width:120px;">Buka Stream</button>
                </div>
                
                <button type="button" class="btn-simpan-produksi" id="btn-start-ai" style="padding:10px 16px; width:100%; text-align:center; margin-bottom:20px; background:#16a34a; border:none;" disabled>&#9654; Mulai Prediksi AI</button>

                <div style="background:#eef3ff; border:1.5px solid #b3c6f7; border-radius:10px; padding:14px;">
                    <h4 style="margin:0 0 10px 0; font-size:14px; color:#0d3b8c;">Status Sistem</h4>
                    <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
                        <span style="font-size:13px; color:#555;">Status Model:</span>
                        <span id="ai-model-status" style="font-size:13px; font-weight:bold; color:#b45309;">Belum Dimuat</span>
                    </div>
                    <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
                        <span style="font-size:13px; color:#555;">Koneksi Kamera:</span>
                        <span id="ai-cam-status" style="font-size:13px; font-weight:bold; color:#b45309;">Terputus</span>
                    </div>
                    <div style="margin-top:16px; padding-top:12px; border-top:1px dashed #b3c6f7;">
                        <span style="font-size:14px; color:#333; font-weight:600; display:block; margin-bottom:8px;">Hasil Deteksi:</span>
                        <div id="ai-predictions-container" style="display:flex; flex-direction:column; gap:6px;">
                            <!-- Prediksi akan muncul di sini -->
                            <div style="font-size:13px; color:#777; font-style:italic;">Menunggu prediksi berjalan...</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    let tmModel, maxPredictions;
    let isPredicting = false;
    let predictionLoopId = null;
    let lastInferenceTime = 0;

    const btnLoadModel = document.getElementById('btn-load-model');
    const btnStartStream = document.getElementById('btn-start-stream');
    const btnStartAi = document.getElementById('btn-start-ai');
    
    const streamImg = document.getElementById('ai-mjpeg-stream');
    const streamPlaceholder = document.getElementById('ai-video-placeholder');
    
    const statusModel = document.getElementById('ai-model-status');
    const statusCam = document.getElementById('ai-cam-status');
    const containerPredictions = document.getElementById('ai-predictions-container');

    // Load URL dari localStorage (biar gak hilang pas direfresh)
    const savedTmUrl = localStorage.getItem('ai_tm_url');
    const savedStreamUrl = localStorage.getItem('ai_stream_url');
    if (savedTmUrl) document.getElementById('tm-url-input').value = savedTmUrl;
    if (savedStreamUrl) document.getElementById('stream-url-input').value = savedStreamUrl;

    // --- FITUR AUTO SYNC DARI MASTER-PAINTING ---
    let lastAutoConnectedUrl = null;
    setInterval(async () => {
        try {
            const res = await fetch('/api/get-cam-ip');
            const data = await res.json();
            if (data.ip) {
                const targetUrl = 'http://' + data.ip + ':81/stream';
                const currentInput = document.getElementById('stream-url-input').value.trim();
                const camStatus = document.getElementById('ai-cam-status').innerText;
                
                // Update text box jika berbeda
                if (currentInput !== targetUrl) {
                    document.getElementById('stream-url-input').value = targetUrl;
                }

                // Klik tombol stream JIKA belum tersambung dan belum pernah coba connect ke URL ini secara otomatis
                if (camStatus !== 'Tersambung' && lastAutoConnectedUrl !== targetUrl) {
                    lastAutoConnectedUrl = targetUrl;
                    btnStartStream.click();
                }
            }
        } catch (e) {
            // Abaikan jika error fetch
        }
    }, 3000); // Cek setiap 3 detik
    // ---------------------------------------------

    btnLoadModel.addEventListener('click', async () => {
        let url = document.getElementById('tm-url-input').value.trim();
        if (!url) {
            alert('Masukkan URL Teachable Machine terlebih dahulu!');
            return;
        }
        if (!url.endsWith('/')) url += '/';
        
        localStorage.setItem('ai_tm_url', url);
        
        statusModel.innerText = 'Memuat Model...';
        statusModel.style.color = '#b45309';
        btnLoadModel.disabled = true;

        try {
            const modelURL = url + "model.json";
            const metadataURL = url + "metadata.json";
            
            // Menggunakan fungsi dari library tmImage
            tmModel = await tmImage.load(modelURL, metadataURL);
            maxPredictions = tmModel.getTotalClasses();
            
            statusModel.innerText = 'Model Siap (' + maxPredictions + ' Kelas)';
            statusModel.style.color = '#16a34a';
            
            checkEnableAiButton();
        } catch (e) {
            console.error('Error memuat model:', e);
            statusModel.innerText = 'Gagal memuat model!';
            statusModel.style.color = '#dc2626';
            alert('Gagal memuat model. Pastikan URL benar dan dapat diakses (publik).');
        } finally {
            btnLoadModel.disabled = false;
        }
    });

    btnStartStream.addEventListener('click', () => {
        const streamUrl = document.getElementById('stream-url-input').value.trim();
        if (!streamUrl) {
            alert('Masukkan URL Stream ESP32-CAM terlebih dahulu!');
            return;
        }
        
        localStorage.setItem('ai_stream_url', streamUrl);
        
        // Coba koneksi ke stream (menggunakan Image caching buster kalau perlu, tapi untuk MJPEG biasa gak perlu)
        streamImg.src = streamUrl;
        
        streamImg.onload = () => {
            streamPlaceholder.style.display = 'none';
            streamImg.style.display = 'block';
            statusCam.innerText = 'Tersambung';
            statusCam.style.color = '#16a34a';
            checkEnableAiButton();
        };

        streamImg.onerror = () => {
            statusCam.innerText = 'Error / Terputus';
            statusCam.style.color = '#dc2626';
            streamImg.style.display = 'none';
            streamPlaceholder.style.display = 'block';
            streamPlaceholder.innerText = '[ Gagal menyambung ke Stream ]';
            isPredicting = false;
            updateAiButtonState();
        };
    });

    btnStartAi.addEventListener('click', () => {
        if (!isPredicting) {
            isPredicting = true;
            updateAiButtonState();
            predictLoop();
        } else {
            isPredicting = false;
            if (predictionLoopId) cancelAnimationFrame(predictionLoopId);
            updateAiButtonState();
        }
    });

    function checkEnableAiButton() {
        if (tmModel && statusCam.innerText === 'Tersambung') {
            btnStartAi.disabled = false;
        } else {
            btnStartAi.disabled = true;
            if (isPredicting) {
                isPredicting = false;
                updateAiButtonState();
            }
        }
    }

    function updateAiButtonState() {
        if (isPredicting) {
            btnStartAi.innerHTML = '&#9209; Hentikan Prediksi';
            btnStartAi.style.background = '#dc2626';
        } else {
            btnStartAi.innerHTML = '&#9654; Mulai Prediksi AI';
            btnStartAi.style.background = '#16a34a';
        }
    }

    async function predictLoop() {
        if (!isPredicting) return;
        
        // Batasi frekuensi prediksi biar browser gak ngelag (misal maksimal 10 fps)
        const now = Date.now();
        if (now - lastInferenceTime > 100) {
            lastInferenceTime = now;
            await predict();
        }
        
        predictionLoopId = requestAnimationFrame(predictLoop);
    }

    async function predict() {
        if (!tmModel || streamImg.style.display === 'none') return;

        try {
            // Prediksi menggunakan gambar stream saat ini
            const prediction = await tmModel.predict(streamImg);
            
            // Urutkan berdasarkan probabilitas tertinggi
            prediction.sort((a, b) => b.probability - a.probability);
            
            // Render hasil ke UI
            containerPredictions.innerHTML = '';
            for (let i = 0; i < maxPredictions; i++) {
                const perc = Math.round(prediction[i].probability * 100);
                
                const itemDiv = document.createElement('div');
                itemDiv.style.display = 'flex';
                itemDiv.style.alignItems = 'center';
                itemDiv.style.justifyContent = 'space-between';
                itemDiv.style.padding = '4px 8px';
                itemDiv.style.background = i === 0 && perc > 50 ? '#dcfce7' : '#fff';
                itemDiv.style.borderRadius = '4px';
                itemDiv.style.border = '1px solid #e5e7eb';
                
                const labelSpan = document.createElement('span');
                labelSpan.style.fontWeight = i === 0 && perc > 50 ? 'bold' : 'normal';
                labelSpan.style.fontSize = '14px';
                labelSpan.innerText = prediction[i].className;
                
                const probSpan = document.createElement('span');
                probSpan.style.fontSize = '13px';
                probSpan.style.color = '#555';
                probSpan.style.fontWeight = 'bold';
                probSpan.innerText = perc + '%';
                
                itemDiv.appendChild(labelSpan);
                itemDiv.appendChild(probSpan);
                
                // Tambahkan progress bar kecil
                const barContainer = document.createElement('div');
                barContainer.style.width = '100%';
                barContainer.style.height = '4px';
                barContainer.style.background = '#e5e7eb';
                barContainer.style.borderRadius = '2px';
                barContainer.style.marginTop = '2px';
                barContainer.style.overflow = 'hidden';
                
                const barFill = document.createElement('div');
                barFill.style.height = '100%';
                barFill.style.width = perc + '%';
                barFill.style.background = i === 0 && perc > 50 ? '#16a34a' : '#9ca3af';
                
                barContainer.appendChild(barFill);
                
                const wrapper = document.createElement('div');
                wrapper.appendChild(itemDiv);
                wrapper.appendChild(barContainer);
                wrapper.style.marginBottom = '6px';
                
                containerPredictions.appendChild(wrapper);
            }
        } catch (e) {
            console.error("Gagal melakukan prediksi:", e);
            // Biasaya CORS error kalo ESP32 gak kirim header Access-Control-Allow-Origin
            if (e.message && e.message.includes("Tainted")) {
                isPredicting = false;
                updateAiButtonState();
                alert("Gagal memproses gambar karena masalah CORS pada ESP32-CAM. Pastikan ESP32 mengirimkan header 'Access-Control-Allow-Origin: *'");
            }
        }
    }
</script>
