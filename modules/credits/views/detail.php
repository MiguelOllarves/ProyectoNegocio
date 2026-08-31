<?php include __DIR__ . '/../../../includes/header.php'; ?>

<div x-data="{ openPaymentModal: false }">
    <!-- Back + Header -->
    <div class="mb-6">
        <a href="<?= BASE_URL ?>credits" class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-brand-600 dark:text-gray-400 dark:hover:text-brand-400 font-medium mb-3 transition-colors">
            <i class="fas fa-arrow-left"></i> Volver a Créditos
        </a>
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
            <div>
                <h2 class="text-xl sm:text-2xl font-bold text-gray-800 dark:text-white">
                    Cuenta de <?= htmlspecialchars($client['name'] ?? 'Cliente') ?>
                </h2>
                <p class="text-gray-500 dark:text-gray-400 text-sm">
                    <?= htmlspecialchars($client['document'] ?? '') ?> • <?= htmlspecialchars($client['phone'] ?? 'Sin teléfono') ?>
                    <?php if (!empty($client['email'])): ?>
                        • <?= htmlspecialchars($client['email']) ?>
                    <?php endif; ?>
                </p>
            </div>
            <div class="flex gap-2 w-full sm:w-auto">
                <a href="<?= BASE_URL ?>credits/ticket/<?= $credit['id'] ?>" target="_blank" class="bg-gray-100 hover:bg-gray-200 text-gray-700 dark:bg-gray-700 dark:hover:bg-gray-600 dark:text-gray-300 px-4 py-2.5 rounded-xl text-sm font-bold transition-all shadow-sm flex items-center flex-1 sm:flex-auto justify-center gap-2">
                    <i class="fas fa-print"></i> Pagaré
                </a>
                <?php if ($credit['status'] !== 'pagado'): ?>
                <button @click="openPaymentModal = true" class="bg-brand-600 hover:bg-brand-700 text-white px-4 py-2.5 rounded-xl text-sm font-bold transition-all shadow-sm flex items-center flex-1 sm:flex-auto justify-center gap-2">
                    <i class="fas fa-plus"></i> Abono
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php
    // =========================================================
    // ALERTA DE VENCIMIENTO EN EL DETALLE DEL CRÉDITO
    // =========================================================
    if ($dueDateInfo):
        $diffDays = $dueDateInfo['diff_days'];
    ?>
        <?php if ($diffDays < 0): ?>
            <?php $daysOverdue = abs($diffDays); ?>
            <div class="mb-5 bg-gradient-to-r from-red-600 to-red-700 text-white rounded-xl p-4 shadow-lg shadow-red-500/20 animate-pulse">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-exclamation-triangle text-2xl"></i>
                    </div>
                    <div class="flex-1">
                        <h3 class="font-black text-lg">
                            🚨 <?= $daysOverdue === 1 ? '¡Este crédito se venció AYER!' : "¡Crédito atrasado por $daysOverdue días!" ?>
                        </h3>
                        <p class="text-red-100 text-sm">
                            La fecha límite era el <?= $dueDateInfo['due_date_formatted'] ?>. 
                            El cliente debe $<?= number_format($credit['remaining_amount'], 2) ?>.
                            <?php if (!empty($client['phone'])): ?>
                                Contactar de inmediato.
                            <?php endif; ?>
                        </p>
                    </div>
                    <?php if (!empty($client['phone'])): ?>
                    <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $client['phone']) ?>?text=<?= urlencode("Hola {$client['name']}, le recordamos que tiene un saldo pendiente de \${$credit['remaining_amount']} con fecha de vencimiento del {$dueDateInfo['due_date_formatted']}. Por favor comuníquese con nosotros para resolver su cuenta. Gracias.") ?>" 
                       target="_blank" class="bg-green-500 hover:bg-green-400 text-white p-3 rounded-xl transition-colors flex-shrink-0 shadow-lg" title="Cobrar por WhatsApp">
                        <i class="fab fa-whatsapp text-xl"></i>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php elseif ($diffDays === 0): ?>
            <div class="mb-5 bg-gradient-to-r from-amber-500 to-orange-500 text-white rounded-xl p-4 shadow-lg shadow-amber-500/20">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-exclamation-circle text-2xl"></i>
                    </div>
                    <div class="flex-1">
                        <h3 class="font-black text-lg">🔴 ¡Este crédito VENCE HOY!</h3>
                        <p class="text-amber-100 text-sm">La fecha límite de pago es hoy <?= $dueDateInfo['due_date_formatted'] ?>. Monto pendiente: $<?= number_format($credit['remaining_amount'], 2) ?></p>
                    </div>
                    <?php if (!empty($client['phone'])): ?>
                    <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $client['phone']) ?>?text=<?= urlencode("Hola {$client['name']}, le recordamos que su pago de \${$credit['remaining_amount']} vence HOY. ¿Desea realizar su abono? Estamos a su orden.") ?>" 
                       target="_blank" class="bg-green-500 hover:bg-green-400 text-white p-3 rounded-xl transition-colors flex-shrink-0 shadow-lg" title="Recordar por WhatsApp">
                        <i class="fab fa-whatsapp text-xl"></i>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php elseif ($diffDays === 1): ?>
            <div class="mb-5 bg-gradient-to-r from-yellow-400 to-amber-400 text-white rounded-xl p-4 shadow-lg shadow-yellow-500/20">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-clock text-xl"></i>
                    </div>
                    <div class="flex-1">
                        <h3 class="font-black text-lg">⏰ Este crédito vence MAÑANA</h3>
                        <p class="text-yellow-100 text-sm">Fecha límite: <?= $dueDateInfo['due_date_formatted'] ?>. Monto pendiente: $<?= number_format($credit['remaining_amount'], 2) ?></p>
                    </div>
                </div>
            </div>
        <?php elseif ($diffDays <= 3 && $diffDays > 1): ?>
            <div class="mb-5 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-4">
                <div class="flex items-center gap-3">
                    <i class="fas fa-info-circle text-blue-500 text-lg"></i>
                    <p class="text-sm font-medium text-blue-800 dark:text-blue-300">
                        📅 Este crédito vence en <strong><?= $diffDays ?> días</strong> (<?= $dueDateInfo['due_date_formatted'] ?>)
                    </p>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- KPI Cards -->
    <?php
        $pct = $credit['total_amount'] > 0 ? min(100, round(($credit['paid_amount'] / $credit['total_amount']) * 100)) : 0;
        $statusLabel = ['activo' => 'Activo', 'pagado' => 'Pagado', 'atrasado' => 'Atrasado', 'cancelado' => 'Cancelado'][$credit['status']] ?? 'Activo';
        $statusColor = ['activo' => 'blue', 'pagado' => 'emerald', 'atrasado' => 'red', 'cancelado' => 'gray'][$credit['status']] ?? 'blue';
    ?>
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-4">
            <p class="text-[10px] uppercase tracking-wider font-bold text-gray-400 mb-1">Total Crédito</p>
            <p class="text-xl font-black text-gray-800 dark:text-white">$<?= number_format($credit['total_amount'], 2) ?></p>
            <?php if (($credit['credit_type'] ?? '') === 'dinero' && ($credit['interest_rate'] ?? 0) > 0): ?>
                <p class="text-[10px] text-gray-500 mt-1">Monto base: $<?= number_format($credit['base_amount'] ?? 0, 2) ?> + <?= floatval($credit['interest_rate']) ?>% Int.</p>
            <?php elseif (($credit['credit_type'] ?? '') === 'producto'): ?>
                <p class="text-[10px] text-gray-500 mt-1">Financiamiento de producto</p>
            <?php endif; ?>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-4">
            <p class="text-[10px] uppercase tracking-wider font-bold text-gray-400 mb-1">Total Abonado</p>
            <p class="text-xl font-black text-brand-600">$<?= number_format($credit['paid_amount'], 2) ?></p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-4">
            <p class="text-[10px] uppercase tracking-wider font-bold text-gray-400 mb-1">Saldo Pendiente</p>
            <p class="text-xl font-black text-red-500">$<?= number_format($credit['remaining_amount'], 2) ?></p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-4">
            <p class="text-[10px] uppercase tracking-wider font-bold text-gray-400 mb-1">Estado</p>
            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-<?= $statusColor ?>-100 text-<?= $statusColor ?>-700 dark:bg-<?= $statusColor ?>-900/30 dark:text-<?= $statusColor ?>-400 <?= $credit['status'] === 'atrasado' ? 'animate-pulse' : '' ?>">
                <?php if ($credit['status'] === 'atrasado'): ?><i class="fas fa-exclamation-triangle text-[10px]"></i><?php endif; ?>
                <?= $statusLabel ?>
            </span>
        </div>
    </div>

    <!-- Barra de Progreso -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-4 mb-6">
        <div class="flex justify-between text-sm mb-2">
            <span class="font-bold text-gray-600 dark:text-gray-300">Progreso de pago</span>
            <span class="font-black text-brand-600"><?= $pct ?>%</span>
        </div>
        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
            <div class="bg-gradient-to-r from-brand-500 to-accent-500 h-3 rounded-full transition-all duration-500" style="width: <?= $pct ?>%"></div>
        </div>
        <div class="flex flex-wrap gap-x-4 gap-y-1 mt-2">
            <?php if ($credit['due_date']): ?>
            <p class="text-xs text-gray-400"><i class="fas fa-calendar mr-1"></i>Fecha límite: <strong><?= date('d/m/Y', strtotime($credit['due_date'])) ?></strong></p>
            <?php endif; ?>
            <?php if ($credit['notes']): ?>
            <p class="text-xs text-gray-400"><i class="fas fa-sticky-note mr-1"></i><?= htmlspecialchars($credit['notes']) ?></p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Historial de Abonos -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
        <div class="p-4 border-b border-gray-100 dark:border-gray-700">
            <h3 class="font-bold text-gray-800 dark:text-white flex items-center gap-2">
                <i class="fas fa-history text-brand-500"></i> Historial de Abonos
            </h3>
        </div>
        
        <?php if (empty($payments)): ?>
            <div class="p-10 text-center text-gray-400 dark:text-gray-500">
                <i class="fas fa-receipt text-3xl mb-3 block opacity-30"></i>
                <p class="text-sm font-medium">No hay abonos registrados aún</p>
            </div>
        <?php else: ?>
            <div class="divide-y divide-gray-100 dark:divide-gray-700">
                <?php foreach ($payments as $p):
                    $statusMap = [
                        'aprobado'  => ['text' => 'Aprobado', 'color' => 'emerald', 'icon' => 'fa-check-circle'],
                        'pendiente' => ['text' => 'Pendiente', 'color' => 'amber', 'icon' => 'fa-clock'],
                        'rechazado' => ['text' => 'Rechazado', 'color' => 'red', 'icon' => 'fa-times-circle'],
                    ];
                    $s = $statusMap[$p['status']] ?? $statusMap['pendiente'];
                ?>
                <div class="p-4 flex items-center gap-4">
                    <div class="w-10 h-10 rounded-xl bg-<?= $s['color'] ?>-100 dark:bg-<?= $s['color'] ?>-900/20 flex items-center justify-center flex-shrink-0">
                        <i class="fas <?= $s['icon'] ?> text-<?= $s['color'] ?>-500"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-bold text-gray-800 dark:text-white">
                            Abono de $<?= number_format($p['amount'], 2) ?> — 
                            <span class="text-<?= $s['color'] ?>-600 dark:text-<?= $s['color'] ?>-400"><?= $s['text'] ?></span>
                        </p>
                        <p class="text-xs text-gray-400 mt-0.5">
                            <?= ucfirst(htmlspecialchars($p['payment_method'] ?? '')) ?>
                            <?= $p['reference'] ? ' • Ref: ' . htmlspecialchars($p['reference']) : '' ?>
                            • <?= date('d/m/Y H:i', strtotime($p['created_at'])) ?>
                            <?= $p['reported_by_name'] ? ' • por ' . htmlspecialchars($p['reported_by_name']) : '' ?>
                        </p>
                        <?php if ($p['notes']): ?>
                        <p class="text-xs text-gray-500 mt-1 italic">"<?= htmlspecialchars($p['notes']) ?>"</p>
                        <?php endif; ?>
                    </div>
                    <?php if ($p['status'] === 'pendiente' && ($_SESSION['role'] ?? '') === 'administrador'): ?>
                    <div class="flex gap-1 flex-shrink-0">
                        <form hx-post="<?= BASE_URL ?>credits/approve" hx-swap="none" hx-confirm="¿Aprobar este abono de $<?= number_format($p['amount'], 2) ?>?" class="inline">
                            <input type="hidden" name="payment_id" value="<?= $p['id'] ?>">
                            <button class="text-emerald-500 hover:text-emerald-600 bg-emerald-50 dark:bg-emerald-900/20 p-2 rounded-lg transition-colors" title="Aprobar">
                                <i class="fas fa-check"></i>
                            </button>
                        </form>
                        <form hx-post="<?= BASE_URL ?>credits/reject" hx-swap="none" hx-confirm="¿Rechazar este abono?" class="inline">
                            <input type="hidden" name="payment_id" value="<?= $p['id'] ?>">
                            <button class="text-red-500 hover:text-red-600 bg-red-50 dark:bg-red-900/20 p-2 rounded-lg transition-colors" title="Rechazar">
                                <i class="fas fa-times"></i>
                            </button>
                        </form>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modal: Registrar Abono -->
    <div x-show="openPaymentModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4 py-8">
            <div x-show="openPaymentModal" x-transition.opacity class="fixed inset-0 bg-gray-900/75 backdrop-blur-sm" @click="openPaymentModal = false"></div>
            <div x-show="openPaymentModal" x-transition class="relative w-full max-w-md bg-white dark:bg-gray-800 rounded-2xl shadow-2xl overflow-hidden">
                <div class="p-5 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <i class="fas fa-coins text-brand-500"></i> Registrar Abono
                    </h3>
                    <button @click="openPaymentModal = false" class="modal-close">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <form hx-post="<?= BASE_URL ?>credits/payment" hx-swap="none" @htmx:after-request="if($event.detail.successful) { openPaymentModal = false; $el.reset(); setTimeout(() => location.reload(), 500); }">
                    <div class="p-5 space-y-4">
                        <input type="hidden" name="credit_id" value="<?= $credit['id'] ?>">
                        <div>
                            <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Monto del Abono ($) *</label>
                            <input type="number" name="amount" step="0.01" min="0.01" max="<?= $credit['remaining_amount'] ?>" required
                                   class="w-full rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2.5 text-sm dark:text-white focus:ring-2 focus:ring-brand-500/30 outline-none"
                                   placeholder="0.00">
                            <p class="text-xs text-gray-400 mt-1">Máximo: $<?= number_format($credit['remaining_amount'], 2) ?></p>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Método de Pago</label>
                            <select name="payment_method" class="w-full rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2.5 text-sm dark:text-white focus:ring-2 focus:ring-brand-500/30 outline-none">
                                <option value="efectivo">Efectivo</option>
                                <option value="transferencia">Transferencia</option>
                                <option value="pago_movil">Pago Móvil</option>
                                <option value="zelle">Zelle</option>
                                <option value="punto">Punto de Venta</option>
                                <option value="biopago">Biopago (BDV)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Nro. Referencia</label>
                            <input type="text" name="reference" class="w-full rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2.5 text-sm dark:text-white focus:ring-2 focus:ring-brand-500/30 outline-none" placeholder="Ej: 00012345 o captura">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Notas</label>
                            <textarea name="notes" rows="2" class="w-full rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2.5 text-sm dark:text-white focus:ring-2 focus:ring-brand-500/30 outline-none resize-none" placeholder="Observación opcional..."></textarea>
                        </div>
                    </div>
                    <div class="p-5 border-t border-gray-100 dark:border-gray-700 flex gap-3">
                        <button type="button" @click="openPaymentModal = false" class="flex-1 px-4 py-2.5 rounded-xl font-bold text-gray-600 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors text-sm">Cancelar</button>
                        <button type="submit" class="flex-1 px-4 py-2.5 rounded-xl font-bold text-white bg-brand-600 hover:bg-brand-700 transition-colors text-sm shadow-sm">Reportar Abono</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>
