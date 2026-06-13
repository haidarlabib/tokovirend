/**
 * DSS Harga Popok — Toko Virend
 * Global JavaScript Utilities & Sidebar Controller
 */

/* =========================================================
   FORMAT UTILITIES
   ========================================================= */
/**
 * Format number to Indonesian Rupiah
 * @param {number} amount
 * @returns {string}
 */
function formatRupiah(amount) {
    if (isNaN(amount) || amount === null || amount === undefined) return 'Rp 0';
    return 'Rp ' + new Intl.NumberFormat('id-ID', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(Math.round(amount));
}

/**
 * Format number with Indonesian locale
 * @param {number} number
 * @param {number} decimals
 * @returns {string}
 */
function formatNumber(number, decimals = 0) {
    if (isNaN(number)) return '0';
    return new Intl.NumberFormat('id-ID', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals
    }).format(number);
}

/**
 * Format percentage
 * @param {number} value
 * @param {number} decimals
 * @returns {string}
 */
function formatPercent(value, decimals = 1) {
    const sign = value > 0 ? '+' : '';
    return sign + value.toFixed(decimals) + '%';
}

/* =========================================================
   SIDEBAR CONTROLLER
   ========================================================= */
document.addEventListener('DOMContentLoaded', function () {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const toggleBtn = document.getElementById('sidebarToggleBtn');

    // Toggle sidebar on mobile
    if (toggleBtn) {
        toggleBtn.addEventListener('click', function () {
            sidebar.classList.toggle('sidebar-open');
            if (overlay) overlay.classList.toggle('active');
        });
    }

    // Close on overlay click
    if (overlay) {
        overlay.addEventListener('click', function () {
            sidebar.classList.remove('sidebar-open');
            overlay.classList.remove('active');
        });
    }

    /* ---------------------------------------------------------
       Animate stat cards on page load
       --------------------------------------------------------- */
    const statCards = document.querySelectorAll('.stat-card');
    statCards.forEach((card, index) => {
        card.style.animationDelay = (index * 0.06) + 's';
        card.classList.add('anim-fade-in-up');
    });

    /* ---------------------------------------------------------
       Animate dashboard cards
       --------------------------------------------------------- */
    const dashCards = document.querySelectorAll('.dashboard-card');
    dashCards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.animationDelay = (0.1 + index * 0.07) + 's';
        card.classList.add('anim-fade-in-up');
        // Trigger reflow to allow animation
        setTimeout(() => { card.style.opacity = ''; }, 10);
    });

    /* ---------------------------------------------------------
       Auto-init DataTables if present and jQuery loaded
       --------------------------------------------------------- */
    if (typeof $ !== 'undefined' && $.fn.dataTable) {
        $('.datatable').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/id.json'
            },
            pageLength: 10,
            responsive: true,
            dom: '<"row mb-2"<"col-sm-6"l><"col-sm-6"f>>t<"row mt-2"<"col-sm-6"i><"col-sm-6"p>>',
        });
    }

    // Initialize Bootstrap Tooltips
    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }

    // Realtime Clock Update
    function updateRealtimeClock() {
        const clockEl = document.getElementById('realtime-clock');
        if (!clockEl) return;
        
        const now = new Date();
        const day = now.getDate();
        const idMonths = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        const month = idMonths[now.getMonth()];
        const year = now.getFullYear();
        
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        
        clockEl.innerHTML = `<i class="bi bi-calendar3 me-1 text-info"></i> ${day} ${month} ${year} | ${hours}:${minutes} WIB`;
    }
    updateRealtimeClock();
    setInterval(updateRealtimeClock, 1000);
});

/* =========================================================
   CHART.JS GLOBAL DEFAULTS
   ========================================================= */
if (typeof Chart !== 'undefined') {
    Chart.defaults.font.family = "'Outfit', sans-serif";
    Chart.defaults.font.size = 12;
    Chart.defaults.color = '#64748b';
    Chart.defaults.plugins.legend.labels.usePointStyle = true;
    Chart.defaults.plugins.legend.labels.boxWidth = 8;
    Chart.defaults.plugins.legend.labels.padding = 16;
    Chart.defaults.plugins.tooltip.backgroundColor = 'rgba(15, 23, 42, 0.85)';
    Chart.defaults.plugins.tooltip.titleFont = { family: "'Outfit', sans-serif", weight: '700', size: 13 };
    Chart.defaults.plugins.tooltip.bodyFont = { family: "'Outfit', sans-serif", size: 12 };
    Chart.defaults.plugins.tooltip.padding = 10;
    Chart.defaults.plugins.tooltip.cornerRadius = 8;
    Chart.defaults.plugins.tooltip.borderColor = 'rgba(255,255,255,0.1)';
    Chart.defaults.plugins.tooltip.borderWidth = 1;
}

/* =========================================================
   PRINT / EXPORT HELPERS
   ========================================================= */
/**
 * Trigger browser print dialog
 */
function printReport() {
    window.print();
}

/**
 * Export to PDF via print (print to PDF)
 */
function exportPDF() {
    // Show print-only elements
    document.querySelectorAll('.print-only').forEach(el => el.style.display = 'block');
    window.print();
    // Re-hide after print
    setTimeout(() => {
        document.querySelectorAll('.print-only').forEach(el => el.style.display = 'none');
    }, 1000);
}

/* =========================================================
   NUMBER ANIMATION (Count-up)
   ========================================================= */
/**
 * Animate number counting up from 0 to target
 * @param {HTMLElement} el
 * @param {number} target
 * @param {number} duration ms
 * @param {string} prefix
 * @param {string} suffix
 */
function animateCountUp(el, target, duration = 1000, prefix = '', suffix = '') {
    const startTime = performance.now();
    const startVal = 0;

    function update(currentTime) {
        const elapsed = currentTime - startTime;
        const progress = Math.min(elapsed / duration, 1);
        // Ease out cubic
        const eased = 1 - Math.pow(1 - progress, 3);
        const current = Math.round(startVal + (target - startVal) * eased);
        el.textContent = prefix + formatNumber(current) + suffix;
        if (progress < 1) requestAnimationFrame(update);
    }

    requestAnimationFrame(update);
}

/* =========================================================
   TOOLTIP HELPERS
   ========================================================= */
/**
 * Standard Indonesian Rupiah tooltip callback for Chart.js
 */
function rupiahTooltipCallback(context) {
    let label = context.dataset.label || '';
    if (label) label += ': ';
    if (context.raw !== null) {
        label += formatRupiah(context.raw);
    }
    return label;
}

/**
 * Create standard Chart.js tooltip options for Rupiah
 */
function getRupiahTooltipOptions() {
    return {
        callbacks: {
            label: rupiahTooltipCallback
        }
    };
}
