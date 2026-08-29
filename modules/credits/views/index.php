<?php include __DIR__ . '/../../../includes/header.php'; ?>

<div class="mb-6" x-data="{ openModal: false, filterStatus: '' }">

    <?php
    // =========================================================
    // BANNER DE ALERTAS CRÍTICAS DE CRÉDITOS VENCIDOS
    // Se muestra cuando hay créditos vencidos o por vencer
    // =========================================================
    if (!empty($creditAlerts)):
        $dangerAlerts = array_filter($creditAlerts, fn($a) => $a['alert_level'] === 'danger');
        $warningAlerts = array_filter($creditAlerts, fn($a) => $a['alert_level'] === 'warning');
        $totalDanger = count($dangerAlerts);
        $totalWarning = count($warningAlerts);
    ?>
    <div class="mb-5 space-y-3" x-data="{ showAll: false }">
        <!-- Resumen de alertas -->
        <?php if ($totalDanger > 0): ?>
        <div class="bg-gradient-to-r from-red-600 to-red-700 text-white rounded-xl p-4 shadow-lg shadow-red-500/20 border border-red-500/30">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-exclamation-triangle text-xl animate-pulse"></i>
                </div>
                <div class="flex-1">
                    <h3 class="font-black text-lg">🚨 <?= $totalDanger ?> Crédito<?= $totalDanger > 1 ? 's' : '' ?> Vencido<?= $totalDanger > 1 ? 's' : '' ?></h3>
                    <p class="text-red-100 text-sm">Hay clientes con pagos atrasados que requieren atención inmediata</p>
                </div>
                <button @click="showAll = !showAll" class="bg-white/20 hover:bg-white/30 px-3 py-1.5 rounded-lg text-xs font-bold transition-all flex-shrink-0">
                    <span x-text="showAll ? 'Ocultar' : 'Ver Detalles'"></span>
                    <i class="fas fa-chevron-down ml-1 text-[10px] transition-transform" :class="showAll ? 'rotate-180' : ''"></i>
                </button>
            </div>
            <!-- Lista desplegable de alertas -->
            <div x-show="showAll" x-transition class="space-y-2 mt-3 pt-3 border-t border-white/20">
                <?php foreach ($dangerAlerts as $alert): ?>
                <div class="bg-white/10 backdrop-blur-sm rounded-lg p-3 flex items-center gap-3">
                    <i class="fas fa-<?= $alert['icon'] ?> text-lg flex-shrink-0"></i>
                    <div class="flex-1 min-w-0">
                        <p class="font-bold text-sm"><?= htmlspecialchars($alert['client_name']) ?></p>
                        <p class="text-red-100 text-xs"><?= htmlspecialchars($alert['message']) ?></p>
                    </div>
                    <div class="flex gap-1 flex-shrink-0">
                        <?php if ($alert['client_phone']): ?>
                        <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $alert['client_phone']) ?>?text=<?= urlencode("Hola {$alert['client_name']}, le recordamos que tiene un saldo pendiente de \${$alert['remaining']}. Por favor comuníquese con nosotros.") ?>" 
                           target="_blank" class="bg-green-500 hover:bg-green-400 text-white p-2 rounded-lg transition-colors" title="Cobrar por WhatsApp">
                            <i class="fab fa-whatsapp"></i>
                        </a>
                        <?php endif; ?>
                        <a href="<?= BASE_URL ?>credits/detail/<?= $alert['credit_id'] ?>" class="bg-white/20 hover:bg-white/30 text-white p-2 rounded-lg transition-colors" title="Ver detalle">
                            <i class="fas fa-eye"></i>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($totalWarning > 0): ?>
        <div class="bg-gradient-to-r from-amber-500 to-yellow-500 text-white rounded-xl p-4 shadow-lg shadow-amber-500/20 border border-amber-400/30">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-clock text-xl"></i>
                </div>
                <div class="flex-1">
                    <h3 class="font-black text-lg">⏰ <?= $totalWarning ?> Crédito<?= $totalWarning > 1 ? 's' : '' ?> por Vencer</h3>
                    <p class="text-amber-100 text-sm">
                        <?php foreach ($warningAlerts as $alert): ?>
                            <strong><?= htmlspecialchars($alert['client_name']) ?></strong> — $<?= number_format($alert['remaining'], 2) ?> vence mañana. 
                        <?php endforeach; ?>
                    </p>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Header -->
    <div class="page-header">
        <div class="flex-1">
            <h2 class="page-title">
                <i class="fas fa-hand-holding-usd text-brand-500 mr-2"></i>Créditos y Fiados
            </h2>
            <p class="page-subtitle">Gestiona las cuentas por cobrar de tus clientes</p>
        </div>
        <button @click="openModal = true" class="btn-gradient w-full sm:w-auto">
            <i class="fas fa-plus mr-2"></i> Nuevo Crédito
        </button>
    </div>

    <!-- Filtros -->
    <div class="flex flex-wrap gap-2 mb-5">
        <button @click="filterStatus = ''" 
            :class="filterStatus === '' ? 'bg-gray-800 text-white dark:bg-white dark:text-gray-900' : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 border border-gray-200 dark:border-gray-700'"
            class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all"
            hx-get="<?= BASE_URL ?>credits/list" hx-target="#credits-tbody" hx-trigger="click">
            Todos
        </button>
        <button @click="filterStatus = 'activo'" 
            :class="filterStatus === 'activo' ? 'bg-blue-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 border border-gray-200 dark:border-gray-700'"
            class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all"
            hx-get="<?= BASE_URL ?>credits/list?status=activo" hx-target="#credits-tbody" hx-trigger="click">
            <i class="fas fa-circle text-[6px] mr-1"></i>Activos
        </button>
        <button @click="filterStatus = 'atrasado'" 
            :class="filterStatus === 'atrasado' ? 'bg-red-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 border border-gray-200 dark:border-gray-700'"
            class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all"
            hx-get="<?= BASE_URL ?>credits/list?status=atrasado" hx-target="#credits-tbody" hx-trigger="click">
            <i class="fas fa-exclamation-triangle text-[8px] mr-1"></i>Atrasados
        </button>
        <button @click="filterStatus = 'pagado'" 
            :class="filterStatus === 'pagado' ? 'bg-emerald-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 border border-gray-200 dark:border-gray-700'"
            class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all"
            hx-get="<?= BASE_URL ?>credits/list?status=pagado" hx-target="#credits-tbody" hx-trigger="click">
            <i class="fas fa-check-circle text-[8px] mr-1"></i>Pagados
        </button>
    </div>

    <!-- Buscador -->
    <div class="mb-4">
        <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <i class="fas fa-search text-gray-400"></i>
            </div>
            <input type="text" data-table-search="#credits-tbody" placeholder="Buscar por nombre de cliente..." class="form-input pl-10 h-10">
        </div>
    </div>

    <!-- Tabla de Créditos -->
    <div class="card">
        <div class="table-wrap">
            <table class="min-w-[700px] w-full text-left border-collapse">
                <thead>
                    <tr class="table-head-row">
                        <th class="p-4">Cliente</th>
                        <th class="p-4 text-right">Total</th>
                        <th class="p-4 text-right">Saldo</th>
                        <th class="p-4">Progreso</th>
                        <th class="p-4 text-center">Estado</th>
                        <th class="p-4 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800" id="credits-tbody"
                       hx-get="<?= BASE_URL ?>credits/list" hx-trigger="load">
                    <tr><td colspan="6" class="p-10 text-center text-gray-400"><i class="fas fa-spinner fa-spin text-2xl mb-3 block"></i>Cargando...</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal: Nuevo Crédito -->
    <div x-show="openModal" class="modal-wrapper" style="display: none;" x-data="{ creditType: 'producto', baseAmount: 0 }" x-cloak>
        <div class="modal-container">
            <div x-show="openModal" class="modal-backdrop" @click="openModal = false"></div>
            
            <div x-show="openModal" class="modal-card modal-card-md animate-fade-in-up">
                <div class="modal-header">
                    <h3 class="modal-title">
                        <i class="fas fa-file-invoice-dollar text-brand-500 mr-2"></i> Nuevo Crédito
                    </h3>
                    <button @click="openModal = false" class="modal-close">
                        <i class="fas fa-times text-lg"></i>
                    </button>
                </div>
                
                <div class="modal-body">
                    <form hx-post="<?= BASE_URL ?>credits/create" hx-swap="none" @htmx:after-request="if($event.target === $el && $event.detail.successful) { openModal = false; $el.reset(); Swal.fire({title: '¡Crédito Creado!', text: 'El registro se ha creado correctamente.', icon: 'success', timer: 2000, showConfirmButton: true, confirmButtonText: 'Continuar', confirmButtonColor: '#10b981', customClass: { popup: 'rounded-2xl' }}).then(() => { htmx.ajax('GET', '<?= BASE_URL ?>credits/list?t=' + new Date().getTime(), {target: '#credits-tbody'}); }); }" class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="form-label">Cliente *</label>
                                <select name="client_id" required class="form-select" hx-get="<?= BASE_URL ?>credits/clients_list" hx-trigger="load" hx-swap="innerHTML">
                                    <option value="">Cargando clientes...</option>
                                </select>
                            </div>
                            <div>
                                <label class="form-label">Tipo de Crédito *</label>
                                <select name="credit_type" x-model="creditType" required class="form-select">
                                    <option value="producto">Financiamiento de Producto</option>
                                    <option value="dinero">Préstamo de Dinero</option>
                                </select>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="form-label">Monto Base ($) *</label>
                                <input type="number" name="base_amount" x-model.number="baseAmount" step="0.01" min="0.01" required class="form-input" placeholder="0.00">
                                <p x-show="creditType === 'producto' && baseAmount > 0 && baseAmount < 3" class="text-[10px] text-red-500 mt-1 font-bold">Mínimo para productos es $3</p>
                                <p x-show="creditType === 'dinero' && baseAmount > 0 && baseAmount < 50" class="text-[10px] text-red-500 mt-1 font-bold">Mínimo para préstamos es $50</p>
                            </div>
                            <div x-show="creditType === 'producto'">
                                <label class="form-label">Pago Inicial (%) *</label>
                                <select name="down_payment_rate" class="form-select">
                                    <option value="0">Sin Inicial (0%)</option>
                                    <option value="5">5% de Inicial</option>
                                    <option value="10">10% de Inicial</option>
                                    <option value="20">20% de Inicial</option>
                                    <option value="30">30% de Inicial</option>
                                </select>
                            </div>
                            <div x-show="creditType === 'dinero'" style="display: none;">
                                <label class="form-label">Interés (%) *</label>
                                <select name="interest_rate" class="form-select">
                                    <option value="0">Sin Interés (0%)</option>
                                    <option value="5">5% de Interés</option>
                                    <option value="10">10% de Interés</option>
                                    <option value="20">20% de Interés</option>
                                    <option value="30">30% de Interés</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="form-label">Fecha Límite *</label>
                            <input type="date" name="due_date" required class="form-input">
                        </div>
                        <div>
                            <label class="form-label">Descripción del crédito / ¿Qué se fió? *</label>
                            <textarea name="notes" rows="2" required class="form-input resize-none" placeholder="Ej: 2 sacos de arroz, 1 caja de refresco..."></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" @click="openModal = false" class="btn-secondary flex-1">Cancelar</button>
                    <button type="submit" @click="$el.closest('.modal-card').querySelector('form').dispatchEvent(new Event('submit', { cancelable: true }))" class="btn-gradient flex-1">Registrar Crédito</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- =========================================================
     SISTEMA DE NOTIFICACIONES PUSH DEL NAVEGADOR
     Se ejecuta al cargar la página de créditos.
     Pide permiso al usuario y envía notificaciones nativas.
========================================================= -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Datos de alertas inyectados desde PHP
    var creditAlerts = <?= json_encode($creditAlerts ?? [], JSON_UNESCAPED_UNICODE) ?>;
    
    if (creditAlerts.length === 0) return;

    // 1. Pedir permiso para notificaciones push
    if ('Notification' in window && Notification.permission === 'default') {
        Notification.requestPermission();
    }

    // 2. Si ya tenemos permiso, enviar notificaciones push nativas
    if ('Notification' in window && Notification.permission === 'granted') {
        // Agrupar: solo enviar resumen si hay muchas
        if (creditAlerts.length <= 3) {
            creditAlerts.forEach(function(alert) {
                var notif = new Notification(alert.title, {
                    body: alert.message,
                    icon: '<?= BASE_URL ?>?serve_logo=1',
                    badge: '<?= BASE_URL ?>?serve_logo=1',
                    tag: 'credit-' + alert.credit_id, // Evitar duplicados
                    requireInteraction: true, // No se oculta sola
                    vibrate: [200, 100, 200],
                });
                notif.onclick = function() {
                    window.focus();
                    window.location.href = '<?= BASE_URL ?>credits/detail/' + alert.credit_id;
                };
            });
        } else {
            // Resumen
            var dangerCount = creditAlerts.filter(a => a.alert_level === 'danger').length;
            var warningCount = creditAlerts.filter(a => a.alert_level === 'warning').length;
            var body = '';
            if (dangerCount > 0) body += '🚨 ' + dangerCount + ' crédito(s) vencido(s)\n';
            if (warningCount > 0) body += '⏰ ' + warningCount + ' crédito(s) por vencer';
            
            var notif = new Notification('📋 Alertas de Créditos', {
                body: body,
                icon: '<?= BASE_URL ?>?serve_logo=1',
                tag: 'credit-summary',
                requireInteraction: true,
                vibrate: [200, 100, 200, 100, 200],
            });
            notif.onclick = function() {
                window.focus();
            };
        }
    }

    // 3. SweetAlert visual con sonido para créditos CRÍTICOS (vencidos)
    var dangerAlerts = creditAlerts.filter(a => a.alert_level === 'danger');
    if (dangerAlerts.length > 0) {
        var alertHtml = '<div class="text-left space-y-2">';
        dangerAlerts.forEach(function(a) {
            alertHtml += '<div class="flex items-center gap-2 p-2 bg-red-50 rounded-lg border border-red-200">';
            alertHtml += '<i class="fas fa-' + a.icon + ' text-red-500"></i>';
            alertHtml += '<div><p class="text-sm font-bold text-red-800">' + a.client_name + '</p>';
            alertHtml += '<p class="text-xs text-red-600">$' + parseFloat(a.remaining).toFixed(2) + ' — ' + a.message.split('tiene')[1] + '</p></div>';
            alertHtml += '</div>';
        });
        alertHtml += '</div>';

        Swal.fire({
            title: '🚨 ¡Créditos Vencidos!',
            html: alertHtml,
            icon: 'error',
            confirmButtonText: 'Entendido',
            confirmButtonColor: '#ef4444',
            showCancelButton: true,
            cancelButtonText: 'Ver Atrasados',
            cancelButtonColor: '#6b7280',
            customClass: { popup: 'rounded-2xl' },
            allowOutsideClick: false,
        }).then((result) => {
            if (!result.isConfirmed) {
                // Filtrar por atrasados
                htmx.ajax('GET', '<?= BASE_URL ?>credits/list?status=atrasado', {target: '#credits-tbody'});
            }
        });
    }
});
</script>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>
