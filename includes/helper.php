<?php
/**
 * Helper Functions — DSS Harga Popok Toko Virend
 * ------------------------------------------------
 * Utility functions for data loading, statistics, and formatting.
 */

// Set Timezone to Asia/Jakarta
date_default_timezone_set('Asia/Jakarta');

// =========================================================
// Constants
// =========================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function get_active_data_dir(): string {
    if (isset($_SESSION['use_uploaded']) && $_SESSION['use_uploaded'] === true) {
        $dir = __DIR__ . '/../data/active';
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        return $dir;
    }
    return __DIR__ . '/../data';
}

define('ACTIVE_DATA_DIR', get_active_data_dir());
define('CSV_PATH', ACTIVE_DATA_DIR . '/hasil_akhir.csv');
define('FORECAST_CSV_PATH', ACTIVE_DATA_DIR . '/hasil_forecast.csv');
define('RAW_CSV_PATH', ACTIVE_DATA_DIR . '/data_raw.csv');
define('MODEL_EVAL_CSV_PATH', ACTIVE_DATA_DIR . '/model_evaluation.csv');
define('DATASET_INFO_JSON_PATH', ACTIVE_DATA_DIR . '/dataset_info.json');
define('PYTHON_API_URL', 'http://127.0.0.1:5000/train'); // Default local for testing; change in production to Railway URL
define('APP_NAME', '"TOKO VIREND" - elastisitas dan simulasi');
define('APP_VERSION', '2.0');

// =========================================================
// Data Loading
// =========================================================

/**
 * Loads and parses data from the main CSV file.
 * Returns an array of associative arrays, one per product.
 *
 * @return array
 */
function load_csv_data(): array {
    $data = [];
    if (!file_exists(CSV_PATH)) {
        return $data;
    }

    if (($handle = fopen(CSV_PATH, 'r')) !== false) {
        $headers = fgetcsv($handle, 2000, ',');
        if ($headers !== false) {
            $headers = array_map('trim', $headers);
            if (!empty($headers)) {
                $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', $headers[0]);
            }
            while (($row = fgetcsv($handle, 2000, ',')) !== false) {
                if (count($headers) === count($row)) {
                    $item = array_combine($headers, array_map('trim', $row));
                    // Type casting with existence checks to prevent undefined key warnings
                    $item['elastisitas']      = isset($item['elastisitas'])      ? (float) $item['elastisitas']      : 0.0;
                    $item['qty_forecast']     = isset($item['qty_forecast'])     ? (int)   $item['qty_forecast']     : 0;
                    $item['harga_saat_ini']   = isset($item['harga_saat_ini'])   ? (int)   $item['harga_saat_ini']   : 0;
                    $item['harga_optimal']    = isset($item['harga_optimal'])    ? (int)   $item['harga_optimal']    : 0;
                    $item['qty_optimal']      = isset($item['qty_optimal'])      ? (int)   $item['qty_optimal']      : 0;
                    $item['revenue_maksimum'] = isset($item['revenue_maksimum']) ? (int)   $item['revenue_maksimum'] : 0;
                    $item['kategori']         = isset($item['kategori'])         ? trim($item['kategori'])         : 'Inelastis';
                    $data[] = $item;
                }
            }
        }
        fclose($handle);
    }
    return $data;
}

// =========================================================
// Summary Statistics
// =========================================================

/**
 * Calculates high-level summary statistics for the dashboard.
 *
 * @param  array $data  Output of load_csv_data()
 * @return array
 */
function get_summary_stats(array $data): array {
    $stats = [
        'jumlah_produk'          => 0,
        'rata_rata_elastisitas'  => 0.0,
        'total_forecast_90_hari' => 0,
        'total_revenue_maksimum' => 0,
        'elastic_count'          => 0,
        'inelastic_count'        => 0,
    ];

    if (empty($data)) {
        return $stats;
    }

    $stats['jumlah_produk'] = count($data);
    $total_elastisitas      = 0.0;

    foreach ($data as $item) {
        $total_elastisitas             += $item['elastisitas'];
        $stats['total_forecast_90_hari'] += $item['qty_forecast'];
        $stats['total_revenue_maksimum'] += $item['revenue_maksimum'];

        if (strtolower(trim($item['kategori'])) === 'elastis') {
            $stats['elastic_count']++;
        } else {
            $stats['inelastic_count']++;
        }
    }

    $stats['rata_rata_elastisitas'] = $total_elastisitas / $stats['jumlah_produk'];

    return $stats;
}

// =========================================================
// EDA — Descriptive Statistics
// =========================================================

/**
 * Computes descriptive statistics for a named numeric field across all products.
 *
 * @param  array  $data   Output of load_csv_data()
 * @param  string $field  Field name to compute stats on
 * @return array  Keys: mean, median, min, max, std_dev, total
 */
function get_descriptive_stats(array $data, string $field): array {
    if (empty($data)) {
        return ['mean' => 0, 'median' => 0, 'min' => 0, 'max' => 0, 'std_dev' => 0, 'total' => 0];
    }

    $values = array_column($data, $field);
    $values = array_map('floatval', $values);
    $n      = count($values);

    // Mean
    $mean = array_sum($values) / $n;

    // Median
    $sorted = $values;
    sort($sorted);
    $median = ($n % 2 === 0)
        ? ($sorted[$n / 2 - 1] + $sorted[$n / 2]) / 2
        : $sorted[(int)($n / 2)];

    // Min, Max
    $min = min($values);
    $max = max($values);

    // Standard Deviation (population)
    $variance = array_sum(array_map(fn($v) => pow($v - $mean, 2), $values)) / $n;
    $std_dev  = sqrt($variance);

    return [
        'mean'    => $mean,
        'median'  => $median,
        'min'     => $min,
        'max'     => $max,
        'std_dev' => $std_dev,
        'total'   => array_sum($values),
    ];
}

/**
 * Returns descriptive statistics for all key numeric fields.
 *
 * @param  array $data
 * @return array  Keyed by field name → stats array
 */
function get_all_descriptive_stats(array $data): array {
    return [
        'qty_forecast'     => get_descriptive_stats($data, 'qty_forecast'),
        'harga_saat_ini'   => get_descriptive_stats($data, 'harga_saat_ini'),
        'harga_optimal'    => get_descriptive_stats($data, 'harga_optimal'),
        'elastisitas'      => get_descriptive_stats($data, 'elastisitas'),
        'qty_optimal'      => get_descriptive_stats($data, 'qty_optimal'),
        'revenue_maksimum' => get_descriptive_stats($data, 'revenue_maksimum'),
    ];
}

// =========================================================
// Revenue Helpers
// =========================================================

/**
 * Returns total revenue at current prices (baseline).
 *
 * @param  array $data
 * @return int
 */
function get_total_revenue_baseline(array $data): int {
    $total = 0;
    foreach ($data as $item) {
        $total += $item['harga_saat_ini'] * $item['qty_forecast'];
    }
    return $total;
}

// =========================================================
// Formatting
// =========================================================

/**
 * Format number as Indonesian Rupiah (e.g., "Rp 58.000").
 *
 * @param  float|int $number
 * @return string
 */
function format_rupiah($number): string {
    return 'Rp ' . number_format((float) $number, 0, ',', '.');
}

/**
 * Format number with Indonesian thousands separator.
 *
 * @param  float|int $number
 * @param  int       $decimals
 * @return string
 */
function format_number($number, int $decimals = 0): string {
    return number_format((float) $number, $decimals, ',', '.');
}

/**
 * Returns 'elastis' or 'inelastis' CSS badge class.
 *
 * @param  string $kategori
 * @return string
 */
function elasticity_badge(string $kategori): string {
    return strtolower(trim($kategori)) === 'elastis'
        ? '<span class="badge-elastic">Elastis</span>'
        : '<span class="badge-inelastic">Inelastis</span>';
}

/**
 * Loads daily forecast data from hasil_forecast.csv.
 *
 * @return array
 */
function load_forecast_data(): array {
    $data = [];
    if (!file_exists(FORECAST_CSV_PATH)) {
        return $data;
    }

    if (($handle = fopen(FORECAST_CSV_PATH, 'r')) !== false) {
        $headers = fgetcsv($handle, 2000, ',');
        if ($headers !== false) {
            $headers = array_map('trim', $headers);
            if (!empty($headers)) {
                $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', $headers[0]);
            }
            while (($row = fgetcsv($handle, 2000, ',')) !== false) {
                if (count($headers) === count($row)) {
                    $item = array_combine($headers, array_map('trim', $row));
                    $item['qty_forecast'] = (float) $item['qty_forecast'];
                    $data[] = $item;
                }
            }
        }
        fclose($handle);
    }
    return $data;
}

/**
 * Loads OLS and forecasting model evaluation results from model_evaluation.csv.
 *
 * @return array
 */
function load_model_evaluation(): array {
    $data = [];
    if (!file_exists(MODEL_EVAL_CSV_PATH)) {
        return $data;
    }

    if (($handle = fopen(MODEL_EVAL_CSV_PATH, 'r')) !== false) {
        $headers = fgetcsv($handle, 2000, ',');
        if ($headers !== false) {
            $headers = array_map('trim', $headers);
            if (!empty($headers)) {
                $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', $headers[0]);
            }
            while (($row = fgetcsv($handle, 2000, ',')) !== false) {
                if (count($headers) === count($row)) {
                    $item = array_combine($headers, array_map('trim', $row));
                    // cast numeric fields
                    $item['r2_elastisitas'] = isset($item['r2_elastisitas']) ? (float) $item['r2_elastisitas'] : 0.0;
                    $item['adj_r2_elastisitas'] = isset($item['adj_r2_elastisitas']) ? (float) $item['adj_r2_elastisitas'] : 0.0;
                    $item['f_stat_elastisitas'] = isset($item['f_stat_elastisitas']) ? (float) $item['f_stat_elastisitas'] : 0.0;
                    $item['p_value_elastisitas'] = isset($item['p_value_elastisitas']) ? (float) $item['p_value_elastisitas'] : 0.0;
                    $item['r2_forecast'] = isset($item['r2_forecast']) ? (float) $item['r2_forecast'] : 0.0;
                    $item['mae_forecast'] = isset($item['mae_forecast']) ? (float) $item['mae_forecast'] : 0.0;
                    $item['rmse_forecast'] = isset($item['rmse_forecast']) ? (float) $item['rmse_forecast'] : 0.0;
                    $item['mape_forecast'] = isset($item['mape_forecast']) ? (float) $item['mape_forecast'] : 0.0;
                    $data[] = $item;
                }
            }
        }
        fclose($handle);
    }
    return $data;
}

/**
 * Loads dataset metadata info from dataset_info.json.
 *
 * @return array
 */
function load_dataset_info(): array {
    $default = [
        'filename' => 'data_penjualan.xlsx',
        'upload_time' => 'Bawaan Sistem',
        'total_records' => 9130,
        'total_columns' => 5,
        'date_range_start' => '01 Jan 2021',
        'date_range_end' => '31 Des 2025',
        'products' => ['S', 'M', 'L', 'XL', 'XXL']
    ];

    if (!file_exists(DATASET_INFO_JSON_PATH)) {
        return $default;
    }

    $json = file_get_contents(DATASET_INFO_JSON_PATH);
    if ($json === false) {
        return $default;
    }

    $decoded = json_decode($json, true);
    return is_array($decoded) ? $decoded : $default;
}

/**
 * Loads raw transactional data from data_raw.csv.
 *
 * @return array
 */
function load_raw_data(): array {
    $data = [];
    if (!file_exists(RAW_CSV_PATH)) {
        return $data;
    }

    if (($handle = fopen(RAW_CSV_PATH, 'r')) !== false) {
        $headers = fgetcsv($handle, 2000, ',');
        if ($headers !== false) {
            $headers = array_map('trim', $headers);
            if (!empty($headers)) {
                $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', $headers[0]);
            }
            while (($row = fgetcsv($handle, 2000, ',')) !== false) {
                if (count($headers) === count($row)) {
                    $item = array_combine($headers, array_map('trim', $row));
                    $item['qty'] = isset($item['qty']) ? (int) $item['qty'] : 0;
                    $item['total_harga'] = isset($item['total_harga']) ? (float) $item['total_harga'] : 0.0;
                    $item['harga_per_unit'] = isset($item['harga_per_unit']) ? (float) $item['harga_per_unit'] : 0.0;
                    $data[] = $item;
                }
            }
        }
        fclose($handle);
    }
    return $data;
}

/**
 * Returns MAPE quality description and Bootstrap badge class.
 *
 * @param float $mape
 * @return array Keys: label, badge
 */
function get_mape_category(float $mape): array {
    if ($mape < 10.0) {
        return ['label' => 'Sangat Baik', 'badge' => 'bg-success'];
    } elseif ($mape <= 20.0) {
        return ['label' => 'Baik', 'badge' => 'bg-primary'];
    } elseif ($mape <= 50.0) {
        return ['label' => 'Cukup', 'badge' => 'bg-warning text-dark'];
    } else {
        return ['label' => 'Kurang Baik', 'badge' => 'bg-danger'];
    }
}

/**
 * Formats a date string into Indonesian date format.
 *
 * @param string $date_str
 * @param bool $short_month
 * @return string
 */
function format_date_indonesian(string $date_str, bool $short_month = false): string {
    $months = [
        'January'   => 'Januari', 'February' => 'Februari', 'March' => 'Maret',
        'April'     => 'April', 'May' => 'Mei', 'June' => 'Juni',
        'July'      => 'Juli', 'August' => 'Agustus', 'September' => 'September',
        'October'   => 'Oktober', 'November' => 'November', 'December' => 'Desember'
    ];
    
    $short_months = [
        'Jan' => 'Jan', 'Feb' => 'Feb', 'Mar' => 'Mar', 'Apr' => 'Apr',
        'May' => 'Mei', 'Jun' => 'Jun', 'Jul' => 'Jul', 'Aug' => 'Agu',
        'Sep' => 'Sep', 'Oct' => 'Okt', 'Nov' => 'Nov', 'Dec' => 'Des'
    ];
    
    try {
        $date = new DateTime($date_str);
        if ($short_month) {
            $eng_month = $date->format('M');
            $indo_month = $short_months[$eng_month] ?? $eng_month;
        } else {
            $eng_month = $date->format('F');
            $indo_month = $months[$eng_month] ?? $eng_month;
        }
        return $date->format('d') . ' ' . $indo_month . ' ' . $date->format('Y');
    } catch (Exception $e) {
        return $date_str;
    }
}

