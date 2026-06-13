<?php
/**
 * Exploratory Data Analysis (EDA) Page — DSS Harga Popok Toko Virend
 * REVISED: Uses raw transactional data (data_raw.csv) for authentic EDA
 */

$raw_data    = load_raw_data();
$dataset_info = load_dataset_info();

// Total statistics
$total_rows = count($raw_data);
$total_cols = !empty($raw_data) ? count(array_keys($raw_data[0])) : 0;
$columns    = !empty($raw_data) ? array_keys($raw_data[0]) : [];

// Date range
$date_start = $dataset_info['date_range_start'] ?? '-';
$date_end   = $dataset_info['date_range_end'] ?? '-';
$products   = $dataset_info['products'] ?? [];

// Descriptive stats for numeric columns
function eda_descriptive_stats(array $data, string $field): array {
    if (empty($data)) return ['mean'=>0,'median'=>0,'min'=>0,'max'=>0,'std_dev'=>0,'total'=>0,'count'=>0];
    $values = array_filter(array_column($data, $field), fn($v) => is_numeric($v));
    $values = array_values(array_map('floatval', $values));
    $n = count($values);
    if ($n === 0) return ['mean'=>0,'median'=>0,'min'=>0,'max'=>0,'std_dev'=>0,'total'=>0,'count'=>0];
    $mean = array_sum($values) / $n;
    $sorted = $values; sort($sorted);
    $median = ($n % 2 === 0) ? ($sorted[$n/2-1] + $sorted[$n/2]) / 2 : $sorted[(int)($n/2)];
    $min = min($values); $max = max($values);
    $variance = array_sum(array_map(fn($v) => pow($v - $mean, 2), $values)) / $n;
    return ['mean'=>$mean,'median'=>$median,'min'=>$min,'max'=>$max,'std_dev'=>sqrt($variance),'total'=>array_sum($values),'count'=>$n];
}

$stats_qty   = eda_descriptive_stats($raw_data, 'qty');
$stats_harga = eda_descriptive_stats($raw_data, 'total_harga');
$stats_hpu   = eda_descriptive_stats($raw_data, 'harga_per_unit');

// Aggregate data for charts
$qty_per_product = [];
$harga_per_product = [];
$count_per_product = [];
$monthly_sales = [];

foreach ($raw_data as $row) {
    $p = $row['produk'] ?? 'Unknown';
    $q = (int)($row['qty'] ?? 0);
    $h = (float)($row['harga_per_unit'] ?? 0);
    $tanggal = $row['tanggal'] ?? '';
    
    // Per product qty
    if (!isset($qty_per_product[$p])) $qty_per_product[$p] = 0;
    $qty_per_product[$p] += $q;
    
    // Per product avg harga
    if (!isset($harga_per_product[$p])) { $harga_per_product[$p] = 0; $count_per_product[$p] = 0; }
    if ($h > 0) { $harga_per_product[$p] += $h; $count_per_product[$p]++; }
    
    // Monthly sales
    if (!empty($tanggal)) {
        $month_key = substr($tanggal, 0, 7); // YYYY-MM
        if (!isset($monthly_sales[$month_key])) $monthly_sales[$month_key] = 0;
        $monthly_sales[$month_key] += $q;
    }
}

// Calculate averages
$avg_harga_per_product = [];
foreach ($harga_per_product as $p => $total) {
    $avg_harga_per_product[$p] = $count_per_product[$p] > 0 ? $total / $count_per_product[$p] : 0;
}

// Sort monthly sales by date
ksort($monthly_sales);

// Top products by total qty
arsort($qty_per_product);

// Dynamic analysis variables for narrative text
$top_products = array_keys($qty_per_product);
$top1_qty_product = isset($top_products[0]) ? $top_products[0] : 'L';
$top2_qty_product = isset($top_products[1]) ? $top_products[1] : '';

$highest_price_product = 'XXL';
$max_price_val = 0;
foreach ($avg_harga_per_product as $p => $val) {
    if ($val > $max_price_val) {
        $max_price_val = $val;
        $highest_price_product = $p;
    }
}

// Scatter data (harga vs qty per transaction)
$scatter_data = [];
foreach ($raw_data as $row) {
    $h = (float)($row['harga_per_unit'] ?? 0);
    $q = (int)($row['qty'] ?? 0);
    if ($h > 0 && $q > 0) {
        $scatter_data[] = ['x' => round($h, 0), 'y' => $q, 'produk' => $row['produk'] ?? ''];
    }
}
// Sample scatter data if too large
if (count($scatter_data) > 500) {
    shuffle($scatter_data);
    $scatter_data = array_slice($scatter_data, 0, 500);
}
?>

<!-- ============================================================
     SECTION HEADER
     ============================================================ -->
<div class="info-box info-primary mb-4 anim-fade-in-up">
    <i class="bi bi-bar-chart-steps info-icon"></i>
    <div>
        <strong>Exploratory Data Analysis (EDA)</strong> —
        Halaman ini menyajikan analisis eksploratif terhadap <strong>data mentah penjualan</strong> (transaksi harian)
        dari dataset <strong><?php echo htmlspecialchars($dataset_info['filename'] ?? 'data_penjualan.xlsx'); ?></strong>.
        Mencakup statistik deskriptif, distribusi variabel, tren waktu, dan korelasi antar variabel.
    </div>
</div>

<!-- ============================================================
     A. INFORMASI DATASET
     ============================================================ -->
<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="dashboard-card anim-fade-in-up">
            <div class="card-header">
                <h5><i class="bi bi-database-fill text-primary me-2"></i>A. Informasi Dataset</h5>
                <span class="text-xs text-slate-500">Data mentah transaksi penjualan</span>
            </div>
            <div class="card-body">
                <div class="row g-4">
                    <div class="col-sm-6 col-md-3">
                        <div class="p-3 bg-slate-50 border rounded-3 text-center">
                            <div class="fs-1 text-primary fw-bold"><?php echo number_format($total_rows); ?></div>
                            <div class="text-xs text-slate-500 text-uppercase fw-semibold">Total Transaksi</div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <div class="p-3 bg-slate-50 border rounded-3 text-center">
                            <div class="fs-1 text-warning fw-bold"><?php echo $total_cols; ?></div>
                            <div class="text-xs text-slate-500 text-uppercase fw-semibold">Jumlah Kolom</div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <div class="p-3 bg-slate-50 border rounded-3 text-center">
                            <div class="fs-1 text-success fw-bold"><?php echo count($products); ?></div>
                            <div class="text-xs text-slate-500 text-uppercase fw-semibold">Jumlah Produk</div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <div class="p-3 bg-slate-50 border rounded-3 text-center">
                            <div class="fs-1 text-danger fw-bold"><?php echo count($monthly_sales); ?></div>
                            <div class="text-xs text-slate-500 text-uppercase fw-semibold">Periode Bulan</div>
                        </div>
                    </div>

                    <!-- Attribute List -->
                    <div class="col-12">
                        <h6 class="fw-bold text-slate-700 mb-2"><i class="bi bi-list-columns me-1"></i>Daftar Kolom Dataset</h6>
                        <div class="d-flex flex-wrap gap-2">
                            <?php
                            $col_desc = [
                                'tanggal'        => 'Tanggal transaksi',
                                'produk'         => 'Ukuran/varian produk popok',
                                'qty'            => 'Jumlah unit terjual',
                                'total_harga'    => 'Total nilai transaksi (Rp)',
                                'harga_per_unit' => 'Harga per unit (Rp)',
                                'waktu input'    => 'Waktu pencatatan',
                            ];
                            foreach ($columns as $col):
                                $desc = $col_desc[$col] ?? $col;
                            ?>
                            <div class="badge bg-slate-100 text-slate-700 px-3 py-2 rounded-2 d-flex align-items-center gap-1 fw-normal"
                                 title="<?php echo htmlspecialchars($desc); ?>">
                                <i class="bi bi-tag text-primary"></i>
                                <code class="text-xs"><?php echo htmlspecialchars($col); ?></code>
                                <span class="text-slate-500 text-xs ms-1">— <?php echo htmlspecialchars($desc); ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Date Range -->
                    <div class="col-12">
                        <div class="info-box info-primary py-2">
                            <i class="bi bi-calendar-range info-icon"></i>
                            <div>
                                <strong>Rentang Data:</strong> Dataset mencakup transaksi penjualan dari
                                <strong><?php echo $date_start; ?></strong> sampai <strong><?php echo $date_end; ?></strong>,
                                dengan total <strong><?php echo number_format($total_rows); ?> transaksi</strong>
                                untuk <strong><?php echo count($products); ?> varian produk</strong>
                                (<?php echo implode(', ', $products); ?>).
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================
     B. STATISTIK DESKRIPTIF
     ============================================================ -->
<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="dashboard-card anim-fade-in-up">
            <div class="card-header">
                <h5><i class="bi bi-calculator-fill text-success me-2"></i>B. Statistik Deskriptif</h5>
                <span class="text-xs text-slate-500">Statistik variabel numerik dari data mentah</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table custom-table mb-0">
                        <thead>
                            <tr>
                                <th>Variabel</th>
                                <th class="text-end">Count</th>
                                <th class="text-end">Mean</th>
                                <th class="text-end">Median</th>
                                <th class="text-end">Min</th>
                                <th class="text-end">Max</th>
                                <th class="text-end">Std Dev</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="fw-semibold text-slate-700">Qty (Unit Terjual)</td>
                                <td class="text-end"><?php echo number_format($stats_qty['count']); ?></td>
                                <td class="text-end"><?php echo number_format($stats_qty['mean'], 2, ',', '.'); ?></td>
                                <td class="text-end"><?php echo number_format($stats_qty['median'], 2, ',', '.'); ?></td>
                                <td class="text-end text-danger"><?php echo number_format($stats_qty['min'], 0, ',', '.'); ?></td>
                                <td class="text-end text-success"><?php echo number_format($stats_qty['max'], 0, ',', '.'); ?></td>
                                <td class="text-end text-warning"><?php echo number_format($stats_qty['std_dev'], 2, ',', '.'); ?></td>
                                <td class="text-end fw-bold"><?php echo number_format($stats_qty['total'], 0, ',', '.'); ?></td>
                            </tr>
                            <tr>
                                <td class="fw-semibold text-slate-700">Total Harga (Rp)</td>
                                <td class="text-end"><?php echo number_format($stats_harga['count']); ?></td>
                                <td class="text-end"><?php echo format_rupiah($stats_harga['mean']); ?></td>
                                <td class="text-end"><?php echo format_rupiah($stats_harga['median']); ?></td>
                                <td class="text-end text-danger"><?php echo format_rupiah($stats_harga['min']); ?></td>
                                <td class="text-end text-success"><?php echo format_rupiah($stats_harga['max']); ?></td>
                                <td class="text-end text-warning"><?php echo format_rupiah($stats_harga['std_dev']); ?></td>
                                <td class="text-end fw-bold"><?php echo format_rupiah($stats_harga['total']); ?></td>
                            </tr>
                            <tr>
                                <td class="fw-semibold text-slate-700">Harga per Unit (Rp)</td>
                                <td class="text-end"><?php echo number_format($stats_hpu['count']); ?></td>
                                <td class="text-end"><?php echo format_rupiah($stats_hpu['mean']); ?></td>
                                <td class="text-end"><?php echo format_rupiah($stats_hpu['median']); ?></td>
                                <td class="text-end text-danger"><?php echo format_rupiah($stats_hpu['min']); ?></td>
                                <td class="text-end text-success"><?php echo format_rupiah($stats_hpu['max']); ?></td>
                                <td class="text-end text-warning"><?php echo format_rupiah($stats_hpu['std_dev']); ?></td>
                                <td class="text-end fw-bold"><?php echo format_rupiah($stats_hpu['total']); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="px-3 py-2 bg-light">
                    <p class="text-xs text-slate-500 mb-0">
                        <i class="bi bi-info-circle me-1"></i>
                        Statistik dihitung dari <?php echo number_format($total_rows); ?> baris data transaksi mentah.
                        Mean = rata-rata, Median = nilai tengah, Std Dev = standar deviasi (populasi).
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================
     C. VISUALISASI DATA
     ============================================================ -->
<div class="mb-3 anim-fade-in-up">
    <h5 class="fw-bold text-slate-800"><i class="bi bi-graph-up text-primary me-2"></i>C. Visualisasi Data Mentah</h5>
    <p class="text-slate-500 small">Grafik interaktif dari data transaksi penjualan harian.</p>
</div>

<!-- Row 1: Distribusi Qty & Harga -->
<div class="row g-4 mb-4">
    <div class="col-12 col-lg-6 anim-fade-in-up">
        <div class="dashboard-card h-100">
            <div class="card-header">
                <h5><i class="bi bi-bar-chart-fill text-info me-2"></i>C1. Total Penjualan per Produk</h5>
                <span class="text-xs text-slate-500">Agregat seluruh periode</span>
            </div>
            <div class="card-body">
                <p class="text-xs text-slate-500 mb-3">
                    Menunjukkan total unit terjual per varian produk selama periode data.
                    Produk dengan volume tertinggi menunjukkan popularitas yang lebih besar di pasar.
                </p>
                <div class="chart-container chart-container-md">
                    <canvas id="chartQtyPerProduct"></canvas>
                </div>
                <div class="mt-3 p-2.5 bg-light rounded text-xs text-slate-600">
                    <i class="bi bi-chat-left-quote-fill text-info me-1"></i>
                    <strong>Analisis:</strong> <?php 
                    if (!empty($top2_qty_product)) {
                        echo "Produk <strong>" . htmlspecialchars($top1_qty_product) . "</strong> dan <strong>" . htmlspecialchars($top2_qty_product) . "</strong> mendominasi kuantitas penjualan.";
                    } else {
                        echo "Produk <strong>" . htmlspecialchars($top1_qty_product) . "</strong> mendominasi kuantitas penjualan.";
                    }
                    ?> Hal ini mencerminkan segmentasi pasar dengan kontribusi kuantitas tertinggi di Toko Virend.
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-6 anim-fade-in-up anim-delay-1">
        <div class="dashboard-card h-100">
            <div class="card-header">
                <h5><i class="bi bi-currency-dollar text-warning me-2"></i>C2. Rata-rata Harga per Unit</h5>
                <span class="text-xs text-slate-500">Harga rata-rata tiap produk</span>
            </div>
            <div class="card-body">
                <p class="text-xs text-slate-500 mb-3">
                    Rata-rata harga jual per unit untuk setiap varian produk.
                    Perbedaan harga antar varian mencerminkan segmentasi pasar dan ukuran kemasan.
                </p>
                <div class="chart-container chart-container-md">
                    <canvas id="chartHargaPerProduct"></canvas>
                </div>
                <div class="mt-3 p-2.5 bg-light rounded text-xs text-slate-600">
                    <i class="bi bi-chat-left-quote-fill text-warning me-1"></i>
                    <strong>Analisis:</strong> Rata-rata harga satuan tertinggi ada pada produk <strong><?php echo htmlspecialchars($highest_price_product); ?></strong>, yang selaras dengan fakta bahwa varian produk dengan spesifikasi lebih tinggi atau kemasan lebih besar dibanderol dengan harga satuan lebih tinggi.
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Row 2: Tren Bulanan -->
<div class="row g-4 mb-4">
    <div class="col-12 anim-fade-in-up">
        <div class="dashboard-card">
            <div class="card-header">
                <h5><i class="bi bi-graph-up text-success me-2"></i>C3. Tren Penjualan Berdasarkan Waktu</h5>
                <span class="text-xs text-slate-500">Tren volume penjualan bulanan</span>
            </div>
            <div class="card-body">
                <p class="text-xs text-slate-500 mb-3">
                    Tren total penjualan (unit) yang diagregasi per bulan sepanjang periode data.
                    Grafik ini membantu mengidentifikasi pola musiman dan tren jangka panjang pada permintaan produk popok.
                </p>
                <div class="chart-container" style="height:320px;">
                    <canvas id="chartTrenBulanan"></canvas>
                </div>
                <div class="mt-3 p-2.5 bg-light rounded text-xs text-slate-600">
                    <i class="bi bi-chat-left-quote-fill text-success me-1"></i>
                    <strong>Analisis:</strong> Tren penjualan berjangka bulanan memperlihatkan kestabilan fluktuatif tanpa penurunan ekstrem, mengonfirmasi bahwa produk popok bayi merupakan komoditas kebutuhan primer yang permintaannya konsisten sepanjang tahun.
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Row 3: Top Produk & Korelasi -->
<div class="row g-4 mb-4">
    <div class="col-12 col-lg-6 anim-fade-in-up">
        <div class="dashboard-card h-100">
            <div class="card-header">
                <h5><i class="bi bi-trophy-fill text-warning me-2"></i>C4. Top Produk Terlaris</h5>
                <span class="text-xs text-slate-500">Peringkat berdasarkan total qty</span>
            </div>
            <div class="card-body">
                <p class="text-xs text-slate-500 mb-3">
                    Peringkat produk berdasarkan total unit terjual selama periode data.
                    Produk teratas merupakan kontributor utama volume penjualan toko.
                </p>
                <div class="chart-container chart-container-md">
                    <canvas id="chartTopProduk"></canvas>
                </div>
                <div class="mt-3 p-2.5 bg-light rounded text-xs text-slate-600">
                    <i class="bi bi-chat-left-quote-fill text-warning me-1"></i>
                    <strong>Analisis:</strong> Produk <strong><?php echo htmlspecialchars($top1_qty_product); ?></strong> menjadi kontributor utama volume transaksi. Fokus pengelolaan stok harus diutamakan pada produk ini guna mencegah hilangnya potensi penjualan akibat kehabisan barang (stockout).
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-6 anim-fade-in-up anim-delay-1">
        <div class="dashboard-card h-100">
            <div class="card-header">
                <h5><i class="bi bi-diagram-3-fill text-danger me-2"></i>C5. Korelasi Harga dan Quantity</h5>
                <span class="text-xs text-slate-500">Scatter Plot</span>
            </div>
            <div class="card-body">
                <p class="text-xs text-slate-500 mb-3">
                    Scatter plot menunjukkan hubungan antara harga per unit dan jumlah unit terjual.
                    Pola negatif (semakin tinggi harga, semakin rendah qty) mengindikasikan hubungan elastisitas harga.
                </p>
                <div class="chart-container chart-container-md">
                    <canvas id="chartKorelasi"></canvas>
                </div>
                <div class="mt-3 p-2.5 bg-light rounded text-xs text-slate-600">
                    <i class="bi bi-chat-left-quote-fill text-danger me-1"></i>
                    <strong>Analisis:</strong> Terlihat sebaran data yang cenderung membentuk kluster harga tertentu per produk. Pada kluster harga yang lebih rendah, kuantitas pembelian cenderung menyebar lebih tinggi, mengindikasikan sensitivitas harga sesuai hukum permintaan.
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================
     D. PREVIEW DATA MENTAH
     ============================================================ -->
<div class="row g-4 mb-4">
    <div class="col-12 anim-fade-in-up">
        <div class="dashboard-card">
            <div class="card-header">
                <h5><i class="bi bi-table text-primary me-2"></i>D. Preview Data Mentah</h5>
                <span class="text-xs text-slate-500">20 baris pertama transaksi</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table custom-table mb-0 text-xs">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Tanggal</th>
                                <th>Produk</th>
                                <th class="text-end">Qty</th>
                                <th class="text-end">Total Harga</th>
                                <th class="text-end">Harga/Unit</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($raw_data, 0, 20) as $idx => $row): ?>
                            <tr>
                                <td class="text-muted"><?php echo $idx + 1; ?></td>
                                <td><?php echo htmlspecialchars($row['tanggal'] ?? '-'); ?></td>
                                <td class="fw-medium text-slate-700"><?php echo htmlspecialchars($row['produk'] ?? '-'); ?></td>
                                <td class="text-end"><?php echo number_format((int)($row['qty'] ?? 0)); ?></td>
                                <td class="text-end"><?php echo format_rupiah($row['total_harga'] ?? 0); ?></td>
                                <td class="text-end text-success"><?php echo format_rupiah($row['harga_per_unit'] ?? 0); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="px-3 py-2 text-center bg-light">
                    <span class="text-xs text-slate-500">
                        <i class="bi bi-info-circle me-1"></i>
                        Menampilkan 20 dari <?php echo number_format($total_rows); ?> baris data.
                    </span>
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
    const productLabels = <?php echo json_encode(array_keys($qty_per_product)); ?>;
    const qtyValues     = <?php echo json_encode(array_values($qty_per_product)); ?>;
    const avgHarga      = <?php echo json_encode(array_values($avg_harga_per_product)); ?>;
    const monthLabels   = <?php echo json_encode(array_keys($monthly_sales)); ?>;
    const monthValues   = <?php echo json_encode(array_values($monthly_sales)); ?>;
    const scatterData   = <?php echo json_encode(array_values($scatter_data)); ?>;

    const vibrant = ['#3b82f6','#f59e0b','#10b981','#ef4444','#8b5cf6','#ec4899','#14b8a6'];

    // C1. Total Qty per Product
    new Chart(document.getElementById('chartQtyPerProduct'), {
        type: 'bar',
        data: {
            labels: productLabels,
            datasets: [{
                label: 'Total Qty',
                data: qtyValues,
                backgroundColor: vibrant.slice(0, productLabels.length),
                borderRadius: 8,
                borderWidth: 0
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false }, tooltip: { callbacks: { label: ctx => 'Qty: ' + ctx.raw.toLocaleString('id-ID') } } },
            scales: { y: { beginAtZero: true, ticks: { callback: v => v.toLocaleString('id-ID') }, grid: { color: '#f1f5f9' } }, x: { grid: { display: false } } }
        }
    });

    // C2. Avg Harga per Product
    new Chart(document.getElementById('chartHargaPerProduct'), {
        type: 'bar',
        data: {
            labels: productLabels,
            datasets: [{
                label: 'Rata-rata Harga/Unit',
                data: avgHarga,
                backgroundColor: vibrant.slice(0, productLabels.length).map(c => c + 'cc'),
                borderColor: vibrant.slice(0, productLabels.length),
                borderWidth: 2,
                borderRadius: 8
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false }, tooltip: { callbacks: { label: ctx => 'Rp ' + Math.round(ctx.raw).toLocaleString('id-ID') } } },
            scales: { y: { beginAtZero: true, ticks: { callback: v => 'Rp ' + (v/1000).toFixed(0) + 'rb' }, grid: { color: '#f1f5f9' } }, x: { grid: { display: false } } }
        }
    });

    // C3. Monthly Trend
    new Chart(document.getElementById('chartTrenBulanan'), {
        type: 'line',
        data: {
            labels: monthLabels,
            datasets: [{
                label: 'Total Qty per Bulan',
                data: monthValues,
                borderColor: '#10b981',
                backgroundColor: 'rgba(16,185,129,0.08)',
                fill: true,
                tension: 0.4,
                pointRadius: 2,
                pointHoverRadius: 6,
                borderWidth: 2.5
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false }, tooltip: { callbacks: { label: ctx => 'Qty: ' + ctx.raw.toLocaleString('id-ID') } } },
            scales: { y: { beginAtZero: true, ticks: { callback: v => v.toLocaleString('id-ID') }, grid: { color: '#f1f5f9' } }, x: { grid: { display: false }, ticks: { maxRotation: 45, autoSkip: true, maxTicksLimit: 24 } } }
        }
    });

    // C4. Top Products (Horizontal Bar)
    new Chart(document.getElementById('chartTopProduk'), {
        type: 'bar',
        data: {
            labels: productLabels,
            datasets: [{
                label: 'Total Qty',
                data: qtyValues,
                backgroundColor: vibrant.slice(0, productLabels.length),
                borderRadius: 8,
                borderWidth: 0
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false }, tooltip: { callbacks: { label: ctx => 'Total Qty: ' + ctx.raw.toLocaleString('id-ID') } } },
            scales: { x: { beginAtZero: true, ticks: { callback: v => v.toLocaleString('id-ID') }, grid: { color: '#f1f5f9' } }, y: { grid: { display: false } } }
        }
    });

    // C5. Scatter: Harga vs Qty
    // Group by product for distinct colors
    const productSet = [...new Set(scatterData.map(d => d.produk))];
    const scatterDatasets = productSet.map((p, i) => ({
        label: p,
        data: scatterData.filter(d => d.produk === p).map(d => ({x: d.x, y: d.y})),
        backgroundColor: vibrant[i % vibrant.length] + '99',
        borderColor: vibrant[i % vibrant.length],
        borderWidth: 1,
        pointRadius: 4,
        pointHoverRadius: 7
    }));

    new Chart(document.getElementById('chartKorelasi'), {
        type: 'scatter',
        data: { datasets: scatterDatasets },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { position: 'top', labels: { usePointStyle: true, pointStyle: 'circle', padding: 15 } } },
            scales: {
                x: { 
                    title: { display: true, text: 'Harga per Unit (Rp)', font: { weight: '600' } }, 
                    ticks: { callback: v => 'Rp ' + (v/1000).toFixed(0) + 'rb' }, 
                    grid: { color: '#f1f5f9' } 
                },
                y: { 
                    title: { display: true, text: 'Quantity (Unit)', font: { weight: '600' } }, 
                    beginAtZero: true, 
                    grid: { color: '#f1f5f9' } 
                }
            }
        }
    });
});
</script>
