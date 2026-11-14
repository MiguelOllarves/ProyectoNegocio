<?php include __DIR__ . '/../../../includes/header.php'; ?>

<div class="mb-6 flex items-center justify-between gap-4">
    <div>
        <h2 class="text-2xl font-extrabold text-gray-800 dark:text-white">Pagos de suscripción</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Revisa los comprobantes antes de activar una suscripción.</p>
    </div>
    <a href="<?= BASE_URL ?>superadmin" class="text-sm font-bold text-brand-600 hover:text-brand-700">Volver al panel</a>
</div>

<div class="space-y-4">
    <?php if (empty($payments)): ?>
        <div class="bg-white dark:bg-slate-800 border border-gray-200 dark:border-gray-700 rounded-xl p-8 text-center text-gray-500">
            No hay pagos pendientes de revisión.
        </div>
    <?php else: ?>
        <?php foreach ($payments as $payment): ?>
            <section class="bg-white dark:bg-slate-800 border border-gray-200 dark:border-gray-700 rounded-xl p-5">
                <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-5">
                    <div class="space-y-1 text-sm text-gray-700 dark:text-gray-300">
                        <h3 class="font-bold text-base text-gray-900 dark:text-white"><?= htmlspecialchars($payment['business_name'] ?? 'Negocio') ?></h3>
                        <p>Negocio #<?= (int)$payment['tenant_id'] ?> · Pago #<?= (int)$payment['id'] ?></p>
                        <p>Monto: $<?= number_format((float)$payment['amount'], 2) ?> · Método: <?= htmlspecialchars($payment['payment_method']) ?></p>
                        <p>Referencia: <?= htmlspecialchars($payment['reference_number']) ?></p>
                        <p>Fecha: <?= htmlspecialchars($payment['created_at']) ?></p>
                    </div>
                    <?php if (!empty($payment['proof_image']) && preg_match('#^data:image/(jpeg|png|webp);base64,#i', $payment['proof_image'])): ?>
                        <img src="<?= htmlspecialchars($payment['proof_image'], ENT_QUOTES, 'UTF-8') ?>" alt="Comprobante del pago" class="max-h-64 max-w-full rounded-lg border border-gray-200 object-contain">
                    <?php else: ?>
                        <p class="text-sm text-amber-700">No hay un comprobante de imagen válido.</p>
                    <?php endif; ?>
                    <div class="flex flex-wrap gap-2">
                        <?php foreach (['approve' => 'Aprobar pago', 'reject' => 'Rechazar'] as $action => $label): ?>
                            <form method="POST" action="<?= BASE_URL ?>superadmin/review_payment">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="id" value="<?= (int)$payment['id'] ?>">
                                <input type="hidden" name="action" value="<?= $action ?>">
                                <button type="submit" class="<?= $action === 'approve' ? 'bg-green-600 hover:bg-green-700' : 'bg-red-600 hover:bg-red-700' ?> text-white font-bold text-sm px-4 py-2 rounded-lg">
                                    <?= $label ?>
                                </button>
                            </form>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>
