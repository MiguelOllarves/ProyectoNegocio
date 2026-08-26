<?php include __DIR__ . '/../../../includes/header.php'; ?>

<div class="page-header">
    <div>
        <h2 class="page-title">Panel de Control</h2>
        <p class="page-subtitle">Resumen general del negocio e inteligencia financiera</p>
    </div>
    
    <div class="flex gap-2">
        <a href="<?= BASE_URL ?>sales" class="btn-gradient">
            <i class="fas fa-cash-register mr-2"></i> Nueva Venta
        </a>
    </div>
</div>

<?php 
// Lógica para calcular días restantes de prueba
if (($subscription_status ?? '') === 'trial') {
    $trialEnds = strtotime($trial_ends_at);
    $now = time();
    $daysLeft = max(0, floor(($trialEnds - $now) / (60 * 60 * 24)));
    $isExpiring = $daysLeft <= 5;
    $bannerColor = $isExpiring ? 'bg-red-50 dark:bg-red-900/30 border-red-200 dark:border-red-800' : 'bg-brand-50 dark:bg-brand-900/30 border-brand-200 dark:border-brand-800';
    $textColor = $isExpiring ? 'text-red-800 dark:text-red-200' : 'text-brand-800 dark:text-brand-200';
    $iconColor = $isExpiring ? 'text-red-600 dark:text-red-400' : 'text-brand-600 dark:text-brand-400';
?>
<div class="mb-6 rounded-xl border <?= $bannerColor ?> p-4 flex flex-col sm:flex-row items-center justify-between gap-4">
    <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-full flex items-center justify-center shrink-0 bg-white/50 dark:bg-black/20 <?= $iconColor ?>">
            <i class="fas <?= $isExpiring ? 'fa-exclamation-triangle' : 'fa-hourglass-half' ?>"></i>
        </div>
        <div>
            <h3 class="font-bold <?= $textColor ?> m-0 p-0 text-sm md:text-base">Período de Prueba Activo</h3>
            <p class="text-xs md:text-sm <?= $textColor ?> opacity-80 mt-0.5">Te quedan <strong class="font-black text-lg"><?= $daysLeft ?></strong> días de acceso completo a todas las funciones.</p>
        </div>
    </div>
    <a href="<?= BASE_URL ?>suscription" class="shrink-0 bg-white dark:bg-slate-800 hover:bg-gray-50 dark:hover:bg-slate-700 <?= $textColor ?> font-bold py-2 px-6 rounded-lg text-sm border border-transparent shadow-sm transition-all focus:ring-2 focus:ring-offset-1 focus:ring-offset-current">
        Ver Planes y Cambiar
    </a>
</div>
<?php } else if (($subscription_status ?? '') === 'expired') { ?>
<div class="mb-6 rounded-xl border bg-gray-100 dark:bg-gray-800 border-gray-300 dark:border-gray-700 p-4 flex flex-col sm:flex-row items-center justify-between gap-4 shadow-sm">
    <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-full flex items-center justify-center shrink-0 bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-400">
            <i class="fas fa-lock"></i>
        </div>
        <div>
            <h3 class="font-bold text-gray-800 dark:text-gray-200 m-0 p-0 text-sm md:text-base">Suscripción Expirada</h3>
            <p class="text-xs md:text-sm text-gray-600 dark:text-gray-400 mt-0.5">La cuenta se encuentra en modo de <strong>Solo Lectura</strong>. Activa un plan para recuperar el control.</p>
        </div>
    </div>
    <a href="<?= BASE_URL ?>suscription" class="shrink-0 bg-brand-600 hover:bg-brand-500 text-white font-bold py-2 px-6 rounded-lg text-sm shadow-sm transition-all">
        Activar Plan Ahora
    </a>
</div>
<?php } ?>

<?php
$userRole = $_SESSION['role'] ?? '';
$userPerms = $_SESSION['permissions'] ?? [];
$canViewReports = ($userRole === 'administrador' || $userRole === 'super_admin' || in_array('reports', $userPerms));
?>

<?php if ($canViewReports): ?>
<!-- Top KPIs Grid -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="kpi-card">
        <div class="absolute -right-4 -top-4 w-16 h-16 bg-brand-500/10 rounded-full blur-xl group-hover:bg-brand-500/20 transition-colors"></div>
        <div class="kpi-icon bg-brand-50 dark:bg-brand-900/30 text-brand-600 dark:text-brand-400 border-brand-100 dark:border-brand-800/50">
            <i class="fas fa-coins"></i>
        </div>
        <div>
            <p class="kpi-label">Ventas Hoy</p>
            <p class="kpi-value">$<?= number_format($today_sales, 2) ?></p>
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
    <?php
    $stockBgClass = $low_stock > 0 ? 'bg-red-500/10 group-hover:bg-red-500/20' : 'bg-gray-500/10 group-hover:bg-gray-500/20';
    $stockIconClass = $low_stock > 0 ? 'bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400 border-red-100 dark:border-red-800/50 animate-pulse' : 'bg-gray-50 dark:bg-gray-800 text-gray-500 border-gray-100 dark:border-gray-700';
    ?>
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-800 p-5 flex items-center hover:shadow-md transition-shadow relative overflow-hidden group">
        <div class="absolute -right-4 -top-4 w-16 h-16 <?= $stockBgClass ?> rounded-full blur-xl transition-colors"></div>
        <div class="w-12 h-12 rounded-xl <?= $stockIconClass ?> flex items-center justify-center text-xl mr-4 border">
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
<?php else: ?>
<!-- Vista simplificada para cajeros y vendedores -->
<div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-8 text-center mt-6">
    <div class="w-20 h-20 bg-brand-50 dark:bg-brand-900/30 text-brand-600 dark:text-brand-400 rounded-full flex items-center justify-center mx-auto mb-6">
        <i class="fas fa-cash-register text-3xl"></i>
    </div>
    <h3 class="text-2xl font-black text-gray-800 dark:text-white mb-2">¡Bienvenido a tu turno!</h3>
    <p class="text-gray-500 dark:text-gray-400 mb-8 max-w-lg mx-auto">Selecciona "Nueva Venta" para acceder a la caja registradora o utiliza el menú lateral para navegar por los módulos autorizados.</p>
    
    <a href="<?= BASE_URL ?>sales" class="bg-brand-600 hover:bg-brand-700 text-white font-bold py-3 px-8 rounded-xl text-lg shadow-sm transition-all inline-flex items-center">
        <i class="fas fa-shopping-cart mr-3"></i> Ir a la Caja Registradora
    </a>
</div>
<?php endif; ?>

<script>
(function() {
    const canvas = document.getElementById('salesChart');
    if (!canvas) return; // Add safety check
    
    const ctx = canvas.getContext('2d');
    const chartDataRaw = <?= json_encode($chart_data) ?>;
    const labels = chartDataRaw.map(d => d.day);
    const data = chartDataRaw.map(d => d.sales);

    // Detección de tema para Chart.js
    const isDark = document.documentElement.classList.contains('dark');
    const gridColor = isDark ? 'rgba(255, 255, 255, 0.05)' : 'rgba(0, 0, 0, 0.05)';
    const textColor = isDark ? '#ffffff' : '#000000';

    // Gradiente para el área bajo la curva
    let gradient = ctx.createLinearGradient(0, 0, 0, 400);
    gradient.addColorStop(0, 'rgba(16, 185, 129, 0.5)'); // brand-500
    gradient.addColorStop(1, 'rgba(16, 185, 129, 0)');

    const salesChart = new Chart(ctx, {
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
                    ticks: { color: textColor, font: { family: 'Inter', weight: 'bold' } }
                },
                x: {
                    grid: { display: false, drawBorder: false },
                    ticks: { color: textColor, font: { family: 'Inter', weight: 'bold' } }
                }
            }
        }
    });

    // Observador para actualizar colores del gráfico al cambiar el tema (claro/oscuro)
    const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            if (mutation.attributeName === 'class') {
                const isDarkActive = document.documentElement.classList.contains('dark');
                const gridColorDyn = isDarkActive ? 'rgba(255, 255, 255, 0.05)' : 'rgba(0, 0, 0, 0.05)';
                const textColorDyn = isDarkActive ? '#ffffff' : '#000000';

                // Actualizar Tooltip
                salesChart.options.plugins.tooltip.backgroundColor = isDarkActive ? '#1e293b' : '#ffffff';
                salesChart.options.plugins.tooltip.titleColor = isDarkActive ? '#ffffff' : '#1f2937';
                salesChart.options.plugins.tooltip.bodyColor = isDarkActive ? '#94a3b8' : '#4b5563';
                salesChart.options.plugins.tooltip.borderColor = isDarkActive ? '#334155' : '#e2e8f0';

                // Actualizar Ejes
                salesChart.options.scales.y.grid.color = gridColorDyn;
                salesChart.options.scales.y.ticks.color = textColorDyn;
                salesChart.options.scales.x.ticks.color = textColorDyn;

                // Refrescar el gráfico
                salesChart.update();
            }
        });
    });

    observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
})();
</script>

<?php if ($canViewReports): ?>
<style>
/* Gradient styles logic ... */
</style>
<?php endif; ?>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>
