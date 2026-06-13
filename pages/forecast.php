<?php
/**
 * Forecast Permintaan — DSS Harga Popok Toko Virend
 */

$data  = load_csv_data();
$stats = get_summary_stats($data);

// Load daily forecast
$forecast_daily = load_forecast_data();

$product_labels = [];
$product_short  = [];
$forecast_qtys  = [];
$harga_arr      = [];

foreach ($data as $item) {
    $parts = explode(' ', $item['produk']);
    $short = implode(' ', array_slice($parts, 0, 3));
    $product_labels[] = $item['produk'];
    $product_short[]  = $short;
    $forecast_qtys[]  = $item['qty_forecast'];
    $harga_arr[]      = $item['harga_saat_ini'];
}

// Prepare daily forecast payload for JavaScript
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

<!-- JSON Payload for JS -->
<script>
    const dailyDates = <?php echo json_encode($daily_dates); ?>;
    const dailyForecastData = <?php echo json_encode($daily_data_by_product); ?>;
    const productsList = <?php echo json_encode($product_labels); ?>;
</script>

<!-- ============================================================
     INFO BOX
     ============================================================ -->
<div class="info-box info-primary mb-4 anim-fade-in-up">
    <i class="bi bi-graph-up-arrow info-icon"></i>
    <div>
        <strong>Proyeksi Permintaan Penjualan (Forecast)</strong> —
        Halaman ini menyajikan estimasi kuantitas produk popok yang akan terjual berdasarkan model regresi semi-log time series <code>ln(Q) = α + β·t</code>.
        Pilih tab di bawah untuk melihat ringkasan total 90 hari atau rincian per tanggal secara harian.
    </div>
</div>

<!-- ============================================================
     TABS SELECTOR
     ============================================================ -->
<ul class="nav nav-tabs custom-tabs mb-4 anim-fade-in-up" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="summary-tab" data-bs-toggle="tab" data-bs-target="#summary-pane" type="button" role="tab" aria-controls="summary-pane" aria-selected="true">
            <i class="bi bi-pie-chart-fill me-2"></i>Ringkasan Total 90 Hari
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="daily-tab" data-bs-toggle="tab" data-bs-target="#daily-pane" type="button" role="tab" aria-controls="daily-pane" aria-selected="false">
            <i class="bi bi-calendar3 me-2"></i>Forecast Harian / Per Tanggal
        </button>
    </li>
</ul>

<!-- ============================================================
     TAB CONTENT
     ============================================================ -->
<div class="tab-content">

    <!-- ── TAB 1: SUMMARY ────────────────────────────────────── -->
    <div class="tab-pane fade show active" id="summary-pane" role="tabpanel" aria-labelledby="summary-tab">
        <!-- STAT SUMMARY -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="stat-card card-info h-100">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <span class="stat-label">Total Forecast Qty</span>
                            <div class="stat-value"><?php echo format_number($stats['total_forecast_90_hari']); ?></div>
                            <span class="stat-sub">Unit · 90 hari</span>
                        </div>
                        <div class="stat-icon"><i class="bi bi-boxes"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card card-primary h-100">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <span class="stat-label">Rata-rata / Produk</span>
                            <div class="stat-value"><?php echo format_number($stats['total_forecast_90_hari'] / max(1, $stats['jumlah_produk'])); ?></div>
                            <span class="stat-sub">Unit rata-rata</span>
                        </div>
                        <div class="stat-icon"><i class="bi bi-box"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card card-success h-100">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <span class="stat-label">Demand Tertinggi</span>
                            <div class="stat-value"><?php echo !empty($forecast_qtys) ? max($forecast_qtys) : 0; ?></div>
                            <span class="stat-sub">Unit (1 produk)</span>
                        </div>
                        <div class="stat-icon"><i class="bi bi-trophy"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card card-warning h-100">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <span class="stat-label">Demand Terendah</span>
                            <div class="stat-value"><?php echo !empty($forecast_qtys) ? min($forecast_qtys) : 0; ?></div>
                            <span class="stat-sub">Unit (1 produk)</span>
                        </div>
                        <div class="stat-icon"><i class="bi bi-arrow-down-circle"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <!-- Table -->
            <div class="col-12 col-xl-5">
                <div class="dashboard-card h-100">
                    <div class="card-header">
                        <h5><i class="bi bi-table text-info me-2"></i>Proyeksi Kuantitas Permintaan</h5>
                        <span class="text-xs text-slate-500">90 Hari · Baseline Price</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table custom-table mb-0">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Produk</th>
                                        <th class="text-end">Harga Saat Ini</th>
                                        <th class="text-end">Forecast Qty (90 Hari)</th>
                                        <th class="text-end">Qty/Hari</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($data as $idx => $item):
                                        $daily = round($item['qty_forecast'] / 90, 1);
                                    ?>
                                    <tr>
                                        <td class="text-muted"><?php echo $idx + 1; ?></td>
                                        <td class="fw-medium small"><?php echo htmlspecialchars($item['produk']); ?></td>
                                        <td class="text-end text-slate-600"><?php echo format_rupiah($item['harga_saat_ini']); ?></td>
                                        <td class="text-end fw-bold text-info"><?php echo format_number($item['qty_forecast']); ?> <span class="text-muted fw-normal text-xs">Pcs</span></td>
                                        <td class="text-end text-slate-500 text-xs">≈ <?php echo number_format($daily, 1, ',', '.'); ?>/hari</td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="3" class="fw-bold text-slate-700">Total</td>
                                        <td class="text-end fw-bold text-success"><?php echo format_number($stats['total_forecast_90_hari']); ?> Pcs</td>
                                        <td class="text-end text-xs text-slate-500">
                                            ≈ <?php echo number_format($stats['total_forecast_90_hari'] / 90, 1, ',', '.'); ?>/hari
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bar Chart Forecast -->
            <div class="col-12 col-xl-7">
                <div class="dashboard-card h-100">
                    <div class="card-header">
                        <h5><i class="bi bi-bar-chart-fill text-info me-2"></i>Visualisasi Distribusi Forecast</h5>
                        <span class="text-xs text-slate-500">Proyeksi Total 90 Hari per Produk</span>
                    </div>
                    <div class="card-body">
                        <div class="chart-container chart-container-lg">
                            <canvas id="forecastBarChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Progress Contribution -->
        <div class="dashboard-card mb-4">
            <div class="card-header">
                <h5><i class="bi bi-activity text-success me-2"></i>Proporsi Permintaan per Produk</h5>
                <span class="text-xs text-slate-500">Kontribusi terhadap Total Forecast 90 Hari</span>
            </div>
            <div class="card-body">
                <?php
                $max_qty = !empty($forecast_qtys) ? max($forecast_qtys) : 1;
                $colors  = ['#4f46e5','#10b981','#f59e0b','#ef4444','#06b6d4','#8b5cf6'];
                foreach ($data as $idx => $item):
                    $pct_total  = $stats['total_forecast_90_hari'] > 0 ? round(($item['qty_forecast'] / $stats['total_forecast_90_hari']) * 100, 1) : 0;
                    $bar_width  = ($item['qty_forecast'] / $max_qty) * 100;
                    $clr        = $colors[$idx % count($colors)];
                ?>
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="fw-medium text-sm text-slate-700"><?php echo htmlspecialchars($item['produk']); ?></span>
                        <div class="d-flex gap-3 align-items-center">
                            <span class="text-xs text-slate-500"><?php echo format_number($item['qty_forecast']); ?> Pcs</span>
                            <span class="text-xs fw-bold" style="color:<?php echo $clr; ?>;"><?php echo $pct_total; ?>%</span>
                        </div>
                    </div>
                    <div class="progress" style="height:8px; background:#f1f5f9; border-radius:4px;">
                        <div class="progress-bar" role="progressbar"
                             style="width:<?php echo $bar_width; ?>%; background-color:<?php echo $clr; ?>; border-radius:4px;"
                             title="<?php echo format_number($item['qty_forecast']); ?> unit">
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- ── TAB 2: DAILY FORECAST ──────────────────────────────── -->
    <div class="tab-pane fade" id="daily-pane" role="tabpanel" aria-labelledby="daily-tab">
        
        <!-- Interactive Filters -->
        <div class="dashboard-card mb-4">
            <div class="card-header py-2">
                <h6 class="fw-bold mb-0 text-slate-700"><i class="bi bi-funnel-fill me-1 text-primary"></i>Filter Forecast Harian</h6>
            </div>
            <div class="card-body py-3">
                <div class="row g-3">
                    <div class="col-sm-4">
                        <label for="filterProduct" class="form-label text-xs fw-bold text-slate-500">Filter Produk:</label>
                        <select id="filterProduct" class="form-select form-select-sm">
                            <option value="ALL">Semua Produk (Multi-Line)</option>
                            <?php foreach ($product_labels as $lbl): ?>
                                <option value="<?php echo htmlspecialchars($lbl); ?>"><?php echo htmlspecialchars($lbl); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-sm-4">
                        <label for="startDate" class="form-label text-xs fw-bold text-slate-500">Tanggal Mulai:</label>
                        <input type="date" id="startDate" class="form-control form-control-sm" value="<?php echo reset($daily_dates); ?>" min="<?php echo reset($daily_dates); ?>" max="<?php echo end($daily_dates); ?>">
                    </div>
                    <div class="col-sm-4">
                        <label for="endDate" class="form-label text-xs fw-bold text-slate-500">Tanggal Selesai:</label>
                        <input type="date" id="endDate" class="form-control form-control-sm" value="<?php echo end($daily_dates); ?>" min="<?php echo reset($daily_dates); ?>" max="<?php echo end($daily_dates); ?>">
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <!-- Chart Trend -->
            <div class="col-12 col-xl-8">
                <div class="dashboard-card h-100">
                    <div class="card-header">
                        <h5><i class="bi bi-graph-up text-primary me-2"></i>Grafik Tren Forecast Harian</h5>
                        <span class="text-xs text-slate-500">Garis Proyeksi Penjualan Harian</span>
                    </div>
                    <div class="card-body">
                        <div class="chart-container chart-container-lg">
                            <canvas id="dailyForecastChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Table Daily Data -->
            <div class="col-12 col-xl-4">
                <div class="dashboard-card h-100">
                    <div class="card-header">
                        <h5><i class="bi bi-table text-primary me-2"></i>Tabel Forecast Harian</h5>
                        <span class="text-xs text-slate-500">Kuantitas per Produk per Hari</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                            <table class="table custom-table table-sm mb-0 text-xs" id="dailyForecastTable">
                                <thead>
                                    <tr class="bg-light sticky-top">
                                        <th>Tanggal</th>
                                        <th>Produk</th>
                                        <th class="text-end">Qty Forecast</th>
                                    </tr>
                                </thead>
                                <tbody id="dailyTableBody">
                                    <!-- Rendered dynamically by JS -->
                                </tbody>
                            </table>
                        </div>
                        <div class="p-3 border-top d-flex justify-content-between align-items-center">
                            <span id="tableRowsInfo" class="text-xs text-slate-500">Showing 0 rows</span>
                            <button class="btn btn-xs btn-outline-success" onclick="exportDailyCSV()"><i class="bi bi-download me-1"></i>Export CSV</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

</div>

<!-- ============================================================
     METODOLOGI TIME SERIES
     ============================================================ -->
<div class="row g-4">
    <div class="col-12 anim-fade-in-up">
        <div class="dashboard-card">
            <div class="card-header">
                <h5><i class="bi bi-journal-text text-primary me-2"></i>Metodologi Peramalan (Forecasting)</h5>
                <span class="text-xs text-slate-500">Pendekatan semi-log time series</span>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="methodology-step pb-3">
                            <div class="step-number">1</div>
                            <div>
                                <h6 class="fw-bold mb-1 text-sm">Pengumpulan Data Penjualan</h6>
                                <p class="text-xs text-slate-500 mb-0">
                                    Data transaksi time series diolah secara harian untuk mendapatkan kuantitas penjualan aktual per produk.
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="methodology-step pb-3">
                            <div class="step-number">2</div>
                            <div>
                                <h6 class="fw-bold mb-1 text-sm">Model Regresi Semi-Log</h6>
                                <p class="text-xs text-slate-500 mb-0">
                                    Mengestimasi parameter model <code>ln(Q) = α + β·t</code> menggunakan metode OLS. Koefisien slope (β) menangkap laju pertumbuhan harian.
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="methodology-step pb-3">
                            <div class="step-number">3</div>
                            <div>
                                <h6 class="fw-bold mb-1 text-sm">Ekstrapolasi Tren Harian</h6>
                                <p class="text-xs text-slate-500 mb-0">
                                    Memproyeksikan penjualan harian 90 hari ke depan dengan menerapkan parameter intercept (α) dan slope (β) pada indeks waktu mendatang.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================
     EVALUASI AKURASI MODEL FORECAST
     ============================================================ -->
<?php
$model_eval = load_model_evaluation();
?>
<div class="row g-4 mt-0 anim-fade-in-up">
    <div class="col-12">
        <div class="dashboard-card">
            <div class="card-header">
                <h5><i class="bi bi-patch-check-fill text-success me-2"></i>Evaluasi Akurasi Model Forecast</h5>
                <span class="text-xs text-slate-500">Analisis tingkat kesalahan peramalan (Goodness of Fit)</span>
            </div>
            <div class="card-body">
                <?php if (empty($model_eval)): ?>
                    <div class="p-5 text-center text-slate-400">
                        <i class="bi bi-exclamation-circle fs-1 d-block mb-3 text-warning"></i>
                        <h5>Data Evaluasi Model Forecast Belum Tersedia</h5>
                        <p class="text-xs text-slate-500">Silakan unggah dataset baru atau jalankan kalkulasi model pada Data Source Manager terlebih dahulu.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive mb-4">
                        <table class="table custom-table mb-0 text-xs">
                            <thead>
                                <tr class="bg-light">
                                    <th>Produk</th>
                                    <th class="text-center" data-bs-toggle="tooltip" title="Mean Absolute Error (MAE): Rata-rata absolut selisih antara data aktual dan hasil forecast. Semakin kecil semakin akurat.">
                                        MAE <i class="bi bi-info-circle text-muted"></i>
                                    </th>
                                    <th class="text-center" data-bs-toggle="tooltip" title="Root Mean Squared Error (RMSE): Mengukur akar rata-rata kuadrat kesalahan. Memberikan penalti lebih berat pada kesalahan besar.">
                                        RMSE <i class="bi bi-info-circle text-muted"></i>
                                    </th>
                                    <th class="text-center" data-bs-toggle="tooltip" title="Mean Absolute Percentage Error (MAPE): Rata-rata persentase penyimpangan hasil forecast dibanding data aktual. Utama untuk mengukur kualitas.">
                                        MAPE (%) <i class="bi bi-info-circle text-muted"></i>
                                    </th>
                                    <th class="text-center" data-bs-toggle="tooltip" title="R² Forecast: Koefisien determinasi model peramalan dalam memodelkan tren historis data aktual.">
                                        R² Forecast <i class="bi bi-info-circle text-muted"></i>
                                    </th>
                                    <th class="text-center" data-bs-toggle="tooltip" title="Kategori Kualitas: Penilaian performa berdasarkan nilai MAPE (Sangat Baik < 10%, Baik 10-20%, Cukup 20-50%, Kurang Baik > 50%).">
                                        Kategori Kualitas <i class="bi bi-info-circle text-muted"></i>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($model_eval as $row):
                                    $mape = $row['mape_forecast'];
                                    $mape_cat = get_mape_category($mape);
                                ?>
                                <tr>
                                    <td class="fw-semibold text-slate-700"><?php echo htmlspecialchars($row['produk']); ?></td>
                                    <td class="text-center font-monospace"><?php echo number_format($row['mae_forecast'], 2, ',', '.'); ?> pcs</td>
                                    <td class="text-center font-monospace"><?php echo number_format($row['rmse_forecast'], 2, ',', '.'); ?> pcs</td>
                                    <td class="text-center font-monospace fw-bold text-slate-800"><?php echo number_format($mape, 2, ',', '.'); ?>%</td>
                                    <td class="text-center font-monospace"><?php echo number_format($row['r2_forecast'], 4, ',', '.'); ?></td>
                                    <td class="text-center">
                                        <span class="badge <?php echo $mape_cat['badge']; ?> px-2.5 py-1">
                                            <?php echo htmlspecialchars($mape_cat['label']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Auto interpretation list for Forecast -->
                    <div class="bg-light p-3 rounded border">
                        <h6 class="fw-bold text-xs text-slate-700 mb-2"><i class="bi bi-chat-left-text-fill text-success me-1"></i>Analisis Kelayakan &amp; Kualitas Forecast:</h6>
                        <ul class="list-unstyled mb-0 d-flex flex-column gap-2 text-xs">
                            <?php foreach ($model_eval as $row):
                                $mape = $row['mape_forecast'];
                                $mape_cat = get_mape_category($mape);
                                $p_val_percent = round($row['r2_forecast'] * 100, 1);
                                $prod_name = htmlspecialchars($row['produk']);
                            ?>
                            <li class="d-flex align-items-start gap-2">
                                <i class="bi bi-check-circle-fill text-success mt-0.5"></i>
                                <div>
                                    Model forecast untuk <strong>produk <?php echo $prod_name; ?></strong> menghasilkan nilai penyimpangan <strong>MAPE sebesar <?php echo number_format($mape, 2, ',', '.'); ?>%</strong>, yang dikategorikan dalam kualitas <span class="badge <?php echo $mape_cat['badge']; ?>"><?php echo $mape_cat['label']; ?></span>. Nilai R² Forecast sebesar <?php echo number_format($row['r2_forecast'], 2, ',', '.'); ?> menunjukkan laju tren waktu mampu menjelaskan <?php echo $p_val_percent; ?>% variasi data permintaan historis. 
                                    <?php if ($mape < 20.0): ?>
                                        Hal ini menunjukkan model memiliki <span class="text-success fw-bold">akurasi yang tinggi</span> dan sangat andal untuk menyusun rencana persediaan 90 hari mendatang.
                                    <?php else: ?>
                                        Model memiliki akurasi tingkat sedang, disarankan untuk dikombinasikan dengan pengawasan stok manual.
                                    <?php endif; ?>
                                </div>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================
     SCRIPTS & CHARTS
     ============================================================ -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    // 1. Render Summary Bar Chart
    const labels = <?php echo json_encode($product_short); ?>;
    const qtys   = <?php echo json_encode($forecast_qtys); ?>;

    new Chart(document.getElementById('forecastBarChart'), {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: 'Forecast Qty (90 Hari)',
                data: qtys,
                backgroundColor: qtys.map((v, i) => {
                    const max = Math.max(...qtys);
                    const opacity = 0.5 + (v / max) * 0.45;
                    return `rgba(6,182,212,${opacity.toFixed(2)})`;
                }),
                borderColor: '#06b6d4',
                borderWidth: 1,
                borderRadius: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: ctx => 'Forecast: ' + ctx.raw + ' unit (≈ ' + (ctx.raw/90).toFixed(1) + '/hari)' } }
            },
            scales: {
                x: { grid: { display: false } },
                y: {
                    title: { display: true, text: 'Kuantitas Permintaan (Pcs)', font: { weight: '600' } },
                    grid: { color: '#f1f5f9' }
                }
            }
        }
    });

    // 2. Daily Forecast Line Chart & Table Controls
    const filterProduct = document.getElementById("filterProduct");
    const startDateInput = document.getElementById("startDate");
    const endDateInput = document.getElementById("endDate");
    
    let dailyChart = null;

    function renderDaily() {
        const selProd = filterProduct.value;
        const start = startDateInput.value;
        const end = endDateInput.value;
        
        // Filter dates
        const filteredDates = dailyDates.filter(d => d >= start && d <= end);
        
        // Build datasets
        const datasets = [];
        const vibrant = ['#4f46e5', '#10b981', '#f59e0b', '#ef4444', '#06b6d4', '#8b5cf6', '#ec4899', '#14b8a6'];
        const colors = {};
        productsList.forEach((p, idx) => {
            colors[p] = vibrant[idx % vibrant.length];
        });
        
        // Table build list
        const tableRows = [];
        
        // Loop over products to extract data
        for (const p in dailyForecastData) {
            if (selProd !== 'ALL' && selProd !== p) continue;
            
            const dataPts = [];
            filteredDates.forEach(d => {
                const qtyVal = dailyForecastData[p][d] || 0.0;
                dataPts.push(qtyVal);
                
                tableRows.push({
                    tanggal: d,
                    produk: p,
                    qty: qtyVal
                });
            });
            
            datasets.push({
                label: 'Forecast ' + p,
                data: dataPts,
                borderColor: colors[p] || '#8b5cf6',
                backgroundColor: 'transparent',
                borderWidth: 2,
                pointRadius: 2,
                tension: 0.1
            });
        }
        
        // Sort table rows by date and product
        tableRows.sort((a,b) => a.tanggal.localeCompare(b.tanggal) || a.produk.localeCompare(b.produk));
        
        // Update Table UI
        const tbody = document.getElementById("dailyTableBody");
        tbody.innerHTML = "";
        tableRows.forEach(row => {
            const tr = document.createElement("tr");
            tr.innerHTML = `
                <td>${row.tanggal}</td>
                <td class="fw-semibold">${row.produk}</td>
                <td class="text-end fw-bold text-info">${row.qty.toFixed(2)}</td>
            `;
            tbody.appendChild(tr);
        });
        document.getElementById("tableRowsInfo").textContent = `Menampilkan ${tableRows.length} baris`;
        
        // Render Chart
        const ctx = document.getElementById('dailyForecastChart').getContext('2d');
        if (dailyChart) {
            dailyChart.destroy();
        }
        
        dailyChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: filteredDates,
                datasets: datasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top' },
                    tooltip: { mode: 'index', intersect: false }
                },
                scales: {
                    x: { grid: { display: false } },
                    y: {
                        title: { display: true, text: 'Kuantitas / Hari (Pcs)' },
                        grid: { color: '#f1f5f9' }
                    }
                }
            }
        });
    }
    
    // Bind listeners
    filterProduct.addEventListener("change", renderDaily);
    startDateInput.addEventListener("change", renderDaily);
    endDateInput.addEventListener("change", renderDaily);
    
    // Initial Render on daily tab shown
    const dailyTab = document.getElementById('daily-tab');
    dailyTab.addEventListener('shown.bs.tab', function () {
        renderDaily();
    });
    
    // Global export function
    window.exportDailyCSV = function() {
        const start = startDateInput.value;
        const end = endDateInput.value;
        const selProd = filterProduct.value;
        
        let csvContent = "data:text/csv;charset=utf-8,tanggal,produk,qty_forecast\n";
        
        dailyDates.forEach(d => {
            if (d < start || d > end) return;
            for (const p in dailyForecastData) {
                if (selProd !== 'ALL' && selProd !== p) continue;
                const qtyVal = dailyForecastData[p][d] || 0.0;
                csvContent += `${d},${p},${qtyVal.toFixed(4)}\n`;
            }
        });
        
        const encodedUri = encodeURI(csvContent);
        const link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", `forecast_harian_${selProd}_${start}_to_${end}.csv`);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    };
});
</script>
