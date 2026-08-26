<?php 
include __DIR__ . '/../../../includes/header.php'; 
$bcv = (float)Settings::get('bcv_rate', 622.21);
?>

<div class="page-header">
    <div>
        <h2 class="page-title">Suscripción y Planes</h2>
        <p class="page-subtitle">Garantiza el acceso a tu plataforma con nuestros planes de pago seguro.</p>
    </div>
</div>

<!-- ESTADO ACTUAL DE LA SUSCRIPCION -->
<div class="card p-6 mb-8 text-center sm:text-left">
    <?php if ($status === 'trial'): 
        $daysLeft = max(0, floor((strtotime($trial_ends) - time()) / 86400));
    ?>
    <div class="flex flex-col sm:flex-row items-center gap-6">
        <div class="w-16 h-16 rounded-full bg-brand-50 dark:bg-brand-900/30 text-brand-600 dark:text-brand-400 flex items-center justify-center flex-shrink-0 border border-brand-100 dark:border-brand-800/50">
            <i class="fas fa-hourglass-half text-2xl"></i>
        </div>
        <div>
            <h3 class="text-xl font-bold text-gray-800 dark:text-white mb-1"><span class="text-brand-600 dark:text-brand-400">Período de Prueba</span> (Nivel Global)</h3>
            <p class="text-gray-500 dark:text-gray-400 text-sm">Disfrutas de acceso total al sistema. <strong class="text-brand-600 dark:text-brand-400">Quedan <?= $daysLeft ?> días</strong>.</p>
        </div>
    </div>
    <?php elseif ($status === 'expired'): ?>
    <div class="flex flex-col sm:flex-row items-center gap-6">
        <div class="w-16 h-16 rounded-full bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400 flex items-center justify-center flex-shrink-0 border border-red-100 dark:border-red-800/50">
            <i class="fas fa-lock text-2xl"></i>
        </div>
        <div>
            <h3 class="text-xl font-bold text-red-600 dark:text-red-400 mb-1">Tu suscripción ha expirado</h3>
            <p class="text-gray-500 dark:text-gray-400 text-sm">Actualmente en modo <strong>Solo Lectura</strong>. Activa un plan para recuperar la creación y edición de datos.</p>
        </div>
    </div>
    <?php else: ?>
    <!-- Active Subscription -->
    <div class="flex flex-col sm:flex-row items-center gap-6">
        <div class="w-16 h-16 rounded-full bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 flex items-center justify-center flex-shrink-0 border border-emerald-100 dark:border-emerald-800/50">
            <i class="fas fa-check-circle text-2xl"></i>
        </div>
        <div>
            <h3 class="text-xl font-bold text-gray-800 dark:text-white mb-1">Suscripción Activa</h3>
            <p class="text-gray-500 dark:text-gray-400 text-sm">Gracias por confiar en TuInventario. Plan actual ID: <?= $current_plan ?></p>
        </div>
    </div>
    <?php endif; ?>
</div>

<div x-data="suscriptionPage()" class="relative">
    
    <!-- SELECCIÓN DE PLANES -->
    <div class="mb-8">
        <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-4">Elige tu Plan</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <?php 
            $i = 0;
            foreach ($plans as $plan): 
                $f = json_decode($plan['features_json'], true);
                $bsPrice = $plan['price'] * $bcv;
            ?>
            <div class="card hover:border-brand-300 dark:hover:border-brand-600 p-6 shadow-sm flex flex-col justify-between relative overflow-hidden group">
                
                <?php if($plan['price'] == 199.00): ?>
                <div class="absolute top-0 right-0 bg-gradient-to-r from-amber-400 to-amber-600 text-white text-[10px] font-black px-3 py-1 rounded-bl-lg uppercase tracking-wider shadow-sm z-10">
                    Mejor Valor
                </div>
                <?php endif; ?>

                <div>
                    <h4 class="font-black text-gray-800 dark:text-white text-xl mb-1"><?= htmlspecialchars($plan['name']) ?></h4>
                    <div class="flex items-baseline gap-2 mt-3">
                        <p class="font-black text-brand-600 dark:text-brand-400 text-4xl">$<?= number_format($plan['price'], 2) ?></p>
                        <p class="text-sm font-bold text-gray-400">USD</p>
                    </div>
                    <p class="text-sm font-bold text-gray-500 mt-1">Ref. Bs <?= number_format($bsPrice, 2, ',', '.') ?></p>
                    <p class="text-[10px] text-gray-400 uppercase tracking-wider mt-2 mb-4 font-bold border-b border-gray-100 dark:border-gray-700 pb-3">Por <?= $plan['duration_days'] ?> días</p>
                    
                    <ul class="text-xs text-gray-600 dark:text-gray-300 space-y-3 mt-4">
                        <li class="flex items-start"><i class="fas fa-check-circle text-brand-500 mr-2 mt-0.5"></i> <span><strong><?= $f['limit_users'] == 999 ? 'Ilimitados' : $f['limit_users'] ?></strong> Usuarios del sistema</span></li>
                        <li class="flex items-start"><i class="fas fa-check-circle text-brand-500 mr-2 mt-0.5"></i> <span><strong><?= $f['limit_products'] == 999999 ? 'Ilimitados' : $f['limit_products'] ?></strong> Productos en inventario</span></li>
                        <li class="flex items-start"><i class="fas fa-check-circle text-brand-500 mr-2 mt-0.5"></i> <span>Soporte <?= $plan['price'] >= 20 ? 'Prioritario 24/7' : 'Estándar' ?></span></li>
                        <li class="flex items-start"><i class="fas fa-check-circle text-brand-500 mr-2 mt-0.5"></i> <span>Respaldos automáticos en la nube</span></li>
                        
                        <?php if(!empty($f['custom_module'])): ?>
                        <li class="flex items-start"><i class="fas fa-star text-amber-500 mr-2 mt-0.5"></i> <span class="font-bold text-amber-700 dark:text-amber-400">Opción a solicitar módulos a medida</span></li>
                        <?php endif; ?>
                    </ul>
                </div>
                
                <div class="mt-8">
                    <button @click="selectPlan(<?= htmlspecialchars(json_encode($plan)) ?>); isModalOpen = true" class="w-full btn-secondary group-hover:bg-brand-500 group-hover:text-white group-hover:border-brand-500">
                        Seleccionar Plan
                    </button>
                </div>
            </div>
            
            <?php if ($i == 0): ?>
            <!-- Columna de reseña central -->
            <div class="bg-gradient-to-br from-brand-50 to-brand-100 dark:from-slate-800 dark:to-slate-700 rounded-2xl p-8 shadow-sm flex flex-col justify-center items-center text-center border border-brand-200 dark:border-brand-700">
                <div class="w-16 h-16 bg-white dark:bg-slate-900 rounded-full flex items-center justify-center shadow-md mb-6 text-brand-500 text-2xl border-4 border-brand-50 dark:border-slate-800">
                    <i class="fas fa-rocket"></i>
                </div>
                <h4 class="font-extrabold text-gray-800 dark:text-white text-xl mb-4">¿Por qué adquirir un plan?</h4>
                <p class="text-sm font-medium text-gray-600 dark:text-gray-300 leading-relaxed mb-4">
                    Más allá de ser una potente herramienta de gestión y soporte continuo que fortalece tu negocio, tu suscripción nos permite impulsar <strong>el desarrollo constante y mantener en línea nuestra infraestructura</strong>.
                </p>
                <p class="text-sm font-bold text-brand-700 dark:text-brand-300">
                    Gracias por tu apoyo, seguiremos creando valor para ti.
                </p>
            </div>
            <?php endif; ?>
            
            <?php 
                $i++;
                endforeach; 
            ?>
        </div>
    </div>

    <!-- PAGO Y CONFIRMACIÓN (MODAL) -->
    <div x-show="isModalOpen" style="display: none;" class="modal-wrapper" x-cloak>
        <div class="modal-container">
            <div @click="isModalOpen = false" class="modal-backdrop"></div>
            <div class="modal-card modal-card-lg animate-fade-in-up">
                <button @click="isModalOpen = false" class="modal-close absolute top-4 right-4 z-10">
                    <i class="fas fa-times"></i>
                </button>

                <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-brand-400 to-brand-600"></div>
                
                <div class="modal-body pb-0 pt-6">
                    <h3 class="text-xl font-black text-gray-800 dark:text-white mb-1"><i class="fas fa-file-invoice-dollar mr-2 text-brand-500"></i> Reportar Pago</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Contratando: <strong x-text="selectedPlan?.name" class="text-brand-600 text-lg ml-1"></strong> por <strong class="text-gray-800 dark:text-white">US$ <span x-text="selectedPlan?.price"></span></strong></p>

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
                        <input type="text" x-model="form.reference_number" class="w-full bg-white dark:bg-slate-900 border border-gray-200 dark:border-gray-700 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-brand-500 outline-none shadow-sm" placeholder="Ej: 124567990" required>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">Capture de Pago</label>
                    <input type="file" @change="form.proof_image = $event.target.files[0]" accept="image/*" class="w-full bg-white dark:bg-slate-900 border border-gray-200 dark:border-gray-700 rounded-xl px-4 py-2 text-sm focus:ring-2 focus:ring-brand-500 outline-none shadow-sm file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100" required>
                </div>

                </div>
                <div class="modal-footer p-6">
                    <button type="submit" form="payForm" :disabled="loading" class="btn-gradient w-full py-4 text-base">
                        <i class="fas fa-paper-plane" x-show="!loading"></i>
                        <i class="fas fa-spinner fa-spin" x-show="loading"></i>
                        <span x-text="loading ? 'Enviando Confirmación...' : 'Reportar Pago'" class="ml-2"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- HISTORIAL DE PAGOS -->
<!-- HISTORIAL DE PAGOS -->
<div class="card mt-8">
    <div class="card-header">
        <h3 class="text-sm font-bold text-gray-800 dark:text-white uppercase tracking-wider"><i class="fas fa-history mr-2 text-gray-400"></i> Historial de Pagos</h3>
    </div>
    <div class="table-wrap">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="table-head-row">
                    <th class="p-4">Fecha / Hora</th>
                    <th class="p-4">Plan Solicitado</th>
                    <th class="p-4">Método</th>
                    <th class="p-4">Referencia</th>
                    <th class="p-4 text-right">Monto</th>
                    <th class="p-4 text-center">Estado</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                <?php if (empty($payments)): ?>
                <tr><td colspan="6" class="p-8 text-center text-gray-400">No hay pagos reportados todavía.</td></tr>
                <?php else: foreach ($payments as $p): 
                    $badgeArr = [
                        'pending' => ['badge-warning', 'En Revisión'],
                        'approved' => ['badge-success', 'Pagado'],
                        'rejected' => ['badge-danger', 'Rechazado']
                    ];
                    $badge = $badgeArr[$p['status']];
                ?>
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                    <td class="p-4 text-sm font-medium text-gray-800 dark:text-gray-300"><?= date('d/m/Y h:i A', strtotime($p['created_at'])) ?></td>
                    <td class="p-4 text-sm text-gray-500">Plan ID: <?= $p['plan_id'] ?></td>
                    <td class="p-4 text-sm uppercase text-gray-500"><i class="fas inline-block pr-1 <?= $p['payment_method'] === 'binance' ? 'fa-coins text-yellow-500' : 'fa-mobile-screen text-red-500' ?>"></i> <?= $p['payment_method'] ?></td>
                    <td class="p-4 font-mono text-xs text-gray-500"><?= htmlspecialchars($p['reference_number']) ?></td>
                    <td class="p-4 text-right font-black text-gray-800 dark:text-white">$<?= number_format($p['amount'], 2) ?></td>
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
        selectedPlan: null,
        isModalOpen: false,
        loading: false,
        form: {
            payment_method: '',
            reference_number: '',
            proof_image: null
        },
        selectPlan(plan) {
            this.selectedPlan = plan;
            this.form.payment_method = '';
            this.form.reference_number = '';
            this.form.proof_image = null;
        },
        async submitPayment() {
            if(!this.selectedPlan || !this.form.payment_method || !this.form.reference_number || !this.form.proof_image) {
                Swal.fire('Atención', 'Por favor completa todos los campos, incluyendo el capture de pago.', 'warning');
                return;
            }
            this.loading = true;

            const fd = new FormData();
            fd.append('plan_id', this.selectedPlan.id);
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
                        title: 'Reporte Enviado',
                        text: data.message,
                        confirmButtonText: 'Entendido'
                    }).then(() => location.reload());
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            } catch (e) {
                Swal.fire('Error', 'Fallo de conexión', 'error');
            }
            this.loading = false;
        }
    }))
})
</script>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>
