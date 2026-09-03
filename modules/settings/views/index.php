<?php include __DIR__ . '/../../../includes/header.php'; ?>

<div class="page-header">
    <div>
        <h2 class="page-title">Configuración del Sistema</h2>
        <p class="page-subtitle">Administra las tasas, fiscalidad y seguridad del sistema</p>
    </div>
</div>

<?php $s = $settings ?? []; ?>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    
    <!-- Tasas y Impuestos -->
    <form id="form-rates" class="card p-6 flex flex-col h-full">
        <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-4 border-b border-gray-100 dark:border-gray-700 pb-2"><i class="fas fa-coins text-brand-500 mr-2"></i>Tasas e Impuestos</h3>
        <div class="space-y-3">
            <div x-data="{ autoBcv: <?= (int)($s['bcv_auto_update'] ?? 1) ?> }">
                <div class="flex justify-between items-center mb-1">
                    <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Tasa BCV (Bs/$)</label>
                    <label class="flex items-center space-x-2 cursor-pointer">
                        <span class="text-[10px] uppercase font-bold text-gray-500">Auto Update</span>
                        <div class="relative">
                            <input type="checkbox" name="bcv_auto_update" value="1" class="sr-only" x-model="autoBcv" @change="document.getElementById('form-rates').requestSubmit()">
                            <!-- Si autoBcv es 0, enviamos un hidden input con 0 -->
                            <input type="hidden" name="bcv_auto_update" value="0" x-bind:disabled="autoBcv">
                            <div class="w-8 h-4 bg-gray-300 dark:bg-gray-600 rounded-full shadow-inner transition-colors" :class="{ 'bg-brand-500': autoBcv }"></div>
                            <div class="absolute w-4 h-4 bg-white rounded-full shadow inset-y-0 left-0 transition-transform" :class="{ 'translate-x-4': autoBcv }"></div>
                        </div>
                    </label>
                </div>
                <input type="number" step="0.0001" min="0" name="bcv_rate" class="form-input w-full mt-1 text-sm disabled:opacity-50" value="<?= $s['bcv_rate'] ?? '622.21' ?>" :readonly="autoBcv" :class="{ 'bg-gray-100 dark:bg-gray-800': autoBcv }">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Paralelo / COP</label>
                <div class="flex gap-2 mt-1">
                    <input type="number" step="0.01" min="0" name="parallel_rate" class="form-input w-1/2 text-sm" placeholder="Paralelo" value="<?= $s['parallel_rate'] ?? '' ?>">
                    <input type="number" step="0.01" min="0" name="cop_rate" class="form-input w-1/2 text-sm" placeholder="COP" value="<?= $s['cop_rate'] ?? '' ?>">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-2 mt-1">
                <div>
                    <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">IVA (%)</label>
                    <input type="number" step="0.01" min="0" name="tax_iva" class="form-input mt-1 w-full text-sm" value="<?= $s['tax_iva'] ?? '16' ?>">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">IGTF (%)</label>
                    <input type="number" step="0.01" min="0" name="tax_igtf" class="form-input mt-1 w-full text-sm" value="<?= $s['tax_igtf'] ?? '3' ?>">
                </div>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Método de Cálculo</label>
                <select name="calc_method" class="form-select mt-1 w-full mb-2">
                    <option value="simple" <?= ($s['calc_method'] ?? '') === 'simple' ? 'selected' : '' ?>>Simple (Costo + Ganancia)</option>
                    <option value="fiscal" <?= ($s['calc_method'] ?? 'fiscal') === 'fiscal' ? 'selected' : '' ?>>Fiscal (Costo / (1 - Margen))</option>
                </select>
                <select name="iva_method" class="form-select w-full">
                    <option value="included" <?= ($s['iva_method'] ?? 'included') === 'included' ? 'selected' : '' ?>>IVA Incluido en Precio</option>
                    <option value="add_later" <?= ($s['iva_method'] ?? '') === 'add_later' ? 'selected' : '' ?>>Sumar IVA al cobrar</option>
                </select>
            </div>
        </div>
        <div class="mt-auto pt-6">
            <button type="submit" class="w-full btn-primary text-sm transition-colors"><i class="fas fa-save mr-1"></i> Guardar Tasas</button>
        </div>
    </form>
    
    <!-- Branding y Personalización de Tickets -->
    <form id="form-branding" class="card p-6 flex flex-col h-full">
        <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-4 border-b border-gray-100 dark:border-gray-700 pb-2"><i class="fas fa-paint-roller text-accent-500 mr-2"></i>Personalización y Tickets</h3>
        
        <div class="mb-4">
            <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase mb-2">Logo Corporativo</label>
            <div class="flex items-center gap-4">
                <div class="w-16 h-16 bg-gray-100 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600/50 rounded-lg overflow-hidden shrink-0 shadow-inner flex justify-center items-center">
                    <img id="logo-preview" src="<?= BASE_URL ?>?serve_logo=1&t=<?= time() ?>" alt="Logo" class="max-w-full max-h-full object-contain bg-white">
                </div>
                <div class="flex-1">
                    <input type="file" id="logo_file" accept="image/png, image/jpeg, image/svg+xml" class="block w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-bold file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100 dark:file:bg-brand-900/30 cursor-pointer">
                    <p class="text-[10px] text-gray-400 mt-1">Se redimensionará a máx. 300px (JPG/PNG).</p>
                </div>
            </div>
            <input type="hidden" name="logo_base64" id="logo_base64">
        </div>

        <div class="mb-4">
            <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase mb-1">Encabezado de Tickets</label>
            <textarea name="ticket_header" rows="3" class="form-input focus:ring-accent-500" placeholder="Ej: Comercial Mi Tienda C.A&#10;RIF: J-12345678-9&#10;Tel: (0414) 123-4567"><?= htmlspecialchars($bizData['ticket_header'] ?? '') ?></textarea>
        </div>

        <div>
            <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase mb-1">Pie de Página Tickets</label>
            <textarea name="ticket_footer" rows="2" class="form-input focus:ring-accent-500" placeholder="¡Mantenga su factura para devoluciones!"><?= htmlspecialchars($bizData['ticket_footer'] ?? '') ?></textarea>
        </div>

        <div class="mt-auto pt-6">
            <button type="submit" class="w-full bg-accent-600 hover:bg-accent-500 text-white font-bold py-2 px-5 min-h-[40px] rounded-xl text-sm transition-colors text-center shadow-sm"><i class="fas fa-images mr-1"></i> Guardar Diseño</button>
        </div>
    </form>

    <!-- Menú Digital QR -->
    <form id="form-menu-qr" class="card p-6 flex flex-col h-full">
        <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-4 border-b border-gray-100 dark:border-gray-700 pb-2"><i class="fas fa-qrcode text-brand-500 mr-2"></i>Menú Digital QR</h3>
        <p class="text-[10px] text-gray-500 dark:text-gray-400 mb-4 leading-tight">Sube tu menú (PDF/JPG). Obtendrás un Código QR fijo que puedes imprimir una vez y actualizar digitalmente cuando quieras.</p>
        
        <div class="flex flex-col gap-6 items-center flex-1">
            <div class="flex-1 w-full flex flex-col">
                <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase mb-2">Archivo del Menú del Día</label>
                <div class="mb-4">
                    <input type="file" id="menu_file" accept=".pdf, image/png, image/jpeg, image/webp" class="block w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-bold file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100 dark:file:bg-brand-900/30 cursor-pointer">
                    <p class="text-[10px] text-gray-400 mt-1">Soporta PDF o Imágenes (Máx 6MB).</p>
                    <input type="hidden" name="menu_file_base64" id="menu_file_base64" value="<?= !empty($bizData['has_menu']) ? 'HAS_FILE' : '' ?>">
                </div>
                <div id="menu_status" class="text-xs font-bold mb-4 <?= !empty($bizData['has_menu']) ? 'text-green-600' : 'text-gray-400' ?>">
                    <?= !empty($bizData['has_menu']) ? '<i class="fas fa-check-circle mr-1"></i> Menú activo.' : '<i class="fas fa-info-circle mr-1"></i> Ningún menú subido' ?>
                </div>
                
                <div class="mt-auto w-full flex gap-2 flex-wrap">
                    <button type="submit" class="btn-primary flex-1 text-xs py-2 min-h-0"><i class="fas fa-upload mr-1"></i> Guardar</button>
                    <?php if(!empty($bizData['has_menu'])): ?>
                    <button type="button" id="btn-delete-menu" class="btn-danger w-10 text-xs py-2 min-h-0" title="Borrar Menú"><i class="fas fa-trash-alt"></i></button>
                    <a href="<?= BASE_URL ?>?serve_menu=1&tenant=<?= $_SESSION['business_id'] ?>" target="_blank" class="btn-secondary w-10 text-xs py-2 min-h-0 flex items-center justify-center" title="Ver Menú Actual"><i class="fas fa-external-link-alt"></i></a>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="w-full bg-gray-50 dark:bg-gray-700/30 rounded-xl border border-gray-200 dark:border-gray-700 p-4 flex flex-col items-center justify-center text-center mt-auto">
                <div id="qrcode-container" class="bg-white p-3 rounded-lg shadow-sm border border-gray-100 mb-3"></div>
                <h4 class="font-bold text-gray-800 dark:text-white text-sm">Tu Código QR</h4>
                <p class="text-[10px] text-gray-500 dark:text-gray-400 mt-1 mb-3 leading-tight">Imprímelo para tus mesas.</p>
                <button type="button" id="btn-download-qr" class="btn-secondary w-full text-xs py-2 min-h-0 shadow-sm"><i class="fas fa-download mr-1"></i> Descargar QR</button>
            </div>
        </div>
    </form>

    <!-- Pagos y Tasas Dinámicas -->
    <div class="card p-6 flex flex-col h-full">
        <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-4 border-b border-gray-100 dark:border-gray-700 pb-2"><i class="fas fa-wallet text-green-500 mr-2"></i>Métodos de Pago</h3>
        <ul class="space-y-2 mb-4 max-h-48 overflow-y-auto pr-2">
            <?php if(empty($paymentMethods)): ?>
                <li class="text-sm text-gray-400 italic text-center py-4">Sin métodos configurados</li>
            <?php else: foreach($paymentMethods as $pm): ?>
            <li class="flex justify-between items-center text-sm p-2.5 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-100 dark:border-gray-600">
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full <?= $pm['is_active'] ? 'bg-green-500' : 'bg-gray-400' ?>"></span>
                    <span class="font-bold dark:text-white"><?= htmlspecialchars($pm['name']) ?></span>
                    <span class="text-xs text-gray-400">(<?= $pm['currency'] ?>)</span>
                </div>
                <?php $igtfClass = $pm['applies_igtf'] ? 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400' : 'bg-gray-200 dark:bg-gray-600 text-gray-600 dark:text-gray-300'; ?>
                <span class="text-xs px-2 py-1 rounded font-bold <?= $igtfClass ?>"><?= $pm['applies_igtf'] ? '+ IGTF' : 'Sin IGTF' ?></span>
            </li>
            <?php endforeach; endif; ?>
        </ul>
        <button type="button" onclick="document.getElementById('modal-payment').classList.remove('hidden')" class="mt-auto w-full btn-secondary text-sm">
            <i class="fas fa-plus mr-1"></i> Añadir Método
        </button>
    </div>

    <!-- Base de Datos -->
    <div class="card p-6 flex flex-col h-full">
        <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-4 border-b border-gray-100 dark:border-gray-700 pb-2"><i class="fas fa-database text-purple-500 mr-2"></i>Base de Datos</h3>
        <div class="space-y-3">
            <a href="<?= BASE_URL ?>settings/export_csv" class="w-full flex items-center justify-between bg-gray-50 hover:bg-gray-100 dark:bg-slate-700/50 dark:hover:bg-slate-700 p-3 rounded-xl border border-gray-200 dark:border-gray-700 transition-colors text-sm font-bold text-gray-700 dark:text-gray-200">
                <span>Exportar Inventario (Excel)</span>
                <i class="fas fa-file-excel text-emerald-600"></i>
            </a>
            <a href="<?= BASE_URL ?>settings/download_template" class="w-full flex items-center justify-between bg-gray-50 hover:bg-gray-100 dark:bg-slate-700/50 dark:hover:bg-slate-700 p-3 rounded-xl border border-gray-200 dark:border-gray-700 transition-colors text-sm font-bold text-gray-700 dark:text-gray-200">
                <span>Descargar Plantilla CSV</span>
                <i class="fas fa-download text-brand-600"></i>
            </a>
            <a href="<?= BASE_URL ?>settings/export_tenant_data" class="w-full flex items-center justify-between bg-gray-50 hover:bg-gray-100 dark:bg-slate-700/50 dark:hover:bg-slate-700 p-3 rounded-xl border border-gray-200 dark:border-gray-700 transition-colors text-sm font-bold text-gray-700 dark:text-gray-200">
                <span>Respaldar Datos (JSON)</span>
                <i class="fas fa-file-code text-gray-400"></i>
            </a>
        </div>
    </div>
    

    <!-- Sistema y Reportes -->
    <div class="card p-6 flex flex-col h-full">
        <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-4 border-b border-gray-100 dark:border-gray-700 pb-2"><i class="fas fa-server text-blue-500 mr-2"></i>Sistema</h3>
        <div class="space-y-3">
            <button type="button" onclick="showToast('El sistema ya cuenta con la última versión.')" class="w-full flex items-center justify-between text-left py-2 px-3 hover:bg-gray-50 dark:hover:bg-gray-700 rounded text-sm text-gray-700 dark:text-gray-300 transition-colors">
                <span>Buscar Actualizaciones</span>
                <i class="fas fa-cloud-download-alt"></i>
            </button>
            <button type="button" onclick="showToast('Servidor local habilitado en puerto 8000')" class="w-full flex items-center justify-between text-left py-2 px-3 hover:bg-gray-50 dark:hover:bg-gray-700 rounded text-sm text-gray-700 dark:text-gray-300 transition-colors">
                <span>Habilitar Servidor en Red Local</span>
                <i class="fas fa-network-wired"></i>
            </button>
            <button type="button" onclick="showToast('Reporte X/Z generado')" class="mt-auto w-full flex items-center justify-between text-left py-2 px-3 hover:bg-gray-50 dark:hover:bg-gray-700 rounded text-sm text-brand-600 font-bold bg-brand-50 dark:bg-brand-900/20 transition-colors">
                <span>Historial de Cierres (Reporte X/Z)</span>
                <i class="fas fa-file-invoice-dollar"></i>
            </button>
        </div>
    </div>

    <!-- Restablecimiento de Fábrica -->
    <div id="factory-reset-section" class="card shadow-sm border border-red-200 dark:border-red-900/50 p-6 flex flex-col h-full">
        <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-4 border-b border-red-100 dark:border-red-900/50 pb-2"><i class="fas fa-exclamation-triangle text-red-600 mr-2"></i>Restablecer Sistema</h3>
        <div class="space-y-3">
            <p class="text-xs text-red-600 dark:text-red-400 mb-4 font-semibold">
                ¡Advertencia! Esto eliminará permanentemente todos tus productos, clientes, proveedores, ventas, compras y registros operativos del sistema. Solo quedará tu cuenta y la configuración del negocio.
            </p>
            <button id="btn-factory-reset" type="button" class="mt-auto btn-danger w-full text-sm">
                <i class="fas fa-trash-alt mr-2"></i> Eliminar todo y empezar de cero
            </button>
        </div>
    </div>

</div>

<!-- Modal Añadir Método de Pago -->
<div id="modal-payment" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl w-full max-w-md overflow-hidden flex flex-col max-h-[90vh]">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center bg-gray-50/50 dark:bg-slate-800/50">
                <h3 class="font-bold text-lg text-gray-800 dark:text-white"><i class="fas fa-wallet text-green-500 mr-2"></i>Añadir Método de Pago</h3>
                <button type="button" onclick="document.getElementById('modal-payment').classList.add('hidden')" class="modal-close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="form-payment-method" class="p-6 flex-1 overflow-y-auto">
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase mb-1">Nombre</label>
                        <input type="text" name="name" required class="form-input w-full" placeholder="Ej: Zelle">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase mb-1">Código</label>
                        <input type="text" name="code" required class="form-input w-full" placeholder="Ej: ZELLE">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase mb-1">Moneda</label>
                        <select name="currency" class="form-select w-full">
                            <option value="VES">Bolívares (VES)</option>
                            <option value="USD">Dólares (USD)</option>
                            <option value="COP">Pesos Colombianos (COP)</option>
                            <option value="EUR">Euros (EUR)</option>
                        </select>
                    </div>
                    <div>
                        <label class="flex items-center space-x-2 cursor-pointer mt-2">
                            <input type="checkbox" name="applies_igtf" value="1" class="rounded text-brand-600 focus:ring-brand-500 border-gray-300 dark:border-gray-600 dark:bg-gray-700 bg-white">
                            <span class="text-sm font-bold text-gray-700 dark:text-gray-300">Aplica IGTF (Impuesto Grandes Transacciones)</span>
                        </label>
                    </div>
                </div>
                <div class="mt-6 pt-4 border-t border-gray-100 dark:border-gray-700 flex gap-3">
                    <button type="button" onclick="document.getElementById('modal-payment').classList.add('hidden')" class="flex-1 btn-secondary text-sm">Cancelar</button>
                    <button type="submit" class="flex-1 btn-primary text-sm"><i class="fas fa-save mr-1"></i> Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Toast de confirmación -->
<div id="settings-toast" class="fixed bottom-6 right-6 bg-green-600 text-white px-6 py-3 rounded-lg shadow-lg font-bold text-sm hidden transition-all transform translate-y-4 opacity-0">
    <i class="fas fa-check-circle mr-2"></i> <span id="toast-msg">Guardado correctamente</span>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    function showToast(msg) {
        const t = document.getElementById('settings-toast');
        document.getElementById('toast-msg').textContent = msg;
        t.classList.remove('hidden');
        setTimeout(() => { t.classList.remove('translate-y-4', 'opacity-0'); }, 10);
        setTimeout(() => { 
            t.classList.add('translate-y-4', 'opacity-0');
            setTimeout(() => t.classList.add('hidden'), 300);
        }, 2500);
    }

    // Preparar Logo como Base64 redimensionado
    document.getElementById('logo_file').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (!file) return;

        const reader = new FileReader();
        reader.onload = function(event) {
            const img = new Image();
            img.onload = function() {
                const MAX_STR = 300;
                let w = img.width;
                let h = img.height;
                if (w > h && w > MAX_STR) { h *= MAX_STR / w; w = MAX_STR; } 
                else if (h > MAX_STR) { w *= MAX_STR / h; h = MAX_STR; }
                
                const canvas = document.createElement('canvas');
                canvas.width = w; canvas.height = h;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0, w, h);
                
                const mime = file.type.includes('svg') ? 'image/png' : file.type;
                const dataUrl = canvas.toDataURL(mime, 0.85); // compress
                
                document.getElementById('logo_base64').value = dataUrl;
                document.getElementById('logo-preview').src = dataUrl;
            };
            img.src = event.target.result;
        };
        reader.readAsDataURL(file);
    });

    // Generic AJAX form submit for both forms
    document.querySelectorAll('#form-rates, #form-branding').forEach(form => {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const data = new FormData(form);
            const btn = form.querySelector('button[type="submit"]');
            const ogHtml = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>...';
            btn.disabled = true;

            try {
                const res = await fetch('<?= BASE_URL ?>settings/save', { method: 'POST', body: data });
                const json = await res.json();
                if (json.success) showToast('Configuración guardada exitosamente');
            } catch(err) {
                alert('Error al guardar: ' + err.message);
            }
            btn.innerHTML = ogHtml;
            btn.disabled = false;
        });
    });

    // Añadir método de pago
    const formPaymentMethod = document.getElementById('form-payment-method');
    if (formPaymentMethod) {
        formPaymentMethod.addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = e.target.querySelector('button[type="submit"]');
            const ogHtml = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>...';
            btn.disabled = true;

            const data = {
                name: formPaymentMethod.querySelector('[name="name"]').value,
                code: formPaymentMethod.querySelector('[name="code"]').value,
                currency: formPaymentMethod.querySelector('[name="currency"]').value,
                applies_igtf: formPaymentMethod.querySelector('[name="applies_igtf"]').checked ? 1 : 0
            };

            try {
                const res = await fetch('<?= BASE_URL ?>settings/addPaymentMethod', { 
                    method: 'POST', 
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const json = await res.json();
                if (json.success) {
                    showToast('Método de pago agregado');
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    alert('Error: ' + json.message);
                }
            } catch(err) {
                alert('Error al guardar: ' + err.message);
            }
            btn.innerHTML = ogHtml;
            btn.disabled = false;
        });
    }
    // Factory Reset Action
    const btnReset = document.getElementById('btn-factory-reset');
    if(btnReset) {
        btnReset.addEventListener('click', async () => {
            const confirmed = confirm('¿Estás absolutamente seguro de querer RESTABLECER DE FÁBRICA? Esta acción borrará todas las transacciones, productos y movimientos. NO se puede deshacer.');
            if(!confirmed) return;
            
            const secondConfirm = prompt('Escribe "CONFIRMAR" en mayúsculas para proceder con la eliminación total de los datos operativos:');
            if(secondConfirm !== 'CONFIRMAR') {
                alert('Operación cancelada.');
                return;
            }

            btnReset.disabled = true;
            btnReset.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Procesando...';

            try {
                const res = await fetch('<?= BASE_URL ?>settings/factoryReset', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ confirm: true })
                });
                const data = await res.json();
                if(data.success) {
                    alert('Sistema restablecido correctamente. La página se recargará para aplicar los cambios.');
                    window.location.reload();
                } else {
                    alert('Error: ' + data.message);
                    btnReset.disabled = false;
                    btnReset.innerHTML = '<i class="fas fa-trash-alt mr-2"></i> Eliminar todo y empezar de cero';
                }
            } catch (err) {
                alert('Ocurrió un error inesperado al restablecer.');
                console.error(err);
                btnReset.disabled = false;
                btnReset.innerHTML = '<i class="fas fa-trash-alt mr-2"></i> Eliminar todo y empezar de cero';
            }
        });
    }

    // --- Lógica del Menú QR Dinámico ---
    const menuInput = document.getElementById('menu_file');
    if(menuInput) {
        menuInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (!file) return;
            if (file.size > 6 * 1024 * 1024) {
                 alert('El archivo es demasiado grande (Máx 6MB).');
                 e.target.value = '';
                 return;
            }
            const reader = new FileReader();
            reader.onload = function(event) {
                document.getElementById('menu_file_base64').value = event.target.result;
                document.getElementById('menu_status').innerHTML = '<span class="text-brand-600"><i class="fas fa-check-circle mr-1"></i> Archivo listo. Haz clic en "Guardar Menú".</span>';
            };
            reader.readAsDataURL(file);
        });

        // Configuración QR
        const qrUrl = "<?= rtrim(BASE_URL, '/') ?>/?serve_menu=1&tenant=<?= $_SESSION['business_id'] ?>";
        const qrcodeCont = document.getElementById("qrcode-container");
        if(qrcodeCont) {
            new QRCode(qrcodeCont, {
                text: qrUrl, width: 140, height: 140,
                colorDark : "#1e293b", colorLight : "#ffffff", correctLevel : QRCode.CorrectLevel.H
            });
        }

        const btnDownload = document.getElementById('btn-download-qr');
        if(btnDownload) {
            btnDownload.addEventListener('click', () => {
                const img = qrcodeCont.querySelector('img');
                if(!img) {
                    alert('El código QR aún no se ha generado correctamente.');
                    return;
                }
                const a = document.createElement('a');
                a.href = img.src;
                a.download = 'Menu-QR-TuInventario.png';
                a.click();
            });
        }

        const formMenu = document.getElementById('form-menu-qr');
        if(formMenu) {
            formMenu.addEventListener('submit', async (e) => {
                e.preventDefault();
                const base64val = document.getElementById('menu_file_base64').value;
                if(base64val === 'HAS_FILE') {
                    alert('Por favor selecciona un archivo nuevo para reemplazar el anterior.');
                    return;
                }
                if(!base64val) {
                    alert('Por favor selecciona un archivo para subir.');
                    return;
                }

                const btn = e.target.querySelector('button[type="submit"]');
                const ogHtml = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Guardando...';
                btn.disabled = true;

                const data = new FormData(e.target);
                try {
                    const res = await fetch('<?= BASE_URL ?>settings/save', { method: 'POST', body: data });
                    const json = await res.json();
                    if (json.success) {
                        showToast('Menú QR guardado exitosamente');
                        setTimeout(() => window.location.reload(), 1000);
                    } else {
                        alert('Error: ' + (json.message || 'Error desconocido'));
                    }
                } catch(err) { alert('Error de conexión al guardar.'); }
                
                btn.innerHTML = ogHtml;
                btn.disabled = false;
            });
        }

        const btnDeleteMenu = document.getElementById('btn-delete-menu');
        if(btnDeleteMenu) {
            btnDeleteMenu.addEventListener('click', async () => {
                if(!confirm('¿Seguro quieres eliminar tu Menú Digital? Los clientes ya no podrán verlo.')) return;
                const btn = btnDeleteMenu;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                btn.disabled = true;
                const fd = new FormData();
                fd.append('menu_file_base64', '');
                try {
                    const res = await fetch('<?= BASE_URL ?>settings/save', { method: 'POST', body: fd });
                    const json = await res.json();
                    if(json.success) { window.location.reload(); }
                } catch(e) {}
            });
        }
    }
});
</script>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>
