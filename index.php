<?php
/**
 * Application Router and Layout Controller
 * DSS Harga Popok — Toko Virend
 */

require_once __DIR__ . '/includes/helper.php';

// Route detection
$page = isset($_GET['page']) ? trim($_GET['page']) : 'dashboard';
$allowed_pages = ['dashboard', 'eda', 'elastisitas', 'forecast', 'simulasi', 'optimasi', 'laporan', 'datasource'];

if (!in_array($page, $allowed_pages, true)) {
    $page = 'dashboard';
}

// Dynamic title
$page_titles = [
    'dashboard'  => 'Dashboard Ringkasan',
    'eda'        => 'Exploratory Data Analysis',
    'elastisitas'=> 'Analisis Elastisitas Harga',
    'forecast'   => 'Forecast Permintaan 90 Hari',
    'simulasi'   => 'Simulasi Kenaikan & Penurunan Harga',
    'optimasi'   => 'Optimasi Harga & Pendapatan',
    'laporan'    => 'Laporan Analisis Lengkap',
    'datasource' => 'Data Source Manager',
];
$page_title = $page_titles[$page] ?? '"TOKO VIREND" - elastisitas dan simulasi';

// Page icons
$page_icons = [
    'dashboard'  => 'bi-grid-1x2-fill',
    'eda'        => 'bi-bar-chart-steps',
    'elastisitas'=> 'bi-percent',
    'forecast'   => 'bi-graph-up-arrow',
    'simulasi'   => 'bi-sliders2',
    'optimasi'   => 'bi-stars',
    'laporan'    => 'bi-file-earmark-pdf-fill',
    'datasource' => 'bi-database-fill-gear',
];
$page_icon = $page_icons[$page] ?? 'bi-circle';

// Load layout
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
?>

<!-- ============================================================
     CONTENT WRAPPER
     ============================================================ -->
<div id="content">

    <!-- Topbar -->
    <header class="topbar d-flex justify-content-between align-items-center no-print">
        <div class="d-flex align-items-center gap-3">
            <!-- Mobile Sidebar Toggle -->
            <button type="button" id="sidebarToggleBtn" class="btn-sidebar-toggle d-lg-none" aria-label="Toggle sidebar">
                <i class="bi bi-list"></i>
            </button>
            <!-- Page Title -->
            <div class="d-flex align-items-center gap-2">
                <i class="bi <?php echo $page_icon; ?> text-primary"></i>
                <h4 class="mb-0"><?php echo htmlspecialchars($page_title); ?></h4>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <?php $ds_info = load_dataset_info(); ?>
            <span class="topbar-badge topbar-badge-filename d-none d-sm-inline-flex" data-bs-toggle="tooltip" data-bs-placement="bottom" title="CSV · <?php echo htmlspecialchars($ds_info['filename']); ?> (Upload: <?php echo htmlspecialchars($ds_info['upload_time']); ?>)">
                <i class="bi bi-database me-1 text-primary"></i> 
                CSV · <?php echo htmlspecialchars($ds_info['filename']); ?>
            </span>
            <span class="topbar-badge d-none d-md-inline-flex">
                <i class="bi bi-table me-1 text-success"></i> 
                <?php echo number_format($ds_info['total_records'], 0, ',', '.'); ?> Baris
            </span>
            <span class="topbar-badge d-none d-lg-inline-flex" id="realtime-clock">
                <i class="bi bi-calendar3 me-1 text-info"></i>
                <?php echo format_date_indonesian('now', true); ?>
            </span>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        <?php
        $page_file = __DIR__ . "/pages/{$page}.php";
        if (file_exists($page_file)) {
            include $page_file;
        } else {
            echo '<div class="alert alert-danger rounded-3 d-flex align-items-center gap-2">
                    <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                    <div>Halaman <strong>' . htmlspecialchars($page) . '</strong> tidak ditemukan.</div>
                  </div>';
        }
        ?>
    </main>

    <!-- Footer -->
    <footer class="footer text-center no-print">
        <span>© 2026 <strong>"TOKO VIREND"</strong> · Elastisitas dan Simulasi Harga Popok Berbasis Regresi Log-Log</span>
    </footer>

</div><!-- /#content -->

<?php include __DIR__ . '/includes/footer.php'; ?>
