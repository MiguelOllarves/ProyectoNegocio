<?php include __DIR__ . '/../../../includes/header.php'; ?>

<div class="mb-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
    <div>
        <h2 class="text-2xl font-extrabold text-gray-800 dark:text-white tracking-tight"><i class="fas fa-crown text-amber-500 mr-2"></i> Panel de Control (Modo Dios)</h2>
        <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">Gestión Absoluta del Ecosistema Tu Inventario.</p>
    </div>
    <div class="flex gap-2">
        <button onclick="resetVisits()" class="bg-red-100 hover:bg-red-200 text-red-700 px-4 py-2 rounded-lg text-sm font-bold transition-all flex items-center">
            <i class="fas fa-eraser mr-2"></i> Resetear Visitas
        </button>
        <a href="<?= BASE_URL ?>superadmin/backup_db" class="bg-gray-800 hover:bg-black text-white px-4 py-2 rounded-lg text-sm font-bold shadow-md transition-all flex items-center">
            <i class="fas fa-database mr-2 text-emerald-400"></i> Respaldar BD
        </a>
    </div>
</div>

<!-- NAVEGACIÓN MODO DIOS -->
<div class="flex border-b border-gray-200 dark:border-gray-700 mb-8 overflow-x-auto hide-scrollbar">
    <a href="<?= BASE_URL ?>superadmin" class="px-6 py-3 border-b-2 font-bold text-sm border-brand-500 text-brand-600 dark:text-brand-400 whitespace-nowrap">
        <i class="fas fa-chart-pie mr-2"></i> Dashboard & Analytics
    </a>
    <a href="<?= BASE_URL ?>superadmin/users" class="px-6 py-3 border-b-2 border-transparent font-bold text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 hover:border-gray-300 transition-all whitespace-nowrap">
        <i class="fas fa-users-cog mr-2"></i> Usuarios (Gran Hermano)
    </a>
    <a href="<?= BASE_URL ?>superadmin/security" class="px-6 py-3 border-b-2 border-transparent font-bold text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 hover:border-gray-300 transition-all whitespace-nowrap flex items-center">
        <i class="fas fa-shield-alt mr-2"></i> Centro de Seguridad
        <?php if(isset($stats['banned_ips']) && $stats['banned_ips'] > 0): ?>
            <span class="ml-2 bg-red-500 text-white text-[10px] px-2 py-0.5 rounded-full"><?= $stats['banned_ips'] ?> Baneos</span>
        <?php endif; ?>
    </a>
</div>

<script>
function resetVisits() {
    if(confirm('¿Estás seguro de que quieres limpiar TODO el registro de visitas? Esto afectará las analíticas del Dashboard.')) {
        fetch('<?= BASE_URL ?>superadmin/resetVisits', { method: 'POST' })
        .then(r => r.json())
        .then(res => { if(res.success) location.reload(); else alert('Error al limpiar'); });
    }
}
</script>

<!-- GLOBAL KPIs -->
<div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-4 mb-8">
    <div class="bg-white dark:bg-slate-800 p-5 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm border-l-4 border-l-purple-500">
        <p class="text-[10px] uppercase tracking-wider text-gray-500 font-bold mb-1">Visitas (Tráfico)</p>
        <p class="text-3xl font-black text-gray-800 dark:text-white"><?= number_format($stats['total_visits'] ?? 0) ?></p>
    </div>
    <div class="bg-white dark:bg-slate-800 p-5 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm border-l-4 border-l-indigo-500">
        <p class="text-[10px] uppercase tracking-wider text-gray-500 font-bold mb-1">Usuarios Totales</p>
        <p class="text-3xl font-black text-gray-800 dark:text-white"><?= number_format($stats['total_users'] ?? 0) ?></p>
    </div>
    <div class="bg-white dark:bg-slate-800 p-5 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm border-l-4 border-l-brand-500">
        <p class="text-[10px] uppercase tracking-wider text-gray-500 font-bold mb-1">Negocios Radicados</p>
        <p class="text-3xl font-black text-gray-800 dark:text-white"><?= number_format($stats['total_tenants'] ?? 0) ?></p>
    </div>
    <div class="bg-white dark:bg-slate-800 p-5 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm border-l-4 border-l-blue-500">
        <p class="text-[10px] uppercase tracking-wider text-gray-500 font-bold mb-1">Planes de Prueba</p>
        <p class="text-3xl font-black text-gray-800 dark:text-white"><?= number_format($stats['active_trials'] ?? 0) ?></p>
    </div>
    <div class="bg-white dark:bg-slate-800 p-5 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm border-l-4 border-l-red-500">
        <p class="text-[10px] uppercase tracking-wider text-gray-500 font-bold mb-1">Cuentas Inactivas</p>
        <p class="text-3xl font-black text-gray-800 dark:text-white"><?= number_format($stats['expired_accounts'] ?? 0) ?></p>
    </div>
    <div class="bg-white dark:bg-slate-800 p-5 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm border-l-4 border-l-emerald-500">
        <p class="text-[10px] uppercase tracking-wider text-gray-500 font-bold mb-1">Ganancias Brutas</p>
        <p class="text-3xl font-black text-emerald-600 dark:text-emerald-400">$<?= number_format($stats['total_income'], 2) ?></p>
    </div>
</div>

<!-- GRÁFICA DE TRÁFICO (ApexCharts) -->
<div class="bg-white dark:bg-slate-800 p-5 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm mb-8">
    <h3 class="text-sm font-bold text-gray-800 dark:text-white uppercase tracking-wider mb-4"><i class="fas fa-chart-line text-brand-500 mr-2"></i> Tráfico Plataforma (Visitas Últimos 7 Días)</h3>
    <div id="trafficChart" class="w-full h-80"></div>
</div>

<?php 
// Formatear datos para ApexCharts
$jsDates = array_map(function($d) { return date('d M', strtotime($d)); }, array_keys($daily_visits ?? []));
$jsSeries = array_values($daily_visits ?? []);
if(empty($jsDates)) { $jsDates = [date('d M')]; $jsSeries = [0]; }
?>
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var options = {
        series: [{
            name: 'Peticiones/Sesiones Públicas',
            data: <?= json_encode($jsSeries) ?>
        }],
        chart: { type: 'area', height: 320, toolbar: { show: false }, fontFamily: 'Inter, sans-serif' },
        colors: ['#06b6d4'], // cyan-500
        fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.0, stops: [0, 100] } },
        dataLabels: { enabled: false },
        stroke: { curve: 'smooth', width: 3 },
        xaxis: {
            categories: <?= json_encode($jsDates) ?>,
            labels: { style: { colors: '#9ca3af' } }
        },
        yaxis: {
            labels: { style: { colors: '#9ca3af' } }
        },
        grid: { borderColor: '#374151', strokeDashArray: 4 },
        theme: { mode: document.documentElement.classList.contains('dark') ? 'dark' : 'light' }
    };

    var chart = new ApexCharts(document.querySelector("#trafficChart"), options);
    chart.render();
});
</script>

<!-- VALIDACIÓN DE PAGOS REPORTADOS -->
<div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden" x-data="superAdminPayments()">
    <div class="p-5 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
        <h3 class="text-sm font-bold text-gray-800 dark:text-white uppercase tracking-wider"><i class="fas fa-money-bill-wave text-green-500 mr-2"></i> Activación de Suscripciones (Binance/BDV)</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm text-gray-600 dark:text-gray-400 border-collapse cursor-default">
            <thead>
                <tr class="bg-gray-50 dark:bg-gray-800/80 uppercase text-[10px] tracking-wider text-gray-500 dark:text-gray-300 border-b border-gray-200 dark:border-gray-700 px-2">
                    <th class="py-4 px-6 font-bold">Inquilino (Tenant)</th>
                    <th class="py-4 px-6 font-bold">Fecha / Plan</th>
                    <th class="py-4 px-6 font-bold">Método / Ref.</th>
                    <th class="py-4 px-6 font-bold text-right">Monto</th>
                    <th class="py-4 px-6 font-bold text-center">Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($payments)): ?>
                <tr><td colspan="5" class="py-10 text-center text-gray-400">Sin pagos reportados en cola.</td></tr>
                <?php else: foreach ($payments as $p): ?>
                <tr class="border-b border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                    <td class="py-3 px-6">
                        <span class="font-bold text-gray-800 dark:text-gray-200"><?= htmlspecialchars($p['business_name']) ?></span><br>
                        <span class="text-[10px] uppercase text-gray-400">ID: <?= $p['tenant_id'] ?></span>
                    </td>
                    <td class="py-3 px-6 text-gray-500">
                        <span class="text-xs"><?= date('d/m y h:iA', strtotime($p['created_at'])) ?></span><br>
                        <span class="font-medium text-brand-600"><?= $p['plan_name'] ?> (+<?= $p['duration_days'] ?> Días)</span>
                    </td>
                    <td class="py-3 px-6 uppercase text-gray-500">
                        <i class="fas inline-block pr-1 <?= $p['payment_method'] === 'binance' ? 'fa-coins text-yellow-500' : 'fa-mobile-screen text-red-500' ?>"></i> 
                        <?= $p['payment_method'] ?><br>
                        <strong class="font-mono text-xs text-gray-800 dark:text-gray-200"><?= htmlspecialchars($p['reference_number']) ?></strong>
                    </td>
                    <td class="py-3 px-6 text-right font-black text-gray-800 dark:text-white text-lg">$<?= number_format($p['amount'], 2) ?></td>
                    <td class="py-3 px-6 text-center">
                        <?php if($p['status'] === 'pending'): ?>
                        <div class="flex items-center justify-center gap-2">
                            <button @click="processPayment(<?= $p['id'] ?>, 'approve')" class="bg-emerald-500 hover:bg-emerald-400 text-white p-2 rounded-lg shadow-sm" title="Aprobar (Sumar Tiempo)"><i class="fas fa-check"></i></button>
                            <button @click="processPayment(<?= $p['id'] ?>, 'reject')" class="bg-red-500 hover:bg-red-400 text-white p-2 rounded-lg shadow-sm" title="Rechazar"><i class="fas fa-times"></i></button>
                        </div>
                        <?php else: 
                            $badge = $p['status'] === 'approved' ? ['bg-emerald-100 text-emerald-800', 'Aprobado'] : ['bg-red-100 text-red-800', 'Rechazado'];
                        ?>
                            <span class="px-2.5 py-1 text-[10px] uppercase tracking-wider font-extrabold rounded-md <?= $badge[0] ?>"><?= $badge[1] ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('superAdminPayments', () => ({
        async processPayment(id, action) {
            const isApprove = action === 'approve';
            
            const result = await Swal.fire({
                title: isApprove ? '¿Aprobar Pago?' : '¿Rechazar Pago?',
                text: isApprove ? 'Se activará la suscripción del tenant automáticamente.' : 'El pago será marcado como inválido.',
                icon: isApprove ? 'question' : 'warning',
                showCancelButton: true,
                confirmButtonColor: isApprove ? '#10b981' : '#ef4444',
                confirmButtonText: 'Sí, continuar',
                cancelButtonText: 'Cancelar'
            });

            if (result.isConfirmed) {
                const fd = new FormData();
                fd.append('payment_id', id);
                fd.append('action', action);

                try {
                    const res = await fetch('<?= BASE_URL ?>superadmin/process_payment', {
                        method: 'POST',
                        body: fd
                    });
                    const data = await res.json();
                    if(data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Procesado',
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => location.reload());
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                } catch(e) {
                    Swal.fire('Error', 'Fallo de conexión', 'error');
                }
            }
        }
    }))
})
</script>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>
