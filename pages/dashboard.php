<?php
/**
 * Dashboard Page — DSS Harga Popok Toko Virend
 */

$data  = load_csv_data();
$stats = get_summary_stats($data);
$total_revenue_baseline = get_total_revenue_baseline($data);

// Pre-compute chart arrays
$product_labels    = [];
$product_labels_short = [];
$current_revenues  = [];
$max_revenues      = [];
$forecast_qtys     = [];
$elasticity_values = [];
$harga_saat_ini_arr = [];
$harga_optimal_arr  = [];

foreach ($data as $item) {
    $label = $item['produk'];
    // Short label: keep brand + size
    $parts = explode(' ', $label);
    $short = implode(' ', array_slice($parts, 0, 3));

    $product_labels[]       = $label;
    $product_labels_short[] = $short;
    $current_revenues[]     = $item['harga_saat_ini'] * $item['qty_forecast'];
    $max_revenues[]         = $item['revenue_maksimum'];
    $forecast_qtys[]        = $item['qty_forecast'];
    $elasticity_values[]    = $item['elastisitas'];
    $harga_saat_ini_arr[]   = $item['harga_saat_ini'];
    $harga_optimal_arr[]    = $item['harga_optimal'];
}

$revenue_gain         = $stats['total_revenue_maksimum'] - $total_revenue_baseline;
$revenue_gain_percent = $total_revenue_baseline > 0
    ? ($revenue_gain / $total_revenue_baseline) * 100
    : 0;
?>

<!-- ============================================================
     INFO BANNER
     ============================================================ -->
<div class="info-box info-primary mb-4 anim-fade-in-up">
    <i class="bi bi-info-circle-fill info-icon"></i>
    <div>
        <strong>Model Regresi Log-Log</strong> —
        Aplikasi ini menyajikan hasil analisis elastisitas harga produk popok bayi di Toko Virend menggunakan
        regresi log-log linear. Model mengukur sensitivitas permintaan terhadap perubahan harga dan memproyeksikan
        strategi optimasi harga untuk memaksimalkan total pendapatan selama 90 hari ke depan.
    </div>
</div>

<!-- ============================================================
     STAT CARDS ROW
     ============================================================ -->
<div class="row g-3 mb-4">

    <!-- Jumlah Produk -->
    <div class="col-6 col-lg-4 col-xl-2 anim-fade-in-up anim-delay-1">
        <div class="stat-card card-primary h-100">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <span class="stat-label">Jumlah Produk</span>
                    <div class="stat-value" id="valProduk"><?php echo $stats['jumlah_produk']; ?></div>
                    <span class="stat-sub">Item teranalisis</span>
                </div>
                <div class="stat-icon"><i class="bi bi-box-seam"></i></div>
            </div>
        </div>
    </div>

    <!-- Produk Elastis -->
    <div class="col-6 col-lg-4 col-xl-2 anim-fade-in-up anim-delay-2">
        <div class="stat-card card-danger h-100">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <span class="stat-label">Produk Elastis</span>
                    <div class="stat-value"><?php echo $stats['elastic_count']; ?></div>
                    <span class="stat-sub">|E| &gt; 1</span>
                </div>
                <div class="stat-icon"><i class="bi bi-arrow-down-up"></i></div>
            </div>
        </div>
    </div>

    <!-- Produk Inelastis -->
    <div class="col-6 col-lg-4 col-xl-2 anim-fade-in-up anim-delay-3">
        <div class="stat-card card-success h-100">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <span class="stat-label">Produk Inelastis</span>
                    <div class="stat-value"><?php echo $stats['inelastic_count']; ?></div>
                    <span class="stat-sub">|E| &le; 1</span>
                </div>
                <div class="stat-icon"><i class="bi bi-shield-check"></i></div>
            </div>
        </div>
    </div>

    <!-- Total Forecast -->
    <div class="col-6 col-lg-4 col-xl-2 anim-fade-in-up anim-delay-4">
        <div class="stat-card card-info h-100">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <span class="stat-label">Total Forecast Qty</span>
                    <div class="stat-value"><?php echo format_number($stats['total_forecast_90_hari']); ?></div>
                    <span class="stat-sub">Unit · 90 hari</span>
                </div>
                <div class="stat-icon"><i class="bi bi-calendar-check"></i></div>
            </div>
        </div>
    </div>

    <!-- Rata-rata Elastisitas -->
    <div class="col-6 col-lg-4 col-xl-2 anim-fade-in-up anim-delay-5">
        <div class="stat-card card-warning h-100">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <span class="stat-label">Rata-rata Elastisitas</span>
                    <div class="stat-value"><?php echo number_format($stats['rata_rata_elastisitas'], 2, ',', '.'); ?></div>
                    <span class="stat-sub">Koefisien E rata-rata</span>
                </div>
                <div class="stat-icon"><i class="bi bi-percent"></i></div>
            </div>
        </div>
    </div>

    <!-- Revenue Maksimum -->
    <div class="col-6 col-lg-4 col-xl-2 anim-fade-in-up anim-delay-6">
        <div class="stat-card card-success h-100">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <span class="stat-label">Potensi Revenue Maks</span>
                    <div class="stat-value" style="font-size:1.15rem;"><?php echo format_rupiah($stats['total_revenue_maksimum']); ?></div>
                    <span class="stat-sub text-success fw-semibold">
                        <i class="bi bi-arrow-up-right"></i>
                        +<?php echo number_format($revenue_gain_percent, 1, ',', '.'); ?>% vs saat ini
                    </span>
                </div>
                <div class="stat-icon"><i class="bi bi-cash-coin"></i></div>
            </div>
        </div>
    </div>

</div>

<!-- ============================================================
     CHARTS ROW
     ============================================================ -->
<div class="row g-4 mb-4">

    <!-- Chart: Revenue Comparison -->
    <div class="col-12 col-xl-8 anim-fade-in-up">
        <div class="dashboard-card h-100">
            <div class="card-header">
                <h5><i class="bi bi-bar-chart-fill text-primary me-2"></i>Perbandingan Proyeksi Pendapatan (90 Hari)</h5>
                <span class="text-slate-500 text-xs">Harga Saat Ini vs Harga Optimal</span>
            </div>
            <div class="card-body">
                <div class="chart-container chart-container-lg">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart: Elasticity Donut -->
    <div class="col-12 col-xl-4 anim-fade-in-up anim-delay-1">
        <div class="dashboard-card h-100">
            <div class="card-header">
                <h5><i class="bi bi-pie-chart-fill text-warning me-2"></i>Kategori Elastisitas</h5>
                <span class="text-slate-500 text-xs">Proporsi Produk</span>
            </div>
            <div class="card-body">
                <div class="chart-container" style="height:220px;">
                    <canvas id="categoryChart"></canvas>
                </div>
                <!-- Legend -->
                <div class="mt-3">
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <span class="small"><span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:#ef4444;margin-right:6px;"></span>Elastis (|E| &gt; 1)</span>
                        <span class="fw-bold badge-elastic"><?php echo $stats['elastic_count']; ?> produk</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center py-2">
                        <span class="small"><span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:#10b981;margin-right:6px;"></span>Inelastis (|E| &le; 1)</span>
                        <span class="fw-bold badge-inelastic"><?php echo $stats['inelastic_count']; ?> produk</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- ============================================================
     CHARTS ROW 2
     ============================================================ -->
<div class="row g-4 mb-4">

    <!-- Chart: Forecast Qty per Product -->
    <div class="col-12 col-lg-6 anim-fade-in-up">
        <div class="dashboard-card h-100">
            <div class="card-header">
                <h5><i class="bi bi-graph-up-arrow text-info me-2"></i>Distribusi Forecast Permintaan</h5>
                <span class="text-slate-500 text-xs">Proyeksi Qty 90 Hari per Produk</span>
            </div>
            <div class="card-body">
                <div class="chart-container chart-container-md">
                    <canvas id="forecastQtyChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart: Elasticity Bars -->
    <div class="col-12 col-lg-6 anim-fade-in-up anim-delay-1">
        <div class="dashboard-card h-100">
            <div class="card-header">
                <h5><i class="bi bi-sliders2 text-danger me-2"></i>Nilai Koefisien Elastisitas</h5>
                <span class="text-slate-500 text-xs">Harga → Permintaan</span>
            </div>
            <div class="card-body">
                <div class="chart-container chart-container-md">
                    <canvas id="elasticitySmallChart"></canvas>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- ============================================================
     REVENUE LIFT SUMMARY
     ============================================================ -->
<div class="row mb-4 anim-fade-in-up">
    <div class="col-12">
        <div class="dashboard-card">
            <div class="card-body">
                <div class="row align-items-center g-3">
                    <div class="col-md-6">
                        <h5 class="fw-bold mb-2">
                            <i class="bi bi-rocket-takeoff text-success me-2"></i>
                            Proyeksi Kenaikan Pendapatan (Revenue Lift)
                        </h5>
                        <p class="text-slate-500 mb-0 small">
                            Dengan menerapkan strategi harga optimal berbasis elastisitas regresi log-log,
                            Toko Virend berpotensi meningkatkan pendapatan popok sebesar
                            <strong class="text-success"><?php echo format_rupiah($revenue_gain); ?></strong>
                            selama 90 hari ke depan.
                            Produk <strong>inelastis</strong> direkomendasikan naik harga (margin lebih tinggi),
                            sedangkan produk <strong>elastis</strong> direkomendasikan turun harga (volume lebih besar).
                        </p>
                    </div>
                    <div class="col-md-3 text-md-center">
                        <div class="p-3 bg-slate-50 rounded-3 border">
                            <div class="text-xs text-slate-500 text-uppercase fw-semibold mb-1">Proyeksi Saat Ini</div>
                            <div class="fs-6 fw-bold text-slate-500 text-decoration-line-through">
                                <?php echo format_rupiah($total_revenue_baseline); ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 text-md-center">
                        <div class="p-3 rounded-3" style="background:linear-gradient(135deg,#d1fae5,#a7f3d0);border:1px solid #6ee7b7;">
                            <div class="text-xs text-success-dark text-uppercase fw-bold mb-1">Proyeksi Optimal</div>
                            <div class="fs-5 fw-bold text-success">
                                <?php echo format_rupiah($stats['total_revenue_maksimum']); ?>
                            </div>
                            <div class="text-xs fw-semibold text-success mt-1">
                                <i class="bi bi-arrow-up-right"></i>
                                +<?php echo number_format($revenue_gain_percent, 1, ',', '.'); ?>%
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================
     PRODUCT SUMMARY TABLE
     ============================================================ -->
<div class="row mb-4 anim-fade-in-up">
    <div class="col-12">
        <div class="dashboard-card">
            <div class="card-header">
                <h5><i class="bi bi-table text-primary me-2"></i>Ringkasan Data Produk</h5>
                <a href="index.php?page=laporan" class="btn btn-sm btn-primary rounded-pill px-3">
                    <i class="bi bi-file-earmark-pdf me-1"></i>Lihat Laporan Lengkap
                </a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table custom-table datatable mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Produk</th>
                                <th class="text-center">Kategori</th>
                                <th class="text-center">Elastisitas (E)</th>
                                <th class="text-end">Forecast Qty</th>
                                <th class="text-end">Harga Saat Ini</th>
                                <th class="text-end">Harga Optimal</th>
                                <th class="text-end">Revenue Maks.</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data as $idx => $item): ?>
                            <tr>
                                <td class="text-muted"><?php echo $idx + 1; ?></td>
                                <td class="fw-semibold"><?php echo htmlspecialchars($item['produk']); ?></td>
                                <td class="text-center"><?php echo elasticity_badge($item['kategori']); ?></td>
                                <td class="text-center fw-bold <?php echo strtolower($item['kategori']) === 'elastis' ? 'text-danger' : 'text-success'; ?>">
                                    <?php echo number_format($item['elastisitas'], 2, ',', '.'); ?>
                                </td>
                                <td class="text-end"><?php echo format_number($item['qty_forecast']); ?> Pcs</td>
                                <td class="text-end text-slate-600"><?php echo format_rupiah($item['harga_saat_ini']); ?></td>
                                <td class="text-end fw-semibold text-primary"><?php echo format_rupiah($item['harga_optimal']); ?></td>
                                <td class="text-end fw-bold text-success"><?php echo format_rupiah($item['revenue_maksimum']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
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
    const labels      = <?php echo json_encode($product_labels_short); ?>;
    const fullLabels  = <?php echo json_encode($product_labels); ?>;
    const revBaseline = <?php echo json_encode($current_revenues); ?>;
    const revOptimal  = <?php echo json_encode($max_revenues); ?>;
    const fQtys       = <?php echo json_encode($forecast_qtys); ?>;
    const eValues     = <?php echo json_encode($elasticity_values); ?>;

    // ── 1. Revenue Comparison Bar ──────────────────────────────
    new Chart(document.getElementById('revenueChart'), {
        type: 'bar',
        data: {
            labels,
            datasets: [
                {
                    label: 'Pendapatan Saat Ini',
                    data: revBaseline,
                    backgroundColor: 'rgba(100,116,139,0.65)',
                    borderColor: 'rgba(100,116,139,1)',
                    borderWidth: 1,
                    borderRadius: 6
                },
                {
                    label: 'Pendapatan Optimal',
                    data: revOptimal,
                    backgroundColor: 'rgba(16,185,129,0.75)',
                    borderColor: 'rgba(16,185,129,1)',
                    borderWidth: 1,
                    borderRadius: 6
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'top' },
                tooltip: { callbacks: { label: ctx => ctx.dataset.label + ': ' + formatRupiah(ctx.raw) } }
            },
            scales: {
                x: { grid: { display: false } },
                y: {
                    ticks: {
                        callback: v => 'Rp ' + (v / 1000000).toFixed(1) + ' Jt'
                    },
                    grid: { color: '#f1f5f9' }
                }
            }
        }
    });

    // ── 2. Category Donut ──────────────────────────────────────
    new Chart(document.getElementById('categoryChart'), {
        type: 'doughnut',
        data: {
            labels: ['Elastis', 'Inelastis'],
            datasets: [{
                data: [<?php echo $stats['elastic_count']; ?>, <?php echo $stats['inelastic_count']; ?>],
                backgroundColor: ['rgba(239,68,68,0.8)', 'rgba(16,185,129,0.8)'],
                borderColor: ['#fff', '#fff'],
                borderWidth: 3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '72%',
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: ctx => ctx.label + ': ' + ctx.raw + ' produk' } }
            }
        }
    });

    // ── 3. Forecast Qty Horizontal Bar ────────────────────────
    new Chart(document.getElementById('forecastQtyChart'), {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: 'Forecast Qty (90 Hari)',
                data: fQtys,
                backgroundColor: 'rgba(6,182,212,0.7)',
                borderColor: 'rgba(6,182,212,1)',
                borderWidth: 1,
                borderRadius: 5
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { color: '#f1f5f9' }, ticks: { callback: v => v + ' pcs' } },
                y: { grid: { display: false } }
            }
        }
    });

    // ── 4. Elasticity Small Bar ───────────────────────────────
    const eColors = eValues.map(v => Math.abs(v) > 1
        ? 'rgba(239,68,68,0.75)'
        : 'rgba(16,185,129,0.75)');
    new Chart(document.getElementById('elasticitySmallChart'), {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: 'Koefisien Elastisitas (E)',
                data: eValues,
                backgroundColor: eColors,
                borderRadius: 5
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: ctx => 'E = ' + ctx.raw.toFixed(2) } }
            },
            scales: {
                x: { grid: { color: '#f1f5f9' } },
                y: { grid: { display: false } }
            }
        }
    });
});
</script>
