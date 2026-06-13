<?php
/**
 * Optimasi Harga & Pendapatan — DSS Harga Popok Toko Virend
 */

$data             = load_csv_data();
$stats            = get_summary_stats($data);
$total_baseline   = get_total_revenue_baseline($data);
$revenue_gain     = $stats['total_revenue_maksimum'] - $total_baseline;
$gain_pct         = $total_baseline > 0 ? ($revenue_gain / $total_baseline) * 100 : 0;

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

$product_labels   = [];
$product_short    = [];
$current_prices   = [];
$optimal_prices   = [];
$revenues_now     = [];
$revenues_opt     = [];

foreach ($data as $item) {
    $parts = explode(' ', $item['produk']);
    $short = implode(' ', array_slice($parts, 0, 3));
    $product_labels[] = $item['produk'];
    $product_short[]  = $short;
    $current_prices[] = $item['harga_saat_ini'];
    $optimal_prices[] = $item['harga_optimal'];
    $revenues_now[]   = $item['harga_saat_ini'] * $item['qty_forecast'];
    $revenues_opt[]   = $item['revenue_maksimum'];
}
?>

<!-- JSON Payload for JS -->
<script>
    const productsData = <?php echo json_encode($data); ?>;
    const dailyForecast = <?php echo json_encode($daily_data_by_product); ?>;
    const dailyDates = <?php echo json_encode($daily_dates); ?>;
</script>

<!-- ============================================================
     INFO BOX
     ============================================================ -->
<div class="info-box info-primary mb-4 anim-fade-in-up">
    <i class="bi bi-stars info-icon"></i>
    <div>
        <strong>Optimasi Harga &amp; Pendapatan</strong> —
        Halaman ini menyajikan penentuan harga optimal untuk memaksimalkan total pendapatan <code>R(P) = P × Q(P)</code>.
        Pilih tab di bawah untuk melihat optimasi keseluruhan 90 hari atau rincian per tanggal secara harian.
    </div>
</div>

<!-- ============================================================
     TABS SELECTOR
     ============================================================ -->
<ul class="nav nav-tabs custom-tabs mb-4 anim-fade-in-up" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="summary-tab" data-bs-toggle="tab" data-bs-target="#summary-pane" type="button" role="tab" aria-controls="summary-pane" aria-selected="true">
            <i class="bi bi-pie-chart-fill me-2"></i>Optimasi Keseluruhan 90 Hari
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="daily-tab" data-bs-toggle="tab" data-bs-target="#daily-pane" type="button" role="tab" aria-controls="daily-pane" aria-selected="false">
            <i class="bi bi-calendar-check me-2"></i>Optimasi Per Tanggal
        </button>
    </li>
</ul>

<!-- ============================================================
     TAB CONTENT
     ============================================================ -->
<div class="tab-content">

    <!-- ── TAB 1: SUMMARY 90 DAYS ────────────────────────────── -->
    <div class="tab-pane fade show active" id="summary-pane" role="tabpanel" aria-labelledby="summary-tab">
        <!-- STAT CARDS ROW -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="stat-card card-secondary h-100">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <span class="stat-label">Revenue Saat Ini</span>
                            <div class="stat-value text-slate-800" style="font-size:1.1rem;"><?php echo format_rupiah($total_baseline); ?></div>
                            <span class="stat-sub">Proyeksi 90 Hari</span>
                        </div>
                        <div class="stat-icon"><i class="bi bi-wallet2"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card card-success h-100">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <span class="stat-label">Revenue Optimal</span>
                            <div class="stat-value text-success" style="font-size:1.1rem;"><?php echo format_rupiah($stats['total_revenue_maksimum']); ?></div>
                            <span class="stat-sub">Setelah Optimasi</span>
                        </div>
                        <div class="stat-icon"><i class="bi bi-cash-coin"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card card-primary h-100">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <span class="stat-label">Revenue Gain</span>
                            <div class="stat-value text-primary" style="font-size:1.1rem;"><?php echo format_rupiah($revenue_gain); ?></div>
                            <span class="stat-sub text-success">Potensi tambahan</span>
                        </div>
                        <div class="stat-icon"><i class="bi bi-graph-up-arrow"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card card-warning h-100">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <span class="stat-label">Peningkatan (%)</span>
                            <div class="stat-value text-warning">+<?php echo number_format($gain_pct, 1, ',', '.'); ?>%</div>
                            <span class="stat-sub">Lift Revenue</span>
                        </div>
                        <div class="stat-icon"><i class="bi bi-percent"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- STRATEGY CARDS -->
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <div class="info-box info-danger h-100 py-3">
                    <i class="bi bi-arrow-down-circle-fill info-icon fs-4"></i>
                    <div>
                        <strong>Produk Elastis → Turunkan Harga</strong>
                        <p class="text-xs mt-1 mb-0">
                            |E| &gt; 1: Penurunan harga memicu kenaikan permintaan yang <em>proporsional lebih besar</em>.
                            Harga optimal lebih rendah dari harga saat ini untuk memaksimalkan total revenue.
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="info-box info-success h-100 py-3">
                    <i class="bi bi-arrow-up-circle-fill info-icon fs-4"></i>
                    <div>
                        <strong>Produk Inelastis → Naikkan Harga</strong>
                        <p class="text-xs mt-1 mb-0">
                            |E| &le; 1: Kenaikan harga hanya menurunkan permintaan <em>sedikit</em>.
                            Harga optimal lebih tinggi dari harga saat ini untuk mengambil margin lebih besar.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- DataTable -->
        <div class="dashboard-card mb-4">
            <div class="card-header">
                <h5><i class="bi bi-table text-primary me-2"></i>Rekomendasi Kebijakan Harga Optimal</h5>
                <span class="text-xs text-slate-500">Hasil Optimasi Model Regresi Log-Log</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table custom-table datatable mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Nama Produk</th>
                                <th class="text-center">Kategori</th>
                                <th class="text-center">E</th>
                                <th class="text-end">Harga Saat Ini</th>
                                <th class="text-end">Harga Optimal</th>
                                <th class="text-center">Δ Harga</th>
                                <th class="text-end">Qty Optimal</th>
                                <th class="text-end">Revenue Maks.</th>
                                <th class="text-end">Δ Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data as $idx => $item):
                                $diff_pct  = (($item['harga_optimal'] - $item['harga_saat_ini']) / $item['harga_saat_ini']) * 100;
                                $rev_now   = $item['harga_saat_ini'] * $item['qty_forecast'];
                                $rev_diff  = $item['revenue_maksimum'] - $rev_now;
                                $priceUp   = $diff_pct > 0;
                            ?>
                            <tr>
                                <td class="text-muted"><?php echo $idx + 1; ?></td>
                                <td class="fw-semibold small"><?php echo htmlspecialchars($item['produk']); ?></td>
                                <td class="text-center"><?php echo elasticity_badge($item['kategori']); ?></td>
                                <td class="text-center fw-bold <?php echo strtolower($item['kategori']) === 'elastis' ? 'text-danger' : 'text-success'; ?>">
                                    <?php echo number_format($item['elastisitas'], 4, ',', '.'); ?>
                                </td>
                                <td class="text-end text-slate-600"><?php echo format_rupiah($item['harga_saat_ini']); ?></td>
                                <td class="text-end fw-bold text-primary"><?php echo format_rupiah($item['harga_optimal']); ?></td>
                                <td class="text-center fw-bold <?php echo $priceUp ? 'text-danger' : 'text-success'; ?>">
                                    <?php echo ($priceUp ? '<i class="bi bi-arrow-up-right"></i> +' : '<i class="bi bi-arrow-down-left"></i> '); ?>
                                    <?php echo number_format($diff_pct, 1, ',', '.'); ?>%
                                </td>
                                <td class="text-end"><?php echo format_number($item['qty_optimal']); ?> Pcs</td>
                                <td class="text-end fw-bold text-success"><?php echo format_rupiah($item['revenue_maksimum']); ?></td>
                                <td class="text-end fw-bold <?php echo $rev_diff >= 0 ? 'text-success' : 'text-danger'; ?>">
                                    <?php echo ($rev_diff >= 0 ? '+' : '') . format_rupiah($rev_diff); ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="4" class="fw-bold text-slate-700 text-center">TOTAL</td>
                                <td class="text-end fw-bold text-slate-700"><?php echo format_rupiah($total_baseline); ?></td>
                                <td colspan="2"></td>
                                <td class="text-end fw-bold text-slate-500"><?php echo format_number($stats['total_forecast_90_hari']); ?> Pcs</td>
                                <td class="text-end fw-bold text-success"><?php echo format_rupiah($stats['total_revenue_maksimum']); ?></td>
                                <td class="text-end fw-bold text-success">+<?php echo format_rupiah($revenue_gain); ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <!-- Charts row -->
        <div class="row g-4">
            <div class="col-12 col-lg-6">
                <div class="dashboard-card h-100">
                    <div class="card-header">
                        <h5><i class="bi bi-bar-chart-fill text-primary me-2"></i>Perbandingan Harga Saat Ini vs Optimal</h5>
                        <span class="text-xs text-slate-500">Per Produk</span>
                    </div>
                    <div class="card-body">
                        <div class="chart-container chart-container-lg">
                            <canvas id="priceComparisonChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-lg-6">
                <div class="dashboard-card h-100">
                    <div class="card-header">
                        <h5><i class="bi bi-bar-chart-fill text-success me-2"></i>Revenue Saat Ini vs Revenue Optimal</h5>
                        <span class="text-xs text-slate-500">Per Produk (90 Hari)</span>
                    </div>
                    <div class="card-body">
                        <div class="chart-container chart-container-lg">
                            <canvas id="revenueComparisonChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── TAB 2: DAILY OPTIMIZATION ─────────────────────────── -->
    <div class="tab-pane fade" id="daily-pane" role="tabpanel" aria-labelledby="daily-tab">
        
        <div class="row g-4">
            <!-- Left inputs -->
            <div class="col-12 col-md-4">
                <div class="dashboard-card h-100">
                    <div class="card-header">
                        <h5><i class="bi bi-gear-fill text-primary me-2"></i>Parameter Harian</h5>
                        <span class="text-xs text-slate-500">Pilih produk &amp; tanggal</span>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="productSelectDaily" class="form-label fw-semibold text-sm">Pilih Produk:</label>
                            <select id="productSelectDaily" class="form-select">
                                <?php foreach ($data as $i => $item): ?>
                                <option value="<?php echo $i; ?>"><?php echo htmlspecialchars($item['produk']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="dateSelectDaily" class="form-label fw-semibold text-sm">Pilih Tanggal:</label>
                            <select id="dateSelectDaily" class="form-select">
                                <?php foreach ($daily_dates as $date): ?>
                                    <option value="<?php echo $date; ?>"><?php echo format_date_indonesian($date, true); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right results -->
            <div class="col-12 col-md-8">
                <div class="dashboard-card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5><i class="bi bi-graph-up text-success me-2"></i>Hasil Optimasi Harian</h5>
                        <span class="badge bg-success-soft text-success">Optimized</span>
                    </div>
                    <div class="card-body">
                        
                        <!-- Output Grid -->
                        <div class="row g-3 mb-4">
                            <!-- Price Card -->
                            <div class="col-12 col-sm-4">
                                <div class="simulator-pane simulator-result-box border-start-primary">
                                    <div class="simulator-result-title">Harga Optimal</div>
                                    <div class="d-flex align-items-baseline gap-1">
                                        <span id="optPrice" class="simulator-result-val text-primary">—</span>
                                    </div>
                                    <div class="mt-1 text-xs">
                                        Harga saat ini: <strong id="basePriceDailyVal">—</strong>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Qty Card -->
                            <div class="col-12 col-sm-4">
                                <div class="simulator-pane simulator-result-box border-start-info">
                                    <div class="simulator-result-title">Kuantitas Optimal</div>
                                    <div class="d-flex align-items-baseline gap-1">
                                        <span id="optQty" class="simulator-result-val text-info">—</span>
                                        <span class="text-muted text-xs">Pcs</span>
                                    </div>
                                    <div class="mt-1 text-xs">
                                        Forecast awal: <strong id="baseQtyDailyVal">—</strong>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Revenue Card -->
                            <div class="col-12 col-sm-4">
                                <div class="simulator-pane simulator-result-box border-start-success">
                                    <div class="simulator-result-title">Revenue Maksimum</div>
                                    <div class="d-flex align-items-baseline gap-1">
                                        <span id="optRevenue" class="simulator-result-val text-success">—</span>
                                    </div>
                                    <div class="mt-1 text-xs">
                                        Revenue awal: <strong id="baseRevDailyVal">—</strong>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Gain Lift Display -->
                        <div class="p-3 bg-light rounded-3 d-flex align-items-center justify-content-between">
                            <div>
                                <h6 class="fw-bold mb-1 text-slate-800"><i class="bi bi-graph-up-arrow me-1 text-success"></i>Revenue Gain Lift</h6>
                                <p class="mb-0 text-xs text-slate-500">Estimasi peningkatan margin keuntungan untuk hari tersebut.</p>
                            </div>
                            <div class="text-end">
                                <h5 class="fw-bold text-success mb-0" id="gainDailyVal">—</h5>
                                <span class="badge bg-success-soft text-success text-xs mt-1" id="gainDailyPct">—</span>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>

    </div>

</div>

<!-- ============================================================
     CHART SCRIPTS
     ============================================================ -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const labels   = <?php echo json_encode($product_short); ?>;
    const pNow     = <?php echo json_encode($current_prices); ?>;
    const pOpt     = <?php echo json_encode($optimal_prices); ?>;
    const rNow     = <?php echo json_encode($revenues_now); ?>;
    const rOpt     = <?php echo json_encode($revenues_opt); ?>;

    function formatRupiah(val) {
        return 'Rp ' + Math.round(val).toLocaleString('id-ID');
    }
    function formatNumber(val, decimals = 0) {
        return val.toLocaleString('id-ID', { minimumFractionDigits: decimals, maximumFractionDigits: decimals });
    }

    // Price comparison
    new Chart(document.getElementById('priceComparisonChart'), {
        type: 'bar',
        data: {
            labels,
            datasets: [
                { label: 'Harga Saat Ini (Rp)', data: pNow, backgroundColor: 'rgba(100,116,139,0.7)', borderRadius: 5 },
                { label: 'Harga Optimal (Rp)',   data: pOpt, backgroundColor: 'rgba(79,70,229,0.75)', borderRadius: 5 }
            ]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: {
                legend: { position: 'top' },
                tooltip: { callbacks: { label: ctx => ctx.dataset.label + ': ' + formatRupiah(ctx.raw) } }
            },
            scales: {
                x: { grid: { display: false } },
                y: { grid: { color: '#f1f5f9' }, ticks: { callback: v => 'Rp ' + (v/1000).toFixed(0) + 'k' } }
            }
        }
    });

    // Revenue comparison
    new Chart(document.getElementById('revenueComparisonChart'), {
        type: 'bar',
        data: {
            labels,
            datasets: [
                { label: 'Revenue Saat Ini',  data: rNow, backgroundColor: 'rgba(100,116,139,0.65)', borderRadius: 5 },
                { label: 'Revenue Maksimum',  data: rOpt, backgroundColor: 'rgba(16,185,129,0.75)',  borderRadius: 5 }
            ]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: {
                legend: { position: 'top' },
                tooltip: { callbacks: { label: ctx => ctx.dataset.label + ': ' + formatRupiah(ctx.raw) } }
            },
            scales: {
                x: { grid: { display: false } },
                y: { grid: { color: '#f1f5f9' }, ticks: { callback: v => 'Rp ' + (v/1000000).toFixed(1) + 'Jt' } }
            }
        }
    });

    // ── Daily Optimization Panel Controls ─────────────────────
    const prodSelect = document.getElementById("productSelectDaily");
    const dateSelect = document.getElementById("dateSelectDaily");
    
    const optPrice = document.getElementById("optPrice");
    const optQty = document.getElementById("optQty");
    const optRevenue = document.getElementById("optRevenue");
    
    const basePriceDailyVal = document.getElementById("basePriceDailyVal");
    const baseQtyDailyVal = document.getElementById("baseQtyDailyVal");
    const baseRevDailyVal = document.getElementById("baseRevDailyVal");
    
    const gainDailyVal = document.getElementById("gainDailyVal");
    const gainDailyPct = document.getElementById("gainDailyPct");

    function calculateDailyOptim() {
        const pIdx = parseInt(prodSelect.value);
        const p = productsData[pIdx];
        if (!p) return;
        
        const selDate = dateSelect.value;
        const qForecast = parseFloat(dailyForecast[p.produk][selDate] || 0.0);
        
        const pAwal = parseFloat(p.harga_saat_ini);
        const E = parseFloat(p.elastisitas);
        
        // Harga optimal is already calculated and set in the hasil_akhir database
        const pOptimal = parseFloat(p.harga_optimal);
        
        // qty optimal daily = qForecast * (pOptimal / pAwal)^E
        const qOptimal = qForecast * Math.pow((pOptimal / pAwal), E);
        
        // Revenue calculations
        const rAwal = pAwal * qForecast;
        const rOptimal = pOptimal * qOptimal;
        const rGain = rOptimal - rAwal;
        const rGainPct = rAwal > 0 ? (rGain / rAwal) * 100 : 0;
        
        // Set outputs
        optPrice.textContent = formatRupiah(pOptimal);
        optQty.textContent = formatNumber(qOptimal, 1);
        optRevenue.textContent = formatRupiah(rOptimal);
        
        basePriceDailyVal.textContent = formatRupiah(pAwal);
        baseQtyDailyVal.textContent = formatNumber(qForecast, 1) + ' Pcs';
        baseRevDailyVal.textContent = formatRupiah(rAwal);
        
        gainDailyVal.textContent = (rGain >= 0 ? '+' : '') + formatRupiah(rGain);
        gainDailyPct.textContent = (rGainPct >= 0 ? '+' : '') + rGainPct.toFixed(1) + '% Revenue Lift';
        
        // Color coding for gain
        if (rGain >= 0) {
            gainDailyVal.className = "fw-bold text-success mb-0";
            gainDailyPct.className = "badge bg-success-soft text-success text-xs mt-1";
        } else {
            gainDailyVal.className = "fw-bold text-danger mb-0";
            gainDailyPct.className = "badge bg-danger-soft text-danger text-xs mt-1";
        }
    }

    prodSelect.addEventListener("change", calculateDailyOptim);
    dateSelect.addEventListener("change", calculateDailyOptim);
    
    // Initial compute on daily tab shown
    const dailyTab = document.getElementById("daily-tab");
    dailyTab.addEventListener('shown.bs.tab', function () {
        calculateDailyOptim();
    });
});
</script>
