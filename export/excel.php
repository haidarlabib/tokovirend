<?php
/**
 * Export to Excel — DSS Harga Popok Toko Virend
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/helper.php';

$data = load_csv_data();
$stats = get_summary_stats($data);
$total_baseline = get_total_revenue_baseline($data);
$revenue_gain = $stats['total_revenue_maksimum'] - $total_baseline;
$gain_pct = $total_baseline > 0 ? ($revenue_gain / $total_baseline) * 100 : 0;

header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=Laporan_DSS_Toko_Virend_" . date('Ymd_His') . ".xls");
header("Pragma: no-cache");
header("Expires: 0");

echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
echo '<head><meta http-equiv="Content-type" content="text/html;charset=utf-8" /></head>';
echo '<body>';
echo '<table border="1">';
echo '<tr><th colspan="12" style="font-size:16pt; font-weight:bold; text-align:center;">LAPORAN HASIL ANALISIS ELASTISITAS DAN OPTIMASI HARGA PRODUK POPOK</th></tr>';
echo '<tr><th colspan="12" style="font-size:12pt; font-weight:bold; text-align:center;">"TOKO VIREND" — Elastisitas &amp; Simulasi</th></tr>';
echo '<tr><td colspan="12" style="text-align:center; font-style:italic;">Digenerate tanggal: ' . format_date_indonesian('now') . ' ' . date('H:i') . ' WIB</td></tr>';
echo '<tr><td colspan="12"></td></tr>';
echo '<tr style="background-color:#f2f2f2; font-weight:bold;">';
echo '<th>No</th>';
echo '<th>Nama Produk</th>';
echo '<th>Elastisitas (E)</th>';
echo '<th>Kategori</th>';
echo '<th>Forecast Qty (90 Hari)</th>';
echo '<th>Harga Saat Ini (Rp)</th>';
echo '<th>Harga Optimal (Rp)</th>';
echo '<th>Perubahan Harga (%)</th>';
echo '<th>Qty Optimal (90 Hari)</th>';
echo '<th>Revenue Baseline (Rp)</th>';
echo '<th>Revenue Maksimum (Rp)</th>';
echo '<th>Delta Revenue (Rp)</th>';
echo '</tr>';

foreach ($data as $idx => $item) {
    $rev_now  = $item['harga_saat_ini'] * $item['qty_forecast'];
    $rev_diff = $item['revenue_maksimum'] - $rev_now;
    $dp       = (($item['harga_optimal'] - $item['harga_saat_ini']) / $item['harga_saat_ini']) * 100;
    
    echo '<tr>';
    echo '<td>' . ($idx + 1) . '</td>';
    echo '<td>' . htmlspecialchars($item['produk']) . '</td>';
    echo '<td>' . number_format($item['elastisitas'], 4, ',', '.') . '</td>';
    echo '<td>' . htmlspecialchars($item['kategori']) . '</td>';
    echo '<td>' . $item['qty_forecast'] . '</td>';
    echo '<td>' . $item['harga_saat_ini'] . '</td>';
    echo '<td>' . $item['harga_optimal'] . '</td>';
    echo '<td>' . number_format($dp, 1, ',', '.') . '%</td>';
    echo '<td>' . $item['qty_optimal'] . '</td>';
    echo '<td>' . $rev_now . '</td>';
    echo '<td>' . $item['revenue_maksimum'] . '</td>';
    echo '<td>' . $rev_diff . '</td>';
    echo '</tr>';
}

echo '<tr style="font-weight:bold; background-color:#e6e6e6;">';
echo '<td colspan="4" style="text-align:center;">TOTAL / RATA-RATA</td>';
echo '<td>' . $stats['total_forecast_90_hari'] . '</td>';
echo '<td>-</td>';
echo '<td>-</td>';
echo '<td>' . number_format($gain_pct, 1, ',', '.') . '%</td>';
echo '<td>-</td>';
echo '<td>' . $total_baseline . '</td>';
echo '<td>' . $stats['total_revenue_maksimum'] . '</td>';
echo '<td>' . $revenue_gain . '</td>';
echo '</tr>';

echo '</table>';
echo '</body>';
echo '</html>';
?>
