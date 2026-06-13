<?php
/**
 * Data Source Manager Page — DSS Harga Popok Toko Virend
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$upload_err = "";
$upload_success = "";

// Check for session flash messages
if (isset($_SESSION['upload_success'])) {
    $upload_success = $_SESSION['upload_success'];
    unset($_SESSION['upload_success']);
}
if (isset($_SESSION['upload_err'])) {
    $upload_err = $_SESSION['upload_err'];
    unset($_SESSION['upload_err']);
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'reset_default') {
            $_SESSION['use_uploaded'] = false;
            $_SESSION['upload_success'] = "Berhasil beralih ke Dataset Default (Data Repository).";
            header("Location: index.php?page=datasource");
            exit;
        } elseif ($_POST['action'] === 'upload_dataset') {
            if (isset($_FILES['dataset_file']) && $_FILES['dataset_file']['error'] === UPLOAD_ERR_OK) {
                $file_tmp = $_FILES['dataset_file']['tmp_name'];
                $file_name = $_FILES['dataset_file']['name'];
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                
                if ($file_ext !== 'xlsx') {
                    $_SESSION['upload_err'] = "Format file tidak didukung! Pastikan Anda meng-upload file Excel (.xlsx).";
                    header("Location: index.php?page=datasource");
                    exit;
                } else {
                    // Path setups
                    $active_dir = __DIR__ . '/../data/active';
                    if (!is_dir($active_dir)) {
                        mkdir($active_dir, 0777, true);
                    }
                    
                    $destination = $active_dir . '/data_penjualan.xlsx';
                    
                    if (move_uploaded_file($file_tmp, $destination)) {
                        // Send the file to Python API (Railway) instead of local exec()
                        $ch = curl_init();
                        curl_setopt($ch, CURLOPT_URL, PYTHON_API_URL);
                        curl_setopt($ch, CURLOPT_POST, true);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLOPT_TIMEOUT, 120); // 120 seconds timeout for regression calculations
                        
                        // Create CURLFile
                        $cfile = new CURLFile($destination, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'file');
                        
                        $post_data = [
                            'file' => $cfile,
                            'original_filename' => $file_name
                        ];
                        
                        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
                        
                        $response = curl_exec($ch);
                        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                        $curl_err = curl_error($ch);
                        curl_close($ch);
                        
                        if ($curl_err) {
                            $_SESSION['upload_err'] = "Gagal menghubungi Python API di Railway: " . $curl_err;
                            if (file_exists($destination)) {
                                @unlink($destination);
                            }
                            header("Location: index.php?page=datasource");
                            exit;
                        }
                        
                        $res_data = json_decode($response, true);
                        if ($http_code === 200 && isset($res_data['success']) && $res_data['success'] === true) {
                            $outputs = $res_data['outputs'];
                            
                            // Write returned outputs to local active data folder
                            file_put_contents($active_dir . '/hasil_forecast.csv', $outputs['hasil_forecast']);
                            file_put_contents($active_dir . '/hasil_akhir.csv', $outputs['hasil_akhir']);
                            file_put_contents($active_dir . '/model_evaluation.csv', $outputs['model_evaluation']);
                            file_put_contents($active_dir . '/data_raw.csv', $outputs['data_raw']);
                            file_put_contents($active_dir . '/dataset_info.json', json_encode($outputs['dataset_info'], JSON_PRETTY_PRINT));
                            
                            $_SESSION['use_uploaded'] = true;
                            $_SESSION['uploaded_file_name'] = $file_name;
                            
                            // Format upload time in Indonesian format
                            $now = new DateTime('now', new DateTimeZone('Asia/Jakarta'));
                            $months = [
                                'January'   => 'Januari', 'February' => 'Februari', 'March' => 'Maret',
                                'April'     => 'April', 'May' => 'Mei', 'June' => 'Juni',
                                'July'      => 'Juli', 'August' => 'Agustus', 'September' => 'September',
                                'October'   => 'Oktober', 'November' => 'November', 'December' => 'Desember'
                            ];
                            $eng_month = $now->format('F');
                            $indo_month = $months[$eng_month] ?? $eng_month;
                            $upload_time_str = $now->format('d') . ' ' . $indo_month . ' ' . $now->format('Y | H:i') . ' WIB';
                            
                            $_SESSION['uploaded_time'] = $upload_time_str;
                            $_SESSION['upload_success'] = "Dataset baru berhasil diproses secara cloud via REST API! Model statistik (Regresi Log-Log & Semi-Log) telah disesuaikan dan diaktifkan di seluruh halaman DSS.";
                            header("Location: index.php?page=datasource");
                            exit;
                        } else {
                            $error_msg = isset($res_data['error']) ? $res_data['error'] : 'Error tidak diketahui dari server';
                            $_SESSION['upload_err'] = "Gagal memproses model regresi secara cloud. Detail Error:\n" . $error_msg;
                            if (file_exists($destination)) {
                                @unlink($destination);
                            }
                            header("Location: index.php?page=datasource");
                            exit;
                        }
                    } else {
                        $_SESSION['upload_err'] = "Gagal memindahkan file ke direktori tujuan.";
                        header("Location: index.php?page=datasource");
                        exit;
                    }
                }
            } else {
                $err_code = $_FILES['dataset_file']['error'] ?? 'No file';
                $_SESSION['upload_err'] = "Gagal mengunggah file. Kode error: " . $err_code;
                header("Location: index.php?page=datasource");
                exit;
            }
        }
    }
}

// Get active directory and files info
$active_dataset = (isset($_SESSION['use_uploaded']) && $_SESSION['use_uploaded'] === true) ? 'uploaded' : 'default';
$active_dir = $active_dataset === 'uploaded' ? __DIR__ . '/../data/active' : __DIR__ . '/../data';

$hasil_akhir_exists = file_exists($active_dir . '/hasil_akhir.csv');
$hasil_forecast_exists = file_exists($active_dir . '/hasil_forecast.csv');

// Count products in active dataset
$active_products_count = 0;
if ($hasil_akhir_exists) {
    if (($handle = fopen($active_dir . '/hasil_akhir.csv', 'r')) !== false) {
        fgetcsv($handle); // header
        while (fgetcsv($handle) !== false) {
            $active_products_count++;
        }
        fclose($handle);
    }
}
?>

<div class="row g-4">
    <!-- Status Card -->
    <div class="col-12 anim-fade-in-up">
        <div class="dashboard-card border-start-primary">
            <div class="card-body py-3">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="p-3 rounded-3 bg-light text-primary">
                            <i class="bi bi-database-check fs-3"></i>
                        </div>
                        <div>
                            <span class="text-xs text-slate-500 text-uppercase fw-bold">Dataset Aktif Saat Ini</span>
                            <h5 class="fw-bold mb-0">
                                <?php if ($active_dataset === 'uploaded'): ?>
                                    <span class="text-success"><i class="bi bi-cloud-arrow-up-fill me-1"></i>Dataset Kustom (Di-upload)</span>
                                <?php else: ?>
                                    <span class="text-primary"><i class="bi bi-folder-fill me-1"></i>Dataset Bawaan (Data Repository)</span>
                                <?php endif; ?>
                            </h5>
                            <?php if ($active_dataset === 'uploaded'): ?>
                                <small class="text-xs text-slate-400">
                                    File: <strong><?php echo htmlspecialchars($_SESSION['uploaded_file_name'] ?? 'data_penjualan.xlsx'); ?></strong> 
                                    · Diproses pada: <?php echo htmlspecialchars($_SESSION['uploaded_time'] ?? '-'); ?>
                                </small>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <div class="text-center px-3 py-2 bg-slate-50 border rounded-3">
                            <span class="text-xs text-slate-500 d-block">Jumlah Produk</span>
                            <strong class="text-slate-800 fs-5"><?php echo $active_products_count; ?></strong>
                        </div>
                        <div class="text-center px-3 py-2 bg-slate-50 border rounded-3">
                            <span class="text-xs text-slate-500 d-block">Status Model</span>
                            <strong class="text-success text-xs d-flex align-items-center gap-1 mt-1">
                                <i class="bi bi-check-circle-fill"></i> Terkalibrasi
                            </strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Alert / Toast Messages -->
    <?php if (!empty($upload_err)): ?>
    <div class="col-12 anim-fade-in-up">
        <div class="alert alert-danger rounded-3 p-3 d-flex align-items-start gap-3 shadow-sm mb-0">
            <i class="bi bi-exclamation-octagon-fill fs-4 mt-0"></i>
            <div class="flex-grow-1">
                <h6 class="fw-bold mb-1">Gagal Memproses Dataset</h6>
                <pre class="mb-0 text-xs text-slate-700 bg-light p-2 border rounded" style="white-space: pre-wrap; font-family: monospace; max-height: 200px; overflow-y: auto;"><?php echo $upload_err; ?></pre>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($upload_success)): ?>
    <div class="col-12 anim-fade-in-up">
        <div class="alert alert-success alert-dismissible fade show rounded-3 p-3 d-flex align-items-center gap-3 shadow-sm mb-0" role="alert">
            <i class="bi bi-check-circle-fill fs-4 text-success"></i>
            <div>
                <strong class="d-block mb-1">Berhasil!</strong>
                <span class="text-sm"><?php echo $upload_success; ?></span>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div>
    <?php endif; ?>

    <!-- Two Options Row -->
    <div class="col-md-6 anim-fade-in-up anim-delay-1">
        <div class="dashboard-card h-100 <?php echo $active_dataset === 'default' ? 'border-primary' : ''; ?>">
            <div class="card-header">
                <h5><i class="bi bi-folder-fill text-primary me-2"></i>1. Gunakan Data Repository</h5>
                <span class="text-xs text-slate-500">Gunakan dataset bawaan skripsi (Toko Virend 2021-2025)</span>
            </div>
            <div class="card-body d-flex flex-column justify-content-between">
                <div>
                    <p class="text-sm text-slate-600 mb-4">
                        Dataset default berisi data historis lengkap penjualan 5 varian popok popok bayi (S, M, L, XL, XXL) di Toko Virend periode 2021-2025. 
                        Data ini telah dianalisis untuk menghasilkan model regresi OLS dan peramalan yang digunakan dalam dokumen skripsi Anda.
                    </p>
                    <div class="p-3 bg-light rounded-3 text-xs mb-4">
                        <strong class="d-block text-slate-700 mb-2">Daftar File Aktif:</strong>
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <span><i class="bi bi-file-earmark-code me-1 text-slate-400"></i>hasil_akhir.csv (Summary &amp; Optimasi)</span>
                            <span class="badge bg-success">Default</span>
                        </div>
                        <div class="d-flex align-items-center justify-content-between">
                            <span><i class="bi bi-file-earmark-code me-1 text-slate-400"></i>hasil_forecast.csv (Proyeksi 90 Hari)</span>
                            <span class="badge bg-success">Default</span>
                        </div>
                    </div>
                </div>
                
                <?php if ($active_dataset === 'default'): ?>
                    <button class="btn btn-primary w-100 disabled" disabled>
                        <i class="bi bi-check-circle me-1"></i> Sedang Aktif
                    </button>
                <?php else: ?>
                    <form method="POST" action="index.php?page=datasource">
                        <input type="hidden" name="action" value="reset_default">
                        <button type="submit" class="btn btn-outline-primary w-100">
                            <i class="bi bi-arrow-left-right me-1"></i> Aktifkan Dataset Repository
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-6 anim-fade-in-up anim-delay-2">
        <div class="dashboard-card h-100 <?php echo $active_dataset === 'uploaded' ? 'border-success' : ''; ?>">
            <div class="card-header">
                <h5><i class="bi bi-cloud-arrow-up-fill text-success me-2"></i>2. Upload Dataset Baru (.xlsx)</h5>
                <span class="text-xs text-slate-500">Unggah transaksi penjualan Excel untuk menghitung ulang model</span>
            </div>
            <div class="card-body">
                <p class="text-sm text-slate-600 mb-3">
                    Unggah file Excel transaksi penjualan popok yang baru. Sistem akan secara dinamis menjalankan OLS regresi linear log-log untuk menghitung elastisitas 
                    dan regresi semi-log untuk menghitung forecast 90 hari ke depan secara otomatis.
                </p>
                
                <!-- Format Requirement Details -->
                <div class="accordion mb-3" id="formatAccordion">
                    <div class="accordion-item border-0 bg-light rounded-3 overflow-hidden">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed py-2 px-3 text-xs fw-bold text-slate-700 bg-light border-0" type="button" data-bs-toggle="collapse" data-bs-target="#formatDetails" aria-expanded="false">
                                <i class="bi bi-info-circle me-1 text-info"></i> Lihat Persyaratan Format Kolom Excel (.xlsx)
                            </button>
                        </h2>
                        <div id="formatDetails" class="accordion-collapse collapse" data-bs-parent="#formatAccordion">
                            <div class="accordion-body p-3 pt-0 text-xs text-slate-600 border-0">
                                File Excel Anda harus berisi sheet pertama dengan header kolom persis seperti di bawah ini:
                                <table class="table table-sm table-bordered mt-2 mb-0 bg-white text-xs">
                                    <thead>
                                        <tr class="bg-light">
                                            <th>Nama Kolom</th>
                                            <th>Tipe Data</th>
                                            <th>Contoh Nilai</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><strong>tanggal</strong></td>
                                            <td>Date / Text</td>
                                            <td><code>2026-01-02</code> atau <code>1/2/2026</code></td>
                                        </tr>
                                        <tr>
                                            <td><strong>produk</strong></td>
                                            <td>Text</td>
                                            <td><code>S</code>, <code>M</code>, <code>L</code>, <code>XL</code>, <code>XXL</code></td>
                                        </tr>
                                        <tr>
                                            <td><strong>qty</strong></td>
                                            <td>Numeric</td>
                                            <td><code>40</code></td>
                                        </tr>
                                        <tr>
                                            <td><strong>total_harga</strong></td>
                                            <td>Numeric</td>
                                            <td><code>66000</code></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- File upload Form -->
                <form id="uploadForm" method="POST" action="index.php?page=datasource" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="upload_dataset">
                    
                    <!-- Drag and drop zone -->
                    <div id="dropZone" class="upload-drop-zone rounded-3 p-4 mb-3 text-center d-flex flex-column align-items-center justify-content-center border-dashed border-2 cursor-pointer" style="border-color: #cbd5e1; background: #f8fafc; transition: all 0.2s;">
                        <i class="bi bi-file-earmark-excel-fill text-success fs-1 mb-2"></i>
                        <span class="text-sm fw-semibold text-slate-700 mb-1">Tarik &amp; Lepaskan file Excel di sini</span>
                        <span class="text-xs text-slate-400 mb-2">Atau klik untuk memilih file</span>
                        <span id="selectedFileName" class="badge bg-secondary d-none text-xs text-wrap py-1.5 px-2"></span>
                        <input type="file" name="dataset_file" id="datasetFileInput" class="d-none" accept=".xlsx">
                    </div>
                    
                    <button type="submit" id="submitBtn" class="btn btn-success w-100" style="display: none;">
                        <i class="bi bi-play-fill me-1"></i> Proses &amp; Jalankan Model Regresi
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- ============================================================
         3. INFORMASI DATASET AKTIF
         ============================================================ -->
    <?php $ds_info = load_dataset_info(); ?>
    <div class="col-12 mt-4 anim-fade-in-up anim-delay-3">
        <div class="dashboard-card">
            <div class="card-header">
                <h5><i class="bi bi-info-circle-fill text-primary me-2"></i>3. Informasi Dataset Aktif</h5>
                <span class="text-xs text-slate-500">Karakteristik file yang sedang aktif di sistem</span>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6 col-lg-3">
                        <div class="p-3 bg-light rounded border text-center h-100 d-flex flex-column justify-content-center">
                            <span class="text-xs text-slate-500 text-uppercase fw-semibold mb-1">Nama File</span>
                            <strong class="text-slate-800 text-wrap"><?php echo htmlspecialchars($ds_info['filename']); ?></strong>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="p-3 bg-light rounded border text-center h-100 d-flex flex-column justify-content-center">
                            <span class="text-xs text-slate-500 text-uppercase fw-semibold mb-1">Jumlah Record</span>
                            <strong class="text-slate-800 text-wrap"><?php echo number_format($ds_info['total_records'], 0, ',', '.'); ?> baris</strong>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="p-3 bg-light rounded border text-center h-100 d-flex flex-column justify-content-center">
                            <span class="text-xs text-slate-500 text-uppercase fw-semibold mb-1">Rentang Tanggal</span>
                            <strong class="text-slate-800 text-wrap"><?php echo htmlspecialchars($ds_info['date_range_start']); ?> - <?php echo htmlspecialchars($ds_info['date_range_end']); ?></strong>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="p-3 bg-light rounded border text-center h-100 d-flex flex-column justify-content-center">
                            <span class="text-xs text-slate-500 text-uppercase fw-semibold mb-1">Terakhir Diupdate</span>
                            <strong class="text-slate-800 text-wrap"><?php echo htmlspecialchars($ds_info['upload_time']); ?></strong>
                        </div>
                    </div>

                    <!-- Column list in active dataset -->
                    <div class="col-12 mt-3">
                        <h6 class="text-xs fw-bold text-slate-700 mb-2">Kolom yang Teridentifikasi:</h6>
                        <div class="d-flex flex-wrap gap-2">
                            <span class="badge bg-slate-100 text-slate-700 border px-3 py-2 rounded text-xs"><i class="bi bi-calendar-check me-1 text-primary"></i>tanggal (Tanggal Transaksi)</span>
                            <span class="badge bg-slate-100 text-slate-700 border px-3 py-2 rounded text-xs"><i class="bi bi-tag me-1 text-success"></i>produk (Ukuran Popok)</span>
                            <span class="badge bg-slate-100 text-slate-700 border px-3 py-2 rounded text-xs"><i class="bi bi-cart3 me-1 text-warning"></i>qty (Kuantitas Penjualan)</span>
                            <span class="badge bg-slate-100 text-slate-700 border px-3 py-2 rounded text-xs"><i class="bi bi-cash me-1 text-danger"></i>total_harga (Total Penjualan)</span>
                            <span class="badge bg-slate-100 text-slate-700 border px-3 py-2 rounded text-xs"><i class="bi bi-coin me-1 text-info"></i>harga_per_unit (Harga Satuan)</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================
         4. PREVIEW DATASET AKTIF
         ============================================================ -->
    <?php $raw_data = load_raw_data(); ?>
    <div class="col-12 mt-4 anim-fade-in-up anim-delay-4">
        <div class="dashboard-card">
            <div class="card-header">
                <h5><i class="bi bi-table text-success me-2"></i>4. Preview Dataset Aktif</h5>
                <span class="text-xs text-slate-500">Menampilkan 15 transaksi pertama dari data mentah</span>
            </div>
            <div class="card-body p-0">
                <?php if (empty($raw_data)): ?>
                    <div class="p-4 text-center text-slate-400">
                        <i class="bi bi-inbox fs-1 d-block mb-2 text-slate-300"></i>
                        Tidak ada data mentah untuk dipratinjau.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table custom-table mb-0 text-xs">
                            <thead>
                                <tr class="bg-light">
                                    <th>#</th>
                                    <th>Tanggal</th>
                                    <th>Produk (Ukuran)</th>
                                    <th class="text-end">Qty (Unit)</th>
                                    <th class="text-end">Total Harga (Rp)</th>
                                    <th class="text-end">Harga/Unit (Rp)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($raw_data, 0, 15) as $idx => $row): ?>
                                <tr>
                                    <td class="text-muted"><?php echo $idx + 1; ?></td>
                                    <td><?php echo htmlspecialchars($row['tanggal']); ?></td>
                                    <td class="fw-semibold text-slate-700"><?php echo htmlspecialchars($row['produk']); ?></td>
                                    <td class="text-end"><?php echo number_format($row['qty'], 0, ',', '.'); ?></td>
                                    <td class="text-end"><?php echo format_rupiah($row['total_harga']); ?></td>
                                    <td class="text-end text-success fw-medium"><?php echo format_rupiah($row['harga_per_unit']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="px-3 py-2.5 border-top bg-light text-center">
                        <span class="text-xs text-slate-500">
                            <i class="bi bi-info-circle me-1"></i>
                            Pratinjau 15 baris pertama dari total <?php echo number_format($ds_info['total_records'], 0, ',', '.'); ?> baris data transaksi.
                        </span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Loading overlay -->
<div id="loadingOverlay" class="d-none" style="position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(15, 23, 42, 0.7); z-index: 9999; display: flex; flex-direction: column; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
    <div class="spinner-border text-success" role="status" style="width: 3.5rem; height: 3.5rem;">
        <span class="visually-hidden">Loading...</span>
    </div>
    <h5 class="text-white fw-bold mt-4 mb-2">Menghitung Ulang Model Statistik...</h5>
    <p class="text-slate-300 text-sm">Menjalankan pemrosesan data, regresi OLS log-log, peramalan time series, dan optimasi harga di server.</p>
</div>

<!-- Drag & drop styling and triggers -->
<script>
document.addEventListener("DOMContentLoaded", function () {
    const dropZone = document.getElementById("dropZone");
    const fileInput = document.getElementById("datasetFileInput");
    const submitBtn = document.getElementById("submitBtn");
    const selectedFileName = document.getElementById("selectedFileName");
    const uploadForm = document.getElementById("uploadForm");
    const loadingOverlay = document.getElementById("loadingOverlay");

    // Click dropzone to trigger input
    dropZone.addEventListener("click", () => fileInput.click());

    // File selection change
    fileInput.addEventListener("change", function () {
        if (fileInput.files.length > 0) {
            updateSelectedFile(fileInput.files[0]);
        }
    });

    // Drag-and-drop events
    dropZone.addEventListener("dragover", function (e) {
        e.preventDefault();
        dropZone.style.borderColor = "#10b981";
        dropZone.style.background = "#f0fdf4";
    });

    dropZone.addEventListener("dragleave", function (e) {
        e.preventDefault();
        dropZone.style.borderColor = "#cbd5e1";
        dropZone.style.background = "#f8fafc";
    });

    dropZone.addEventListener("drop", function (e) {
        e.preventDefault();
        dropZone.style.borderColor = "#cbd5e1";
        dropZone.style.background = "#f8fafc";
        
        if (e.dataTransfer.files.length > 0) {
            const file = e.dataTransfer.files[0];
            const fileExt = file.name.split('.').pop().toLowerCase();
            if (fileExt === 'xlsx') {
                fileInput.files = e.dataTransfer.files;
                updateSelectedFile(file);
            } else {
                alert("Format file salah! Mohon unggah file dengan format .xlsx");
            }
        }
    });

    function updateSelectedFile(file) {
        selectedFileName.textContent = file.name + " (" + (file.size / 1024).toFixed(1) + " KB)";
        selectedFileName.classList.remove("d-none");
        submitBtn.style.display = "block";
    }

    // Show loading overlay on form submit
    uploadForm.addEventListener("submit", function () {
        loadingOverlay.classList.remove("d-none");
    });
});
</script>
