<?php
/**
 * Analisis Elastisitas Harga — DSS Harga Popok Toko Virend
 */

$data = load_csv_data();
$stats = get_summary_stats($data);

$product_labels   = [];
$product_short    = [];
$elasticity_vals  = [];
$bar_colors       = [];
$bar_border       = [];

foreach ($data as $item) {
    $parts = explode(' ', $item['produk']);
    $short = implode(' ', array_slice($parts, 0, 3));
    $product_labels[]  = $item['produk'];
    $product_short[]   = $short;
    $elasticity_vals[] = $item['elastisitas'];

    if (strtolower(trim($item['kategori'])) === 'elastis') {
        $bar_colors[] = 'rgba(239,68,68,0.75)';
        $bar_border[] = 'rgba(239,68,68,1)';
    } else {
        $bar_colors[] = 'rgba(16,185,129,0.75)';
        $bar_border[] = 'rgba(16,185,129,1)';
    }
}
?>

<!-- ============================================================
     INFO BOX
     ============================================================ -->
<div class="info-box info-primary mb-4 anim-fade-in-up">
    <i class="bi bi-info-circle-fill info-icon"></i>
    <div>
        <strong>Koefisien Elastisitas Harga</strong> —
        Nilai elastisitas (<em>E</em>) menunjukkan persentase perubahan jumlah permintaan akibat perubahan harga 1%.
        Diperoleh dari koefisien regresi log-log:
        <strong>ln(Q) = α + E·ln(P)</strong>, sehingga <em>E</em> langsung merupakan elastisitas harga permintaan.
    </div>
</div>

<!-- ============================================================
     INTERPRETASI CARDS
     ============================================================ -->
<div class="row g-3 mb-4">
    <!-- Elastis -->
    <div class="col-md-6 anim-fade-in-up anim-delay-1">
        <div class="dashboard-card h-100">
            <div class="card-body">
                <div class="d-flex align-items-start gap-3">
                    <div style="width:44px;height:44px;border-radius:12px;background:#fee2e2;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="bi bi-exclamation-triangle-fill text-danger fs-5"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold text-danger mb-1">Elastis (|E| &gt; 1) — <?php echo $stats['elastic_count']; ?> Produk</h6>
                        <p class="text-xs text-slate-500 mb-2">
                            Permintaan <strong>sangat sensitif</strong> terhadap perubahan harga. Kenaikan harga 1%
                            menurunkan kuantitas lebih dari 1%, sehingga total pendapatan berpotensi turun.
                        </p>
                        <div class="info-box info-danger py-2 px-3">
                            <i class="bi bi-lightbulb-fill info-icon" style="font-size:0.9rem;"></i>
                            <div class="text-xs">
                                <strong>Strategi:</strong> Turunkan atau stabilkan harga untuk menstimulasi
                                volume penjualan dan mengkompensasi margin yang lebih rendah.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Inelastis -->
    <div class="col-md-6 anim-fade-in-up anim-delay-2">
        <div class="dashboard-card h-100">
            <div class="card-body">
                <div class="d-flex align-items-start gap-3">
                    <div style="width:44px;height:44px;border-radius:12px;background:#d1fae5;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="bi bi-shield-check-fill text-success fs-5"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold text-success mb-1">Inelastis (|E| &le; 1) — <?php echo $stats['inelastic_count']; ?> Produk</h6>
                        <p class="text-xs text-slate-500 mb-2">
                            Permintaan <strong>kurang sensitif</strong> terhadap perubahan harga. Kenaikan harga 1%
                            hanya menurunkan kuantitas kurang dari 1%, sehingga total pendapatan berpotensi naik.
                        </p>
                        <div class="info-box info-success py-2 px-3">
                            <i class="bi bi-lightbulb-fill info-icon" style="font-size:0.9rem;"></i>
                            <div class="text-xs">
                                <strong>Strategi:</strong> Naikkan harga secara bertahap untuk meningkatkan
                                margin keuntungan tanpa kehilangan banyak pelanggan.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================
     MAIN CONTENT: TABLE + CHART
     ============================================================ -->
<div class="row g-4">

    <!-- Tabel Elastisitas -->
    <div class="col-12 col-xl-5 anim-fade-in-up">
        <div class="dashboard-card h-100">
            <div class="card-header">
                <h5><i class="bi bi-table text-primary me-2"></i>Daftar Elastisitas Produk</h5>
                <span class="text-xs text-slate-500">Hasil Regresi Log-Log</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table custom-table datatable mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Produk</th>
                                <th class="text-center">Elastisitas (E)</th>
                                <th class="text-center">|E|</th>
                                <th class="text-center">Kategori</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data as $idx => $item): ?>
                            <tr>
                                <td class="text-muted"><?php echo $idx + 1; ?></td>
                                <td class="fw-medium small"><?php echo htmlspecialchars($item['produk']); ?></td>
                                <td class="text-center fw-bold <?php echo strtolower($item['kategori']) === 'elastis' ? 'text-danger' : 'text-success'; ?>">
                                    <?php echo number_format($item['elastisitas'], 2, ',', '.'); ?>
                                </td>
                                <td class="text-center text-slate-600">
                                    <?php echo number_format(abs($item['elastisitas']), 2, ',', '.'); ?>
                                </td>
                                <td class="text-center">
                                    <?php echo elasticity_badge($item['kategori']); ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="2" class="fw-bold text-slate-700">Rata-rata</td>
                                <td class="text-center fw-bold text-warning">
                                    <?php echo number_format($stats['rata_rata_elastisitas'], 2, ',', '.'); ?>
                                </td>
                                <td class="text-center fw-bold text-warning">
                                    <?php echo number_format(abs($stats['rata_rata_elastisitas']), 2, ',', '.'); ?>
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Bar Chart Elastisitas -->
    <div class="col-12 col-xl-7 anim-fade-in-up anim-delay-1">
        <div class="dashboard-card h-100">
            <div class="card-header">
                <h5><i class="bi bi-bar-chart-fill text-danger me-2"></i>Grafik Nilai Koefisien Elastisitas</h5>
                <span class="text-xs text-slate-500">Horizontal Bar — Hasil Model Regresi Log-Log</span>
            </div>
            <div class="card-body">
                <div class="d-flex gap-3 mb-3 text-xs">
                    <span><i class="bi bi-square-fill text-danger me-1"></i>Elastis (|E| &gt; 1): Sensitif terhadap harga</span>
                    <span><i class="bi bi-square-fill text-success me-1"></i>Inelastis (|E| &le; 1): Kurang sensitif</span>
                </div>
                <div class="chart-container chart-container-lg">
                    <canvas id="elasticityBarChart"></canvas>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- ============================================================
     INTERPRETASI PER PRODUK
     ============================================================ -->
<div class="row g-4 mt-0">
    <div class="col-12 anim-fade-in-up">
        <div class="dashboard-card">
            <div class="card-header">
                <h5><i class="bi bi-lightbulb-fill text-warning me-2"></i>Interpretasi Elastisitas per Produk</h5>
                <span class="text-xs text-slate-500">Implikasi kebijakan harga</span>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <?php foreach ($data as $idx => $item):
                        $e       = $item['elastisitas'];
                        $absE    = abs($e);
                        $isElast = strtolower(trim($item['kategori'])) === 'elastis';
                        $colorClass = $isElast ? 'danger' : 'success';
                        $icon       = $isElast ? 'arrow-down-circle-fill' : 'arrow-up-circle-fill';
                        $naik_pct_qty = $absE; // 1% naik harga → −E% qty
                    ?>
                    <div class="col-md-6 col-xl-4">
                        <div class="p-3 border rounded-3 h-100" style="border-color: <?php echo $isElast ? '#fecaca' : '#a7f3d0'; ?> !important; background: <?php echo $isElast ? '#fff5f5' : '#f0fdf4'; ?>;">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <i class="bi bi-<?php echo $icon; ?> text-<?php echo $colorClass; ?>"></i>
                                <strong class="text-xs text-slate-700"><?php echo htmlspecialchars($item['produk']); ?></strong>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-xs text-slate-500">Koefisien E:</span>
                                <strong class="text-xs text-<?php echo $colorClass; ?>"><?php echo number_format($e, 2, ',', '.'); ?></strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-xs text-slate-500">Kategori:</span>
                                <?php echo elasticity_badge($item['kategori']); ?>
                            </div>
                            <p class="text-xs text-slate-600 mb-0">
                                <?php if ($isElast): ?>
                                    Kenaikan harga <strong>1%</strong> menurunkan permintaan <strong><?php echo number_format($absE, 2, ',', '.'); ?>%</strong>.
                                    Disarankan <strong class="text-danger">tidak menaikkan harga</strong>.
                                <?php else: ?>
                                    Kenaikan harga <strong>1%</strong> hanya menurunkan permintaan <strong><?php echo number_format($absE, 2, ',', '.'); ?>%</strong>.
                                    Disarankan <strong class="text-success">menaikkan harga bertahap</strong>.
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================
     EVALUASI KINERJA MODEL REGRESI LOG-LOG
     ============================================================ -->
<?php
$model_eval = load_model_evaluation();
?>
<div class="row g-4 mt-0 anim-fade-in-up">
    <div class="col-12">
        <div class="dashboard-card">
            <div class="card-header">
                <h5><i class="bi bi-clipboard2-check-fill text-info me-2"></i>Evaluasi Kinerja Model Regresi Log-Log</h5>
                <span class="text-xs text-slate-500">Uji Kelayakan Statistik Model Elastisitas</span>
            </div>
            <div class="card-body">
                <?php if (empty($model_eval)): ?>
                    <div class="p-5 text-center text-slate-400">
                        <i class="bi bi-exclamation-circle fs-1 d-block mb-3 text-warning"></i>
                        <h5>Data Evaluasi Model Belum Tersedia</h5>
                        <p class="text-xs text-slate-500">Silakan unggah dataset baru atau jalankan kalkulasi model pada Data Source Manager terlebih dahulu.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive mb-4">
                        <table class="table custom-table mb-0 text-xs">
                            <thead>
                                <tr class="bg-light">
                                    <th>Produk</th>
                                    <th class="text-center" data-bs-toggle="tooltip" title="Koefisien Determinasi (R²): Mengukur persentase variasi Q yang dapat dijelaskan oleh variasi P.">
                                        R² <i class="bi bi-info-circle text-muted"></i>
                                    </th>
                                    <th class="text-center" data-bs-toggle="tooltip" title="Adjusted R²: R² yang disesuaikan dengan derajat kebebasan untuk menghindari overfitting.">
                                        Adjusted R² <i class="bi bi-info-circle text-muted"></i>
                                    </th>
                                    <th class="text-center" data-bs-toggle="tooltip" title="F-Statistic: Menguji signifikansi pengaruh variabel independen secara simultan.">
                                        F-Statistic <i class="bi bi-info-circle text-muted"></i>
                                    </th>
                                    <th class="text-center" data-bs-toggle="tooltip" title="P-Value (F-Stat): Nilai signifikansi. Model dianggap signifikan secara statistik jika P-Value < 0.05.">
                                        P-Value (F) <i class="bi bi-info-circle text-muted"></i>
                                    </th>
                                    <th class="text-center" data-bs-toggle="tooltip" title="Status Kelayakan: Layak jika F-statistic signifikan secara statistik (P-Value < 0.05).">
                                        Status Model <i class="bi bi-info-circle text-muted"></i>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($model_eval as $row):
                                    $is_layak = strtolower($row['status_model_elastisitas']) === 'layak';
                                ?>
                                <tr>
                                    <td class="fw-semibold text-slate-700"><?php echo htmlspecialchars($row['produk']); ?></td>
                                    <td class="text-center font-monospace"><?php echo number_format($row['r2_elastisitas'], 4, ',', '.'); ?></td>
                                    <td class="text-center font-monospace"><?php echo number_format($row['adj_r2_elastisitas'], 4, ',', '.'); ?></td>
                                    <td class="text-center font-monospace"><?php echo number_format($row['f_stat_elastisitas'], 2, ',', '.'); ?></td>
                                    <td class="text-center font-monospace <?php echo $row['p_value_elastisitas'] < 0.05 ? 'text-success fw-bold' : 'text-danger'; ?>">
                                        <?php echo number_format($row['p_value_elastisitas'], 6, ',', '.'); ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($is_layak): ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1"><i class="bi bi-check-circle-fill me-1"></i>Layak</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1"><i class="bi bi-x-circle-fill me-1"></i>Tidak Layak</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Auto interpretation list -->
                    <div class="bg-light p-3 rounded border">
                        <h6 class="fw-bold text-xs text-slate-700 mb-2"><i class="bi bi-chat-left-text-fill text-info me-1"></i>Interpretasi Otomatis Kelayakan Model:</h6>
                        <ul class="list-unstyled mb-0 d-flex flex-column gap-2 text-xs">
                            <?php foreach ($model_eval as $row):
                                $p_val_percent = round($row['r2_elastisitas'] * 100, 1);
                                $is_layak = strtolower($row['status_model_elastisitas']) === 'layak';
                            ?>
                            <li class="d-flex align-items-start gap-2">
                                <i class="bi <?php echo $is_layak ? 'bi-check-circle-fill text-success' : 'bi-x-circle-fill text-danger'; ?> mt-0.5"></i>
                                <div>
                                    Model untuk <strong>produk <?php echo htmlspecialchars($row['produk']); ?></strong> memiliki nilai <strong>R² sebesar <?php echo number_format($row['r2_elastisitas'], 2, ',', '.'); ?></strong> yang menunjukkan bahwa <strong><?php echo $p_val_percent; ?>%</strong> variasi permintaan dapat dijelaskan oleh perubahan harga. 
                                    <?php if ($is_layak): ?>
                                        Dengan p-value &lt; 0.05, model ini <span class="text-success fw-bold">signifikan secara statistik</span> dan <span class="badge bg-success-subtle text-success border border-success-subtle">Layak</span> digunakan untuk simulasi penetapan harga.
                                    <?php else: ?>
                                        Dengan p-value &ge; 0.05, model ini <span class="text-danger fw-bold">tidak signifikan secara statistik</span> dan <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Tidak Layak</span> secara empiris, disarankan menggunakan data histori lebih panjang.
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
     CHART SCRIPT
     ============================================================ -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const labels = <?php echo json_encode($product_short); ?>;
    const eVals  = <?php echo json_encode($elasticity_vals); ?>;
    const bColors = <?php echo json_encode($bar_colors); ?>;
    const bBorder = <?php echo json_encode($bar_border); ?>;

    new Chart(document.getElementById('elasticityBarChart'), {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: 'Koefisien Elastisitas (E)',
                data: eVals,
                backgroundColor: bColors,
                borderColor: bBorder,
                borderWidth: 1,
                borderRadius: 6
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: ctx => 'E = ' + ctx.raw.toFixed(2) + (Math.abs(ctx.raw) > 1 ? ' (Elastis)' : ' (Inelastis)') } },
                annotation: {}
            },
            scales: {
                x: {
                    title: { display: true, text: 'Koefisien Elastisitas (E)', font: { weight: '600' } },
                    grid: { color: '#f1f5f9' },
                    ticks: {
                        callback: v => v.toFixed(1)
                    }
                },
                y: { grid: { display: false } }
            }
        }
    });
});
</script>
