<?php
$current_page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
?>
<!-- ============================================================
     SIDEBAR NAVIGATION — DSS Harga Popok Toko Virend
     ============================================================ -->
<nav id="sidebar">

    <!-- Brand Header -->
    <div class="sidebar-header">
        <div class="sidebar-brand-icon">
            <i class="bi bi-clipboard2-data"></i>
        </div>
        <div class="sidebar-brand-text">
            <h6>TOKO VIREND</h6>
            <small>Elastisitas &amp; Simulasi</small>
        </div>
    </div>

    <!-- Navigation Items -->
    <ul class="sidebar-nav">
        <!-- Overview Group -->
        <li class="sidebar-section-label">Menu Utama</li>

        <li class="<?php echo $current_page === 'datasource' ? 'active' : ''; ?>">
            <a href="index.php?page=datasource">
                <i class="bi bi-database-fill-gear nav-icon"></i>
                Data Source Manager
            </a>
        </li>

        <li class="<?php echo $current_page === 'dashboard' ? 'active' : ''; ?>">
            <a href="index.php?page=dashboard">
                <i class="bi bi-grid-1x2-fill nav-icon"></i>
                Dashboard
            </a>
        </li>

        <li class="<?php echo $current_page === 'eda' ? 'active' : ''; ?>">
            <a href="index.php?page=eda">
                <i class="bi bi-bar-chart-steps nav-icon"></i>
                Exploratory Data Analysis
                <span class="nav-badge">EDA</span>
            </a>
        </li>

        <hr class="sidebar-divider">
        <li class="sidebar-section-label">Analisis</li>

        <li class="<?php echo $current_page === 'elastisitas' ? 'active' : ''; ?>">
            <a href="index.php?page=elastisitas">
                <i class="bi bi-percent nav-icon"></i>
                Analisis Elastisitas
            </a>
        </li>

        <li class="<?php echo $current_page === 'forecast' ? 'active' : ''; ?>">
            <a href="index.php?page=forecast">
                <i class="bi bi-graph-up-arrow nav-icon"></i>
                Forecast Permintaan
            </a>
        </li>

        <hr class="sidebar-divider">
        <li class="sidebar-section-label">Pengambilan Keputusan</li>

        <li class="<?php echo $current_page === 'simulasi' ? 'active' : ''; ?>">
            <a href="index.php?page=simulasi">
                <i class="bi bi-sliders2 nav-icon"></i>
                Simulasi Harga
            </a>
        </li>

        <li class="<?php echo $current_page === 'optimasi' ? 'active' : ''; ?>">
            <a href="index.php?page=optimasi">
                <i class="bi bi-stars nav-icon"></i>
                Optimasi Harga
            </a>
        </li>

        <hr class="sidebar-divider">

        <li class="<?php echo $current_page === 'laporan' ? 'active' : ''; ?>">
            <a href="index.php?page=laporan">
                <i class="bi bi-file-earmark-pdf-fill nav-icon"></i>
                Laporan Analisis
            </a>
        </li>
    </ul>

    <!-- Sidebar Footer -->
    <div class="sidebar-footer">
        <p>
            <i class="bi bi-mortarboard me-1"></i> Skripsi 2026<br>
            Regresi Log-Log &amp; Elastisitas Harga
        </p>
    </div>
</nav>
