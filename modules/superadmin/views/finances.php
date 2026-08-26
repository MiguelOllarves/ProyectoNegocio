<?php include __DIR__ . '/../../../includes/header.php'; ?>

<div class="mb-6 flex justify-between items-center">
    <div>
        <h2 class="text-2xl font-extrabold text-gray-800 dark:text-white tracking-tight"><i class="fas fa-chart-pie text-brand-500 mr-2"></i> Finanzas y Pagos</h2>
        <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">Valida transferencias y gestiona los ingresos del SaaS.</p>
    </div>
</div>

<div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden" x-data="superAdminFinances()">
    <div class="p-5 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center bg-gray-50 dark:bg-slate-800/80">
        <h3 class="text-sm font-bold text-gray-800 dark:text-white uppercase tracking-wider"><i class="fas fa-money-check-alt text-emerald-500 mr-2"></i> Historial de Reportes de Pago</h3>
    </div>
    
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm text-gray-600 dark:text-gray-400 border-collapse cursor-default">
            <thead>
                <tr class="bg-white dark:bg-slate-800 uppercase text-[10px] tracking-wider text-gray-500 dark:text-gray-300 border-b border-gray-200 dark:border-gray-700 px-2">
                    <th class="py-4 px-6 font-bold">Inquilino (Tenant)</th>
                    <th class="py-4 px-6 font-bold">Plan Aplicado</th>
                    <th class="py-4 px-6 font-bold">Referencia y Comprobante</th>
                    <th class="py-4 px-6 font-bold text-right">Monto</th>
                    <th class="py-4 px-6 font-bold text-center">Acción (Auditoría)</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($payments)): ?>
                    <tr><td colspan="5" class="py-10 text-center text-gray-400 font-medium">No hay pagos reportados todavía.</td></tr>
                <?php else: foreach($payments as $p): ?>
                    <tr class="border-b border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-slate-800/50 transition-colors">
                        <td class="py-4 px-6">
                            <span class="font-bold text-gray-800 dark:text-gray-200 block"><?= htmlspecialchars($p['business_name']) ?></span>
                            <span class="text-xs text-gray-400"><i class="far fa-calendar mr-1"></i> <?= date('d M, Y', strtotime($p['created_at'])) ?></span>
                        </td>
                        <td class="py-4 px-6">
                            <span class="bg-gray-100 text-gray-700 dark:bg-slate-700 dark:text-gray-300 font-bold px-2 py-1 rounded text-xs uppercase"><?= htmlspecialchars($p['plan_name']) ?></span>
                        </td>
                        <td class="py-4 px-6">
                            <span class="font-mono font-bold text-emerald-600 dark:text-emerald-400"><?= htmlspecialchars($p['reference_number']) ?></span><br>
                            <span class="text-xs text-gray-500 bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-gray-700 block px-2 py-0.5 rounded mt-1 shadow-sm w-fit">
                                <i class="fas fa-bank mr-1 text-slate-400"></i> <?= htmlspecialchars($p['payment_method']) ?>
                            </span>
                            <!-- Placeholder for Capture / Receipt Viewer -->
                            <?php if(!empty($p['proof_image'])): ?>
                            <button class="mt-2 text-[10px] uppercase font-bold text-blue-500 hover:text-blue-700 underline" @click="viewCapture('<?= $p['id'] ?>', '<?= htmlspecialchars($p['reference_number']) ?>')"><i class="fas fa-image mr-1"></i> Ver Comprobante (Capture)</button>
                            <?php else: ?>
                            <span class="mt-2 text-[10px] uppercase font-bold text-gray-400 block"><i class="fas fa-image mr-1"></i> Sin Imagen Adjunta</span>
                            <?php endif; ?>
                        </td>
                        <td class="py-4 px-6 text-right">
                            <span class="text-lg font-black text-gray-800 dark:text-gray-200">$<?= number_format($p['amount'], 2) ?></span>
                        </td>
                        <td class="py-4 px-6 text-center">
                            <?php if($p['status'] === 'pending'): ?>
                                <button @click="process(<?= $p['id'] ?>, 'approve')" class="bg-emerald-500 hover:bg-emerald-600 text-white p-2 rounded-lg transition-colors mr-1 tooltip" title="Aprobar Pago (Activar Inquilino)"><i class="fas fa-check"></i></button>
                                <button @click="process(<?= $p['id'] ?>, 'reject')" class="bg-red-50 hover:bg-red-100 text-red-500 p-2 rounded-lg transition-colors tooltip" title="Rechazar Pago"><i class="fas fa-times"></i></button>
                            <?php elseif($p['status'] === 'approved'): ?>
                                <span class="bg-emerald-100 text-emerald-700 px-3 py-1 rounded font-bold text-xs uppercase"><i class="fas fa-check-circle mr-1"></i> Pagado</span>
                            <?php else: ?>
                                <span class="bg-red-100 text-red-700 px-3 py-1 rounded font-bold text-xs uppercase"><i class="fas fa-times-circle mr-1"></i> Rechazado</span>
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
    Alpine.data('superAdminFinances', () => ({
        viewCapture(id, ref) {
            Swal.fire({
                title: 'Comprobante #' + ref,
                imageUrl: '<?= BASE_URL ?>superadmin/payment_proof/' + id,
                imageAlt: 'Comprobante de Pago',
                confirmButtonColor: '#10b981',
                confirmButtonText: 'Cerrar'
            });
        },
        async process(id, act) {
            const result = await Swal.fire({
                title: act === 'approve' ? '¿Aprobar y Activar Cuenta?' : '¿Rechazar Pago?',
                text: act === 'approve' ? 'El inquilino volverá a estar 100% activo.' : 'El pago será marcado como inválido.',
                icon: act === 'approve' ? 'question' : 'warning',
                showCancelButton: true,
                confirmButtonColor: act === 'approve' ? '#10b981' : '#ef4444',
                confirmButtonText: 'Sí, continuar',
                cancelButtonText: 'Cancelar'
            });

            if (result.isConfirmed) {
                const fd = new FormData();
                fd.append('id', id);
                fd.append('action', act);

                try {
                    const res = await fetch('<?= BASE_URL ?>superadmin/process_payment', {
                        method: 'POST', body: fd
                    });
                    const d = await res.json();
                    if(d.success) location.reload();
                    else Swal.fire('Error', d.message, 'error');
                } catch(e) {
                    Swal.fire('Error', 'Falla de conexión', 'error');
                }
            }
        }
    }))
})
</script>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>
