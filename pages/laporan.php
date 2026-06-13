<?php
/**
 * Laporan Analisis Lengkap — DSS Harga Popok Toko Virend
 */

$data           = load_csv_data();
$stats          = get_summary_stats($data);
$total_baseline = get_total_revenue_baseline($data);
$revenue_gain   = $stats['total_revenue_maksimum'] - $total_baseline;
$gain_pct       = $total_baseline > 0 ? ($revenue_gain / $total_baseline) * 100 : 0;
$desc_stats     = get_all_descriptive_stats($data);
$today          = format_date_indonesian('now');
$gen_time       = date('H:i');
?>

<!-- ============================================================
     PRINT-ONLY HEADER
     ============================================================ -->
<div class="print-only print-header">
    <h2>LAPORAN ANALISIS ELASTISITAS DAN OPTIMASI HARGA PRODUK POPOK</h2>
    <h3 style="font-size:12pt;font-weight:600;">"TOKO VIREND" — Elastisitas dan Simulasi</h3>
    <p>Berbasis Model Regresi Log-Log | Digenerate: <?php echo $today; ?>, Pukul <?php echo $gen_time; ?> WIB</p>
    <p style="font-size:9pt;font-style:italic;">
        "Regresi Log-Log Untuk Elastisitas dan Simulasi Kenaikan Harga Produk Popok
        Berdasarkan Data Penjualan Time Series Pada Toko Virend"
    </p>
</div>

<!-- ============================================================
     TOPBAR ACTIONS
     ============================================================ -->
<div class="row mb-4 anim-fade-in-up no-print">
    <div class="col-12">
        <div class="dashboard-card">
            <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <h5 class="fw-bold mb-1 text-slate-800">
                        <i class="bi bi-file-earmark-text-fill text-primary me-2"></i>
                        Laporan Rekomendasi Kebijakan Harga
                    </h5>
                    <p class="text-slate-500 text-xs mb-0">
                        Rangkuman hasil analisis EDA, elastisitas, forecast, simulasi, dan optimasi harga popok bayi
                        di Toko Virend. Digenerate: <?php echo $today; ?>.
                    </p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="export/excel.php" class="btn btn-success">
                        <i class="bi bi-file-earmark-excel me-2"></i>Export Excel
                    </a>
                    <a href="export/pdf.php" target="_blank" class="btn btn-primary">
                        <i class="bi bi-file-earmark-pdf me-2"></i>Export PDF
                    </a>
                    <button onclick="window.print()" class="btn btn-outline-secondary">
                        <i class="bi bi-printer me-2"></i>Print Laporan
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================
     RINGKASAN EKSEKUTIF
     ============================================================ -->
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="dashboard-card anim-fade-in-up">
            <div class="card-header">
                <h5><i class="bi bi-bookmark-star-fill text-warning me-2"></i>Ringkasan Eksekutif</h5>
                <span class="text-xs text-slate-500">Executive Summary</span>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-6 col-md-3 border-md-end">
                        <div class="text-xs text-slate-500 mb-1">Jumlah Produk Teranalisis</div>
                        <div class="fs-5 fw-bold text-slate-800"><?php echo $stats['jumlah_produk']; ?> Item</div>
                    </div>
                    <div class="col-6 col-md-3 border-md-end">
                        <div class="text-xs text-slate-500 mb-1">Proyeksi Revenue Saat Ini</div>
                        <div class="fs-5 fw-bold text-slate-600 text-decoration-line-through"><?php echo format_rupiah($total_baseline); ?></div>
                    </div>
                    <div class="col-6 col-md-3 border-md-end">
                        <div class="text-xs text-slate-500 mb-1">Proyeksi Revenue Optimal</div>
                        <div class="fs-5 fw-bold text-success"><?php echo format_rupiah($stats['total_revenue_maksimum']); ?></div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="text-xs text-slate-500 mb-1">Estimasi Revenue Lift</div>
                        <div class="fs-5 fw-bold text-primary">
                            +<?php echo format_rupiah($revenue_gain); ?>
                            <span class="text-xs text-success">(+<?php echo number_format($gain_pct, 1, ',', '.'); ?>%)</span>
                        </div>
                    </div>
                </div>
                <hr class="my-3">
                <p class="text-slate-600 text-sm mb-0">
                    Analisis menggunakan model <strong>regresi log-log</strong> pada data penjualan time series Toko Virend
                    mengidentifikasi <strong><?php echo $stats['elastic_count']; ?> produk elastis</strong> (sensitif terhadap harga)
                    dan <strong><?php echo $stats['inelastic_count']; ?> produk inelastis</strong> (kurang sensitif terhadap harga).
                    Rata-rata koefisien elastisitas <em>E = <?php echo number_format($stats['rata_rata_elastisitas'], 2, ',', '.'); ?></em>,
                    menunjukkan produk secara keseluruhan bersifat elastis. Penerapan strategi harga optimal berpotensi
                    meningkatkan total proyeksi pendapatan 90 hari sebesar <strong><?php echo format_rupiah($revenue_gain); ?></strong>.
                </p>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================
     TABEL KOMPREHENSIF
     ============================================================ -->
<div class="row g-4 mb-4">
    <div class="col-12 anim-fade-in-up">
        <div class="dashboard-card">
            <div class="card-header">
                <h5><i class="bi bi-table text-primary me-2"></i>Tabel Hasil Analisis Komprehensif</h5>
                <span class="text-xs text-slate-500">Gabungan Elastisitas · Forecast · Optimasi</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table custom-table table-bordered datatable mb-0" id="reportTable">
                        <thead>
                            <tr class="bg-slate-50">
                                <th class="align-middle">#</th>
                                <th class="align-middle">Nama Produk</th>
                                <th class="align-middle text-center">Elastisitas</th>
                                <th class="align-middle text-center">Kategori</th>
                                <th class="align-middle text-end">Forecast Qty</th>
                                <th class="align-middle text-end">Harga Saat Ini</th>
                                <th class="align-middle text-end">Harga Optimal</th>
                                <th class="align-middle text-center">Δ Harga</th>
                                <th class="align-middle text-end">Qty Optimal</th>
                                <th class="align-middle text-end">Revenue Saat Ini</th>
                                <th class="align-middle text-end">Revenue Maks.</th>
                                <th class="align-middle text-end">Δ Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data as $idx => $item):
                                $rev_now  = $item['harga_saat_ini'] * $item['qty_forecast'];
                                $rev_diff = $item['revenue_maksimum'] - $rev_now;
                                $dp       = (($item['harga_optimal'] - $item['harga_saat_ini']) / $item['harga_saat_ini']) * 100;
                            ?>
                            <tr>
                                <td class="text-muted"><?php echo $idx + 1; ?></td>
                                <td class="fw-semibold text-slate-800 small"><?php echo htmlspecialchars($item['produk']); ?></td>
                                <td class="text-center fw-bold <?php echo strtolower($item['kategori']) === 'elastis' ? 'text-danger' : 'text-success'; ?>">
                                    <?php echo number_format($item['elastisitas'], 2, ',', '.'); ?>
                                </td>
                                <td class="text-center"><?php echo elasticity_badge($item['kategori']); ?></td>
                                <td class="text-end"><?php echo format_number($item['qty_forecast']); ?> Pcs</td>
                                <td class="text-end text-slate-600"><?php echo format_rupiah($item['harga_saat_ini']); ?></td>
                                <td class="text-end fw-semibold text-primary"><?php echo format_rupiah($item['harga_optimal']); ?></td>
                                <td class="text-center fw-bold <?php echo $dp > 0 ? 'text-danger' : 'text-success'; ?>">
                                    <?php echo ($dp >= 0 ? '+' : '') . number_format($dp, 1, ',', '.') . '%'; ?>
                                </td>
                                <td class="text-end"><?php echo format_number($item['qty_optimal']); ?> Pcs</td>
                                <td class="text-end text-slate-600"><?php echo format_rupiah($rev_now); ?></td>
                                <td class="text-end fw-bold text-success"><?php echo format_rupiah($item['revenue_maksimum']); ?></td>
                                <td class="text-end fw-bold <?php echo $rev_diff >= 0 ? 'text-success' : 'text-danger'; ?>">
                                    <?php echo ($rev_diff >= 0 ? '+' : '') . format_rupiah($rev_diff); ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="fw-bold">
                                <td colspan="4" class="text-center text-slate-700">TOTAL / RATA-RATA</td>
                                <td class="text-end"><?php echo format_number($stats['total_forecast_90_hari']); ?> Pcs</td>
                                <td class="text-end text-slate-500">—</td>
                                <td class="text-end text-slate-500">—</td>
                                <td class="text-center text-success">+<?php echo number_format($gain_pct, 1, ',', '.'); ?>%</td>
                                <td class="text-end text-slate-500">—</td>
                                <td class="text-end text-slate-700"><?php echo format_rupiah($total_baseline); ?></td>
                                <td class="text-end text-success"><?php echo format_rupiah($stats['total_revenue_maksimum']); ?></td>
                                <td class="text-end text-success">+<?php echo format_rupiah($revenue_gain); ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================
     METODOLOGI PENELITIAN
     ============================================================ -->
<div class="row g-4 mb-4">
    <div class="col-12 anim-fade-in-up">
        <div class="dashboard-card">
            <div class="card-header">
                <h5><i class="bi bi-journal-bookmark-fill text-primary me-2"></i>Metodologi Penelitian</h5>
                <span class="text-xs text-slate-500">Pendekatan Regresi Log-Log</span>
            </div>
            <div class="card-body">
                <div class="row g-4">
                    <div class="col-md-6">
                        <h6 class="fw-bold text-slate-700 mb-3"><i class="bi bi-1-circle-fill text-primary me-2"></i>Model Matematika</h6>
                        <div class="p-3 bg-slate-50 rounded-3 border">
                            <p class="text-xs text-slate-600 mb-2"><strong>Model Regresi Log-Log:</strong></p>
                            <div class="text-center fw-bold mb-2">
                                <code class="text-slate-800">ln(Q) = α + E · ln(P) + ε</code>
                            </div>
                            <p class="text-xs text-slate-500 mb-0">Di mana:</p>
                            <ul class="text-xs text-slate-500 mb-0">
                                <li><strong>Q</strong> = Kuantitas permintaan (unit terjual)</li>
                                <li><strong>P</strong> = Harga produk (Rp)</li>
                                <li><strong>E</strong> = Koefisien elastisitas harga</li>
                                <li><strong>α</strong> = Konstanta (intercept)</li>
                                <li><strong>ε</strong> = Error term</li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6 class="fw-bold text-slate-700 mb-3"><i class="bi bi-2-circle-fill text-primary me-2"></i>Formula Simulasi & Optimasi</h6>
                        <div class="p-3 bg-slate-50 rounded-3 border">
                            <p class="text-xs text-slate-600 mb-1"><strong>Simulasi Permintaan:</strong></p>
                            <div class="text-center fw-bold mb-2">
                                <code class="text-slate-800">Q<sub>baru</sub> = Q<sub>forecast</sub> × (P<sub>baru</sub>/P<sub>0</sub>)<sup>E</sup></code>
                            </div>
                            <p class="text-xs text-slate-600 mb-1"><strong>Revenue:</strong></p>
                            <div class="text-center fw-bold mb-2">
                                <code class="text-slate-800">R = P<sub>baru</sub> × Q<sub>baru</sub></code>
                            </div>
                            <p class="text-xs text-slate-600 mb-1"><strong>Harga Optimal:</strong></p>
                            <div class="text-center fw-bold">
                                <code class="text-slate-800">P* = argmax R(P)</code>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-3 mt-2">
                    <div class="col-md-4">
                        <div class="methodology-step">
                            <div class="step-number">1</div>
                            <div>
                                <strong class="text-sm">Pengumpulan Data</strong>
                                <p class="text-xs text-slate-500 mb-0">Data penjualan time series Toko Virend dikumpulkan mencakup harga, quantity, dan periode waktu per produk.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="methodology-step">
                            <div class="step-number">2</div>
                            <div>
                                <strong class="text-sm">Estimasi Model</strong>
                                <p class="text-xs text-slate-500 mb-0">Model log-log diestimasi dengan OLS setelah transformasi logaritmik, menghasilkan koefisien elastisitas (E) per produk.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="methodology-step">
                            <div class="step-number">3</div>
                            <div>
                                <strong class="text-sm">Optimasi & Rekomendasi</strong>
                                <p class="text-xs text-slate-500 mb-0">Harga optimal ditemukan secara numerik untuk memaksimumkan fungsi pendapatan R(P) = P × Q(P) berdasarkan koefisien E.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================
     REKOMENDASI AKHIR
     ============================================================ -->
<div class="row g-4">
    <div class="col-12 anim-fade-in-up">
        <div class="dashboard-card">
            <div class="card-header">
                <h5><i class="bi bi-check2-all text-success me-2"></i>Kesimpulan & Rekomendasi Kebijakan</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <!-- Elastic products -->
                    <div class="col-md-6">
                        <h6 class="fw-bold text-danger mb-2"><i class="bi bi-arrow-down-circle me-1"></i>Produk Elastis — Turunkan Harga</h6>
                        <?php foreach ($data as $item): if (strtolower(trim($item['kategori'])) !== 'elastis') continue; ?>
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span class="text-sm text-slate-700"><?php echo htmlspecialchars($item['produk']); ?></span>
                            <div class="text-end">
                                <del class="text-slate-400 text-xs"><?php echo format_rupiah($item['harga_saat_ini']); ?></del>
                                <strong class="text-success d-block"><?php echo format_rupiah($item['harga_optimal']); ?></strong>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <!-- Inelastic products -->
                    <div class="col-md-6">
                        <h6 class="fw-bold text-success mb-2"><i class="bi bi-arrow-up-circle me-1"></i>Produk Inelastis — Naikkan Harga</h6>
                        <?php foreach ($data as $item): if (strtolower(trim($item['kategori'])) !== 'inelastis') continue; ?>
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span class="text-sm text-slate-700"><?php echo htmlspecialchars($item['produk']); ?></span>
                            <div class="text-end">
                                <del class="text-slate-400 text-xs"><?php echo format_rupiah($item['harga_saat_ini']); ?></del>
                                <strong class="text-primary d-block"><?php echo format_rupiah($item['harga_optimal']); ?></strong>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="info-box info-success mt-4">
                    <i class="bi bi-rocket-takeoff-fill info-icon"></i>
                    <div>
                        <strong>Potensi Total Revenue Lift:</strong>
                        Dengan menerapkan seluruh rekomendasi harga optimal di atas, Toko Virend berpotensi
                        meningkatkan total proyeksi pendapatan penjualan popok bayi dari
                        <strong><?php echo format_rupiah($total_baseline); ?></strong> menjadi
                        <strong class="text-success"><?php echo format_rupiah($stats['total_revenue_maksimum']); ?></strong>
                        — sebuah peningkatan sebesar <strong>+<?php echo format_rupiah($revenue_gain); ?> (+<?php echo number_format($gain_pct, 1, ',', '.'); ?>%)</strong>
                        dalam periode 90 hari ke depan.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
