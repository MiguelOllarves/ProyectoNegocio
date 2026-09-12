<?php 
include __DIR__ . '/../../../includes/header.php'; 
$bcv = (float)Settings::get('bcv_rate', 622.21);
$bsPrice = $price * $bcv;
?>

<div class="page-header">
    <div>
        <h2 class="page-title">Suscripción</h2>
        <p class="page-subtitle">Mantén tu negocio activo con solo $3 USD al mes.</p>
    </div>
</div>

<!-- ESTADO ACTUAL DE LA SUSCRIPCIÓN -->
<div class="card p-6 mb-8">
    <?php if ($status === 'trial'): ?>
    <div class="flex flex-col sm:flex-row items-center gap-6">
        <div class="w-16 h-16 rounded-full bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 flex items-center justify-center flex-shrink-0 border border-blue-100 dark:border-blue-800/50">
            <i class="fas fa-gift text-2xl"></i>
        </div>
        <div class="flex-1">
            <h3 class="text-xl font-bold text-gray-800 dark:text-white mb-1">
                <span class="text-blue-600 dark:text-blue-400">Período de Prueba</span>
            </h3>
            <p class="text-gray-500 dark:text-gray-400 text-sm">
                Disfrutas de acceso total al sistema. <strong class="text-blue-600">Quedan <?= $days_remaining ?> días</strong>.
            </p>
            <p class="text-xs text-gray-400 mt-1">Tu prueba termina el <?= date('d/m/Y', strtotime($expires_at)) ?></p>
        </div>
        <div class="text-right">
            <span class="badge badge-info">PRUEBA</span>
        </div>
    </div>
    
    <?php elseif ($status === 'active'): ?>
    <div class="flex flex-col sm:flex-row items-center gap-6">
        <div class="w-16 h-16 rounded-full bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 flex items-center justify-center flex-shrink-0 border border-emerald-100 dark:border-emerald-800/50">
            <i class="fas fa-check-circle text-2xl"></i>
        </div>
        <div class="flex-1">
            <h3 class="text-xl font-bold text-gray-800 dark:text-white mb-1">
                <span class="text-emerald-600 dark:text-emerald-400">Suscripción Activa</span>
            </h3>
            <p class="text-gray-500 dark:text-gray-400 text-sm">
                Tu suscripción está activa. <strong class="text-emerald-600">Quedan <?= $days_remaining ?> días</strong> de acceso.
            </p>
            <p class="text-xs text-gray-400 mt-1">Vence el <?= date('d/m/Y', strtotime($expires_at)) ?> a las 23:59</p>
        </div>
        <div class="text-right">
            <span class="badge badge-success">ACTIVO</span>
            <p class="text-xs text-gray-400 mt-1">Próximo pago disponible: <?= $next_payment_date ?></p>
        </div>
    </div>
    
    <?php else: ?>
    <div class="flex flex-col sm:flex-row items-center gap-6">
        <div class="w-16 h-16 rounded-full bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400 flex items-center justify-center flex-shrink-0 border border-red-100 dark:border-red-800/50">
            <i class="fas fa-lock text-2xl"></i>
        </div>
        <div class="flex-1">
            <h3 class="text-xl font-bold text-red-600 dark:text-red-400 mb-1">Suscripción Vencida</h3>
            <p class="text-gray-500 dark:text-gray-400 text-sm">
                Tu acceso está en <strong>solo lectura</strong>. Para recuperar todas las funciones, activa tu suscripción mensual.
            </p>
            <p class="text-xs text-gray-400 mt-1">El pago está disponible a partir del próximo mes.</p>
        </div>
        <div class="text-right">
            <span class="badge badge-danger">VENCIDO</span>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- PLAN ÚNICO $3/MES -->
<div class="mb-8">
    <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-4">Plan Mensual</h3>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        
        <!-- Card del Plan -->
        <div class="card border-2 border-brand-300 dark:border-brand-600 p-6 shadow-md relative overflow-hidden">
            <div class="absolute top-0 right-0 bg-gradient-to-r from-brand-400 to-brand-600 text-white text-[10px] font-black px-3 py-1 rounded-bl-lg uppercase tracking-wider">
                Único Plan
            </div>
            
            <div>
                <h4 class="font-black text-gray-800 dark:text-white text-2xl mb-1">Plan Mensual</h4>
                <div class="flex items-baseline gap-2 mt-4">
                    <p class="font-black text-brand-600 dark:text-brand-400 text-5xl">$3</p>
                    <p class="text-sm font-bold text-gray-400">USD / mes</p>
                </div>
                <p class="text-sm font-bold text-gray-500 mt-2">Ref. Bs <?= number_format($bsPrice, 2, ',', '.') ?></p>
                
                <ul class="text-sm text-gray-600 dark:text-gray-300 space-y-3 mt-6">
                    <li class="flex items-start"><i class="fas fa-check-circle text-brand-500 mr-2 mt-0.5"></i> <span><strong>Ilimitados</strong> Usuarios del sistema</span></li>
                    <li class="flex items-start"><i class="fas fa-check-circle text-brand-500 mr-2 mt-0.5"></i> <span><strong>Ilimitados</strong> Productos en inventario</span></li>
                    <li class="flex items-start"><i class="fas fa-check-circle text-brand-500 mr-2 mt-0.5"></i> <span>Soporte prioritario 24/7</span></li>
                    <li class="flex items-start"><i class="fas fa-check-circle text-brand-500 mr-2 mt-0.5"></i> <span>Respaldos automáticos en la nube</span></li>
                    <li class="flex items-start"><i class="fas fa-star text-amber-500 mr-2 mt-0.5"></i> <span class="font-bold text-amber-700 dark:text-amber-400">Módulos a medida disponibles</span></li>
                </ul>
            </div>
            
            <div class="mt-8">
                <?php if ($can_pay && ($status === 'expired' || $status === 'trial')): ?>
                <button @click="openPaymentModal()" class="w-full btn-gradient text-lg py-4">
                    <i class="fas fa-credit-card mr-2"></i> Activar Suscripción - $3 USD
                </button>
                <?php elseif (!$can_pay && $status === 'active'): ?>
                <div class="w-full text-center py-4 bg-emerald-50 dark:bg-emerald-900/20 rounded-xl border border-emerald-200 dark:border-emerald-800">
                    <i class="fas fa-check-circle text-emerald-500 mr-2"></i>
                    <span class="text-emerald-700 dark:text-emerald-300 font-bold">Ya tienes suscripción activa este mes</span>
                </div>
                <?php else: ?>
                <div class="w-full text-center py-4 bg-gray-50 dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
                    <i class="fas fa-clock text-gray-400 mr-2"></i>
                    <span class="text-gray-500 dark:text-gray-400 font-bold">Próximo pago disponible: <?= $next_payment_date ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Info -->
        <div class="bg-gradient-to-br from-brand-50 to-brand-100 dark:from-slate-800 dark:to-slate-700 rounded-2xl p-8 shadow-sm flex flex-col justify-center items-center text-center border border-brand-200 dark:border-brand-700">
            <div class="w-16 h-16 bg-white dark:bg-slate-900 rounded-full flex items-center justify-center shadow-md mb-6 text-brand-500 text-2xl border-4 border-brand-50 dark:border-slate-800">
                <i class="fas fa-calendar-check"></i>
            </div>
            <h4 class="font-extrabold text-gray-800 dark:text-white text-xl mb-4">¿Cómo funciona?</h4>
            <div class="text-sm font-medium text-gray-600 dark:text-gray-300 leading-relaxed space-y-3">
                <p>📅 <strong>Pago mensual:</strong> Solo $3 USD por mes, sin compromisos anuales.</p>
                <p>🔄 <strong>Renovación automática:</strong> Tu suscripción vence el último día de cada mes.</p>
                <p>⚡ <strong>Activación inmediata:</strong> Al confirmar tu pago, tu acceso se activa al instante.</p>
                <p>🔒 <strong>Modo seguro:</strong> Si vences, puedes seguir viendo tus datos en modo lectura.</p>
            </div>
            <p class="text-sm font-bold text-brand-700 dark:text-brand-300 mt-6">
                Gracias por confiar en TuInventario. 💚
            </p>
        </div>
    </div>
</div>

<!-- MODAL DE PAGO -->
<div x-show="isModalOpen" style="display: none;" class="modal-wrapper" x-cloak>
    <div class="modal-container">
        <div @click="isModalOpen = false" class="modal-backdrop"></div>
        <div class="modal-card modal-card-lg animate-fade-in-up">
            <button @click="isModalOpen = false" class="modal-close absolute top-4 right-4 z-10">
                <i class="fas fa-times"></i>
            </button>

            <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-brand-400 to-brand-600"></div>
            
            <div class="modal-body pb-0 pt-6">
                <h3 class="text-xl font-black text-gray-800 dark:text-white mb-1">
                    <i class="fas fa-file-invoice-dollar mr-2 text-brand-500"></i> Reportar Pago
                </h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">
                    Suscripción Mensual por <strong class="text-brand-600 text-lg ml-1">US$ 3.00</strong>
                </p>

                <!-- INSTRUCCIONES BANCARIAS Y BINANCE -->
                <div class="bg-slate-50 dark:bg-slate-900/50 rounded-xl p-4 mb-6 border border-slate-200 dark:border-slate-700/50">
                    <h4 class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-3">Cuentas Recaudadoras</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs" x-data="{ qrMode: null }">
                        <div class="bg-white dark:bg-slate-800 p-3 rounded-lg shadow-sm border border-gray-100 dark:border-gray-700 relative">
                            <div class="font-bold text-gray-800 dark:text-white mb-2 flex items-center justify-between">
                                <span class="flex items-center"><img src="https://upload.wikimedia.org/wikipedia/commons/e/e8/Binance_Logo.svg" class="w-4 h-4 mr-1.5 filter dark:invert" alt="Binance"> Binance Pay</span>
                                <button type="button" @click="qrMode = (qrMode === 'binance' ? null : 'binance')" class="text-[10px] bg-yellow-50 text-yellow-700 px-2 py-1 rounded-md hover:bg-yellow-100 transition"><i class="fas fa-qrcode mr-1"></i> Ver QR</button>
                            </div>
                            <div x-show="qrMode !== 'binance'">
                                <p class="text-gray-600 dark:text-gray-400"><strong>Pay ID:</strong> MaomSkill</p>
                                <p class="text-gray-600 dark:text-gray-400"><strong>USDT:</strong> Red TRC20</p>
                            </div>
                            <div x-show="qrMode === 'binance'" style="display:none;" class="text-center py-2 relative">
                                <img src="<?= BASE_URL ?>assets/images/binance_qr.jpg" class="w-28 h-28 mx-auto rounded-lg shadow-sm border border-gray-200">
                                <p class="text-[9px] text-gray-500 mt-1">Escanea desde tu App Binance</p>
                            </div>
                        </div>
                        <div class="bg-white dark:bg-slate-800 p-3 rounded-lg shadow-sm border border-gray-100 dark:border-gray-700 relative">
                            <div class="font-bold text-gray-800 dark:text-white mb-2 flex items-center justify-between">
                                <span class="flex items-center"><i class="fas fa-university text-red-500 mr-1.5"></i> Pago Móvil BDV</span>
                                <button type="button" @click="qrMode = (qrMode === 'bdv' ? null : 'bdv')" class="text-[10px] bg-red-50 text-red-600 px-2 py-1 rounded-md hover:bg-red-100 transition"><i class="fas fa-qrcode mr-1"></i> Ver QR</button>
                            </div>
                            <div x-show="qrMode !== 'bdv'">
                                <p class="text-gray-600 dark:text-gray-400 flex justify-between"><span><strong>Cédula:</strong> V-18224757</span> <button type="button" onclick="navigator.clipboard.writeText('18224757')" class="text-gray-400 hover:text-brand-500"><i class="fas fa-copy"></i></button></p>
                                <p class="text-gray-600 dark:text-gray-400 flex justify-between"><span><strong>Tel:</strong> 0414-5176772</span> <button type="button" onclick="navigator.clipboard.writeText('04145176772')" class="text-gray-400 hover:text-brand-500"><i class="fas fa-copy"></i></button></p>
                                <p class="text-gray-600 dark:text-gray-400"><strong>Banco:</strong> BDV (0102)</p>
                            </div>
                            <div x-show="qrMode === 'bdv'" style="display:none;" class="text-center py-2 relative">
                                <img src="<?= BASE_URL ?>assets/images/bdv_qr.jpg" alt="QR Pago Movil" class="w-28 h-28 mx-auto rounded-lg shadow-sm border border-gray-200 object-cover">
                                <p class="text-[9px] text-gray-500 mt-1">Escanea con tu App de Banco de Venezuela</p>
                            </div>
                        </div>
                    </div>
                    <p class="text-[10px] font-bold text-center mt-3 text-gray-500">*Los pagos en Bs se calculan a la tasa oficial del BCV del día.</p>
                    <p class="text-[10px] font-bold text-center mt-1 text-amber-600">*Envía exactamente $3.00 USD o su equivalente en Bs.</p>
                </div>

                <form id="payForm" @submit.prevent="submitPayment" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">Método de Pago</label>
                            <select x-model="form.payment_method" class="w-full bg-white dark:bg-slate-900 border border-gray-200 dark:border-gray-700 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-brand-500 outline-none shadow-sm" required>
                                <option value="">-- Seleccionar --</option>
                                <option value="binance">Binance Pay / USDT</option>
                                <option value="bdv">Pago Móvil (BDV)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">Ref. / ID Transacción</label>
                            <input type="text" x-model="form.reference_number" class="w-full bg-white dark:bg-slate-900 border border-gray-200 dark:border-gray-700 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-brand-500 outline-none shadow-sm" placeholder="Ej: 124567990" required minlength="5" maxlength="100">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">Capture de Pago</label>
                        <input type="file" @change="form.proof_image = $event.target.files[0]" accept="image/*" class="w-full bg-white dark:bg-slate-900 border border-gray-200 dark:border-gray-700 rounded-xl px-4 py-2 text-sm focus:ring-2 focus:ring-brand-500 outline-none shadow-sm file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100" required>
                        <p class="text-[10px] text-gray-400 mt-1">Máximo 5MB. Formatos: JPG, PNG, WEBP</p>
                    </div>
                </div>
                <div class="modal-footer p-6">
                    <button type="submit" form="payForm" :disabled="loading" class="btn-gradient w-full py-4 text-base">
                        <i class="fas fa-paper-plane" x-show="!loading"></i>
                        <i class="fas fa-spinner fa-spin" x-show="loading"></i>
                        <span x-text="loading ? 'Procesando...' : 'Reportar Pago - $3 USD'" class="ml-2"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- HISTORIAL DE PAGOS -->
<div class="card mt-8">
    <div class="card-header">
        <h3 class="text-sm font-bold text-gray-800 dark:text-white uppercase tracking-wider">
            <i class="fas fa-history mr-2 text-gray-400"></i> Historial de Pagos
        </h3>
    </div>
    <div class="table-wrap">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="table-head-row">
                    <th class="p-4">Fecha</th>
                    <th class="p-4">Monto</th>
                    <th class="p-4">Método</th>
                    <th class="p-4">Referencia</th>
                    <th class="p-4 text-center">Estado</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                <?php if (empty($payments)): ?>
                <tr><td colspan="5" class="p-8 text-center text-gray-400">No hay pagos registrados.</td></tr>
                <?php else: foreach ($payments as $p): 
                    $badgeArr = [
                        'pending' => ['badge-warning', 'En Revisión'],
                        'approved' => ['badge-success', 'Aprobado'],
                        'rejected' => ['badge-danger', 'Rechazado']
                    ];
                    $badge = $badgeArr[$p['status']] ?? ['badge-secondary', 'Desconocido'];
                ?>
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                    <td class="p-4 text-sm font-medium text-gray-800 dark:text-gray-300">
                        <?= date('d/m/Y', strtotime($p['created_at'])) ?>
                    </td>
                    <td class="p-4 text-right font-black text-gray-800 dark:text-white">
                        $<?= number_format($p['amount'], 2) ?>
                    </td>
                    <td class="p-4 text-sm uppercase text-gray-500">
                        <i class="fas inline-block pr-1 <?= $p['payment_method'] === 'binance' ? 'fa-coins text-yellow-500' : 'fa-mobile-screen text-red-500' ?>"></i> 
                        <?= $p['payment_method'] ?>
                    </td>
                    <td class="p-4 font-mono text-xs text-gray-500">
                        <?= htmlspecialchars($p['reference_number']) ?>
                    </td>
                    <td class="p-4 text-center">
                        <span class="<?= $badge[0] ?>"><?= $badge[1] ?></span>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('suscriptionPage', () => ({
        isModalOpen: false,
        loading: false,
        form: {
            payment_method: '',
            reference_number: '',
            proof_image: null
        },
        openPaymentModal() {
            this.form.payment_method = '';
            this.form.reference_number = '';
            this.form.proof_image = null;
            this.isModalOpen = true;
        },
        async submitPayment() {
            if(!this.form.payment_method || !this.form.reference_number || !this.form.proof_image) {
                Swal.fire('Atención', 'Por favor completa todos los campos, incluyendo el capture de pago.', 'warning');
                return;
            }
            
            if(this.form.reference_number.length < 5) {
                Swal.fire('Atención', 'La referencia debe tener al menos 5 caracteres.', 'warning');
                return;
            }
            
            this.loading = true;

            const fd = new FormData();
            fd.append('payment_method', this.form.payment_method);
            fd.append('reference_number', this.form.reference_number);
            fd.append('proof_image', this.form.proof_image);

            try {
                const res = await fetch('<?= BASE_URL ?>suscription/pay', {
                    method: 'POST',
                    body: fd
                });
                const data = await res.json();
                if(data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Suscripción Activada!',
                        text: data.message,
                        confirmButtonText: 'Entendido'
                    }).then(() => location.reload());
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            } catch (e) {
                Swal.fire('Error', 'Fallo de conexión. Intenta de nuevo.', 'error');
            }
            this.loading = false;
        }
    }))
})
</script>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>
