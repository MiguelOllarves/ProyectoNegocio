<?php include __DIR__ . '/../../../includes/header.php'; ?>

<div class="mb-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
    <div>
        <h2 class="text-2xl font-extrabold text-gray-800 dark:text-white tracking-tight">Pedidos de Tienda Online</h2>
        <p class="text-gray-500 dark:text-gray-400 text-sm font-medium mt-1">Gestión de pedidos recibidos a través de la web</p>
    </div>
    
    <div class="flex gap-2">
        <a href="<?= BASE_URL ?>storefront" class="bg-white dark:bg-slate-800 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-slate-700 text-gray-700 dark:text-gray-300 font-bold py-2 px-4 rounded-lg shadow-sm transition-all text-sm flex items-center">
            <i class="fas fa-store mr-2"></i> Ir a Configuración
        </a>
    </div>
</div>

<div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50/50 dark:bg-slate-800/50 border-b border-gray-100 dark:border-gray-800 text-xs uppercase tracking-wider text-gray-500 dark:text-gray-400 font-bold">
                    <th class="p-4">Fecha</th>
                    <th class="p-4">Cliente</th>
                    <th class="p-4">Total</th>
                    <th class="p-4">Método</th>
                    <th class="p-4">Estado</th>
                    <th class="p-4 text-center">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50 dark:divide-gray-800/50">
                <?php if(empty($orders)): ?>
                    <tr>
                        <td colspan="6" class="p-12 text-center text-gray-400">
                            <i class="fas fa-box-open text-4xl mb-3 opacity-30"></i>
                            <p>No tienes pedidos registrados aún.</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach($orders as $o): 
                        $badgeColors = [
                            'pendiente' => 'bg-amber-100 text-amber-700 border-amber-200 dark:bg-amber-900/30 dark:text-amber-400 dark:border-amber-800',
                            'despachado' => 'bg-emerald-100 text-emerald-700 border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-400 dark:border-emerald-800',
                            'cancelado' => 'bg-red-100 text-red-700 border-red-200 dark:bg-red-900/30 dark:text-red-400 dark:border-red-800'
                        ];
                        $badgeColor = $badgeColors[$o['status']] ?? 'bg-gray-100 text-gray-700 border-gray-200';
                        $items = json_decode($o['items_json'] ?? '[]', true);
                    ?>
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-slate-700/50 transition-colors group">
                            <td class="p-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                <?= date('d/m/Y h:i A', strtotime($o['created_at'])) ?>
                            </td>
                            <td class="p-4">
                                <p class="font-bold text-gray-800 dark:text-white text-sm"><?= htmlspecialchars($o['customer_name']) ?></p>
                                <p class="text-xs text-gray-500 mt-0.5"><i class="fas fa-phone mr-1 opacity-50"></i> <?= htmlspecialchars($o['customer_phone']) ?></p>
                                <p class="text-xs text-gray-500 mt-0.5"><i class="fas fa-map-marker-alt mr-1 opacity-50"></i> <?= htmlspecialchars($o['customer_address']) ?></p>
                            </td>
                            <td class="p-4">
                                <p class="font-black text-gray-800 dark:text-white text-sm">$<?= number_format($o['total_usd'], 2) ?></p>
                                <p class="text-[10px] font-bold text-gray-500">Bs. <?= number_format($o['total_bs'], 2) ?></p>
                            </td>
                            <td class="p-4 whitespace-nowrap text-sm font-medium text-gray-600 dark:text-gray-300">
                                <i class="fas fa-credit-card mr-1 text-gray-400"></i> <?= htmlspecialchars($o['payment_method']) ?>
                            </td>
                            <td class="p-4 whitespace-nowrap">
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold border uppercase tracking-wider <?= $badgeColor ?>">
                                    <?= htmlspecialchars($o['status']) ?>
                                </span>
                            </td>
                            <td class="p-4 whitespace-nowrap text-center">
                                <button type="button" onclick="viewOrder(<?= htmlspecialchars(json_encode([
                                    'id' => $o['id'],
                                    'name' => $o['customer_name'],
                                    'phone' => $o['customer_phone'],
                                    'address' => $o['customer_address'],
                                    'notes' => $o['notes'],
                                    'payment_method' => $o['payment_method'],
                                    'total_usd' => $o['total_usd'],
                                    'total_bs' => $o['total_bs'],
                                    'items' => $items,
                                    'status' => $o['status']
                                ])) ?>)" class="text-gray-400 hover:text-brand-500 transition-colors p-2 rounded-lg hover:bg-brand-50 dark:hover:bg-brand-900/20" title="Ver Detalle">
                                    <i class="fas fa-eye"></i>
                                </button>
                                
                                <?php if($o['status'] === 'pendiente'): ?>
                                    <form method="POST" action="<?= BASE_URL ?>storefront/updateOrderStatus" class="inline-block" onsubmit="return confirm('¿Marcar pedido como DESPACHADO?')">
                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                        <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                                        <input type="hidden" name="status" value="despachado">
                                        <button type="submit" class="text-emerald-500 hover:text-emerald-600 transition-colors p-2 rounded-lg hover:bg-emerald-50 dark:hover:bg-emerald-900/20" title="Marcar como Despachado">
                                            <i class="fas fa-check-circle"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Detalle Pedido (Alpine) -->
<div id="orderModal" x-data="{ open: false, order: null }" @open-order.window="order = $event.detail; open = true" 
     class="fixed inset-0 z-[60] overflow-y-auto" style="display: none;" x-show="open" x-cloak>
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
        
        <div x-show="open" x-transition.opacity class="fixed inset-0 transition-opacity bg-slate-900/60 backdrop-blur-sm" @click="open = false"></div>

        <div x-show="open" x-transition class="relative inline-block w-full max-w-lg p-0 text-left align-middle transition-all transform bg-white dark:bg-slate-800 rounded-2xl shadow-2xl overflow-hidden border border-gray-100 dark:border-gray-700">
            <!-- Header -->
            <div class="p-5 border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-slate-800/50 flex justify-between items-center">
                <div>
                    <h3 class="font-extrabold text-lg text-gray-900 dark:text-white">Detalle de Pedido #<span x-text="order?.id"></span></h3>
                    <span class="text-xs font-bold px-2 py-0.5 rounded-full uppercase tracking-wider bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 mt-1 inline-block" x-text="order?.status"></span>
                </div>
                <button @click="open = false" class="modal-close">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- Body -->
            <div class="p-5 flex-1 space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Cliente</p>
                        <p class="text-sm font-bold text-gray-800 dark:text-white mt-1" x-text="order?.name"></p>
                        <p class="text-sm text-gray-500 mt-0.5"><i class="fas fa-phone mr-1"></i> <span x-text="order?.phone"></span></p>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Método de Pago</p>
                        <p class="text-sm font-bold text-gray-800 dark:text-white mt-1"><i class="fas fa-credit-card mr-1 text-brand-500"></i> <span x-text="order?.payment_method"></span></p>
                    </div>
                </div>

                <div>
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Dirección de Entrega</p>
                    <p class="text-sm text-gray-700 dark:text-gray-300 mt-1 bg-gray-50 dark:bg-slate-700/50 p-2.5 rounded-lg border border-gray-100 dark:border-gray-700" x-text="order?.address"></p>
                </div>

                <template x-if="order?.notes">
                    <div>
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Notas Adicionales</p>
                        <p class="text-sm text-amber-700 dark:text-amber-400 mt-1 bg-amber-50 dark:bg-amber-900/20 p-2.5 rounded-lg border border-amber-100 dark:border-amber-800/50 italic" x-text="order?.notes"></p>
                    </div>
                </template>

                <div>
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-2">Artículos</p>
                    <div class="bg-gray-50 dark:bg-slate-700/50 rounded-xl border border-gray-100 dark:border-gray-700 p-2">
                        <template x-for="item in order?.items" :key="item.id">
                            <div class="flex justify-between items-center p-2 border-b border-gray-100 dark:border-gray-700 last:border-0">
                                <div class="flex-1">
                                    <p class="text-sm font-bold text-gray-800 dark:text-white" x-text="item.name"></p>
                                    <p class="text-xs text-gray-500" x-text="item.qty + ' x $' + parseFloat(item.price).toFixed(2)"></p>
                                </div>
                                <p class="text-sm font-black text-gray-900 dark:text-white" x-text="'$' + parseFloat(item.qty * item.price).toFixed(2)"></p>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="p-5 border-t border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-slate-800/50 flex justify-between items-center">
                <span class="font-bold text-gray-500 dark:text-gray-400">TOTAL</span>
                <div class="text-right">
                    <span class="text-2xl font-black text-gray-900 dark:text-white block leading-none" x-text="'$' + parseFloat(order?.total_usd).toFixed(2)"></span>
                    <span class="text-xs font-bold text-gray-500" x-text="'Bs. ' + parseFloat(order?.total_bs).toFixed(2)"></span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function viewOrder(orderData) {
    window.dispatchEvent(new CustomEvent('open-order', { detail: orderData }));
}
</script>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>
