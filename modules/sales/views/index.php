<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Punto de Venta</title>
    <link rel="stylesheet" href="<?= BASE_URL ?? "" ?>css/tailwind.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" crossorigin="anonymous" referrerpolicy="no-referrer">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <meta name="csrf-token" content="<?= $_SESSION['csrf_token'] ?? '' ?>">
    
</head>
<body class="bg-gray-50 text-gray-800 dark:bg-gray-900 dark:text-gray-100 font-sans overflow-hidden flex flex-col md:flex-row selection:bg-brand-500 selection:text-white transition-colors fixed inset-0" x-data="{ mobileCartOpen: false }">

    <!-- Global Variables -->
    <script>
        const inventoryProducts = <?= json_encode($products ?? []) ?>;
        const bcvRate = <?= json_encode($bcvRate ?? 622.21) ?>;
        const eurRate = <?= json_encode($eurRate ?? 670.50) ?>;
        const BASE_URL = '<?= BASE_URL ?>';
        
        if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }

        // Global CSRF interceptor for standalone POS page
        window.csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        const _origFetch = window.fetch;
        window.fetch = function() {
            let [resource, config] = arguments;
            if(config === undefined) config = {};
            if(config.method && ['POST', 'PUT', 'DELETE'].includes(config.method.toUpperCase())) {
                config.headers = { ...config.headers, 'X-CSRF-Token': window.csrfToken };
            }
            return _origFetch(resource, config);
        };
    </script>

    <!-- Sidebar / Bottom Nav (Mobile) -->
    <aside class="w-full md:w-16 h-16 md:h-full bg-white dark:bg-gray-800 border-t md:border-t-0 md:border-r border-gray-200 dark:border-gray-700 flex flex-row md:flex-col items-center justify-around md:justify-start md:py-4 z-40 transition-colors shadow-sm order-last md:order-first relative">
        <a href="<?= BASE_URL ?>dashboard" title="Dashboard">
            <i class="fas fa-boxes text-brand-500 text-2xl md:mb-8 hover:text-brand-400 transition-colors cursor-pointer"></i>
        </a>
        <a href="<?= BASE_URL ?>sales/history" class="text-gray-400 hover:text-brand-500 md:mb-6 transition-colors" title="Historial y Anulaciones"><i class="fas fa-history text-xl md:text-xl"></i></a>
        <a href="<?= BASE_URL ?>inventory" class="text-gray-400 hover:text-brand-500 md:mb-6 transition-colors" title="Inventario"><i class="fas fa-box-open text-xl md:text-xl"></i></a>
        <a href="<?= BASE_URL ?>settings" class="text-gray-400 hover:text-brand-500 md:mb-6 transition-colors" title="Configuración"><i class="fas fa-cog text-xl"></i></a>
        <div class="md:mt-auto flex items-center">
            <a href="<?= BASE_URL ?>logout" class="text-gray-400 hover:text-red-500 transition-colors md:p-2" title="Salir" onclick="return confirm('¿Seguro que deseas salir del Punto de Venta y cerrar sesión?');">
                <i class="fas fa-sign-out-alt text-xl"></i>
            </a>
        </div>
    </aside>

    <!-- Área Central: Catálogo -->
    <main class="flex-1 flex flex-col p-2 md:p-4 bg-gray-50 dark:bg-gray-900 overflow-hidden transition-colors order-1 md:order-2">
        <div class="mb-2 md:mb-4 flex items-center gap-2">
            <div class="relative flex-1">
                <i class="fas fa-search absolute left-4 top-3.5 text-gray-400"></i>
                <input type="text" id="search-product" class="w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg pl-12 pr-4 py-3 text-gray-800 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition-all font-medium shadow-sm" placeholder="Buscar producto...">
            </div>
            <!-- Mobile Cart Button Trigger -->
            <button @click="mobileCartOpen = true" class="md:hidden bg-brand-500 text-white w-12 h-12 rounded-xl shadow-md flex items-center justify-center shrink-0 relative">
                <i class="fas fa-shopping-cart text-lg"></i>
                <span id="mobile-cart-badge" class="absolute -top-1.5 -right-1.5 bg-red-500 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full border-2 border-brand-500 shadow-sm">0</span>
            </button>
        </div>
        
        <div class="flex-1 overflow-y-auto pr-1 md:pr-2 custom-scrollbar">
            <div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-2 md:gap-4" id="products-grid">
                <!-- Javascript renders products here -->
            </div>
            <div id="empty-products" class="hidden text-center text-gray-400 dark:text-gray-500 mt-10 md:mt-20">
                <i class="fas fa-search text-3xl md:text-4xl mb-3 md:mb-4 opacity-50"></i>
                <p class="text-sm md:text-base">No se encontraron productos</p>
            </div>
        </div>
    </main>

    <!-- Panel Derecho: Carrito y Totales -->
    <aside :class="mobileCartOpen ? 'translate-y-0 block' : 'translate-y-full md:translate-y-0 hidden md:flex'" 
           class="fixed inset-0 md:relative md:inset-auto md:w-96 lg:w-[400px] md:h-full bg-white dark:bg-gray-800 flex flex-col shadow-2xl z-[100] md:z-20 border-none md:border-l border-gray-200 dark:border-gray-700 transition-transform duration-300 md:transition-none md:order-3 pt-safe">
        
        <!-- Mobile Cart Header -->
        <div class="md:hidden flex justify-between items-center px-4 py-3 border-b border-gray-100 dark:border-gray-700 bg-white dark:bg-gray-800 shrink-0">
            <h3 class="font-bold text-gray-800 dark:text-white flex items-center">
                <i class="fas fa-shopping-cart text-brand-500 mr-2"></i> Mi Carrito
            </h3>
            <button @click="mobileCartOpen = false" class="modal-close">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Tasa BCV Header -->
        <div class="px-3 md:px-5 py-2 md:py-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-850 justify-between items-center text-xs md:text-sm font-bold text-gray-600 dark:text-gray-300 shadow-sm hidden md:flex">
            <div class="flex items-center gap-2">
                <i class="fas fa-coins text-brand-500"></i>
                <span>Tasa BCV: Bs. <?= number_format($bcvRate ?? 622.21, 2) ?></span>
            </div>
            <span class="text-green-500 flex items-center animate-pulse"><i class="fas fa-circle text-[8px] mr-1"></i></span>
        </div>
        
        <!-- Cart Items -->
        <div class="flex-1 p-2 overflow-y-auto custom-scrollbar bg-white dark:bg-gray-800 relative pb-[50px] md:pb-2" id="cart-items">
            <!-- Javascript renders cart items here -->
            <div id="empty-cart" class="text-center text-gray-400 dark:text-gray-500 mt-10 md:mt-20">
                <i class="fas fa-shopping-basket text-3xl md:text-4xl mb-3 md:mb-4 opacity-30"></i>
                <p class="text-xs md:text-sm font-medium">Carrito vacío</p>
            </div>
        </div>

        <!-- Totales (Dark Theme) -->
        <div class="p-3 md:p-5 bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700 shadow-inner shrink-0">
            <div class="flex justify-between text-gray-500 dark:text-gray-400 mb-1 text-xs md:text-sm font-medium">
                <span>Subtotal (Base)</span> <span id="pos-subtotal" class="text-gray-800 dark:text-gray-200">$0.00</span>
            </div>
            <div class="flex justify-between text-gray-500 dark:text-gray-400 mb-1 text-xs md:text-sm font-medium">
                <span>IVA (16%)</span> <span id="pos-iva" class="text-gray-800 dark:text-gray-200">$0.00</span>
            </div>
            <div class="flex justify-between text-yellow-600 dark:text-yellow-500 font-bold mb-2 md:mb-4 text-xs md:text-sm bg-yellow-100 dark:bg-yellow-500/10 px-2 py-1 -mx-2 rounded">
                <span><i class="fas fa-bolt mr-1"></i> IGTF (3%)</span> <span id="pos-igtf">$0.00</span>
            </div>
            
            <div class="flex justify-between items-end border-t border-gray-200 dark:border-gray-700 pt-2 md:pt-3 mb-3 md:mb-5">
                <span class="text-gray-600 dark:text-gray-300 text-sm md:text-lg font-bold uppercase tracking-wider">Total</span>
                <div class="text-right">
                    <div class="text-2xl md:text-4xl font-black text-gray-800 dark:text-white leading-none" id="cart-total">$0.00</div>
                    <div class="text-xs md:text-sm font-bold text-brand-600 dark:text-brand-400 mt-1" id="cart-total-bs">Bs 0.00</div>
                </div>
            </div>

            <!-- Footer Action Buttons -->
            <div class="flex space-x-2 md:space-x-3 pb-safe">
                <button id="clear-cart" class="w-14 py-4 md:py-3 bg-red-50 dark:bg-gray-800 text-red-500 dark:text-red-400 hover:bg-red-500 hover:text-white rounded-lg transition border border-red-200 dark:border-gray-700 hover:border-transparent flex items-center justify-center shadow-sm" title="Vaciar Carrito">
                    <i class="fas fa-trash"></i>
                </button>
                <button id="open-payment-modal" class="flex-1 py-4 md:py-3 bg-brand-600 hover:bg-brand-500 text-white text-lg md:text-base font-bold rounded-lg transition shadow-lg flex justify-center items-center uppercase tracking-wide disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                    Cobrar <i class="fas fa-arrow-right ml-2"></i>
                </button>
            </div>
        </div>
    </aside>

    <!-- Modal de Pago Híbrido (Alpine.js) -->
    <div x-data="{ open: false }" @open-payment.window="open = true" @close-payment.window="open = false" x-show="open" class="fixed inset-0 z-[110] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" style="display: none;" x-transition.opacity>
        <div @click.away="open = false" class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-2xl p-6 w-full max-w-xl mx-auto flex flex-col transform transition-all" x-show="open" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 scale-95 y-4" x-transition:enter-end="opacity-100 scale-100 y-0">
            
            <div class="flex justify-between items-center mb-5 border-b border-gray-100 dark:border-gray-700 pb-4">
                <h3 class="text-xl font-bold text-gray-800 dark:text-white flex items-center"><i class="fas fa-cash-register text-brand-500 mr-3"></i> Procesar Recepción de Pago</h3>
                <button @click="open = false" class="modal-close"><i class="fas fa-times"></i></button>
            </div>
            
            <div class="mb-5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl p-4">
                <h4 class="text-xs font-bold text-gray-500 uppercase mb-3 tracking-wider">Pagos Recibidos</h4>
                <div id="payments-list" class="space-y-2 max-h-32 overflow-y-auto custom-scrollbar pr-1"></div>
            </div>

            <div class="flex flex-col sm:flex-row gap-2 sm:gap-3 mb-6">
                <select id="pay-method" class="w-full sm:w-[45%] text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-800 dark:text-white rounded-lg focus:ring-brand-500 focus:border-brand-500 py-2.5 px-3 outline-none">
                    <option value="usd_cash" data-currency="USD" data-igtf="1">USD Efectivo (IGTF 3%)</option>
                    <option value="bs_cash" data-currency="VES" data-igtf="0">BS Efectivo</option>
                    <option value="bs_pm" data-currency="VES" data-igtf="0">BS Pago Móvil</option>
                    <option value="bs_pos" data-currency="VES" data-igtf="0">BS Punto Venta</option>
                    <option value="eur_cash" data-currency="EUR" data-igtf="1">EUR Efectivo (IGTF 3%)</option>
                </select>
                <div class="flex gap-2 w-full sm:w-[55%]">
                    <div class="relative flex-1">
                        <input type="number" step="0.01" id="pay-amount" class="w-full text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white rounded-lg focus:ring-brand-500 focus:border-brand-500 py-2.5 pl-3 pr-2 outline-none font-bold" placeholder="0.00">
                    </div>
                    <button id="add-payment-btn" class="w-12 shrink-0 bg-brand-600 hover:bg-brand-500 text-white rounded-lg transition-colors flex items-center justify-center font-bold shadow-md">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
            </div>
            
            <div class="grid grid-cols-2 gap-4 mb-6">
                <div class="bg-red-500/10 border border-red-500/20 rounded-xl p-4 flex flex-col justify-center">
                    <span class="text-xs text-red-400 font-bold uppercase tracking-wider mb-1">Resta Pagar</span>
                    <span id="modal-remaining" class="font-black text-red-500 text-2xl truncate">$0.00</span>
                    <span id="modal-remaining-bs" class="text-xs font-bold text-red-400 mt-1">Bs 0.00</span>
                </div>
                <div class="bg-green-500/10 border border-green-500/20 rounded-xl p-4 flex flex-col justify-center text-right">
                    <span class="text-xs text-green-400 font-bold uppercase tracking-wider mb-1">Cambio a dar</span>
                    <span id="modal-change" class="font-black text-green-500 text-2xl truncate">$0.00</span>
                </div>
            </div>

            <div class="flex space-x-3 pt-4 border-t border-gray-700">
                <button @click="open = false" class="w-1/3 py-3 bg-gray-700 hover:bg-gray-600 text-white rounded-lg font-bold transition-colors">
                    Cancelar
                </button>
                <button id="btn-confirm-sale" class="flex-1 py-3 bg-green-600 hover:bg-green-500 text-white rounded-lg font-bold shadow-lg transition-colors flex justify-center items-center disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                    <i class="fas fa-check-circle mr-2"></i> Finalizar Venta
                </button>
            </div>
        </div>
    </div>

    <!-- Modal Peso / Volumen -->
    <div x-data="{ 
            openWeight: false, 
            product: null, 
            isEdit: false,
            inputQty: '', 
            inputUnit: 'g', 
            totalStr: '0.00',
            saleFactor: 1,
            initModal(p, editMode) {
                this.product = p;
                this.isEdit = editMode;
                this.inputUnit = p.base_unit_abbr || (p.measurement_type === 'peso' ? 'g' : 'ml');
                this.inputQty = editMode ? (window.posState.cart.find(i => i.id === p.id)?.qty || '') : '';
                // Si es modo edicion y lo pasamos, la cart.qty está en unidad de venta.
                // Para simplificar, en modo edicion mostramos la cantidad de venta en el inputUnit de venta.
                if (editMode && this.product.sale_unit_abbr) {
                    this.inputUnit = this.product.sale_unit_abbr;
                }
                this.saleFactor = p.sale_unit_factor || 1;
                this.calc();
                this.openWeight = true;
                setTimeout(() => document.getElementById('weight-input').focus(), 100);
            },
            setQuick(val, unit) {
                this.inputQty = val;
                this.inputUnit = unit;
                this.calc();
            },
            calc() {
                if(!this.product) return;
                let val = parseFloat(this.inputQty) || 0;
                let qtyInSaleUnit = 0;
                
                if (this.inputUnit.toLowerCase() === (this.product.base_unit_abbr || '').toLowerCase() && this.saleFactor > 0) {
                    qtyInSaleUnit = val / this.saleFactor;
                } else {
                    qtyInSaleUnit = val;
                }
                this.totalStr = (qtyInSaleUnit * parseFloat(this.product.price)).toFixed(2);
            },
            confirm() {
                let val = parseFloat(this.inputQty) || 0;
                if(val <= 0) return;
                
                let qtyInSaleUnit = 0;
                if (this.inputUnit.toLowerCase() === (this.product.base_unit_abbr || '').toLowerCase() && this.saleFactor > 0) {
                    qtyInSaleUnit = val / this.saleFactor;
                } else {
                    qtyInSaleUnit = val;
                }
                
                if (this.isEdit) {
                    window.posState.setQty(this.product.id, qtyInSaleUnit);
                } else {
                    window.posState.addProduct(this.product, qtyInSaleUnit);
                }
                this.openWeight = false;
            }
        }" 
        @open-weight-modal.window="initModal($event.detail.product, $event.detail.existingItem)" 
        x-show="openWeight" class="fixed inset-0 z-[110] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" style="display: none;" x-transition>
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 w-full max-w-sm mx-auto shadow-2xl border border-gray-100 dark:border-gray-700" @click.away="openWeight = false">
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-1" x-text="product?.name"></h3>
            <p class="text-brand-500 font-bold mb-4 text-sm">Precio: $<span x-text="parseFloat(product?.price || 0).toFixed(2)"></span> / <span x-text="product?.sale_unit_abbr || 'und'"></span></p>
            
            <div class="flex gap-2 mb-4">
                <input id="weight-input" type="number" step="any" x-model="inputQty" @input="calc" class="flex-1 text-xl bg-gray-50 dark:bg-gray-900 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white rounded-lg focus:ring-brand-500 focus:border-brand-500 p-3 outline-none font-bold text-center" placeholder="0">
                <select x-model="inputUnit" @change="calc" class="w-24 bg-gray-50 dark:bg-gray-900 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white rounded-lg focus:ring-brand-500 focus:border-brand-500 p-3 font-bold cursor-pointer">
                    <template x-if="product?.base_unit_abbr">
                        <option :value="product.base_unit_abbr" x-text="product.base_unit_abbr"></option>
                    </template>
                    <template x-if="product?.sale_unit_abbr">
                        <option :value="product.sale_unit_abbr" x-text="product.sale_unit_abbr"></option>
                    </template>
                </select>
            </div>
            
            <div class="grid grid-cols-4 gap-2 mb-6" x-show="product?.measurement_type === 'peso'">
                <button type="button" @click="setQuick(100, product?.base_unit_abbr)" class="py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg text-sm font-bold transition">100g</button>
                <button type="button" @click="setQuick(250, product?.base_unit_abbr)" class="py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg text-sm font-bold transition">250g</button>
                <button type="button" @click="setQuick(500, product?.base_unit_abbr)" class="py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg text-sm font-bold transition">500g</button>
                <button type="button" @click="setQuick(1, product?.sale_unit_abbr)" class="py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg text-sm font-bold transition">1kg</button>
            </div>
            
            <div class="bg-brand-50 dark:bg-brand-900/20 rounded-lg p-4 mb-6 flex justify-between items-center border border-brand-100 dark:border-brand-800">
                <span class="text-brand-800 dark:text-brand-300 font-bold">Subtotal:</span>
                <span class="text-2xl font-black text-brand-600 dark:text-brand-400">$<span x-text="totalStr"></span></span>
            </div>
            
            <div class="flex space-x-3">
                <button @click="openWeight = false" class="w-1/3 py-3 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-800 dark:text-white rounded-lg font-bold transition-colors">
                    Cancelar
                </button>
                <button @click="confirm" class="flex-1 py-3 bg-brand-600 hover:bg-brand-500 text-white rounded-lg font-bold shadow-lg transition-colors flex justify-center items-center">
                    <i class="fas fa-check mr-2"></i> Confirmar
                </button>
            </div>
        </div>
    </div>

    <!-- Modal de Venta Exitosa (Alpine.js) -->
    <div x-data="{ openSuccess: false, saleId: null }" @sale-success.window="openSuccess = true; saleId = $event.detail.sale_id; $dispatch('close-payment');" x-show="openSuccess" class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" style="display: none;" x-transition>
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 w-full max-w-sm mx-auto text-center shadow-2xl border border-gray-100 dark:border-gray-700">
            <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 dark:bg-green-900/30 mb-5">
                <i class="fas fa-check text-2xl text-green-600 dark:text-green-400"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">¡Venta Exitosa!</h3>
            <p class="text-sm text-gray-500 mb-6">El pago ha sido procesado y el inventario actualizado.</p>
            
            <div class="flex flex-col gap-3">
                <a :href="`<?= BASE_URL ?>sales/receipt/${saleId}`" target="_blank" class="w-full py-3 bg-brand-600 hover:bg-brand-500 text-white rounded-lg font-bold shadow-md transition flex justify-center items-center">
                    <i class="fas fa-print mr-2"></i> Imprimir Recibo
                </a>
                <button @click="openSuccess = false" class="w-full py-3 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-800 dark:text-white rounded-lg font-bold transition">
                    Nueva Venta
                </button>
            </div>
        </div>
    </div>

<style>
    .custom-scrollbar::-webkit-scrollbar {
        width: 6px;
    }
    .custom-scrollbar::-webkit-scrollbar-track {
        background: rgba(31, 41, 55, 0.5); 
    }
    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: rgba(75, 85, 99, 0.8); 
        border-radius: 10px;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background: rgba(107, 114, 128, 1); 
    }
</style>
<script src="<?= BASE_URL ?>js/pos.js?v=1"></script>
</body>
</html>
