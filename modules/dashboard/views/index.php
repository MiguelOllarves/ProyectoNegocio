<?php include __DIR__ . '/../../../includes/header.php'; ?>

<div class="mb-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
    <div>
        <h2 class="text-2xl font-extrabold text-gray-800 dark:text-white tracking-tight">Panel de Control</h2>
        <p class="text-gray-500 dark:text-gray-400 text-sm font-medium mt-1">Resumen general del negocio e inteligencia financiera</p>
    </div>
    
    <div class="flex gap-2">
        <a href="<?= BASE_URL ?>sales" class="bg-gradient-to-r from-brand-600 to-accent-600 hover:from-brand-500 hover:to-accent-500 text-white font-bold py-2 px-4 rounded-lg shadow-sm shadow-brand-500/20 transition-all text-sm flex items-center">
            <i class="fas fa-cash-register mr-2"></i> Nueva Venta
        </a>
    </div>
</div>

<!-- Top KPIs Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <!-- Ventas Hoy -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-800 p-5 flex items-center hover:shadow-md transition-shadow relative overflow-hidden group">
        <div class="absolute -right-4 -top-4 w-16 h-16 bg-brand-500/10 rounded-full blur-xl group-hover:bg-brand-500/20 transition-colors"></div>
        <div class="w-12 h-12 rounded-xl bg-brand-50 dark:bg-brand-900/30 text-brand-600 dark:text-brand-400 flex items-center justify-center text-xl mr-4 border border-brand-100 dark:border-brand-800/50">
            <i class="fas fa-coins"></i>
        </div>
        <div>
            <p class="text-[11px] uppercase tracking-wider font-bold text-gray-500 dark:text-gray-400 mb-1">Ventas Hoy</p>
            <p class="text-2xl font-black text-gray-800 dark:text-white">$<?= number_format($today_sales, 2) ?></p>
        </div>
    </div>

    <!-- Valor del Inventario -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-800 p-5 flex items-center hover:shadow-md transition-shadow relative overflow-hidden group">
        <div class="absolute -right-4 -top-4 w-16 h-16 bg-accent-500/10 rounded-full blur-xl group-hover:bg-accent-500/20 transition-colors"></div>
        <div class="w-12 h-12 rounded-xl bg-accent-50 dark:bg-accent-900/30 text-accent-600 dark:text-accent-400 flex items-center justify-center text-xl mr-4 border border-accent-100 dark:border-accent-800/50">
            <i class="fas fa-boxes-stacked"></i>
        </div>
        <div>
            <p class="text-[11px] uppercase tracking-wider font-bold text-gray-500 dark:text-gray-400 mb-1">Valor Inv.</p>
            <p class="text-2xl font-black text-gray-800 dark:text-white">$<?= number_format($inventory_value, 2) ?></p>
        </div>
    </div>

    <!-- Ganancia Estimada (Proyección Inventario) -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-800 p-5 flex items-center hover:shadow-md transition-shadow relative overflow-hidden group">
        <div class="absolute -right-4 -top-4 w-16 h-16 bg-green-500/10 rounded-full blur-xl group-hover:bg-green-500/20 transition-colors"></div>
        <div class="w-12 h-12 rounded-xl bg-green-50 dark:bg-green-900/30 text-green-600 dark:text-green-400 flex items-center justify-center text-xl mr-4 border border-green-100 dark:border-green-800/50">
            <i class="fas fa-arrow-trend-up"></i>
        </div>
        <div>
            <p class="text-[11px] uppercase tracking-wider font-bold text-gray-500 dark:text-gray-400 mb-1">Ganancia Est.</p>
            <p class="text-2xl font-black text-gray-800 dark:text-white">$<?= number_format($estimated_profit, 2) ?></p>
        </div>
    </div>

    <!-- Stock Crítico -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-800 p-5 flex items-center hover:shadow-md transition-shadow relative overflow-hidden group">
        <div class="absolute -right-4 -top-4 w-16 h-16 <?= $low_stock > 0 ? 'bg-red-500/10 group-hover:bg-red-500/20' : 'bg-gray-500/10 group-hover:bg-gray-500/20' ?> rounded-full blur-xl transition-colors"></div>
        <div class="w-12 h-12 rounded-xl <?= $low_stock > 0 ? 'bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400 border-red-100 dark:border-red-800/50 animate-pulse' : 'bg-gray-50 dark:bg-gray-800 text-gray-500 border-gray-100 dark:border-gray-700' ?> flex items-center justify-center text-xl mr-4 border">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <div>
            <p class="text-[11px] uppercase tracking-wider font-bold text-gray-500 dark:text-gray-400 mb-1">Stock Crítico</p>
            <p class="text-2xl font-black text-gray-800 dark:text-white"><?= $low_stock ?></p>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Chart Section -->
    <div class="lg:col-span-2">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-800 h-full flex flex-col">
            <div class="p-5 border-b border-gray-100 dark:border-gray-800">
                <h3 class="text-sm font-bold text-gray-800 dark:text-white"><i class="fas fa-chart-area text-brand-500 mr-2"></i>Tendencia de Ventas (7 Días)</h3>
            </div>
            <div class="p-5 relative w-full flex-1 min-h-[300px]">
                <canvas id="salesChart"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Aside Actividad en Vivo -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-800 flex flex-col h-[400px]">
        <div class="p-4 border-b border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-slate-800/50 rounded-t-xl flex justify-between items-center relative overflow-hidden">
            <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-brand-500 to-accent-500"></div>
            <h3 class="text-sm font-bold text-gray-800 dark:text-white"><i class="fas fa-satellite-dish text-brand-500 mr-2"></i>Actividad en Vivo</h3>
            <span class="inline-flex h-2.5 w-2.5 relative">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-brand-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-full w-full bg-brand-500"></span>
            </span>
        </div>
        <div class="p-4 pb-2 flex-1 overflow-y-auto space-y-0" hx-get="<?= BASE_URL ?>dashboard/activity" hx-trigger="load, every 5s" hx-swap="innerHTML">
            <div class="text-center text-gray-400 mt-10">
                <i class="fas fa-spinner fa-spin text-2xl"></i>
                <p class="text-sm font-medium mt-3">Sincronizando feed...</p>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('salesChart').getContext('2d');
    const chartDataRaw = <?= json_encode($chart_data) ?>;
    const labels = chartDataRaw.map(d => d.day);
    const data = chartDataRaw.map(d => d.sales);

    // Detección de tema para Chart.js
    const isDark = document.documentElement.classList.contains('dark');
    const gridColor = isDark ? 'rgba(255, 255, 255, 0.05)' : 'rgba(0, 0, 0, 0.05)';
    const textColor = isDark ? 'rgba(255, 255, 255, 0.5)' : 'rgba(0, 0, 0, 0.5)';

    // Gradiente para el área bajo la curva
    let gradient = ctx.createLinearGradient(0, 0, 0, 400);
    gradient.addColorStop(0, 'rgba(16, 185, 129, 0.5)'); // brand-500
    gradient.addColorStop(1, 'rgba(16, 185, 129, 0)');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Ingresos ($)',
                data: data,
                borderColor: '#10b981', // brand-500
                backgroundColor: gradient,
                borderWidth: 3,
                pointBackgroundColor: '#ffffff',
                pointBorderColor: '#10b981',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: isDark ? '#1e293b' : '#ffffff',
                    titleColor: isDark ? '#ffffff' : '#1f2937',
                    bodyColor: isDark ? '#94a3b8' : '#4b5563',
                    borderColor: isDark ? '#334155' : '#e2e8f0',
                    borderWidth: 1,
                    padding: 12,
                    boxPadding: 6,
                    usePointStyle: true,
                }
            },
            scales: { 
                y: { 
                    beginAtZero: true,
                    grid: { color: gridColor, drawBorder: false },
                    ticks: { color: textColor, font: { family: 'Inter' } }
                },
                x: {
                    grid: { display: false, drawBorder: false },
                    ticks: { color: textColor, font: { family: 'Inter' } }
                }
            }
        }
    });
});
</script>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>
