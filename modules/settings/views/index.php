<?php include __DIR__ . '/../../../includes/header.php'; ?>

<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Configuración del Sistema</h2>
    <p class="text-gray-600 dark:text-gray-400 text-sm">Administra las tasas, fiscalidad y seguridad del ERP</p>
</div>

<?php $s = $settings ?? []; ?>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    
    <!-- Tasas y Impuestos -->
    <form id="form-rates" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
        <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-4 border-b border-gray-100 dark:border-gray-700 pb-2"><i class="fas fa-coins text-brand-500 mr-2"></i>Tasas e Impuestos</h3>
        <div class="space-y-3">
            <div>
                <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Tasa BCV (Bs/$)</label>
                <input type="number" step="0.01" name="bcv_rate" class="mt-1 w-full border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-brand-500 text-sm" value="<?= $s['bcv_rate'] ?? '622.21' ?>">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Paralelo / COP</label>
                <div class="flex gap-2">
                    <input type="number" step="0.01" name="parallel_rate" class="mt-1 w-1/2 border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg text-sm" placeholder="Paralelo" value="<?= $s['parallel_rate'] ?? '' ?>">
                    <input type="number" step="0.01" name="cop_rate" class="mt-1 w-1/2 border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg text-sm" placeholder="COP" value="<?= $s['cop_rate'] ?? '' ?>">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">IVA (%)</label>
                    <input type="number" step="0.01" name="tax_iva" class="mt-1 w-full border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg text-sm" value="<?= $s['tax_iva'] ?? '16' ?>">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">IGTF (%)</label>
                    <input type="number" step="0.01" name="tax_igtf" class="mt-1 w-full border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg text-sm" value="<?= $s['tax_igtf'] ?? '3' ?>">
                </div>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Método de Cálculo</label>
                <select name="calc_method" class="mt-1 w-full border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg text-sm mb-2">
                    <option value="simple" <?= ($s['calc_method'] ?? '') === 'simple' ? 'selected' : '' ?>>Simple (Costo + Ganancia)</option>
                    <option value="fiscal" <?= ($s['calc_method'] ?? 'fiscal') === 'fiscal' ? 'selected' : '' ?>>Fiscal (Costo / (1 - Margen))</option>
                </select>
                <select name="iva_method" class="w-full border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg text-sm">
                    <option value="included" <?= ($s['iva_method'] ?? 'included') === 'included' ? 'selected' : '' ?>>IVA Incluido en Precio</option>
                    <option value="add_later" <?= ($s['iva_method'] ?? '') === 'add_later' ? 'selected' : '' ?>>Sumar IVA al cobrar</option>
                </select>
            </div>
        </div>
        <button type="submit" class="mt-4 w-full bg-brand-600 hover:bg-brand-500 text-white font-bold py-2 rounded-lg text-sm transition-colors"><i class="fas fa-save mr-1"></i> Guardar Tasas</button>
    </form>

    <!-- Pagos y Tasas Dinámicas -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
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
                <span class="text-xs px-2 py-1 rounded font-bold <?= $pm['applies_igtf'] ? 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400' : 'bg-gray-200 dark:bg-gray-600 text-gray-600 dark:text-gray-300' ?>"><?= $pm['applies_igtf'] ? '+ IGTF' : 'Sin IGTF' ?></span>
            </li>
            <?php endforeach; endif; ?>
        </ul>
        <button class="w-full bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 dark:text-white text-gray-800 font-bold py-2 rounded-lg text-sm transition-colors border border-gray-200 dark:border-gray-600">
            <i class="fas fa-plus mr-1"></i> Añadir Método
        </button>
    </div>

    <!-- Base de Datos -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
        <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-4 border-b border-gray-100 dark:border-gray-700 pb-2"><i class="fas fa-database text-purple-500 mr-2"></i>Base de Datos</h3>
        <div class="space-y-3">
            <button class="w-full flex items-center justify-between bg-gray-50 hover:bg-gray-100 dark:bg-gray-700 dark:hover:bg-gray-600 p-3 rounded-lg border border-gray-200 dark:border-gray-600 transition-colors">
                <span class="font-medium text-gray-700 dark:text-gray-200 text-sm">Exportar Inventario (Excel)</span>
                <i class="fas fa-file-excel text-green-600"></i>
            </button>
            <button class="w-full flex items-center justify-between bg-gray-50 hover:bg-gray-100 dark:bg-gray-700 dark:hover:bg-gray-600 p-3 rounded-lg border border-gray-200 dark:border-gray-600 transition-colors">
                <span class="font-medium text-gray-700 dark:text-gray-200 text-sm">Descargar Plantilla CSV</span>
                <i class="fas fa-download text-brand-600"></i>
            </button>
            <button class="w-full flex items-center justify-between bg-gray-50 hover:bg-gray-100 dark:bg-gray-700 dark:hover:bg-gray-600 p-3 rounded-lg border border-gray-200 dark:border-gray-600 transition-colors">
                <span class="font-medium text-gray-700 dark:text-gray-200 text-sm">Respaldar SQLite (.db)</span>
                <i class="fas fa-hdd text-gray-600 dark:text-gray-400"></i>
            </button>
        </div>
    </div>
    
    <!-- Seguridad -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
        <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-4 border-b border-gray-100 dark:border-gray-700 pb-2"><i class="fas fa-shield-alt text-red-500 mr-2"></i>Seguridad</h3>
        <div x-data="{ show: false }" class="space-y-3">
            <div>
                <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Nueva Contraseña</label>
                <div class="relative mt-1">
                    <input :type="show ? 'text' : 'password'" class="w-full border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-brand-500 pr-10 text-sm">
                    <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-400 hover:text-gray-600">
                        <i class="fas" :class="show ? 'fa-eye-slash' : 'fa-eye'"></i>
                    </button>
                </div>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Confirmar Contraseña</label>
                <input :type="show ? 'text' : 'password'" class="mt-1 w-full border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-brand-500 text-sm">
            </div>
            <button class="mt-2 w-full bg-red-600/10 hover:bg-red-600/20 text-red-600 font-bold py-2 rounded-lg text-sm transition-colors border border-red-200">Actualizar Accesos</button>
        </div>
    </div>

    <!-- Sistema y Reportes -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
        <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-4 border-b border-gray-100 dark:border-gray-700 pb-2"><i class="fas fa-server text-blue-500 mr-2"></i>Sistema</h3>
        <div class="space-y-3">
            <button class="w-full flex items-center justify-between text-left py-2 px-3 hover:bg-gray-50 dark:hover:bg-gray-700 rounded text-sm text-gray-700 dark:text-gray-300 transition-colors">
                <span>Buscar Actualizaciones ERP</span>
                <i class="fas fa-cloud-download-alt"></i>
            </button>
            <button class="w-full flex items-center justify-between text-left py-2 px-3 hover:bg-gray-50 dark:hover:bg-gray-700 rounded text-sm text-gray-700 dark:text-gray-300 transition-colors">
                <span>Habilitar Servidor en Red Local</span>
                <i class="fas fa-network-wired"></i>
            </button>
            <button class="w-full flex items-center justify-between text-left py-2 px-3 hover:bg-gray-50 dark:hover:bg-gray-700 rounded text-sm text-brand-600 font-bold bg-brand-50 dark:bg-brand-900/20 transition-colors">
                <span>Historial de Cierres (Reporte X/Z)</span>
                <i class="fas fa-file-invoice-dollar"></i>
            </button>
        </div>
    </div>

    <!-- Personalización -->
    <form id="form-company" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
        <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-4 border-b border-gray-100 dark:border-gray-700 pb-2"><i class="fas fa-paint-brush text-pink-500 mr-2"></i>Personalización</h3>
        <div class="space-y-3">
            <div>
                <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Nombre del Negocio</label>
                <input type="text" name="business_name" class="mt-1 w-full border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-brand-500 text-sm" value="<?= htmlspecialchars($s['business_name'] ?? 'TuInventarioApp ERP') ?>">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase mb-2">Logo</label>
                <div class="flex items-center gap-4">
                    <div class="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded flex items-center justify-center border-2 border-dashed border-gray-300 dark:border-gray-500 text-gray-400 cursor-pointer hover:border-brand-500 hover:text-brand-500 transition-colors">
                        <i class="fas fa-upload"></i>
                    </div>
                    <div class="flex-1 text-xs text-gray-500 dark:text-gray-400">
                        Sube una imagen cuadrada (JPG/PNG). Tamaño máximo: 1MB.
                    </div>
                </div>
            </div>
            <button type="submit" class="mt-2 w-full bg-brand-600 hover:bg-brand-500 text-white font-bold py-2 rounded-lg text-sm"><i class="fas fa-save mr-1"></i> Guardar Cambios</button>
        </div>
    </form>

</div>

<!-- Toast de confirmación -->
<div id="settings-toast" class="fixed bottom-6 right-6 bg-green-600 text-white px-6 py-3 rounded-lg shadow-lg font-bold text-sm hidden transition-all transform translate-y-4 opacity-0">
    <i class="fas fa-check-circle mr-2"></i> <span id="toast-msg">Guardado correctamente</span>
</div>

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

    // Generic AJAX form submit
    document.querySelectorAll('#form-rates, #form-company').forEach(form => {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const data = new FormData(form);
            try {
                const res = await fetch('<?= BASE_URL ?>settings/save', { method: 'POST', body: data });
                const json = await res.json();
                if (json.success) showToast('Configuración guardada exitosamente');
            } catch(err) {
                alert('Error al guardar: ' + err.message);
            }
        });
    });
});
</script>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>
