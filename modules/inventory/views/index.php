<?php include __DIR__ . '/../../../includes/header.php'; ?>

<div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4" x-data="{ openModal: false, showQR: false, qrName: '', qrCode: '' }">
    <div>
        <h2 class="text-2xl font-extrabold text-gray-800 dark:text-white tracking-tight">Inventario y Productos</h2>
        <p class="text-gray-500 dark:text-gray-400 text-sm font-medium mt-1">Gestión centralizada de existencias</p>
    </div>
    <button @click="openModal = true" class="bg-gradient-to-r from-brand-600 to-accent-600 hover:from-brand-500 hover:to-accent-500 text-white font-bold px-5 py-2.5 rounded-lg shadow-sm shadow-brand-500/20 transition-all flex items-center justify-center w-full sm:w-auto text-sm">
        <i class="fas fa-plus mr-2"></i> Registrar Producto
    </button>
    
    <!-- Modal Full Product Registration Alpine HTMX -->
    <div x-show="openModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-start justify-center min-h-screen px-4 pt-10 pb-20 text-center sm:p-0">
            <!-- Overlay -->
            <div x-show="openModal" x-transition.opacity class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="openModal = false"></div>
            
            <!-- Modal Body -->
            <div x-show="openModal" 
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="relative inline-block w-full max-w-4xl p-6 md:p-8 overflow-hidden text-left align-middle bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-gray-100 dark:border-gray-800 my-8"
                 x-data="productForm()">
                 
                <div class="flex justify-between items-center mb-6 border-b border-gray-100 dark:border-gray-800 pb-4">
                    <h3 class="text-xl font-black text-gray-800 dark:text-white flex items-center">
                        <div class="w-8 h-8 rounded-lg bg-brand-50 dark:bg-brand-900/30 text-brand-600 dark:text-brand-400 flex items-center justify-center mr-3">
                            <i class="fas fa-box-open"></i>
                        </div>
                        Registrar Nuevo Producto
                    </h3>
                    <button @click="openModal = false" class="text-gray-400 hover:text-red-500 transition-colors w-8 h-8 flex items-center justify-center rounded-full hover:bg-red-50 dark:hover:bg-red-900/20">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <form hx-post="<?= BASE_URL ?>inventory/create" hx-encoding="multipart/form-data" hx-swap="none" 
                      @htmx:after-request="if($event.detail.successful) { openModal = false; $el.reset(); document.body.dispatchEvent(new Event('inventoryUpdated')); }" 
                      class="space-y-6">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <!-- Columna Izquierda: Datos Básicos e Inventario -->
                        <div class="space-y-5">
                            <h4 class="text-xs font-bold text-gray-400 uppercase tracking-widest border-b border-gray-100 dark:border-gray-800 pb-2">1. Datos Básicos</h4>
                            
                            <div>
                                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1.5">Nombre del Producto *</label>
                                <input type="text" name="name" required class="w-full bg-gray-50 dark:bg-slate-800 border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-2 text-sm text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-500 transition-shadow">
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1.5">Código Barras</label>
                                    <input type="text" name="barcode" placeholder="Opcional" class="w-full bg-gray-50 dark:bg-slate-800 border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-2 text-sm text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-500">
                                </div>
                                <div x-data="{ catMode: 'select' }">
                                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1.5">Categoría *</label>
                                    <template x-if="catMode === 'select'">
                                        <select name="category_id" required @change="if($event.target.value === 'new') catMode = 'input'" class="w-full bg-gray-50 dark:bg-slate-800 border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-2 text-sm text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-500">
                                            <option value="">Seleccione...</option>
                                            <?php if(!empty($categories)): foreach($categories as $c): ?>
                                                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                                            <?php endforeach; endif; ?>
                                            <option value="new" class="font-bold text-brand-600 dark:text-brand-400">+ Añadir Nueva...</option>
                                        </select>
                                    </template>
                                    <template x-if="catMode === 'input'">
                                        <div class="flex items-center gap-2">
                                            <input type="hidden" name="category_id" value="new">
                                            <input type="text" name="new_category" required placeholder="Nombre..." class="w-full bg-brand-50 dark:bg-brand-900/20 border border-brand-200 dark:border-brand-800 rounded-lg px-3 py-2 text-sm text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-500">
                                            <button type="button" @click="catMode = 'select'" class="text-gray-400 hover:text-red-500"><i class="fas fa-times"></i></button>
                                        </div>
                                    </template>
                                </div>
                            </div>
                            
                            <div x-data="{ supplierMode: 'select' }">
                                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1.5">Proveedor (Opcional)</label>
                                <template x-if="supplierMode === 'select'">
                                    <select name="supplier_id" @change="if($event.target.value === 'new') supplierMode = 'input'" class="w-full bg-gray-50 dark:bg-slate-800 border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-2 text-sm text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-500">
                                        <option value="">Ninguno</option>
                                        <?php if(!empty($brands)): foreach($brands as $b): ?>
                                            <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['name']) ?></option>
                                        <?php endforeach; endif; ?>
                                        <option value="new" class="font-bold text-brand-600 dark:text-brand-400">+ Añadir Nuevo...</option>
                                    </select>
                                </template>
                                <template x-if="supplierMode === 'input'">
                                    <div class="flex items-center gap-2">
                                        <input type="hidden" name="supplier_id" value="new">
                                        <input type="text" name="new_supplier" required placeholder="Nombre..." class="w-full bg-brand-50 dark:bg-brand-900/20 border border-brand-200 dark:border-brand-800 rounded-lg px-3 py-2 text-sm text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-500">
                                        <button type="button" @click="supplierMode = 'select'" class="text-gray-400 hover:text-red-500"><i class="fas fa-times"></i></button>
                                    </div>
                                </template>
                            </div>

                            <div>
                                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1.5">Fotografía</label>
                                <div class="relative">
                                    <input type="file" name="image" accept="image/*" class="w-full bg-gray-50 dark:bg-slate-800 border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-1.5 text-sm file:mr-4 file:py-1 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100 dark:file:bg-brand-900/30 dark:file:text-brand-400">
                                </div>
                            </div>
                            
                            <h4 class="text-xs font-bold text-gray-400 uppercase tracking-widest border-b border-gray-100 dark:border-gray-800 pb-2 pt-4">2. Inventario</h4>

                            <div class="grid grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1.5">Stock</label>
                                    <input type="number" name="stock" value="0" required class="w-full bg-gray-50 dark:bg-slate-800 border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-2 text-sm text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-500">
                                </div>
                                <div x-data="{ unitMode: 'select' }">
                                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1.5">Unidad</label>
                                    <template x-if="unitMode === 'select'">
                                        <select name="unit_of_measure" @change="if($event.target.value === 'new') unitMode = 'input'" class="w-full bg-gray-50 dark:bg-slate-800 border border-gray-200 dark:border-gray-700 rounded-lg px-2 py-2 text-sm text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-500">
                                            <option value="Unidades">Und</option>
                                            <option value="Kg">Kg</option>
                                            <option value="Litros">Litros</option>
                                            <option value="Metros">Metros</option>
                                            <option value="new" class="font-bold text-brand-600">+ Otra...</option>
                                        </select>
                                    </template>
                                    <template x-if="unitMode === 'input'">
                                        <div class="flex items-center gap-1">
                                            <input type="hidden" name="unit_of_measure" value="new">
                                            <input type="text" name="new_unit" required placeholder="Ej: Rollo" class="w-full bg-brand-50 border border-brand-200 rounded-lg px-2 py-2 text-xs focus:outline-none">
                                            <button type="button" @click="unitMode = 'select'" class="text-red-500"><i class="fas fa-times text-xs"></i></button>
                                        </div>
                                    </template>
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1.5">Mínimo</label>
                                    <input type="number" name="min_stock" value="5" class="w-full bg-gray-50 dark:bg-slate-800 border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-2 text-sm text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-500">
                                </div>
                            </div>
                        </div>

                        <!-- Columna Derecha: Costos, Precio, Fiscal -->
                        <div class="space-y-5 bg-gray-50 dark:bg-slate-800/50 p-5 rounded-xl border border-gray-100 dark:border-gray-700 flex flex-col">
                            <h4 class="text-xs font-bold text-brand-600 dark:text-brand-400 uppercase tracking-widest border-b border-brand-100 dark:border-brand-900/30 pb-2 mb-2">3. Costos y Precios</h4>
                            
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1.5">Tipo de Costo</label>
                                    <select name="cost_type" x-model="costType" class="w-full bg-white dark:bg-slate-900 border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-2 text-sm text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-500">
                                        <option value="unit">Costo Fijo Unitario</option>
                                        <option value="bulk">Costo por Bulto/Pack</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1.5">Divisa Costo</label>
                                    <select name="currency" class="w-full bg-white dark:bg-slate-900 border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-2 text-sm text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-500">
                                        <option value="USD">USD ($)</option>
                                        <option value="VES">BS (Bs.)</option>
                                        <option value="EUR">EUR (€)</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div x-show="costType === 'unit'" class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-bold text-brand-700 dark:text-brand-400 mb-1.5">Costo x Unidad *</label>
                                    <div class="relative">
                                        <span class="absolute left-3 top-2 text-gray-400 font-bold">$</span>
                                        <input type="number" step="0.01" name="unit_cost" x-model.number="unitCost" class="w-full bg-white dark:bg-slate-900 border border-gray-200 dark:border-gray-700 rounded-lg pl-8 pr-3 py-2 text-sm text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-500 font-medium">
                                    </div>
                                </div>
                            </div>

                            <div x-show="costType === 'bulk'" class="grid grid-cols-2 gap-4 bg-brand-50 dark:bg-brand-900/10 p-3 rounded-lg border border-brand-100 dark:border-brand-800/30">
                                <div>
                                    <label class="block text-xs font-bold text-brand-700 dark:text-brand-400 mb-1.5">Costo del Bulto *</label>
                                    <input type="number" step="0.01" name="bulk_cost" x-model.number="bulkCost" class="w-full bg-white dark:bg-slate-900 border border-brand-200 dark:border-brand-700/50 rounded-lg px-3 py-2 text-sm text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-500">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-brand-700 dark:text-brand-400 mb-1.5">Und. por Bulto *</label>
                                    <input type="number" name="units_per_bulk" x-model.number="unitsPerBulk" class="w-full bg-white dark:bg-slate-900 border border-brand-200 dark:border-brand-700/50 rounded-lg px-3 py-2 text-sm text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-500">
                                </div>
                                <div class="col-span-2 text-xs flex justify-between items-center px-1">
                                    <span class="text-gray-500 dark:text-gray-400">Costo Unitario Calculado:</span>
                                    <span class="font-black text-brand-600 dark:text-brand-400">$<span x-text="calculatedBaseCost.toFixed(2)"></span></span>
                                </div>
                            </div>
                            
                            <hr class="border-gray-200 dark:border-gray-700/50 my-2">

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1.5">% Margen Ganancia</label>
                                    <div class="relative">
                                        <input type="number" step="0.01" name="profit_margin" x-model.number="profitMargin" @input="calculateFinalPrice" class="w-full bg-white dark:bg-slate-900 border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-2 text-sm text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-500 font-bold">
                                        <span class="absolute right-3 top-2 text-gray-400 font-bold">%</span>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1.5">Precio de Venta</label>
                                    <div class="relative">
                                        <span class="absolute left-3 top-2 text-brand-500 font-bold">$</span>
                                        <input type="number" step="0.01" name="price" x-model.number="finalPrice" @input="calculateMargin" class="w-full bg-white dark:bg-slate-900 border border-brand-200 dark:border-brand-700 shadow-inner rounded-lg pl-8 pr-3 py-2 text-sm text-xl text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-500 font-black text-brand-700 dark:text-brand-400">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mt-auto pt-4 flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-bold text-gray-700 dark:text-gray-300 mb-0.5">Producto Exento de IVA</p>
                                    <p class="text-[10px] text-gray-500 dark:text-gray-400">No aplica el impuesto al momento del cobro</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                  <input type="checkbox" name="is_tax_exempt" value="1" class="sr-only peer">
                                  <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-brand-500"></div>
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-6 pt-5 border-t border-gray-100 dark:border-gray-800 flex justify-end gap-3">
                        <button type="button" @click="openModal = false" class="px-5 py-2.5 bg-white dark:bg-slate-800 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700 font-medium text-sm transition-colors">Cancelar</button>
                        <button type="submit" class="px-6 py-2.5 bg-gradient-to-r from-brand-600 to-accent-600 text-white rounded-lg font-bold shadow-md shadow-brand-500/20 hover:shadow-lg hover:shadow-brand-500/30 transition-all text-sm flex items-center">
                            <i class="fas fa-save mr-2"></i> Confirmar y Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function productForm() {
    return {
        costType: 'unit',
        unitCost: 0,
        bulkCost: 0,
        unitsPerBulk: 1,
        profitMargin: 0,
        finalPrice: 0,
        
        get calculatedBaseCost() {
            if (this.costType === 'unit') return parseFloat(this.unitCost) || 0;
            const bulk = parseFloat(this.bulkCost) || 0;
            const units = parseInt(this.unitsPerBulk) || 1;
            return units > 0 ? bulk / units : 0;
        },
        
        calculateFinalPrice() {
            const baseCost = this.calculatedBaseCost;
            const margin = parseFloat(this.profitMargin) || 0;
            // Forma correcta fiscal: Costo / (1 - Margen%) -> para asegurar la ganancia neta.
            // O podemos usar Costo * (1 + Margen%) que es más común en pequeños negocios.
            // Usaremos el clásico Costo * (1 + Margen) según requisito #3.
            this.finalPrice = parseFloat((baseCost * (1 + (margin / 100))).toFixed(2));
        },
        
        calculateMargin() {
            const baseCost = this.calculatedBaseCost;
            const price = parseFloat(this.finalPrice) || 0;
            if (baseCost > 0) {
                this.profitMargin = parseFloat((((price / baseCost) - 1) * 100).toFixed(2));
            } else {
                this.profitMargin = 0;
            }
        }
    }
}
</script>

<div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden mt-4">
    <div class="overflow-x-auto">
        <table class="min-w-[600px] w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50/50 dark:bg-slate-800/50 border-b border-gray-100 dark:border-gray-800 text-gray-500 dark:text-gray-400 uppercase text-[10px] tracking-widest font-black">
                    <th class="p-4 rounded-tl-xl">Producto</th>
                    <th class="p-4">Categoría</th>
                    <th class="p-4 text-center">Stock</th>
                    <th class="p-4 text-right">Precio ($)</th>
                    <th class="p-4 text-right rounded-tr-xl">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800" hx-get="<?= BASE_URL ?>inventory/list" hx-trigger="load, inventoryUpdated from:body" id="inventory-tbody">
                <tr><td colspan="5" class="p-12 text-center text-gray-400 dark:text-gray-500"><i class="fas fa-circle-notch fa-spin text-3xl mb-3 block opacity-50"></i>Sincronizando inventario...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Scripts for HTMX listening to re-attach any Alpine listeners if needed -->
<script>
    document.body.addEventListener('htmx:afterSwap', function(event) {
        if(event.detail.target.id === 'inventory-tbody') {
            // Reinit alpine bindings if necessary or icons
        }
    });
</script>

<!-- Librería para generar Código QR -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

<!-- QR Modal -->
<div id="qrModal" class="fixed inset-0 bg-slate-900/60 z-50 hidden flex items-center justify-center backdrop-blur-sm transition-opacity">
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl p-6 w-full max-w-sm m-4 relative border border-gray-100 dark:border-gray-700 zoom-in animate-fade-in-up">
        <button onclick="closeQrModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 dark:hover:text-white transition-colors w-8 h-8 rounded-full hover:bg-gray-100 dark:hover:bg-slate-700 flex items-center justify-center">
            <i class="fas fa-times"></i>
        </button>
        <div class="text-center">
            <h3 id="qrTitle" class="text-lg font-black text-gray-800 dark:text-white mb-1">Producto</h3>
            <p id="qrSubtitle" class="text-[10px] uppercase font-bold tracking-widest text-brand-600 dark:text-brand-400 mb-6 bg-brand-50 dark:bg-brand-900/30 inline-block px-3 py-1 rounded-full"></p>
            <div id="qrContainer" class="flex justify-center bg-white p-5 rounded-2xl border border-gray-100 inline-block mx-auto shadow-sm"></div>
            <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500 mt-5">Úsalo para lector rápido</p>
        </div>
    </div>
</div>

<script>
    let qrcodeContainer = null;
    
    function openQrModal(name, code) {
        document.getElementById('qrModal').classList.remove('hidden');
        document.getElementById('qrTitle').innerText = name;
        document.getElementById('qrSubtitle').innerText = code;
        
        const container = document.getElementById('qrContainer');
        container.innerHTML = ''; 
        
        qrcodeContainer = new QRCode(container, {
            text: code,
            width: 180,
            height: 180,
            colorDark : "#0f172a", 
            colorLight : "#ffffff",
            correctLevel : QRCode.CorrectLevel.H
        });
    }

    function closeQrModal() {
        document.getElementById('qrModal').classList.add('hidden');
    }
</script>

<style>
@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(10px) scale(0.95); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}
.animate-fade-in-up {
    animation: fadeInUp 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
</style>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>
