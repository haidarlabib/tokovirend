<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sistem Pendukung Keputusan Penentuan Harga Produk Popok Berdasarkan Regresi Log-Log — Toko Virend">
    <meta name="author" content="Toko Virend Research">
    <meta name="keywords" content="regresi log-log, elastisitas harga, popok, DSS, sistem pendukung keputusan">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) . ' — "TOKO VIREND" - elastisitas dan simulasi' : '"TOKO VIREND" - elastisitas dan simulasi'; ?></title>

    <!-- Preconnect for faster font load -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

    <!-- Custom CSS (loaded last to override) -->
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="wrapper">
<!-- Sidebar Overlay (mobile) -->
<div id="sidebarOverlay" class="sidebar-overlay"></div>
