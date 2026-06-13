<?php
/**
 * Simulasi Harga Realtime — DSS Harga Popok Toko Virend
 */

$data = load_csv_data();
$forecast_daily = load_forecast_data();

// Prepare dates
$daily_dates = [];
$daily_data_by_product = [];

foreach ($forecast_daily as $row) {
    $d = $row['tanggal'];
    $p = $row['produk'];
    $q = (float)$row['qty_forecast'];
    
    if (!in_array($d, $daily_dates)) {
        $daily_dates[] = $d;
    }
    
    if (!isset($daily_data_by_product[$p])) {
        $daily_data_by_product[$p] = [];
    }
    $daily_data_by_product[$p][$d] = $q;
}
sort($daily_dates);
?>

<!-- JSON payload for JavaScript -->
<script>
    const productsData = <?php echo json_encode($data); ?>;
    const dailyForecast = <?php echo json_encode($daily_data_by_product); ?>;
    const dailyDates = <?php echo json_encode($daily_dates); ?>;
</script>

<!-- ============================================================
     INFO BOX
     ============================================================ -->
<div class="info-box info-primary mb-4 anim-fade-in-up">
    <i class="bi bi-sliders2 info-icon"></i>
    <div>
        <strong>Simulasi Penyesuaian Harga — Realtime</strong> —
        Gunakan simulator ini untuk memodelkan dampak perubahan harga terhadap kuantitas permintaan dan total pendapatan.
        Tersedia 2 mode: <strong>Mode 1 (Keseluruhan 90 Hari)</strong> dan <strong>Mode 2 (Per Tanggal Forecast)</strong>.
        Rumus: <code>Q<sub>baru</sub> = Q<sub>forecast</sub> × (P<sub>baru</sub> / P<sub>awal</sub>)<sup>E</sup></code>
        dan <code>Revenue = P<sub>baru</sub> × Q<sub>baru</sub></code>.
    </div>
</div>

<!-- ============================================================
     MAIN SIMULATOR LAYOUT
     ============================================================ -->
<div class="row g-4 mb-4">

    <!-- ── Left: Control Panel ─────────────────────────────────── -->
    <div class="col-12 col-lg-5 anim-fade-in-up">
        <div class="dashboard-card h-100">
            <div class="card-header">
                <h5><i class="bi bi-gear-fill text-primary me-2"></i>Parameter Simulasi</h5>
                <span class="text-xs text-slate-500">Pilih mode, produk, &amp; atur harga</span>
            </div>
            <div class="card-body">

                <!-- Mode Selector -->
                <div class="mb-4">
                    <label class="form-label fw-bold text-xs text-slate-500 mb-2">Mode Simulasi:</label>
                    <div class="btn-group w-100" role="group">
                        <input type="radio" class="btn-check" name="simMode" id="mode90" value="90" checked>
                        <label class="btn btn-sm btn-outline-primary" for="mode90"><i class="bi bi-calendar-range me-1"></i>Keseluruhan 90 Hari</label>

                        <input type="radio" class="btn-check" name="simMode" id="modeDate" value="date">
                        <label class="btn btn-sm btn-outline-primary" for="modeDate"><i class="bi bi-calendar-date me-1"></i>Per Tanggal Forecast</label>
                    </div>
                </div>

                <!-- Product Selector -->
                <div class="mb-3">
                    <label for="productSelect" class="form-label fw-semibold text-sm">Pilih Produk Popok:</label>
                    <select id="productSelect" class="form-select">
                        <?php foreach ($data as $i => $item): ?>
                        <option value="<?php echo $i; ?>"><?php echo htmlspecialchars($item['produk']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Date Selector (shown only for Mode 2) -->
                <div id="dateSelectContainer" class="mb-3 d-none">
                    <label for="dateSelect" class="form-label fw-semibold text-sm">Pilih Tanggal Forecast:</label>
                    <select id="dateSelect" class="form-select form-select-sm">
                        <?php foreach ($daily_dates as $date): ?>
                            <option value="<?php echo $date; ?>"><?php echo format_date_indonesian($date, true); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Baseline Info Panel -->
                <div class="p-3 rounded-3 mb-4" style="background:#f8fafc;border:1px solid #e2e8f0;">
                    <h6 class="fw-bold text-slate-700 mb-3"><i class="bi bi-clipboard-data me-1 text-primary"></i>Kondisi Baseline</h6>
                    <div class="row g-2">
                        <div class="col-6">
                            <span class="text-xs text-slate-500 d-block">Harga Saat Ini:</span>
                            <strong id="basePriceDisplay" class="text-slate-800">—</strong>
                        </div>
                        <div class="col-6">
                            <span id="forecastQtyLabel" class="text-xs text-slate-500 d-block">Forecast Qty (90 Hari):</span>
                            <strong id="baseQtyDisplay" class="text-slate-800">—</strong>
                        </div>
                        <div class="col-6">
                            <span class="text-xs text-slate-500 d-block">Koefisien Elastisitas (E):</span>
                            <strong id="elasticityDisplay" class="text-slate-800">—</strong>
                        </div>
                        <div class="col-6">
                            <span class="text-xs text-slate-500 d-block">Kategori:</span>
                            <span id="categoryBadge">—</span>
                        </div>
                        <div class="col-12 mt-1">
                            <span id="baselineRevLabel" class="text-xs text-slate-500 d-block">Revenue Baseline:</span>
                            <strong id="baselineRevDisplay" class="text-slate-700">—</strong>
                        </div>
                    </div>
                </div>

                <!-- Price Input -->
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label for="simulatedPriceInput" class="form-label fw-semibold text-sm mb-0">
                            Harga Simulasi Baru (Rp):
                        </label>
                        <span id="pricePercentChange" class="badge bg-secondary">0%</span>
                    </div>
                    <input type="number" id="simulatedPriceInput"
                           class="form-control fw-bold text-primary fs-5 mb-2"
                           step="500" placeholder="Masukkan harga...">
                    <input type="range" id="simulatedPriceRange" class="form-range" step="500">
                    <div class="d-flex justify-content-between text-xs text-slate-400 mt-1">
                        <span id="minRangeLabel">—</span>
                        <span id="maxRangeLabel">—</span>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-outline-secondary flex-fill" onclick="adjustPrice(-0.05)">
                        <i class="bi bi-dash-circle me-1"></i>−5%
                    </button>
                    <button class="btn btn-sm btn-outline-secondary flex-fill" onclick="resetPrice()">
                        <i class="bi bi-arrow-counterclockwise me-1"></i>Reset
                    </button>
                    <button class="btn btn-sm btn-outline-secondary flex-fill" onclick="adjustPrice(0.05)">
                        <i class="bi bi-plus-circle me-1"></i>+5%
                    </button>
                </div>

            </div>
        </div>
    </div>

    <!-- ── Right: Results ─────────────────────────────────────── -->
    <div class="col-12 col-lg-7 anim-fade-in-up anim-delay-1">
        <div class="dashboard-card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5><i class="bi bi-graph-up text-success me-2"></i>Hasil Simulasi Proyeksi</h5>
                <span class="badge bg-success-soft text-success">Real-time</span>
            </div>
            <div class="card-body">

                <!-- Output Cards -->
                <div class="row g-3 mb-4">
                    <div class="col-12 col-sm-6">
                        <div class="simulator-pane simulator-result-box">
                            <div class="simulator-result-title">Estimasi Kuantitas Baru</div>
                            <div class="d-flex align-items-baseline gap-1">
                                <span id="simulatedQty" class="simulator-result-val">—</span>
                                <span class="text-muted text-xs">Pcs</span>
                            </div>
                            <div class="mt-1 text-xs">
                                Perubahan volume:
                                <strong id="qtyChangePercent" class="text-slate-500">—</strong>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6">
                        <div id="revenueResultBox" class="simulator-pane simulator-result-box">
                            <div class="simulator-result-title">Estimasi Total Pendapatan</div>
                            <div id="simulatedRevenue" class="simulator-result-val">—</div>
                            <div class="mt-1 text-xs">
                                Selisih:
                                <strong id="revChangeVal" class="text-slate-500">—</strong>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Progress comparison -->
                <div class="mb-4">
                    <h6 class="fw-bold text-sm text-slate-700 mb-3">
                        <i class="bi bi-bar-chart-steps me-1 text-primary"></i>
                        Visualisasi Perbandingan Pendapatan
                    </h6>
                    <div class="mb-2">
                        <div class="d-flex justify-content-between text-xs mb-1">
                            <span class="text-slate-500">Pendapatan Baseline (Harga Saat Ini)</span>
                            <span id="baseRevenueDisplay" class="fw-semibold text-slate-600">—</span>
                        </div>
                        <div class="progress" style="height:12px;">
                            <div id="baseRevBar" class="progress-bar bg-secondary" style="width:50%"></div>
                        </div>
                    </div>
                    <div>
                        <div class="d-flex justify-content-between text-xs mb-1">
                            <span class="text-slate-500">Pendapatan Simulasi (Harga Baru)</span>
                            <span id="simRevDisplay" class="fw-semibold text-primary">—</span>
                        </div>
                        <div class="progress" style="height:12px;">
                            <div id="simRevBar" class="progress-bar bg-primary" style="width:50%"></div>
                        </div>
                    </div>
                </div>

                <!-- Live Chart -->
                <div>
                    <h6 class="fw-bold text-sm text-slate-700 mb-2">
                        <i class="bi bi-activity me-1 text-primary"></i>
                        Grafik Revenue — Baseline vs Simulasi
                    </h6>
                    <div class="chart-container" style="height:160px;">
                        <canvas id="simComparisonChart"></canvas>
                    </div>
                </div>

                <!-- Advisory -->
                <div id="advisoryBox" class="info-box info-primary mt-4">
                    <i class="bi bi-lightbulb-fill info-icon" id="advisoryIcon"></i>
                    <div>
                        <strong id="advisoryTitle">Panduan Kebijakan Harga</strong>
                        <p class="mb-0 text-xs mt-1" id="advisoryText">Pilih produk dan atur harga simulasi untuk mendapatkan rekomendasi kebijakan harga.</p>
                    </div>
                </div>

            </div>
        </div>
    </div>

</div>

<!-- ============================================================
     SIMULATION HISTORY TABLE
     ============================================================ -->
<div class="row g-4">
    <div class="col-12 anim-fade-in-up">
        <div class="dashboard-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5><i class="bi bi-clock-history text-warning me-2"></i>Riwayat Simulasi</h5>
                <button class="btn btn-sm btn-outline-secondary" onclick="clearHistory()">
                    <i class="bi bi-trash me-1"></i>Hapus Riwayat
                </button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table custom-table mb-0" id="historyTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Produk</th>
                                <th>Mode/Tanggal</th>
                                <th class="text-end">Harga Awal</th>
                                <th class="text-end">Harga Baru</th>
                                <th class="text-center">Perubahan</th>
                                <th class="text-end">Qty Baru</th>
                                <th class="text-end">Revenue Baru</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody id="historyBody">
                            <tr>
                                <td colspan="9" class="text-center text-slate-400 py-4">
                                    <i class="bi bi-inbox fs-4 d-block mb-1"></i>
                                    Belum ada riwayat simulasi. Gunakan simulator di atas untuk memulai.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================
     SIMULATION SCRIPT
     ============================================================ -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    /* ── DOM refs ─────────────────────────────────────────────── */
    const productSelect      = document.getElementById('productSelect');
    const dateSelect         = document.getElementById('dateSelect');
    const dateSelectContainer= document.getElementById('dateSelectContainer');
    const basePriceDisplay   = document.getElementById('basePriceDisplay');
    const baseQtyDisplay     = document.getElementById('baseQtyDisplay');
    const elasticityDisplay = document.getElementById('elasticityDisplay');
    const categoryBadge      = document.getElementById('categoryBadge');
    const baselineRevDisplay = document.getElementById('baselineRevDisplay');
    
    const simulatedPriceInput= document.getElementById('simulatedPriceInput');
    const simulatedPriceRange= document.getElementById('simulatedPriceRange');
    const pricePercentChange = document.getElementById('pricePercentChange');
    const minRangeLabel      = document.getElementById('minRangeLabel');
    const maxRangeLabel      = document.getElementById('maxRangeLabel');
    
    const simulatedQty       = document.getElementById('simulatedQty');
    const qtyChangePercent   = document.getElementById('qtyChangePercent');
    const simulatedRevenue   = document.getElementById('simulatedRevenue');
    const revChangeVal       = document.getElementById('revChangeVal');
    
    const baseRevenueDisplay = document.getElementById('baseRevenueDisplay');
    const simRevenueDisplay  = document.getElementById('simRevDisplay');
    const baseRevBar         = document.getElementById('baseRevBar');
    const simRevBar          = document.getElementById('simRevBar');
    
    const revenueResultBox   = document.getElementById('revenueResultBox');
    const advisoryBox        = document.getElementById('advisoryBox');
    const advisoryIcon       = document.getElementById('advisoryIcon');
    const advisoryTitle      = document.getElementById('advisoryTitle');
    const advisoryText       = document.getElementById('advisoryText');
    
    const historyBody        = document.getElementById('historyBody');
    
    const forecastQtyLabel   = document.getElementById('forecastQtyLabel');
    const baselineRevLabel   = document.getElementById('baselineRevLabel');

    let chartInstance = null;
    let historyCount = 0;

    // Handle Mode Switch
    const modeRadios = document.querySelectorAll('input[name="simMode"]');
    modeRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.value === 'date') {
                dateSelectContainer.classList.remove('d-none');
                forecastQtyLabel.textContent = "Forecast Qty (Hari Ini):";
                baselineRevLabel.textContent = "Revenue Baseline (Hari Ini):";
            } else {
                dateSelectContainer.classList.add('d-none');
                forecastQtyLabel.textContent = "Forecast Qty (90 Hari):";
                baselineRevLabel.textContent = "Revenue Baseline (90 Hari):";
            }
            updateBaseline();
            calculateSimulation();
        });
    });

    /* ── Helper: Formatters ────────────────────────────────────── */
    function formatRupiah(val) {
        return 'Rp ' + Math.round(val).toLocaleString('id-ID');
    }
    function formatNumber(val, decimals = 0) {
        return val.toLocaleString('id-ID', { minimumFractionDigits: decimals, maximumFractionDigits: decimals });
    }

    /* ── Update Baseline Information ──────────────────────────── */
    function updateBaseline() {
        const pIdx = parseInt(productSelect.value);
        const p = productsData[pIdx];
        if (!p) return;

        basePriceDisplay.textContent = formatRupiah(p.harga_saat_ini);
        elasticityDisplay.textContent = p.elastisitas.toFixed(4);

        // Category Badge
        categoryBadge.className = 'badge';
        if (p.kategori.toLowerCase() === 'elastis') {
            categoryBadge.classList.add('bg-danger-soft', 'text-danger');
            categoryBadge.textContent = 'Elastis';
        } else {
            categoryBadge.classList.add('bg-success-soft', 'text-success');
            categoryBadge.textContent = 'Inelastis';
        }

        // Qty Forecast based on mode
        const mode = document.querySelector('input[name="simMode"]:checked').value;
        let qForecast = 0;
        if (mode === '90') {
            qForecast = parseFloat(p.qty_forecast);
        } else {
            const selDate = dateSelect.value;
            qForecast = parseFloat(dailyForecast[p.produk][selDate] || 0.0);
        }
        baseQtyDisplay.textContent = formatNumber(qForecast, 1) + ' Pcs';

        // Baseline Revenue
        const baseRev = p.harga_saat_ini * qForecast;
        baselineRevDisplay.textContent = formatRupiah(baseRev);

        // Price Slider boundaries (±50% range)
        const minPrice = Math.round(p.harga_saat_ini * 0.5);
        const maxPrice = Math.round(p.harga_saat_ini * 1.5);
        
        simulatedPriceRange.min = minPrice;
        simulatedPriceRange.max = maxPrice;
        minRangeLabel.textContent = formatRupiah(minPrice);
        maxRangeLabel.textContent = formatRupiah(maxPrice);

        // If the price input is blank or reset, set to current baseline price
        if (!simulatedPriceInput.value || simulatedPriceInput.dataset.prodIndex !== productSelect.value) {
            simulatedPriceInput.value = p.harga_saat_ini;
            simulatedPriceRange.value = p.harga_saat_ini;
            simulatedPriceInput.dataset.prodIndex = productSelect.value;
        }
    }

    /* ── Calculate Simulation Outcomes ───────────────────────── */
    function calculateSimulation() {
        const pIdx = parseInt(productSelect.value);
        const p = productsData[pIdx];
        if (!p) return;

        const pAwal = parseFloat(p.harga_saat_ini);
        const pBaru = parseFloat(simulatedPriceInput.value) || pAwal;
        const E = parseFloat(p.elastisitas);

        // Get forecast based on mode
        const mode = document.querySelector('input[name="simMode"]:checked').value;
        let qForecast = 0;
        let modeLabel = "90 Hari";
        if (mode === '90') {
            qForecast = parseFloat(p.qty_forecast);
        } else {
            const selDate = dateSelect.value;
            qForecast = parseFloat(dailyForecast[p.produk][selDate] || 0.0);
            modeLabel = selDate;
        }

        // Q_baru = Q_forecast * (P_baru / P_awal)^E
        const qBaru = qForecast * Math.pow((pBaru / pAwal), E);
        // Revenue = P_baru * Q_baru
        const rBaru = pBaru * qBaru;
        const rAwal = pAwal * qForecast;

        const priceDiffPct = ((pBaru - pAwal) / pAwal) * 100;
        const qtyDiffPct = qForecast > 0 ? ((qBaru - qForecast) / qForecast) * 100 : 0;
        const revDiff = rBaru - rAwal;
        const revDiffPct = rAwal > 0 ? (revDiff / rAwal) * 100 : 0;

        // Render input feedback
        pricePercentChange.textContent = (priceDiffPct >= 0 ? '+' : '') + priceDiffPct.toFixed(1) + '%';
        pricePercentChange.className = 'badge ' + (priceDiffPct > 0 ? 'bg-primary' : (priceDiffPct < 0 ? 'bg-danger' : 'bg-secondary'));

        // Render Qty Result
        simulatedQty.textContent = formatNumber(qBaru, 1);
        qtyChangePercent.textContent = (qtyDiffPct >= 0 ? '+' : '') + qtyDiffPct.toFixed(1) + '%';
        qtyChangePercent.className = 'fw-bold ' + (qtyDiffPct >= 0 ? 'text-success' : 'text-danger');

        // Render Revenue Result
        simulatedRevenue.textContent = formatRupiah(rBaru);
        revChangeVal.textContent = (revDiff >= 0 ? '+' : '') + formatRupiah(revDiff) + ' (' + (revDiffPct >= 0 ? '+' : '') + revDiffPct.toFixed(1) + '%)';
        revChangeVal.className = 'fw-bold ' + (revDiff >= 0 ? 'text-success' : 'text-danger');

        // Result Box Styling
        revenueResultBox.className = 'simulator-pane simulator-result-box ';
        if (revDiff > 0) {
            revenueResultBox.classList.add('border-start-success');
        } else if (revDiff < 0) {
            revenueResultBox.classList.add('border-start-danger');
        } else {
            revenueResultBox.classList.add('border-start-slate');
        }

        // Update progress bars
        baseRevenueDisplay.textContent = formatRupiah(rAwal);
        simRevenueDisplay.textContent = formatRupiah(rBaru);

        const maxRev = Math.max(rAwal, rBaru);
        const baseWidth = maxRev > 0 ? (rAwal / maxRev) * 100 : 0;
        const simWidth = maxRev > 0 ? (rBaru / maxRev) * 100 : 0;

        baseRevBar.style.width = baseWidth + '%';
        simRevBar.style.width = simWidth + '%';

        // Update Live Chart
        updateChart(rAwal, rBaru);

        // Update Advisory
        updateAdvisory(p.kategori, priceDiffPct, revDiff);
    }

    /* ── Render Advisory Policy Recommendations ───────────────── */
    function updateAdvisory(kategori, priceDiffPct, revDiff) {
        advisoryBox.className = 'info-box ';
        
        if (priceDiffPct === 0) {
            advisoryBox.classList.add('info-primary');
            advisoryIcon.className = 'bi bi-info-circle-fill info-icon';
            advisoryTitle.textContent = 'Parameter Baseline';
            advisoryText.innerHTML = 'Ini adalah kondisi harga penjualan sekarang. Ubah slider atau isi kolom harga simulasi untuk memodelkan skenario kenaikan/penurunan harga.';
            return;
        }

        const isElastis = kategori.toLowerCase() === 'elastis';

        if (revDiff > 0) {
            advisoryBox.classList.add('info-success');
            advisoryIcon.className = 'bi bi-check-circle-fill info-icon text-success';
            advisoryTitle.textContent = 'Skenario Menguntungkan (Rekomendasi)';
            
            if (isElastis && priceDiffPct < 0) {
                advisoryText.innerHTML = `Produk bersifat <strong>Elastis</strong>. Penurunan harga sebesar <strong>${Math.abs(priceDiffPct).toFixed(1)}%</strong> memicu lonjakan permintaan yang besar sehingga <strong>total pendapatan diproyeksikan meningkat</strong>. Kebijakan ini disarankan.`;
            } else {
                advisoryText.innerHTML = `Produk bersifat <strong>Inelastis</strong>. Kenaikan harga sebesar <strong>${priceDiffPct.toFixed(1)}%</strong> melampaui penurunan kuantitas penjualan, sehingga <strong>total pendapatan tetap meningkat</strong>. Skema ini aman untuk dieksekusi.`;
            }
        } else {
            advisoryBox.classList.add('info-danger');
            advisoryIcon.className = 'bi bi-exclamation-triangle-fill info-icon text-danger';
            advisoryTitle.textContent = 'Skenario Berisiko (Hindari)';
            
            if (isElastis && priceDiffPct > 0) {
                advisoryText.innerHTML = `Produk bersifat <strong>Elastis</strong>. Kenaikan harga sebesar <strong>${priceDiffPct.toFixed(1)}%</strong> menyebabkan penurunan drastis pada volume penjualan, sehingga <strong>pendapatan turun</strong>. Disarankan untuk membatalkan skenario ini.`;
            } else {
                advisoryText.innerHTML = `Penurunan harga sebesar <strong>${Math.abs(priceDiffPct).toFixed(1)}%</strong> pada produk <strong>Inelastis</strong> tidak menaikkan volume penjualan secara proporsional. <strong>Pendapatan akan berkurang</strong>. Sebaiknya hindari skema ini.`;
            }
        }
    }

    /* ── Render & Update Chart.js Comparison ──────────────────── */
    function updateChart(rAwal, rBaru) {
        const ctx = document.getElementById('simComparisonChart').getContext('2d');
        const data = [rAwal, rBaru];

        if (chartInstance) {
            chartInstance.data.datasets[0].data = data;
            chartInstance.update();
            return;
        }

        chartInstance = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Baseline (Saat Ini)', 'Simulasi (Baru)'],
                datasets: [{
                    label: 'Pendapatan (Rp)',
                    data: data,
                    backgroundColor: ['#64748b', '#4f46e5'],
                    borderRadius: 6,
                    borderWidth: 0,
                    barThickness: 50
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: ctx => formatRupiah(ctx.raw) } }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { callback: v => formatRupiah(v) }
                    },
                    y: { grid: { display: false } }
                }
            }
        });
    }

    /* ── Global triggers bound to UI ───────────────────────────── */
    window.adjustPrice = function(pct) {
        const pIdx = parseInt(productSelect.value);
        const p = productsData[pIdx];
        if (!p) return;
        
        let newPrice = Math.round(parseFloat(simulatedPriceInput.value) * (1 + pct));
        
        // Clamp bounds
        const minPrice = Math.round(p.harga_saat_ini * 0.5);
        const maxPrice = Math.round(p.harga_saat_ini * 1.5);
        newPrice = Math.max(minPrice, Math.min(maxPrice, newPrice));

        simulatedPriceInput.value = newPrice;
        simulatedPriceRange.value = newPrice;
        calculateSimulation();
        addToHistory();
    };

    window.resetPrice = function() {
        const pIdx = parseInt(productSelect.value);
        const p = productsData[pIdx];
        if (!p) return;

        simulatedPriceInput.value = p.harga_saat_ini;
        simulatedPriceRange.value = p.harga_saat_ini;
        calculateSimulation();
    };

    // Synchronization Input and Range Slider
    simulatedPriceInput.addEventListener('input', function() {
        simulatedPriceRange.value = this.value;
        calculateSimulation();
    });
    
    simulatedPriceRange.addEventListener('input', function() {
        simulatedPriceInput.value = this.value;
        calculateSimulation();
    });

    // Handle change of inputs to record history
    simulatedPriceInput.addEventListener('change', addToHistory);
    simulatedPriceRange.addEventListener('change', addToHistory);

    productSelect.addEventListener('change', function() {
        updateBaseline();
        calculateSimulation();
    });

    dateSelect.addEventListener('change', function() {
        updateBaseline();
        calculateSimulation();
    });

    /* ── History Manager ────────────────────────────────────────── */
    function addToHistory() {
        const pIdx = parseInt(productSelect.value);
        const p = productsData[pIdx];
        if (!p) return;

        const pAwal = parseFloat(p.harga_saat_ini);
        const pBaru = parseFloat(simulatedPriceInput.value) || pAwal;
        const E = parseFloat(p.elastisitas);
        
        if (pBaru === pAwal) return; // ignore baseline changes

        // Get forecast based on mode
        const mode = document.querySelector('input[name="simMode"]:checked').value;
        let qForecast = 0;
        let modeLabel = "90 Hari";
        if (mode === '90') {
            qForecast = parseFloat(p.qty_forecast);
        } else {
            const selDate = dateSelect.value;
            qForecast = parseFloat(dailyForecast[p.produk][selDate] || 0.0);
            modeLabel = selDate;
        }

        const qBaru = qForecast * Math.pow((pBaru / pAwal), E);
        const rBaru = pBaru * qBaru;
        const rAwal = pAwal * qForecast;
        const revDiff = rBaru - rAwal;
        const priceDiffPct = ((pBaru - pAwal) / pAwal) * 100;

        if (historyCount === 0) {
            historyBody.innerHTML = ""; // Clear empty state
        }
        
        historyCount++;
        const statusBadge = revDiff >= 0 
            ? '<span class="badge bg-success-soft text-success"><i class="bi bi-arrow-up-right me-1"></i>Meningkat</span>'
            : '<span class="badge bg-danger-soft text-danger"><i class="bi bi-arrow-down-left me-1"></i>Menurun</span>';

        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${historyCount}</td>
            <td class="fw-semibold">${htmlspecialchars(p.produk)}</td>
            <td class="text-xs text-slate-500">${modeLabel}</td>
            <td class="text-end">${formatRupiah(pAwal)}</td>
            <td class="text-end fw-bold text-primary">${formatRupiah(pBaru)}</td>
            <td class="text-center fw-medium ${priceDiffPct >= 0 ? 'text-primary' : 'text-danger'}">
                ${priceDiffPct >= 0 ? '+' : ''}${priceDiffPct.toFixed(1)}%
            </td>
            <td class="text-end">${formatNumber(qBaru, 1)} pcs</td>
            <td class="text-end fw-bold ${revDiff >= 0 ? 'text-success' : 'text-danger'}">${formatRupiah(rBaru)}</td>
            <td class="text-center">${statusBadge}</td>
        `;
        
        // Insert at the top of history table
        historyBody.insertBefore(tr, historyBody.firstChild);
    }

    window.clearHistory = function() {
        historyCount = 0;
        historyBody.innerHTML = `
            <tr>
                <td colspan="9" class="text-center text-slate-400 py-4">
                    <i class="bi bi-inbox fs-4 d-block mb-1"></i>
                    Belum ada riwayat simulasi. Gunakan simulator di atas untuk memulai.
                </td>
            </tr>
        `;
    };

    function htmlspecialchars(str) {
        return str.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
    }

    /* ── Initializations ───────────────────────────────────────── */
    updateBaseline();
    calculateSimulation();
});
</script>
