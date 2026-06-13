<?php
/**
 * Export PDF — Print-friendly Laporan Analisis
 * DSS Harga Popok Toko Virend
 *
 * This page renders a printer-optimized HTML report.
 * Open in browser and use Ctrl+P → Save as PDF.
 */

require_once __DIR__ . '/../includes/helper.php';

$data           = load_csv_data();
$stats          = get_summary_stats($data);
$total_baseline = get_total_revenue_baseline($data);
$revenue_gain   = $stats['total_revenue_maksimum'] - $total_baseline;
$gain_pct       = $total_baseline > 0 ? ($revenue_gain / $total_baseline) * 100 : 0;
$today          = format_date_indonesian('now');
$gen_time       = date('H:i');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan "TOKO VIREND" — Elastisitas &amp; Simulasi</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Outfit', sans-serif;
            font-size: 11pt;
            color: #1e293b;
            background: white;
            padding: 24mm 20mm;
        }

        /* ── Header ────────────────────────────── */
        .report-header {
            text-align: center;
            border-bottom: 3px solid #4f46e5;
            padding-bottom: 16px;
            margin-bottom: 24px;
        }
        .report-header .logo-box {
            width: 52px; height: 52px;
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            border-radius: 12px;
            display: inline-flex; align-items: center; justify-content: center;
            margin-bottom: 10px;
            color: white; font-size: 22px;
        }
        .report-header h1 { font-size: 16pt; font-weight: 800; color: #1e293b; }
        .report-header h2 { font-size: 12pt; font-weight: 600; color: #4f46e5; margin: 4px 0; }
        .report-header p  { font-size: 9pt; color: #64748b; }

        /* ── Sections ───────────────────────────── */
        .section-title {
            font-size: 12pt;
            font-weight: 700;
            color: #1e293b;
            border-left: 4px solid #4f46e5;
            padding-left: 10px;
            margin: 20px 0 10px;
            page-break-after: avoid;
        }

        /* ── Stat Grid ───────────────────────────── */
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            margin-bottom: 20px;
        }

        .stat-box {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 12px;
            text-align: center;
            background: #f8fafc;
        }

        .stat-box .val { font-size: 14pt; font-weight: 800; color: #4f46e5; }
        .stat-box .lbl { font-size: 7.5pt; color: #64748b; font-weight: 600; text-transform: uppercase; }

        /* ── Tables ─────────────────────────────── */
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9pt;
            margin-bottom: 16px;
            page-break-inside: avoid;
        }

        thead th {
            background: #1e293b;
            color: white;
            padding: 7px 8px;
            text-align: left;
            font-weight: 700;
            font-size: 8pt;
        }

        tbody tr:nth-child(even) { background: #f8fafc; }
        tbody tr:hover { background: #f1f5f9; }

        tbody td {
            padding: 7px 8px;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: middle;
        }

        tfoot td {
            padding: 7px 8px;
            background: #e2e8f0;
            font-weight: 700;
            border-top: 2px solid #cbd5e1;
        }

        .text-right  { text-align: right; }
        .text-center { text-align: center; }
        .text-green  { color: #047857; font-weight: 700; }
        .text-red    { color: #b91c1c; font-weight: 700; }
        .text-blue   { color: #3730a3; font-weight: 700; }
        .text-muted  { color: #64748b; }

        .badge {
            display: inline-block;
            padding: 2px 7px;
            border-radius: 10px;
            font-size: 7.5pt;
            font-weight: 700;
        }
        .badge-e { background: #fee2e2; color: #b91c1c; }
        .badge-i { background: #d1fae5; color: #047857; }

        /* ── Methodology Box ─────────────────────── */
        .method-box {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 14px 16px;
            background: #f8fafc;
            margin-bottom: 16px;
        }
        .method-box h4 { font-size: 10pt; font-weight: 700; color: #1e293b; margin-bottom: 8px; }
        .method-box p  { font-size: 9pt;  color: #64748b; margin-bottom: 6px; }
        .formula-box {
            background: white;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 8px 12px;
            text-align: center;
            font-weight: 700;
            font-size: 10pt;
            color: #1e293b;
            margin: 8px 0;
        }

        /* ── Summary Box ─────────────────────────── */
        .summary-box {
            background: linear-gradient(135deg, #d1fae5, #a7f3d0);
            border: 1px solid #6ee7b7;
            border-radius: 10px;
            padding: 16px;
            margin-top: 20px;
        }
        .summary-box h3 { font-size: 11pt; font-weight: 700; color: #047857; margin-bottom: 8px; }
        .summary-box p  { font-size: 9.5pt; color: #065f46; }

        /* ── Footer ──────────────────────────────── */
        .report-footer {
            margin-top: 24px;
            padding-top: 12px;
            border-top: 1px solid #e2e8f0;
            text-align: center;
            font-size: 8.5pt;
            color: #94a3b8;
        }

        /* ── Print ───────────────────────────────── */
        @media print {
            body { padding: 12mm 15mm; }
            .no-print { display: none !important; }
            .page-break { page-break-before: always; }
        }

        /* ── Print button ────────────────────────── */
        .print-btn-bar {
            position: fixed;
            bottom: 20px; right: 20px;
            display: flex; gap: 10px;
            z-index: 99;
        }
        .btn-print, .btn-back {
            padding: 10px 20px;
            border-radius: 8px;
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            font-size: 13px;
            cursor: pointer;
            border: none;
        }
        .btn-print { background: #4f46e5; color: white; }
        .btn-back  { background: #f1f5f9; color: #475569; }
        .btn-print:hover { background: #3730a3; }
        .btn-back:hover  { background: #e2e8f0; }
        @media print { .print-btn-bar { display: none; } }
    </style>
</head>
<body>

<!-- ── Report Header ──────────────────────────────────────── -->
<div class="report-header">
    <div class="logo-box">📊</div>
    <h1>LAPORAN ANALISIS ELASTISITAS DAN OPTIMASI HARGA PRODUK POPOK</h1>
    <h2>"TOKO VIREND" — Elastisitas &amp; Simulasi</h2>
    <p>
        "Regresi Log-Log Untuk Elastisitas dan Simulasi Kenaikan Harga Produk Popok
        Berdasarkan Data Penjualan Time Series Pada Toko Virend"
        <br>Digenerate: <?php echo $today; ?>, Pukul <?php echo $gen_time; ?> WIB
    </p>
</div>

<!-- ── Stat Summary ───────────────────────────────────────── -->
<div class="section-title">Ringkasan Eksekutif</div>
<div class="stat-grid">
    <div class="stat-box">
        <div class="val"><?php echo $stats['jumlah_produk']; ?></div>
        <div class="lbl">Jumlah Produk</div>
    </div>
    <div class="stat-box">
        <div class="val" style="font-size:10pt;"><?php echo format_rupiah($total_baseline); ?></div>
        <div class="lbl">Revenue Saat Ini</div>
    </div>
    <div class="stat-box">
        <div class="val" style="font-size:10pt;color:#047857;"><?php echo format_rupiah($stats['total_revenue_maksimum']); ?></div>
        <div class="lbl">Revenue Optimal</div>
    </div>
    <div class="stat-box">
        <div class="val" style="color:#047857;">+<?php echo number_format($gain_pct, 1, ',', '.'); ?>%</div>
        <div class="lbl">Revenue Lift</div>
    </div>
</div>

<!-- ── Comprehensive Table ─────────────────────────────────── -->
<div class="section-title">Tabel Hasil Analisis Komprehensif</div>
<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Nama Produk</th>
            <th class="text-center">E</th>
            <th class="text-center">Kategori</th>
            <th class="text-right">Forecast Qty</th>
            <th class="text-right">Harga Saat Ini</th>
            <th class="text-right">Harga Optimal</th>
            <th class="text-center">Δ Harga</th>
            <th class="text-right">Qty Optimal</th>
            <th class="text-right">Rev. Saat Ini</th>
            <th class="text-right">Rev. Maksimum</th>
            <th class="text-right">Δ Revenue</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($data as $idx => $item):
            $rev_now  = $item['harga_saat_ini'] * $item['qty_forecast'];
            $rev_diff = $item['revenue_maksimum'] - $rev_now;
            $dp       = (($item['harga_optimal'] - $item['harga_saat_ini']) / $item['harga_saat_ini']) * 100;
            $isElast  = strtolower(trim($item['kategori'])) === 'elastis';
        ?>
        <tr>
            <td><?php echo $idx + 1; ?></td>
            <td><strong><?php echo htmlspecialchars($item['produk']); ?></strong></td>
            <td class="text-center <?php echo $isElast ? 'text-red' : 'text-green'; ?>">
                <?php echo number_format($item['elastisitas'], 2, ',', '.'); ?>
            </td>
            <td class="text-center">
                <span class="badge <?php echo $isElast ? 'badge-e' : 'badge-i'; ?>">
                    <?php echo $isElast ? 'Elastis' : 'Inelastis'; ?>
                </span>
            </td>
            <td class="text-right"><?php echo format_number($item['qty_forecast']); ?> Pcs</td>
            <td class="text-right text-muted"><?php echo format_rupiah($item['harga_saat_ini']); ?></td>
            <td class="text-right text-blue"><?php echo format_rupiah($item['harga_optimal']); ?></td>
            <td class="text-center <?php echo $dp > 0 ? 'text-red' : 'text-green'; ?>">
                <?php echo ($dp >= 0 ? '+' : '') . number_format($dp, 1, ',', '.') . '%'; ?>
            </td>
            <td class="text-right"><?php echo format_number($item['qty_optimal']); ?> Pcs</td>
            <td class="text-right text-muted"><?php echo format_rupiah($rev_now); ?></td>
            <td class="text-right text-green"><?php echo format_rupiah($item['revenue_maksimum']); ?></td>
            <td class="text-right <?php echo $rev_diff >= 0 ? 'text-green' : 'text-red'; ?>">
                <?php echo ($rev_diff >= 0 ? '+' : '') . format_rupiah($rev_diff); ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
    <tfoot>
        <tr>
            <td colspan="4" class="text-center">TOTAL</td>
            <td class="text-right"><?php echo format_number($stats['total_forecast_90_hari']); ?> Pcs</td>
            <td class="text-right">—</td>
            <td class="text-right">—</td>
            <td class="text-center text-green">+<?php echo number_format($gain_pct, 1, ',', '.'); ?>%</td>
            <td class="text-right">—</td>
            <td class="text-right"><?php echo format_rupiah($total_baseline); ?></td>
            <td class="text-right text-green"><?php echo format_rupiah($stats['total_revenue_maksimum']); ?></td>
            <td class="text-right text-green">+<?php echo format_rupiah($revenue_gain); ?></td>
        </tr>
    </tfoot>
</table>

<!-- ── Methodology ────────────────────────────────────────── -->
<div class="section-title page-break">Metodologi Penelitian</div>
<div class="method-box">
    <h4>Model Regresi Log-Log</h4>
    <p>Model matematika yang digunakan untuk mengestimasi elastisitas harga permintaan produk popok:</p>
    <div class="formula-box">ln(Q) = α + E · ln(P) + ε</div>
    <p>Di mana Q = kuantitas, P = harga, E = koefisien elastisitas, α = intercept, ε = error term.</p>

    <h4 style="margin-top:12px;">Formula Simulasi & Optimasi</h4>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:8px;">
        <div>
            <p style="font-size:8.5pt;margin-bottom:4px;"><strong>Simulasi Permintaan:</strong></p>
            <div class="formula-box">Q_baru = Q_forecast × (P_baru / P₀)^E</div>
        </div>
        <div>
            <p style="font-size:8.5pt;margin-bottom:4px;"><strong>Revenue & Optimasi:</strong></p>
            <div class="formula-box">R = P × Q(P) → P* = argmax R(P)</div>
        </div>
    </div>
</div>

<!-- ── Conclusion ─────────────────────────────────────────── -->
<div class="section-title">Kesimpulan & Rekomendasi</div>

<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Produk</th>
            <th class="text-center">Kategori</th>
            <th class="text-center">Koefisien E</th>
            <th class="text-right">Harga Saat Ini</th>
            <th class="text-right">Harga Optimal</th>
            <th class="text-center">Rekomendasi</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($data as $idx => $item):
            $isElast = strtolower(trim($item['kategori'])) === 'elastis';
            $rek = $isElast ? '↓ Turunkan Harga' : '↑ Naikkan Harga';
        ?>
        <tr>
            <td><?php echo $idx + 1; ?></td>
            <td><strong><?php echo htmlspecialchars($item['produk']); ?></strong></td>
            <td class="text-center">
                <span class="badge <?php echo $isElast ? 'badge-e' : 'badge-i'; ?>">
                    <?php echo $isElast ? 'Elastis' : 'Inelastis'; ?>
                </span>
            </td>
            <td class="text-center <?php echo $isElast ? 'text-red' : 'text-green'; ?>">
                <?php echo number_format($item['elastisitas'], 2, ',', '.'); ?>
            </td>
            <td class="text-right text-muted"><?php echo format_rupiah($item['harga_saat_ini']); ?></td>
            <td class="text-right text-blue"><strong><?php echo format_rupiah($item['harga_optimal']); ?></strong></td>
            <td class="text-center <?php echo $isElast ? 'text-red' : 'text-green'; ?>"><?php echo $rek; ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<!-- ── Summary Box ────────────────────────────────────────── -->
<div class="summary-box">
    <h3>💡 Potensi Revenue Lift</h3>
    <p>
        Dengan menerapkan seluruh rekomendasi harga optimal berbasis model regresi log-log,
        Toko Virend berpotensi meningkatkan total proyeksi pendapatan penjualan produk popok bayi
        dari <strong><?php echo format_rupiah($total_baseline); ?></strong> menjadi
        <strong><?php echo format_rupiah($stats['total_revenue_maksimum']); ?></strong>
        — sebuah peningkatan sebesar
        <strong>+<?php echo format_rupiah($revenue_gain); ?> (+<?php echo number_format($gain_pct, 1, ',', '.'); ?>%)</strong>
        dalam periode 90 hari ke depan.
    </p>
</div>

<!-- ── Footer ─────────────────────────────────────────────── -->
<div class="report-footer">
    <p>
        Laporan ini merupakan output dari Sistem Pendukung Keputusan (DSS) berbasis Regresi Log-Log — Toko Virend &nbsp;|&nbsp;
        © 2026 &nbsp;|&nbsp; Digenerate: <?php echo $today; ?>, Pukul <?php echo $gen_time; ?> WIB
    </p>
</div>

<!-- ── Print Buttons ──────────────────────────────────────── -->
<div class="print-btn-bar no-print">
    <button class="btn-back" onclick="window.history.back()">← Kembali</button>
    <button class="btn-print" onclick="window.print()">🖨️ Print / Save PDF</button>
</div>

<script>
// Auto trigger print dialog
window.addEventListener('load', function() {
    // Small delay to ensure fonts loaded
    setTimeout(() => {
        // Uncomment below to auto-open print dialog:
        // window.print();
    }, 800);
});
</script>
</body>
</html>
