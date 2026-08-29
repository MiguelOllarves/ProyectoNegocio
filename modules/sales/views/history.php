<?php include __DIR__ . '/../../../includes/header.php'; ?>

<div class="page-header">
    <div>
        <h2 class="page-title">Historial de Ventas del Día</h2>
        <p class="page-subtitle">Gestiona las facturas y realiza anulaciones</p>
    </div>
    
    <div class="flex gap-2">
        <a href="<?= BASE_URL ?>sales" class="btn-gradient">
            <i class="fas fa-cash-register mr-2"></i> Volver a Caja
        </a>
    </div>
</div>

<div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50 dark:bg-slate-800/50 border-b border-gray-100 dark:border-gray-800 text-xs uppercase tracking-wider text-gray-500 dark:text-gray-400">
                    <th class="p-4 font-bold">ID Factura</th>
                    <th class="p-4 font-bold">Hora</th>
                    <th class="p-4 font-bold">Cajero</th>
                    <th class="p-4 font-bold">Total</th>
                    <th class="p-4 font-bold">Estado</th>
                    <th class="p-4 font-bold text-center">Acciones</th>
                </tr>
            </thead>
            <tbody id="history-tbody" class="divide-y divide-gray-100 dark:divide-gray-800 text-sm">
                <?php if(empty($sales)): ?>
                <tr>
                    <td colspan="6" class="p-8 text-center text-gray-400">No hay ventas registradas el día de hoy.</td>
                </tr>
                <?php else: ?>
                    <?php foreach($sales as $s): ?>
                    <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/50 transition-colors">
                        <td class="p-4 font-mono font-bold text-gray-700 dark:text-gray-300">#<?= str_pad($s['id'], 6, '0', STR_PAD_LEFT) ?></td>
                        <td class="p-4 text-gray-600 dark:text-gray-400"><?= date('h:i A', strtotime($s['created_at'])) ?></td>
                        <td class="p-4">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                <i class="fas fa-user mr-1.5 opacity-70"></i> <?= htmlspecialchars($s['cashier'] ?? 'Desconocido') ?>
                            </span>
                        </td>
                        <td class="p-4 font-bold text-gray-800 dark:text-gray-200">$<?= number_format($s['total'], 2) ?></td>
                        <td class="p-4">
                            <?php if(($s['status'] ?? '') === 'anulada'): ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400">Anulada</span>
                            <?php else: ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">Completada</span>
                            <?php endif; ?>
                        </td>
                        <td class="p-4 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <button onclick="printReceipt(<?= $s['id'] ?>)" class="w-8 h-8 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors flex items-center justify-center" title="Imprimir Recibo">
                                    <i class="fas fa-print"></i>
                                </button>
                                <?php if(($s['status'] ?? '') !== 'anulada'): ?>
                                <button onclick="voidSale(<?= $s['id'] ?>)" class="w-8 h-8 rounded-lg bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-900/40 transition-colors flex items-center justify-center" title="Anular Factura">
                                    <i class="fas fa-ban"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; 
                    $colspan = 6;
                    $hxTarget = '#history-tbody';
                    // HTMX needs hx-select since the response contains the full page
                    // We modify pagination.php inline or dynamically add hx-select here if needed
                    ?>
                    <?php require __DIR__ . '/../../../includes/pagination.php'; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function printReceipt(id) {
    const w = window.open('<?= BASE_URL ?>sales/receipt/' + id, 'ticket_printer', 'width=400,height=600');
    if (w) w.focus();
}

function voidSale(id) {
    if(confirm('¿ESTÁS SEGURO DE ANULAR ESTA FACTURA?\n\nEsta acción devolverá los productos al inventario y registrará la salida del dinero. Esta acción no se puede deshacer.')) {
        fetch('<?= BASE_URL ?>sales/voidSale/' + id, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            }
        })
        .then(r => r.json())
        .then(data => {
            if(data.success) {
                alert('Anulación exitosa');
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(e => {
            alert('Ocurrió un error en la conexión');
            console.error(e);
        });
    }
}
</script>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>
