<?php include __DIR__ . '/../../../includes/header.php'; ?>

<div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4" x-data="{ openModal: false, showQR: false, qrName: '', qrCode: '' }">
    <div>
        <h2 class="text-2xl font-extrabold text-gray-800 dark:text-white tracking-tight">Inventario y Productos</h2>
        <p class="text-gray-500 dark:text-gray-400 text-sm font-medium mt-1">Gestión centralizada de existencias</p>
    </div>
    <a href="<?= BASE_URL ?>inventory/create_view" class="bg-gradient-to-r from-brand-600 to-accent-600 hover:from-brand-500 hover:to-accent-500 text-white font-bold px-5 py-2.5 rounded-lg shadow-sm shadow-brand-500/20 transition-all flex items-center justify-center w-full sm:w-auto text-sm">
        <i class="fas fa-plus mr-2"></i> Registrar Producto
    </a>
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
                    <button @click="openModal = false" class="modal-close">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <form hx-post="<?= BASE_URL ?>inventory/create" hx-encoding="multipart/form-data" hx-swap="none" 
                      @htmx:after-request="if($event.detail.successful) { openModal = false; $el.reset(); Swal.fire({title: '¡Registro Exitoso!', text: 'El producto ha sido guardado correctamente.', icon: 'success', timer: 2000, showConfirmButton: true, confirmButtonText: 'Continuar', confirmButtonColor: '#10b981', customClass: { popup: 'rounded-2xl' }}).then(() => { htmx.ajax('GET', '<?= BASE_URL ?>inventory/list?t=' + new Date().getTime(), {target: '#inventory-tbody'}); }); }" 
                      class="space-y-6">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <!-- Columna Izquierda: Datos Básicos e Inventario -->
                        <div class="space-y-5">
                            <h4 class="text-xs font-bold text-gray-400 uppercase tracking-widest border-b border-gray-100 dark:border-gray-800 pb-2">1. Datos Básicos</h4>
                            
                            <div>
                                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1.5">Nombre del Producto *</label>
                                <input type="text" name="name" required class="w-full bg-gray-50 dark:bg-slate-800 border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-2 text-sm text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-500 transition-shadow">
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
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
                                    <select name="brand_id" @change="if($event.target.value === 'new') supplierMode = 'input'" class="w-full bg-gray-50 dark:bg-slate-800 border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-2 text-sm text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-500">
                                        <option value="">Ninguno</option>
                                        <?php if(!empty($brands)): foreach($brands as $b): ?>
                                            <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['name']) ?></option>
                                        <?php endforeach; endif; ?>
                                        <option value="new" class="font-bold text-brand-600 dark:text-brand-400">+ Añadir Nuevo...</option>
                                    </select>
                                </template>
                                <template x-if="supplierMode === 'input'">
                                    <div class="flex items-center gap-2">
                                        <input type="hidden" name="brand_id" value="new">
                                        <input type="text" name="new_brand" required placeholder="Nombre..." class="w-full bg-brand-50 dark:bg-brand-900/20 border border-brand-200 dark:border-brand-800 rounded-lg px-3 py-2 text-sm text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-500">
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

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1.5">Stock</label>
                                    <input type="number" min="0" name="stock" value="0" required class="w-full bg-gray-50 dark:bg-slate-800 border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-2 text-sm text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-500">
                                </div>
                                <div x-data="{ unitMode: 'select' }">
                                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1.5">Unidad</label>
                                    <template x-if="unitMode === 'select'">
                                        <select name="unit_of_measure" x-model="unitOfMeasure" @change="if($event.target.value === 'new') unitMode = 'input'" class="w-full bg-gray-50 dark:bg-slate-800 border border-gray-200 dark:border-gray-700 rounded-lg px-2 py-2 text-sm text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-500">
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
                                            <input type="text" name="new_unit" x-model="unitOfMeasure" required placeholder="Ej: Rollo" class="w-full bg-brand-50 border border-brand-200 rounded-lg px-2 py-2 text-xs focus:outline-none">
                                            <button type="button" @click="unitMode = 'select'" class="text-red-500"><i class="fas fa-times text-xs"></i></button>
                                        </div>
                                    </template>
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1.5">Mínimo</label>
                                    <input type="number" min="0" name="min_stock" value="5" class="w-full bg-gray-50 dark:bg-slate-800 border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-2 text-sm text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-500">
                                </div>
                            </div>
                        </div>

                        <!-- Columna Derecha: Costos, Precio, Fiscal -->
                        <div class="space-y-5 bg-gray-50 dark:bg-slate-800/50 p-5 rounded-xl border border-gray-100 dark:border-gray-700 flex flex-col">
                            <h4 class="text-xs font-bold text-brand-600 dark:text-brand-400 uppercase tracking-widest border-b border-brand-100 dark:border-brand-900/30 pb-2 mb-2">3. Costos y Precios</h4>
                            
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1.5">Tipo de Costo</label>
                                    <select name="cost_type" x-model="costType" class="w-full bg-white dark:bg-slate-900 border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-2 text-sm text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-500">
                                        <option value="unit">Costo Fijo Unitario</option>
                                        <option value="bulk">Costo por Bulto</option>
                                        <option value="paquete">Costo por Paquete</option>
                                        <option value="combo">Costo por Combo</option>
                                        <option value="saco">Costo por Saco</option>
                                        <option value="cajas">Costo por Cajas</option>
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
                            
                            <template x-if="costType === 'unit'">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-bold text-brand-700 dark:text-brand-400 mb-1.5">Costo x Unidad *</label>
                                        <div class="relative">
                                            <span class="absolute left-3 top-2 text-gray-400 font-bold">$</span>
                                            <input type="number" step="0.01" min="0" name="unit_cost" x-model.number="unitCost" @input="calculateFinalPrice" class="w-full bg-white dark:bg-slate-900 border border-gray-200 dark:border-gray-700 rounded-lg pl-8 pr-3 py-2 text-sm text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-500 font-medium">
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <template x-if="costType !== 'unit'">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 bg-brand-50 dark:bg-brand-900/10 p-3 rounded-lg border border-brand-100 dark:border-brand-800/30">
                                    <div>
                                        <label class="block text-xs font-bold text-brand-700 dark:text-brand-400 mb-1.5">Costo del <span x-text="getBulkName"></span> *</label>
                                        <input type="number" step="0.01" min="0" name="bulk_cost" x-model.number="bulkCost" @input="calculateFinalPrice" class="w-full bg-white dark:bg-slate-900 border border-brand-200 dark:border-brand-700/50 rounded-lg px-3 py-2 text-sm text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-500">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-brand-700 dark:text-brand-400 mb-1.5"><span x-text="unitOfMeasure || 'Und.'"></span> por <span x-text="getBulkName"></span> *</label>
                                        <input type="number" min="1" name="units_per_bulk" x-model.number="unitsPerBulk" @input="calculateFinalPrice" class="w-full bg-white dark:bg-slate-900 border border-brand-200 dark:border-brand-700/50 rounded-lg px-3 py-2 text-sm text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-500">
                                    </div>
                                    <div class="col-span-1 sm:col-span-2 text-xs flex justify-between items-center px-1">
                                        <span class="text-gray-500 dark:text-gray-400">Costo Unitario Calculado:</span>
                                        <span class="font-black text-brand-600 dark:text-brand-400">$<span x-text="calculatedBaseCost.toFixed(2)"></span></span>
                                    </div>
                                </div>
                            </template>
                            
                            <hr class="border-gray-200 dark:border-gray-700/50 my-2">

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1.5">% Margen Ganancia</label>
                                    <div class="relative">
                                        <input type="number" step="0.01" min="0" name="profit_margin" x-model.number="profitMargin" @input="calculateFinalPrice" class="w-full bg-white dark:bg-slate-900 border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-2 text-sm text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-500 font-bold">
                                        <span class="absolute right-3 top-2 text-gray-400 font-bold">%</span>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1.5">Precio de Venta</label>
                                    <div class="relative">
                                        <span class="absolute left-3 top-2 text-brand-500 font-bold">$</span>
                                        <input type="number" step="0.01" min="0" name="price" x-model.number="finalPrice" @input="calculateMargin" class="w-full bg-white dark:bg-slate-900 border border-brand-200 dark:border-brand-700 shadow-inner rounded-lg pl-8 pr-3 py-2 text-sm text-xl text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-500 font-black text-brand-700 dark:text-brand-400">
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
        unitOfMeasure: 'Und',
        
        get getBulkName() {
            const map = {
                'bulk': 'Bulto',
                'paquete': 'Paquete',
                'combo': 'Combo',
                'saco': 'Saco',
                'cajas': 'Caja'
            };
            return map[this.costType] || 'Bulto';
        },
        
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

<!-- Search + Actions Bar -->
<div class="flex flex-col sm:flex-row items-center gap-3 mt-4 mb-4">
    <div class="relative flex-1 w-full">
        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
        <input type="text" data-table-search="#inventory-tbody" placeholder="Buscar producto, categoría, marca..." class="w-full bg-white dark:bg-slate-800 border border-gray-200 dark:border-gray-700 rounded-lg pl-10 pr-4 py-2.5 text-sm text-gray-800 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand-500 transition-all shadow-sm">
    </div>
    <div class="flex gap-2 w-full sm:w-auto overflow-x-auto pb-1 sm:pb-0">
        <button onclick="triggerImport()" class="no-print flex-1 sm:flex-none justify-center bg-blue-50 dark:bg-blue-900/10 border border-blue-200 dark:border-blue-800/30 text-blue-600 dark:text-blue-500 px-4 py-2.5 rounded-lg text-sm font-medium hover:bg-blue-100 dark:hover:bg-blue-900/20 transition-colors shadow-sm flex items-center gap-2 whitespace-nowrap">
            <i class="fas fa-file-import"></i> Importar
        </button>
        <a href="<?= BASE_URL ?>inventory/print" target="_blank" class="no-print flex-1 sm:flex-none justify-center bg-white dark:bg-slate-800 border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300 px-4 py-2.5 rounded-lg text-sm font-medium hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors shadow-sm flex items-center gap-2 whitespace-nowrap">
            <i class="fas fa-print"></i> Imprimir
        </a>
        <a href="<?= BASE_URL ?>settings/export_csv" class="no-print flex-1 sm:flex-none justify-center bg-green-50 dark:bg-green-900/10 border border-green-200 dark:border-green-800/30 text-green-600 dark:text-green-500 px-4 py-2.5 rounded-lg text-sm font-medium hover:bg-green-100 dark:hover:bg-green-900/20 transition-colors shadow-sm flex items-center gap-2 whitespace-nowrap">
            <i class="fas fa-file-excel"></i> Excel
        </a>
    </div>
</div>

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
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800" hx-get="<?= BASE_URL ?>inventory/list" hx-trigger="load" id="inventory-tbody">
                <tr><td colspan="5" class="p-12 text-center text-gray-400 dark:text-gray-500"><i class="fas fa-circle-notch fa-spin text-3xl mb-3 block opacity-50"></i>Sincronizando inventario...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Scripts for HTMX listening -->
<script>
    document.body.addEventListener('htmx:afterSwap', function(event) {
        if(event.detail.target.id === 'inventory-tbody') {
            // Re-render barcodes in new rows
            document.querySelectorAll('[data-barcode]').forEach(el => {
                try { JsBarcode(el, el.getAttribute('data-barcode'), { width:1, height:30, displayValue:false, margin:0 }); } catch(e){}
            });
        }
    });
</script>

<!-- Librería para generar Código QR -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

<!-- QR / Barcode Modal -->
<div id="qrModal" class="fixed inset-0 bg-slate-900/60 z-50 hidden flex items-center justify-center backdrop-blur-sm transition-opacity">
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl p-6 w-full max-w-sm m-4 relative border border-gray-100 dark:border-gray-700 animate-fade-in-up">
        <button onclick="closeQrModal()" class="modal-close absolute top-4 right-4">
            <i class="fas fa-times"></i>
        </button>
        <div class="text-center">
            <h3 id="qrTitle" class="text-lg font-black text-gray-800 dark:text-white mb-1">Producto</h3>
            <p id="qrSubtitle" class="text-[10px] uppercase font-bold tracking-widest text-brand-600 dark:text-brand-400 mb-4 bg-brand-50 dark:bg-brand-900/30 inline-block px-3 py-1 rounded-full"></p>
            <!-- Barcode Image -->
            <div id="barcodeContainer" class="flex justify-center mb-3"><svg id="barcodeSvg"></svg></div>
            <!-- QR Image -->
            <div id="qrContainer" class="flex justify-center bg-white p-4 rounded-2xl border border-gray-100 inline-block mx-auto shadow-sm"></div>
            <div class="mt-4 flex justify-center gap-3">
                <button onclick="printLabel()" class="bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold px-4 py-2 rounded-lg transition-colors"><i class="fas fa-print mr-1"></i> Imprimir Etiqueta</button>
            </div>
        </div>
    </div>
</div>

<!-- EDIT PRODUCT MODAL -->
<div id="editModal" class="fixed inset-0 bg-slate-900/60 z-50 hidden overflow-y-auto backdrop-blur-sm" style="display: none;">
    <div class="flex items-start justify-center min-h-screen px-4 pt-10 pb-20 text-center sm:p-0">
        <div class="fixed inset-0" onclick="closeEditModal()"></div>
        <div class="relative inline-block w-full max-w-4xl p-6 md:p-8 overflow-hidden text-left align-middle bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-gray-100 dark:border-gray-800 my-8 animate-fade-in-up"
             x-data="productForm()"
             @load-edit-data.window="
                costType = $event.detail.cost_type || 'unit';
                unitCost = parseFloat($event.detail.unit_cost) || 0;
                bulkCost = parseFloat($event.detail.bulk_cost) || 0;
                unitsPerBulk = parseInt($event.detail.units_per_bulk) || 1;
                profitMargin = parseFloat($event.detail.profit_margin) || 0;
                finalPrice = parseFloat($event.detail.price) || 0;
                unitOfMeasure = $event.detail.unit_of_measure || 'Unidades';
             ">
            <div class="flex justify-between items-center mb-6 border-b border-gray-100 dark:border-gray-800 pb-4">
                <h3 class="text-xl font-black text-gray-800 dark:text-white flex items-center">
                    <div class="w-8 h-8 rounded-lg bg-brand-50 dark:bg-brand-900/30 text-brand-600 dark:text-brand-400 flex items-center justify-center mr-3"><i class="fas fa-edit"></i></div>
                    Editar Producto
                </h3>
                <button onclick="closeEditModal()" class="modal-close"><i class="fas fa-times"></i></button>
            </div>
            
            <form id="editForm" hx-encoding="multipart/form-data" hx-swap="none" class="space-y-6"
                  @htmx:after-request="if($event.detail.successful) { closeEditModal(); Swal.fire({title: '¡Actualizado!', text: 'El producto ha sido modificado correctamente.', icon: 'success', timer: 2000, showConfirmButton: true, confirmButtonText: 'Continuar', confirmButtonColor: '#10b981', customClass: { popup: 'rounded-2xl' }}).then(() => { htmx.ajax('GET', '<?= BASE_URL ?>inventory/list?t=' + new Date().getTime(), {target: '#inventory-tbody'}); }); }">
                <input type="hidden" id="edit-id" name="id">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <!-- Columna Izquierda: Datos Básicos e Inventario -->
                    <div class="space-y-5">
                        <h4 class="text-xs font-bold text-gray-400 uppercase tracking-widest border-b border-gray-100 dark:border-gray-800 pb-2">1. Datos Básicos</h4>
                        
                        <div>
                            <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1.5">Nombre del Producto *</label>
                            <input type="text" name="name" id="edit-name" required class="w-full bg-gray-50 dark:bg-slate-800 border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-2 text-sm text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-500 transition-shadow">
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1.5">Código Barras</label>
                                <input type="text" name="barcode" id="edit-barcode" class="w-full bg-gray-50 dark:bg-slate-800 border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-2 text-sm text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-500">
                            </div>
                            <div x-data="{ catMode: 'select' }">
                                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1.5">Categoría *</label>
                                <template x-if="catMode === 'select'">
                                    <select name="category_id" id="edit-category" required @change="if($event.target.value === 'new') catMode = 'input'" class="w-full bg-gray-50 dark:bg-slate-800 border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-2 text-sm text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-500">
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
                                <select name="brand_id" id="edit-supplier" @change="if($event.target.value === 'new') supplierMode = 'input'" class="w-full bg-gray-50 dark:bg-slate-800 border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-2 text-sm text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-500">
                                    <option value="">Ninguno</option>
                                    <?php if(!empty($brands)): foreach($brands as $b): ?>
                                        <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['name']) ?></option>
                                    <?php endforeach; endif; ?>
                                    <option value="new" class="font-bold text-brand-600 dark:text-brand-400">+ Añadir Nuevo...</option>
                                </select>
                            </template>
                            <template x-if="supplierMode === 'input'">
                                <div class="flex items-center gap-2">
                                    <input type="hidden" name="brand_id" value="new">
                                    <input type="text" name="new_brand" required placeholder="Nombre..." class="w-full bg-brand-50 dark:bg-brand-900/20 border border-brand-200 dark:border-brand-800 rounded-lg px-3 py-2 text-sm text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-500">
                                    <button type="button" @click="supplierMode = 'select'" class="text-gray-400 hover:text-red-500"><i class="fas fa-times"></i></button>
                                </div>
                            </template>
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1.5">Fotografía (Dejar vacío para no cambiar)</label>
                            <div class="relative">
                                <input type="file" name="image" accept="image/*" class="w-full bg-gray-50 dark:bg-slate-800 border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-1.5 text-sm file:mr-4 file:py-1 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100 dark:file:bg-brand-900/30 dark:file:text-brand-400">
                            </div>
                        </div>
                        
                        <h4 class="text-xs font-bold text-gray-400 uppercase tracking-widest border-b border-gray-100 dark:border-gray-800 pb-2 pt-4">2. Inventario</h4>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1.5">Stock</label>
                                <input type="number" min="0" name="stock" id="edit-stock" required class="w-full bg-gray-50 dark:bg-slate-800 border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-2 text-sm text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-500">
                            </div>
                            <div x-data="{ unitMode: 'select' }">
                                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1.5">Unidad</label>
                                <template x-if="unitMode === 'select'">
                                    <select name="unit_of_measure" x-model="unitOfMeasure" id="edit-unit" @change="if($event.target.value === 'new') unitMode = 'input'" class="w-full bg-gray-50 dark:bg-slate-800 border border-gray-200 dark:border-gray-700 rounded-lg px-2 py-2 text-sm text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-500">
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
                                        <input type="text" name="new_unit" x-model="unitOfMeasure" required placeholder="Ej: Rollo" class="w-full bg-brand-50 border border-brand-200 rounded-lg px-2 py-2 text-xs focus:outline-none">
                                        <button type="button" @click="unitMode = 'select'" class="text-red-500"><i class="fas fa-times text-xs"></i></button>
                                    </div>
                                </template>
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1.5">Mínimo</label>
                                <input type="number" min="0" name="min_stock" id="edit-min_stock" class="w-full bg-gray-50 dark:bg-slate-800 border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-2 text-sm text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-500">
                            </div>
                        </div>
                    </div>

                    <!-- Columna Derecha: Costos, Precio, Fiscal -->
                    <div class="space-y-5 bg-gray-50 dark:bg-slate-800/50 p-5 rounded-xl border border-gray-100 dark:border-gray-700 flex flex-col">
                        <h4 class="text-xs font-bold text-brand-600 dark:text-brand-400 uppercase tracking-widest border-b border-brand-100 dark:border-brand-900/30 pb-2 mb-2">3. Costos y Precios</h4>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1.5">Tipo de Costo</label>
                                <select name="cost_type" id="edit-cost_type" x-model="costType" class="w-full bg-white dark:bg-slate-900 border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-2 text-sm text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-500">
                                    <option value="unit">Costo Fijo Unitario</option>
                                    <option value="bulk">Costo por Bulto</option>
                                    <option value="paquete">Costo por Paquete</option>
                                    <option value="combo">Costo por Combo</option>
                                    <option value="saco">Costo por Saco</option>
                                    <option value="cajas">Costo por Cajas</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1.5">Divisa Costo</label>
                                <select name="currency" id="edit-currency" class="w-full bg-white dark:bg-slate-900 border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-2 text-sm text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-500">
                                    <option value="USD">USD ($)</option>
                                    <option value="VES">BS (Bs.)</option>
                                    <option value="EUR">EUR (€)</option>
                                </select>
                            </div>
                        </div>
                        
                        <template x-if="costType === 'unit'">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-bold text-brand-700 dark:text-brand-400 mb-1.5">Costo x Unidad *</label>
                                    <div class="relative">
                                        <span class="absolute left-3 top-2 text-gray-400 font-bold">$</span>
                                        <input type="number" step="0.01" min="0" name="unit_cost" id="edit-unit_cost" x-model.number="unitCost" @input="calculateFinalPrice" class="w-full bg-white dark:bg-slate-900 border border-gray-200 dark:border-gray-700 rounded-lg pl-8 pr-3 py-2 text-sm text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-500 font-medium">
                                    </div>
                                </div>
                            </div>
                        </template>

                        <template x-if="costType !== 'unit'">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 bg-brand-50 dark:bg-brand-900/10 p-3 rounded-lg border border-brand-100 dark:border-brand-800/30">
                                <div>
                                    <label class="block text-xs font-bold text-brand-700 dark:text-brand-400 mb-1.5">Costo del Bulto *</label>
                                    <input type="number" step="0.01" min="0" name="bulk_cost" id="edit-bulk_cost" x-model.number="bulkCost" @input="calculateFinalPrice" class="w-full bg-white dark:bg-slate-900 border border-brand-200 dark:border-brand-700/50 rounded-lg px-3 py-2 text-sm text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-500">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-brand-700 dark:text-brand-400 mb-1.5">Und. por Bulto *</label>
                                    <input type="number" min="1" name="units_per_bulk" id="edit-units_per_bulk" x-model.number="unitsPerBulk" @input="calculateFinalPrice" class="w-full bg-white dark:bg-slate-900 border border-brand-200 dark:border-brand-700/50 rounded-lg px-3 py-2 text-sm text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-500">
                                </div>
                                <div class="col-span-1 sm:col-span-2 text-xs flex justify-between items-center px-1">
                                    <span class="text-gray-500 dark:text-gray-400">Costo Unitario Calculado:</span>
                                    <span class="font-black text-brand-600 dark:text-brand-400">$<span x-text="calculatedBaseCost.toFixed(2)"></span></span>
                                </div>
                            </div>
                        </template>
                        
                        <hr class="border-gray-200 dark:border-gray-700/50 my-2">

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1.5">% Margen Ganancia</label>
                                <div class="relative">
                                    <input type="number" step="0.01" min="0" name="profit_margin" id="edit-profit_margin" x-model.number="profitMargin" @input="calculateFinalPrice" class="w-full bg-white dark:bg-slate-900 border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-2 text-sm text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-500 font-bold">
                                    <span class="absolute right-3 top-2 text-gray-400 font-bold">%</span>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1.5">Precio de Venta</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-2 text-brand-500 font-bold">$</span>
                                    <input type="number" step="0.01" min="0" name="price" id="edit-price" x-model.number="finalPrice" @input="calculateMargin" class="w-full bg-white dark:bg-slate-900 border border-brand-200 dark:border-brand-700 shadow-inner rounded-lg pl-8 pr-3 py-2 text-sm text-xl text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-500 font-black text-brand-700 dark:text-brand-400">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-auto pt-4 flex items-center justify-between">
                            <div>
                                <p class="text-sm font-bold text-gray-700 dark:text-gray-300 mb-0.5">Producto Exento de IVA</p>
                                <p class="text-[10px] text-gray-500 dark:text-gray-400">No aplica el impuesto al momento del cobro</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                              <input type="checkbox" name="is_tax_exempt" id="edit-tax" value="1" class="sr-only peer">
                              <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-brand-500"></div>
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="mt-6 pt-5 border-t border-gray-100 dark:border-gray-800 flex justify-end gap-3">
                    <button type="button" onclick="closeEditModal()" class="px-5 py-2.5 bg-white dark:bg-slate-800 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700 font-medium text-sm transition-colors">Cancelar</button>
                    <button type="submit" class="px-6 py-2.5 bg-gradient-to-r from-brand-600 to-accent-600 text-white rounded-lg font-bold shadow-md shadow-brand-500/20 hover:shadow-lg hover:shadow-brand-500/30 transition-all text-sm flex items-center">
                        <i class="fas fa-save mr-2"></i> Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    let qrcodeContainer = null;
    
    function openQrModal(name, code) {
        document.getElementById('qrModal').classList.remove('hidden');
        document.getElementById('qrTitle').innerText = name;
        document.getElementById('qrSubtitle').innerText = code;
        
        // Render barcode SVG
        try { JsBarcode('#barcodeSvg', code, { width: 2, height: 60, displayValue: true, fontSize: 14, margin: 5, background: '#ffffff' }); } catch(e) {}
        
        const container = document.getElementById('qrContainer');
        container.innerHTML = ''; 
        
        qrcodeContainer = new QRCode(container, {
            text: code, width: 150, height: 150,
            colorDark : "#0f172a", colorLight : "#ffffff",
            correctLevel : QRCode.CorrectLevel.H
        });
    }

    function closeQrModal() {
        document.getElementById('qrModal').classList.add('hidden');
    }

    function printLabel() {
        const modal = document.getElementById('qrModal');
        const content = modal.querySelector('.text-center').innerHTML;
        const win = window.open('', '_blank', 'width=400,height=500');
        win.document.write('<html><head><title>Etiqueta</title><style>body{font-family:Inter,sans-serif;text-align:center;padding:20px} h3{margin:0 0 4px;font-size:16px} p{margin:0 0 10px;font-size:10px;color:#666} svg{max-width:100%} button{display:none} img{max-width:180px}</style></head><body>');
        win.document.write(content);
        win.document.write('</body></html>');
        win.document.close();
        win.print();
    }

    // ===== EDIT PRODUCT =====
    function editProduct(id) {
        fetch('<?= BASE_URL ?>inventory/edit/' + id)
            .then(r => r.json())
            .then(p => {
                document.getElementById('edit-id').value = p.id;
                document.getElementById('edit-name').value = p.name || '';
                document.getElementById('edit-barcode').value = p.barcode || '';
                document.getElementById('edit-stock').value = p.stock || 0;
                document.getElementById('edit-min_stock').value = p.min_stock || 5;
                document.getElementById('edit-category').value = p.category_id || '';
                
                // Nuevos campos
                let suppSelect = document.getElementById('edit-supplier');
                if(suppSelect) suppSelect.value = p.brand_id || '';
                
                let unitSelect = document.getElementById('edit-unit');
                if(unitSelect) {
                    // Check if unit exists in options
                    let exists = false;
                    for (let i = 0; i < unitSelect.options.length; i++) {
                        if (unitSelect.options[i].value === p.unit_of_measure) exists = true;
                    }
                    if(!exists && p.unit_of_measure) {
                        let opt = new Option(p.unit_of_measure, p.unit_of_measure);
                        unitSelect.insertBefore(opt, unitSelect.lastElementChild);
                    }
                    unitSelect.value = p.unit_of_measure || 'Unidades';
                }
                
                let currSelect = document.getElementById('edit-currency');
                if(currSelect) currSelect.value = p.currency || 'USD';
                
                let taxCheck = document.getElementById('edit-tax');
                if(taxCheck) taxCheck.checked = (p.is_tax_exempt == 1);
                
                // Dispatch event to Alpine for calculated fields
                window.dispatchEvent(new CustomEvent('load-edit-data', { detail: p }));
                
                // Set HTMX action URL
                const form = document.getElementById('editForm');
                form.setAttribute('hx-post', '<?= BASE_URL ?>inventory/update/' + p.id);
                htmx.process(form); // Re-process HTMX attributes
                
                document.getElementById('editModal').classList.remove('hidden');
                document.getElementById('editModal').style.display = '';
            })
            .catch(err => alert('Error al cargar producto'));
    }

    function closeEditModal() {
        document.getElementById('editModal').classList.add('hidden');
        document.getElementById('editModal').style.display = 'none';
    }

    // Close on success (EDIT)
    document.body.addEventListener('htmx:afterRequest', function(e) {
        if (e.detail.elt && e.detail.elt.id === 'editForm' && e.detail.successful) {
            closeEditModal();
            Swal.fire({
                title: '¡Actualizado!',
                text: 'El producto ha sido modificado exitosamente.',
                icon: 'success',
                timer: 2000,
                showConfirmButton: true,
                confirmButtonText: 'Continuar',
                confirmButtonColor: '#10b981',
                customClass: { popup: 'rounded-2xl' }
            }).then(() => {
                htmx.ajax('GET', '<?= BASE_URL ?>inventory/list?t=' + new Date().getTime(), {target: '#inventory-tbody'});
            });
        }
    });

    // ===== DELETE PRODUCT =====
    function confirmDeleteProduct(id) {
        Swal.fire({
            title: '¿Eliminar producto?',
            text: "Esta acción no se puede deshacer.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#10b981',
            cancelButtonColor: '#ef4444',
            confirmButtonText: '<i class="fas fa-trash mr-1"></i> Sí, eliminar',
            cancelButtonText: 'Cancelar',
            customClass: {
                popup: 'rounded-2xl',
                confirmButton: 'rounded-lg px-4 py-2 font-bold shadow-md',
                cancelButton: 'rounded-lg px-4 py-2 font-bold'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                deleteProduct(id);
            }
        });
    }

    function deleteProduct(id) {
        fetch('<?= BASE_URL ?>inventory/delete/' + id, { method: 'GET', headers: { 'HX-Request': 'true' }})
            .then(async r => {
                if (!r.ok) {
                    const text = await r.text();
                    if (text.includes('dependency_error')) {
                        Swal.fire({
                            title: 'No se puede eliminar', 
                            text: 'Este producto está asociado a ventas o compras existentes en el historial.', 
                            icon: 'error',
                            confirmButtonColor: '#10b981',
                            customClass: { popup: 'rounded-2xl' }
                        });
                    } else {
                        Swal.fire('Error', 'Ocurrió un error inesperado al eliminar. Contacte soporte.', 'error');
                    }
                    throw new Error('Server returned an error');
                }
                
                Swal.fire({
                    title: '¡Eliminado!',
                    text: 'El producto ha sido eliminado exitosamente.',
                    icon: 'success',
                    timer: 1500,
                    showConfirmButton: false,
                    customClass: { popup: 'rounded-2xl' }
                }).then(() => {
                    htmx.ajax('GET', '<?= BASE_URL ?>inventory/list?t=' + new Date().getTime(), {target: '#inventory-tbody'});
                });
            })
            .catch(err => console.error(err));
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

<!-- SheetJS for Excel/CSV Parsing -->
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
<input type="file" id="bulkUploadInput" class="hidden" accept=".csv, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel" onchange="handleBulkImport(event)">

<script>
function triggerImport() {
    document.getElementById('bulkUploadInput').click();
}

function handleBulkImport(e) {
    const file = e.target.files[0];
    if (!file) return;
    
    Swal.fire({
        title: 'Analizando archivo...',
        text: 'Por favor, espere mientras leemos los datos.',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });
    
    const reader = new FileReader();
    reader.onload = function(e) {
        try {
            const data = new Uint8Array(e.target.result);
            const workbook = XLSX.read(data, {type: 'array'});
            const firstSheetName = workbook.SheetNames[0];
            const worksheet = workbook.Sheets[firstSheetName];
            
            // Expected columns: Producto, Categoria, SKU, CodigoBarras, Precio, Costo, Stock
            const json = XLSX.utils.sheet_to_json(worksheet, {defval: ""});
            
            if (json.length === 0) {
                Swal.fire('Error', 'El archivo parece estar vacío.', 'error');
                return;
            }
            
            // Check if essential columns exist (at least 'Producto')
            if (!json[0].hasOwnProperty('Producto')) {
                Swal.fire('Formato Inválido', 'El archivo debe contener una columna llamada "Producto".', 'error');
                return;
            }
            
            Swal.fire({
                title: 'Confirmar Importación',
                text: `Se encontraron ${json.length} productos listos para procesar. ¿Desea iniciar la carga masiva?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#10b981',
                cancelButtonColor: '#ef4444',
                confirmButtonText: '<i class="fas fa-check mr-1"></i> Iniciar Importación',
                cancelButtonText: 'Cancelar',
                customClass: { popup: 'rounded-2xl' }
            }).then((result) => {
                if (result.isConfirmed) {
                    processBulkImport(json);
                } else {
                    document.getElementById('bulkUploadInput').value = '';
                }
            });
            
        } catch(err) {
            console.error(err);
            Swal.fire('Error', 'No se pudo leer el archivo. Asegurese de que sea un Excel o CSV válido.', 'error');
        }
    };
    reader.readAsArrayBuffer(file);
}

function processBulkImport(dataArray) {
    Swal.fire({
        title: 'Importando Productos',
        text: 'Enviando datos al servidor, esto puede tomar unos momentos...',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });
    
    fetch('<?= BASE_URL ?>inventory/bulk_import', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(dataArray)
    })
    .then(async r => {
        if (!r.ok) throw new Error('Network error');
        return r.json();
    })
    .then(res => {
        document.getElementById('bulkUploadInput').value = '';
        if (res.success) {
            Swal.fire({
                title: 'Importación Completada',
                html: `Se importaron <b class="text-brand-600">${res.imported}</b> productos.<br>` + 
                      (res.errors > 0 ? `<span class="text-red-500">Hubo ${res.errors} errores (omitidos).</span>` : ''),
                icon: 'success',
                timer: 4000,
                customClass: { popup: 'rounded-2xl' }
            }).then(() => {
                htmx.ajax('GET', '<?= BASE_URL ?>inventory/list?t=' + new Date().getTime(), {target: '#inventory-tbody'});
            });
        } else {
            Swal.fire('Error en la importación', res.message || 'Hubo un error del servidor', 'error');
        }
    })
    .catch(err => {
        document.getElementById('bulkUploadInput').value = '';
        Swal.fire('Error', 'Fallo de conexión al importar. ' + err.message, 'error');
    });
}
</script>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>
