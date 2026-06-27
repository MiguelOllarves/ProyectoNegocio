<?php include __DIR__ . '/../../../includes/header.php'; ?>
<div class="mb-6">
    <p class="text-sm text-gray-500 dark:text-gray-400">Compras > Nueva</p>
    <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Registrar Compra de Mercancía</h2>
</div>
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
        <div class="grid grid-cols-2 gap-4 mb-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Proveedor</label>
                <select id="supplier_id" class="w-full rounded-md border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500">
                    <option value="">-- Seleccionar --</option>
                    <?php foreach($suppliers as $s): ?>
                        <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Producto</label>
                <select id="product_select" class="w-full rounded-md border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500">
                    <option value="">-- Seleccionar Producto --</option>
                    <?php foreach($products as $p): ?>
                        <option value="<?= $p['id'] ?>" data-name="<?= htmlspecialchars($p['name']) ?>"><?= htmlspecialchars($p['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="grid grid-cols-3 gap-4 mb-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Cantidad</label>
                <input type="number" id="item_qty" value="1" min="1" class="w-full rounded-md border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Costo Unitario ($)</label>
                <input type="number" id="item_cost" step="0.01" value="0" min="0" class="w-full rounded-md border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500">
            </div>
            <div class="flex items-end">
                <button onclick="addPurchaseItem()" class="w-full bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-medium transition-colors"><i class="fas fa-plus mr-1"></i> Agregar</button>
            </div>
        </div>
        <table class="w-full text-left text-sm" id="purchaseTable">
            <thead><tr class="border-b border-gray-200 dark:border-gray-600 text-gray-500 dark:text-gray-400 text-xs uppercase">
                <th class="pb-2">Producto</th><th class="pb-2 text-center">Cant.</th><th class="pb-2 text-right">Costo U.</th><th class="pb-2 text-right">Subtotal</th><th class="pb-2"></th>
            </tr></thead>
            <tbody id="purchaseItems"></tbody>
            <tfoot><tr class="border-t-2 border-gray-300 dark:border-gray-500 font-bold text-gray-800 dark:text-white">
                <td colspan="3" class="pt-3 text-right">TOTAL:</td><td class="pt-3 text-right" id="purchaseTotal">$0.00</td><td></td>
            </tr></tfoot>
        </table>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 h-fit">
        <h3 class="font-bold text-gray-800 dark:text-white mb-3">Notas</h3>
        <textarea id="purchase_notes" rows="4" placeholder="Observaciones opcionales..." class="w-full rounded-md border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500"></textarea>
        <button onclick="submitPurchase()" id="btnSubmitPurchase" class="w-full mt-4 bg-brand-600 hover:bg-brand-700 text-white py-3 rounded-lg font-bold transition-colors shadow-sm text-lg">
            <i class="fas fa-check-circle mr-2"></i> Registrar Compra
        </button>
    </div>
</div>
<script>
let purchaseCart = [];

function addPurchaseItem() {
    const sel = document.getElementById('product_select');
    const qty = parseInt(document.getElementById('item_qty').value);
    const cost = parseFloat(document.getElementById('item_cost').value);
    if (!sel.value || qty < 1 || cost <= 0) { alert('Completa todos los campos.'); return; }

    purchaseCart.push({
        product_id: sel.value,
        name: sel.options[sel.selectedIndex].dataset.name,
        quantity: qty,
        cost: cost
    });
    renderPurchaseCart();
}

function removePurchaseItem(i) { purchaseCart.splice(i, 1); renderPurchaseCart(); }

function renderPurchaseCart() {
    const tbody = document.getElementById('purchaseItems');
    let total = 0;
    tbody.innerHTML = purchaseCart.map((item, i) => {
        const sub = item.quantity * item.cost;
        total += sub;
        return `<tr class="border-b border-gray-100 dark:border-gray-700">
            <td class="py-2 text-gray-800 dark:text-gray-100">${item.name}</td>
            <td class="py-2 text-center">${item.quantity}</td>
            <td class="py-2 text-right">$${item.cost.toFixed(2)}</td>
            <td class="py-2 text-right font-semibold">$${sub.toFixed(2)}</td>
            <td class="py-2 text-right"><button onclick="removePurchaseItem(${i})" class="text-red-400 hover:text-red-600"><i class="fas fa-times"></i></button></td>
        </tr>`;
    }).join('');
    document.getElementById('purchaseTotal').textContent = '$' + total.toFixed(2);
}

function submitPurchase() {
    if (purchaseCart.length === 0) { alert('Agrega al menos un producto.'); return; }
    const btn = document.getElementById('btnSubmitPurchase');
    btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Procesando...';

    fetch('<?= BASE_URL ?>purchases/create', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            supplier_id: document.getElementById('supplier_id').value || null,
            notes: document.getElementById('purchase_notes').value,
            items: purchaseCart
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert('¡Compra #' + data.purchase_id + ' registrada! El stock fue actualizado automáticamente.');
            window.location.href = '<?= BASE_URL ?>purchases';
        } else {
            alert('Error: ' + data.message);
            btn.disabled = false; btn.innerHTML = '<i class="fas fa-check-circle mr-2"></i> Registrar Compra';
        }
    })
    .catch(() => { btn.disabled = false; btn.innerHTML = '<i class="fas fa-check-circle mr-2"></i> Registrar Compra'; });
}
</script>
<?php include __DIR__ . '/../../../includes/footer.php'; ?>
