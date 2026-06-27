<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Punto de Venta - ERP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        brand: {
                            400: '#60a5fa',
                            500: '#3b82f6',
                            600: '#2563eb'
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50 text-gray-800 dark:bg-gray-900 dark:text-gray-100 font-sans h-screen overflow-hidden flex flex-col md:flex-row selection:bg-brand-500 selection:text-white transition-colors">

    <!-- Global Variables -->
    <script>
        const inventoryProducts = <?= json_encode($products ?? []) ?>;
        const bcvRate = <?= json_encode($bcvRate ?? 622.21) ?>;
        const BASE_URL = '<?= BASE_URL ?>';
        
        // Auto-detect theme for POS since it lacks header.php
        if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    </script>

    <!-- Sidebar / Bottom Nav (Mobile) -->
    <aside class="w-full md:w-16 h-16 md:h-full bg-white dark:bg-gray-800 border-t md:border-t-0 md:border-r border-gray-200 dark:border-gray-700 flex flex-row md:flex-col items-center justify-around md:justify-start md:py-4 z-30 transition-colors shadow-sm order-last md:order-first">
        <a href="<?= BASE_URL ?>dashboard" title="Dashboard">
            <i class="fas fa-boxes text-brand-500 text-2xl md:mb-8 hover:text-brand-400 transition-colors cursor-pointer"></i>
        </a>
        <a href="<?= BASE_URL ?>inventory" class="text-gray-400 hover:text-brand-500 md:mb-6 transition-colors" title="Inventario"><i class="fas fa-box-open text-xl"></i></a>
        <a href="<?= BASE_URL ?>settings" class="text-gray-400 hover:text-brand-500 md:mb-6 transition-colors" title="Configuración"><i class="fas fa-cog text-xl"></i></a>
        <div class="md:mt-auto flex items-center">
            <a href="<?= BASE_URL ?>logout" class="text-gray-400 hover:text-red-500 transition-colors" title="Salir"><i class="fas fa-sign-out-alt text-xl"></i></a>
        </div>
    </aside>

    <!-- Área Central: Catálogo -->
    <main class="flex-1 flex flex-col p-2 md:p-4 bg-gray-50 dark:bg-gray-900 overflow-hidden transition-colors order-1 md:order-2">
        <div class="mb-2 md:mb-4 relative">
            <i class="fas fa-search absolute left-4 top-3.5 text-gray-400"></i>
            <input type="text" id="search-product" class="w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg pl-12 pr-4 py-3 text-gray-800 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition-all font-medium shadow-sm" placeholder="Buscar por código de barras o nombre...">
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
    <aside class="w-full md:w-96 lg:w-[400px] h-2/5 md:h-full bg-white dark:bg-gray-800 flex flex-col shadow-2xl z-20 border-t md:border-t-0 md:border-l border-gray-200 dark:border-gray-700 transition-colors order-2 md:order-3">
        <!-- Tasa BCV Header -->
        <div class="px-3 md:px-5 py-2 md:py-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-850 flex justify-between items-center text-xs md:text-sm font-bold text-gray-600 dark:text-gray-300 shadow-sm">
            <div class="flex items-center gap-2">
                <i class="fas fa-coins text-brand-500"></i>
                <span>Tasa BCV: Bs. <?= number_format($bcvRate ?? 622.21, 2) ?></span>
            </div>
            <span class="text-green-500 flex items-center animate-pulse"><i class="fas fa-circle text-[8px] mr-1"></i></span>
        </div>
        
        <!-- Cart Items -->
        <div class="flex-1 p-2 overflow-y-auto custom-scrollbar bg-white dark:bg-gray-800" id="cart-items">
            <!-- Javascript renders cart items here -->
            <div id="empty-cart" class="text-center text-gray-400 dark:text-gray-500 mt-10 md:mt-20">
                <i class="fas fa-shopping-basket text-3xl md:text-4xl mb-3 md:mb-4 opacity-30"></i>
                <p class="text-xs md:text-sm font-medium">Carrito vacío</p>
            </div>
        </div>

        <!-- Totales (Dark Theme) -->
        <div class="p-3 md:p-5 bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700 shadow-inner">
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
            <div class="flex space-x-2 md:space-x-3">
                <button id="clear-cart" class="w-12 md:w-14 py-2 md:py-3 bg-red-50 dark:bg-gray-800 text-red-500 dark:text-red-400 hover:bg-red-500 hover:text-white rounded-lg transition border border-red-200 dark:border-gray-700 hover:border-transparent flex items-center justify-center shadow-sm" title="Vaciar Carrito">
                    <i class="fas fa-trash"></i>
                </button>
                <button class="w-12 md:w-14 py-2 md:py-3 bg-yellow-50 dark:bg-gray-800 text-yellow-600 dark:text-yellow-500 hover:bg-yellow-500 hover:text-white rounded-lg transition border border-yellow-200 dark:border-gray-700 hover:border-transparent flex items-center justify-center shadow-sm" title="Poner en Espera">
                    <i class="fas fa-pause"></i>
                </button>
                <button id="open-payment-modal" class="flex-1 py-2 md:py-3 bg-brand-600 hover:bg-brand-500 text-white text-sm md:text-base font-bold rounded-lg transition shadow-lg flex justify-center items-center uppercase tracking-wide disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                    Cobrar <i class="fas fa-arrow-right ml-2"></i>
                </button>
            </div>
        </div>
    </aside>

    <!-- Modal de Pago Híbrido (Alpine.js) -->
    <div x-data="{ open: false }" @open-payment.window="open = true" @close-payment.window="open = false" x-show="open" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" style="display: none;" x-transition.opacity>
        <div @click.away="open = false" class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-2xl p-6 w-full max-w-xl mx-auto flex flex-col transform transition-all" x-show="open" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 scale-95 y-4" x-transition:enter-end="opacity-100 scale-100 y-0">
            
            <div class="flex justify-between items-center mb-5 border-b border-gray-100 dark:border-gray-700 pb-4">
                <h3 class="text-xl font-bold text-gray-800 dark:text-white flex items-center"><i class="fas fa-cash-register text-brand-500 mr-3"></i> Procesar Recepción de Pago</h3>
                <button @click="open = false" class="text-gray-400 hover:text-gray-800 dark:hover:text-white transition-colors"><i class="fas fa-times text-xl"></i></button>
            </div>
            
            <div class="mb-5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl p-4">
                <h4 class="text-xs font-bold text-gray-500 uppercase mb-3 tracking-wider">Pagos Recibidos</h4>
                <div id="payments-list" class="space-y-2 max-h-32 overflow-y-auto custom-scrollbar pr-1"></div>
            </div>

            <div class="flex gap-3 mb-6">
                <select id="pay-method" class="w-[45%] text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-800 dark:text-white rounded-lg focus:ring-brand-500 focus:border-brand-500 py-2.5 px-3 outline-none">
                    <option value="usd_cash" data-currency="USD" data-igtf="1">USD Efectivo (Aplica IGTF 3%)</option>
                    <option value="bs_cash" data-currency="VES" data-igtf="0">BS Efectivo</option>
                    <option value="bs_pm" data-currency="VES" data-igtf="0">BS Pago Móvil</option>
                    <option value="bs_pos" data-currency="VES" data-igtf="0">BS Punto Venta</option>
                    <option value="eur_cash" data-currency="EUR" data-igtf="1">EUR Efectivo (Aplica IGTF 3%)</option>
                </select>
                <div class="relative w-[40%]">
                    <input type="number" step="0.01" id="pay-amount" class="w-full text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white rounded-lg focus:ring-brand-500 focus:border-brand-500 py-2.5 pl-3 pr-2 outline-none font-bold" placeholder="0.00">
                </div>
                <button id="add-payment-btn" class="w-[15%] bg-brand-600 hover:bg-brand-500 text-white rounded-lg transition-colors flex items-center justify-center font-bold shadow-md">
                    <i class="fas fa-plus"></i>
                </button>
            </div>
            
            <div class="grid grid-cols-2 gap-4 mb-6">
                <div class="bg-red-500/10 border border-red-500/20 rounded-xl p-4 flex flex-col justify-center">
                    <span class="text-xs text-red-400 font-bold uppercase tracking-wider mb-1">Resta Pagar</span>
                    <span id="modal-remaining" class="font-black text-red-500 text-2xl truncate">$0.00</span>
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

<script>
class POSController {
    constructor(config) {
        this.bcvRate = config.bcvRate || 622.21;
        this.eurRate = config.eurRate || 670.50; // Ejemplo ficticio
        this.ivaRate = config.ivaRate || 0.16;
        this.igtfRate = config.igtfRate || 0.03;
        this.cart = [];
        this.payments = [];
        this.taxChargeMethod = config.taxChargeMethod || 'included'; 
    }

    addProduct(product, qty = 1) {
        const existing = this.cart.find(item => item.id === product.id);
        if (existing) {
            existing.qty += qty;
        } else {
            this.cart.push({
                ...product, 
                qty: qty,
                exempt: product.is_tax_exempt == 1
            });
        }
        this.calculate();
    }

    removeProduct(productId) {
        this.cart = this.cart.filter(item => item.id !== productId);
        this.calculate();
    }
    
    changeQty(productId, delta) {
        const item = this.cart.find(i => i.id === productId);
        if(!item) return;
        item.qty += delta;
        if(item.qty <= 0) {
            this.removeProduct(productId);
        } else {
            this.calculate();
        }
    }

    addPayment(method, methodName, amountLocalCurrency, appliesIgtf, currencyCode) {
        let amountUsd = 0;
        let amountVes = 0;

        if (currencyCode === 'USD') {
            amountUsd = amountLocalCurrency;
            amountVes = amountLocalCurrency * this.bcvRate;
        } else if (currencyCode === 'VES') {
            amountUsd = amountLocalCurrency / this.bcvRate;
            amountVes = amountLocalCurrency;
        } else if (currencyCode === 'EUR') {
            // Conversión aproximada
            const eurToUsd = this.eurRate / this.bcvRate;
            amountUsd = amountLocalCurrency * eurToUsd;
            amountVes = amountLocalCurrency * this.eurRate;
        }
        
        this.payments.push({
            method: method,
            methodName: methodName,
            amount: amountLocalCurrency,
            currency: currencyCode,
            amountUsd: amountUsd,
            amountVes: amountVes,
            appliesIgtf: appliesIgtf
        });
        
        this.calculate();
    }
    
    removePayment(index) {
        this.payments.splice(index, 1);
        this.calculate();
    }
    
    emptyCart() {
        this.cart = [];
        this.payments = [];
        this.calculate();
    }

    calculate() {
        let subtotalItems = 0; 
        let totalIva = 0; 
        
        this.cart.forEach(item => {
            const lineTotal = parseFloat(item.price) * item.qty;
            if (item.exempt) {
                subtotalItems += lineTotal;
            } else {
                if (this.taxChargeMethod === 'included') {
                    const lineBase = lineTotal / (1 + this.ivaRate);
                    subtotalItems += lineBase;
                    totalIva += (lineTotal - lineBase);
                } else {
                    subtotalItems += lineTotal;
                    totalIva += (lineTotal * this.ivaRate);
                }
            }
        });

        const grandTotalWithoutIgtf = subtotalItems + totalIva;

        let totalIgtf = 0;
        let paidUsd = 0;

        this.payments.forEach(payment => {
            paidUsd += payment.amountUsd;
            if (payment.appliesIgtf) {
                totalIgtf += (payment.amountUsd * this.igtfRate);
            }
        });

        const grandTotalUsd = grandTotalWithoutIgtf + totalIgtf;
        const remainingUsd = grandTotalUsd - paidUsd;
        
        this.currentTotals = {
            subtotalUsd: subtotalItems,
            ivaUsd: totalIva,
            igtfUsd: totalIgtf,
            totalUsd: grandTotalUsd,
            totalVes: grandTotalUsd * this.bcvRate,
            paidUsd: paidUsd,
            remainingUsd: remainingUsd > 0 ? remainingUsd : 0,
            changeUsd: remainingUsd < 0 ? Math.abs(remainingUsd) : 0,
            changeVes: remainingUsd < 0 ? (Math.abs(remainingUsd) * this.bcvRate) : 0
        };

        this.render();
    }

    render() {
        const tots = this.currentTotals;
        document.getElementById('pos-subtotal').innerText = `$${tots.subtotalUsd.toFixed(2)}`;
        document.getElementById('pos-iva').innerText = `$${tots.ivaUsd.toFixed(2)}`;
        document.getElementById('pos-igtf').innerText = `$${tots.igtfUsd.toFixed(2)}`;
        document.getElementById('cart-total').innerText = `$${tots.totalUsd.toFixed(2)}`;
        document.getElementById('cart-total-bs').innerHTML = `Bs ${tots.totalVes.toFixed(2)}`;
        
        const openBtn = document.getElementById('open-payment-modal');
        const emptyUI = document.getElementById('empty-cart');
        const cartUI = document.getElementById('cart-items');
        
        if (this.cart.length === 0) {
            openBtn.disabled = true;
            emptyUI.classList.remove('hidden');
            Array.from(cartUI.children).forEach(el => {
                if(el.id !== 'empty-cart') el.remove();
            });
        } else {
            openBtn.disabled = false;
            emptyUI.classList.add('hidden');
            
            // Clean older dynamic items
            Array.from(cartUI.children).forEach(el => {
                if(el.id !== 'empty-cart') el.remove();
            });

            this.cart.forEach(item => {
                const div = document.createElement('div');
                div.className = 'flex flex-col bg-gray-850 border border-gray-700/50 p-3 rounded-xl mb-2';
                div.innerHTML = `
                    <div class="flex justify-between items-start mb-2">
                        <div class="flex-1 pr-2">
                            <h4 class="text-sm font-bold text-gray-200 leading-tight">${item.name}</h4>
                            <span class="text-brand-400 font-bold text-xs mt-1 block">$${parseFloat(item.price).toFixed(2)} ${item.exempt ? '<span class="text-gray-500 font-normal">(E)</span>' : ''}</span>
                        </div>
                        <div class="text-right">
                            <span class="text-white font-bold block">$${(item.price * item.qty).toFixed(2)}</span>
                        </div>
                    </div>
                    <div class="flex justify-between items-center mt-1 border-t border-gray-700/50 pt-2">
                        <div class="flex items-center bg-gray-900 rounded-lg border border-gray-700 overflow-hidden shadow-inner">
                            <button class="w-8 h-8 flex items-center justify-center text-gray-400 hover:text-white hover:bg-gray-700 transition" onclick="window.posState.changeQty(${item.id}, -1)"><i class="fas fa-minus text-xs"></i></button>
                            <span class="w-10 text-center font-bold text-sm text-gray-200">${item.qty}</span>
                            <button class="w-8 h-8 flex items-center justify-center text-gray-400 hover:text-white hover:bg-gray-700 transition" onclick="window.posState.changeQty(${item.id}, 1)"><i class="fas fa-plus text-xs"></i></button>
                        </div>
                        <button class="w-8 h-8 rounded-lg bg-red-500/10 text-red-400 hover:bg-red-500 hover:text-white transition-colors flex items-center justify-center" onclick="window.posState.removeProduct(${item.id})">
                            <i class="fas fa-trash text-xs"></i>
                        </button>
                    </div>
                `;
                cartUI.appendChild(div);
            });
        }
        
        // Modal UI
        const btnConfirm = document.getElementById('btn-confirm-sale');
        if (tots.remainingUsd > 0) {
            document.getElementById('modal-remaining').innerText = `$${tots.remainingUsd.toFixed(2)}`;
            document.getElementById('modal-change').innerText = `$0.00`;
            btnConfirm.disabled = true;
        } else {
            document.getElementById('modal-remaining').innerText = `$0.00`;
            document.getElementById('modal-change').innerText = `$${tots.changeUsd.toFixed(2)} (Bs ${tots.changeVes.toFixed(2)})`;
            btnConfirm.disabled = false;
        }
        
        // Render payments in modal
        const payListUI = document.getElementById('payments-list');
        payListUI.innerHTML = '';
        if(this.payments.length === 0) {
            payListUI.innerHTML = '<div class="text-center text-gray-500 text-xs py-2 italic font-medium">Ningún pago recibido aún.</div>';
        }
        this.payments.forEach((p, idx) => {
            const div = document.createElement('div');
            div.className = "flex justify-between items-center text-sm bg-gray-800 border border-gray-700 p-3 rounded-lg shadow-sm";
            let symbol = p.currency === 'VES' ? 'Bs' : (p.currency === 'USD' ? '$' : '€');
            div.innerHTML = `
                <div class="flex items-center">
                    <div class="w-8 h-8 rounded bg-gray-700 flex items-center justify-center mr-3 text-brand-400">
                        <i class="fas ${p.currency === 'VES' ? 'fa-money-bill' : 'fa-dollar-sign'}"></i>
                    </div>
                    <span class="font-bold text-gray-300">${p.methodName}</span>
                </div>
                <div class="flex items-center gap-4">
                    <span class="font-black text-white text-lg">${symbol}${p.amount.toFixed(2)}</span>
                    <button class="w-7 h-7 rounded-full bg-red-500/10 text-red-500 hover:bg-red-500 hover:text-white transition flex items-center justify-center" onclick="window.posState.removePayment(${idx})">
                        <i class="fas fa-times text-xs"></i>
                    </button>
                </div>
            `;
            payListUI.appendChild(div);
        });
    }
}

document.addEventListener('DOMContentLoaded', () => {
    // Init POS Config
    window.posState = new POSController({
        bcvRate: bcvRate,
        ivaRate: 0.16, 
        igtfRate: 0.03,
        taxChargeMethod: 'included' 
    });

    const grid = document.getElementById('products-grid');
    const search = document.getElementById('search-product');
    const emptyProds = document.getElementById('empty-products');

    function renderProducts(filter = '') {
        grid.innerHTML = '';
        let foundCount = 0;
        const term = filter.toLowerCase();
        
        inventoryProducts.forEach(p => {
            if (p.name.toLowerCase().includes(term) || (p.barcode && p.barcode.toLowerCase().includes(term))) {
                foundCount++;
                const card = document.createElement('div');
                card.className = `bg-gray-850 hover:bg-gray-800 border-2 border-gray-800 hover:border-brand-500 transition-all rounded-xl p-4 cursor-pointer shadow-lg group`;
                card.innerHTML = `
                    <div class="h-24 w-full bg-gray-900 rounded-lg mb-3 flex items-center justify-center border border-gray-700/50 group-hover:border-brand-500/50 transition-colors overflow-hidden">
                        ${p.image ? `<img src="${BASE_URL}../${p.image}" class="h-full object-contain">` : '<i class="fas fa-box text-3xl text-gray-700"></i>'}
                    </div>
                    <div class="font-bold text-gray-200 text-sm mb-2 line-clamp-2 leading-tight" title="${p.name}">${p.name}</div>
                    <div class="flex justify-between items-end mt-auto">
                        <span class="text-brand-400 font-extrabold text-lg tracking-tight">$${parseFloat(p.price).toFixed(2)}</span>
                    </div>
                `;
                card.addEventListener('click', () => window.posState.addProduct(p, 1));
                grid.appendChild(card);
            }
        });

        if (foundCount === 0) {
            emptyProds.classList.remove('hidden');
        } else {
            emptyProds.classList.add('hidden');
        }
    }

    search.addEventListener('input', e => renderProducts(e.target.value));

    document.getElementById('clear-cart').addEventListener('click', () => {
        if(confirm('¿Seguro que deseas vaciar la orden actual?')) {
            window.posState.emptyCart();
        }
    });

    document.getElementById('open-payment-modal').addEventListener('click', () => {
        window.dispatchEvent(new CustomEvent('open-payment'));
    });

    // Payment additions logic
    document.getElementById('add-payment-btn').addEventListener('click', () => {
        const select = document.getElementById('pay-method');
        const inputAmount = document.getElementById('pay-amount');
        const selectedOpt = select.options[select.selectedIndex];
        
        const amount = parseFloat(inputAmount.value);
        if(!amount || amount <= 0) return;
        
        const currencyCode = selectedOpt.dataset.currency;
        const appliesIgtf = selectedOpt.dataset.igtf === '1';
        
        window.posState.addPayment(select.value, selectedOpt.text, amount, appliesIgtf, currencyCode);
        inputAmount.value = '';
    });

    document.getElementById('btn-confirm-sale').addEventListener('click', () => {
        const btnProcess = document.getElementById('btn-confirm-sale');
        btnProcess.disabled = true;
        btnProcess.innerHTML = '<i class="fas fa-circle-notch fa-spin mr-2"></i> Procesando...';

        const payload = {
            items: window.posState.cart,
            totals: window.posState.currentTotals,
            payments: window.posState.payments
        };

        fetch(BASE_URL + 'sales/process', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                window.location.href = BASE_URL + 'sales/receipt/' + data.sale_id;
            } else {
                alert('Error crítico devuelto por servidor: ' + data.message);
                btnProcess.disabled = false;
                btnProcess.innerHTML = '<i class="fas fa-check-circle mr-2"></i> Finalizar Venta';
            }
        })
        .catch(err => {
            alert('Fallo de Red. Verifique la conexión con el servidor ERP.');
            btnProcess.disabled = false;
            btnProcess.innerHTML = '<i class="fas fa-check-circle mr-2"></i> Finalizar Venta';
        });
    });

    renderProducts();
    // Iniciar con cálculo en cero
    window.posState.calculate();
});
</script>
</body>
</html>
