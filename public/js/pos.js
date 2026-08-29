class POSController {
    constructor(config) {
        this.bcvRate = config.bcvRate || 622.21;
        this.eurRate = config.eurRate || 670.50; 
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
        // avoid floating point precision issues
        item.qty = parseFloat((item.qty + delta).toFixed(3));
        if(item.qty <= 0) {
            this.removeProduct(productId);
        } else {
            this.calculate();
        }
    }

    setQty(productId, value) {
        const item = this.cart.find(i => i.id === productId);
        if(!item) return;
        const val = parseFloat(value);
        if (isNaN(val) || val <= 0) {
            this.removeProduct(productId);
        } else {
            item.qty = val;
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

        window.dispatchEvent(new CustomEvent('pos-calculated'));
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
                const isFractional = item.allow_fractional_sales == 1;
                const step = isFractional ? '0.01' : '1';
                const delta = isFractional ? 0.1 : 1;
                const unitAbbr = item.sale_unit_abbr || '';
                
                const div = document.createElement('div');
                div.className = 'flex flex-col bg-gray-50 dark:bg-gray-850 border border-gray-200 dark:border-gray-700/50 p-3 rounded-xl mb-2';
                div.innerHTML = `
                    <div class="flex justify-between items-start mb-2">
                        <div class="flex-1 pr-2">
                            <h4 class="text-sm font-bold text-gray-800 dark:text-gray-200 leading-tight">${item.name}</h4>
                            <span class="text-brand-500 dark:text-brand-400 font-bold text-xs mt-1 block">$${parseFloat(item.price).toFixed(2)} / ${unitAbbr} ${item.exempt ? '<span class="text-gray-400 dark:text-gray-500 font-normal">(E)</span>' : ''}</span>
                        </div>
                        <div class="text-right">
                            <span class="text-gray-900 dark:text-white font-bold block">$${(item.price * item.qty).toFixed(2)}</span>
                            <span class="text-xs text-brand-600 dark:text-brand-400 font-bold block mt-0.5">Bs ${((item.price * item.qty) * this.bcvRate).toFixed(2)}</span>
                        </div>
                    </div>
                    <div class="flex justify-between items-center mt-1 border-t border-gray-200 dark:border-gray-700/50 pt-2">
                        <div class="flex items-center bg-gray-100 dark:bg-gray-900 rounded-lg border border-gray-300 dark:border-gray-700 overflow-hidden shadow-inner">
                            <button class="w-8 h-8 flex items-center justify-center text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-white hover:bg-gray-200 dark:hover:bg-gray-700 transition" onclick="window.posState.changeQty(${item.id}, -${delta})"><i class="fas fa-minus text-xs"></i></button>
                            <input type="number" step="${step}" min="0" value="${item.qty}" class="w-20 text-center font-bold text-sm text-gray-800 dark:text-gray-200 bg-transparent border-none focus:ring-0 p-0" onchange="window.posState.setQty(${item.id}, this.value)">
                            <button class="w-8 h-8 flex items-center justify-center text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-white hover:bg-gray-200 dark:hover:bg-gray-700 transition" onclick="window.posState.changeQty(${item.id}, ${delta})"><i class="fas fa-plus text-xs"></i></button>
                        </div>
                        <button class="w-8 h-8 rounded-lg bg-red-100 dark:bg-red-500/10 text-red-500 dark:text-red-400 hover:bg-red-500 hover:text-white transition-colors flex items-center justify-center" onclick="window.posState.removeProduct(${item.id})">
                            <i class="fas fa-trash text-xs"></i>
                        </button>
                    </div>
                `;
                cartUI.appendChild(div);
            });
        }
        
        // Modal UI
        const btnConfirm = document.getElementById('btn-confirm-sale');
        if (tots.remainingUsd > 0.005) {
            document.getElementById('modal-remaining').innerText = `$${tots.remainingUsd.toFixed(2)}`;
            document.getElementById('modal-remaining-bs').innerText = `Bs ${(tots.remainingUsd * this.bcvRate).toFixed(2)}`;
            document.getElementById('modal-change').innerHTML = `$0.00<br><span class="text-xs">Bs 0.00</span>`;
            btnConfirm.disabled = true;
        } else {
            document.getElementById('modal-remaining').innerText = `$0.00`;
            document.getElementById('modal-remaining-bs').innerText = ``;
            document.getElementById('modal-change').innerHTML = `$${tots.changeUsd.toFixed(2)}<br><span class="text-xs">Bs ${tots.changeVes.toFixed(2)}</span>`;
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
            div.className = "flex justify-between items-center text-sm bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-3 rounded-lg shadow-sm";
            let symbol = p.currency === 'VES' ? 'Bs' : (p.currency === 'USD' ? '$' : '€');
            div.innerHTML = `
                <div class="flex items-center">
                    <div class="w-8 h-8 rounded bg-gray-200 dark:bg-gray-700 flex items-center justify-center mr-3 text-brand-600 dark:text-brand-400">
                        <i class="fas ${p.currency === 'VES' ? 'fa-money-bill' : 'fa-dollar-sign'}"></i>
                    </div>
                    <span class="font-bold text-gray-800 dark:text-gray-300">${p.methodName}</span>
                </div>
                <div class="flex items-center gap-4">
                    <span class="font-black text-gray-900 dark:text-white text-lg">${symbol}${p.amount.toFixed(2)}</span>
                    <button class="w-7 h-7 rounded-full bg-red-100 dark:bg-red-500/10 text-red-500 hover:bg-red-500 hover:text-white transition flex items-center justify-center" onclick="window.posState.removePayment(${idx})">
                        <i class="fas fa-times text-xs"></i>
                    </button>
                </div>
            `;
            payListUI.appendChild(div);
        });
        
        // Update Mobile Cart Badge
        const mobileBadge = document.getElementById('mobile-cart-badge');
        if(mobileBadge) {
            const totalItems = this.cart.reduce((acc, item) => acc + item.qty, 0);
            mobileBadge.innerText = totalItems;
            if(totalItems > 0) {
                mobileBadge.classList.remove('hidden');
                // Auto open mobile cart on first item addition if not already open
                if(totalItems === 1 && this.cart.length === 1 && typeof Alpine !== 'undefined') {
                    // It's handled manually via explicit button click to avoid annoying pop-up
                }
            } else {
                mobileBadge.classList.add('hidden');
                // Close cart automatically if emptied on mobile
                const mainBody = document.querySelector('[x-data]');
                if(mainBody && mainBody.__x && mainBody.__x.$data) {
                    mainBody.__x.$data.mobileCartOpen = false;
                }
            }
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    // Init POS Config
    window.posState = new POSController({
        bcvRate: bcvRate,
        eurRate: eurRate,
        ivaRate: 0.16, 
        igtfRate: 0.03,
        taxChargeMethod: 'included' 
    });

    const grid = document.getElementById('products-grid');
    // Change grid to smaller cards
    grid.className = 'grid grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-2 md:gap-3';
    
    const search = document.getElementById('search-product');
    const emptyProds = document.getElementById('empty-products');

    let activeCategory = '';
    
    inventoryProducts.forEach(p => {
        if (!p.category_name && p.is_dish == 1) {
            p.category_name = 'Menús / Platos';
        }
    });
    
    const categories = [...new Set(inventoryProducts.map(p => p.category_name).filter(Boolean))].sort();
    const searchDiv = document.querySelector('.mb-2.md\\:mb-4.flex');
    const catScroll = document.createElement('div');
    catScroll.className = 'flex overflow-x-auto gap-2 pb-2 mb-2 custom-scrollbar';
    
    function renderCategoryButtons() {
        catScroll.innerHTML = `<button data-cat="" class="cat-btn px-3 py-1.5 rounded-full text-xs whitespace-nowrap transition-all flex-shrink-0 ${activeCategory === '' ? 'text-white font-bold bg-brand-500 shadow-md' : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700'}">Todos</button>`;
        categories.forEach(c => {
            catScroll.innerHTML += `<button data-cat="${c}" class="cat-btn px-3 py-1.5 rounded-full text-xs whitespace-nowrap transition-all flex-shrink-0 ${activeCategory === c ? 'text-white font-bold bg-brand-500 shadow-md' : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700'}">${c}</button>`;
        });
        catScroll.querySelectorAll('.cat-btn').forEach(btn => {
            btn.onclick = () => {
                activeCategory = btn.dataset.cat;
                renderCategoryButtons();
                renderProducts(search.value);
            };
        });
    }
    
    if (categories.length > 0) {
        searchDiv.parentNode.insertBefore(catScroll, searchDiv.nextSibling);
        renderCategoryButtons();
    }

    function renderProducts(filter = '') {
        grid.innerHTML = '';
        let foundCount = 0;
        const term = filter.toLowerCase();
        
        inventoryProducts.forEach(p => {
            const matchesCat = activeCategory === '' || p.category_name === activeCategory;
            if (matchesCat && (p.name.toLowerCase().includes(term) || (p.barcode && p.barcode.toLowerCase().includes(term)))) {
                foundCount++;
                const card = document.createElement('div');
                card.className = `bg-white dark:bg-gray-850 hover:bg-gray-50 dark:hover:bg-gray-800 border-2 border-gray-100 dark:border-gray-800 hover:border-brand-500 dark:hover:border-brand-500 transition-all rounded-xl p-2 cursor-pointer shadow-sm dark:shadow-none group flex flex-col`;
                card.innerHTML = `
                    <div class="aspect-square bg-gray-50 dark:bg-gray-800 rounded-lg mb-2 flex items-center justify-center overflow-hidden w-full relative">
                        ${p.image ? `<img src="${(p.image === 'base64') ? BASE_URL + 'inventory/image?id=' + p.id : ((p.image.startsWith('data:image') || p.image.startsWith('http')) ? p.image : BASE_URL + '../' + p.image)}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" alt="${p.name}">` 
                        : `<i class="fas fa-image text-gray-300 dark:text-gray-600 text-2xl"></i>`}
                    </div>
                    <div class="flex-1 flex flex-col justify-between">
                        ${p.category_name ? `<span class="text-[9px] uppercase tracking-widest font-bold mb-1 text-brand-500 truncate">${p.category_name}</span>` : ''}
                        <h3 class="font-bold text-gray-800 dark:text-gray-200 leading-tight line-clamp-2 text-[11px] sm:text-xs mb-1">${p.name}</h3>
                        <div>
                            <p class="text-gray-900 dark:text-gray-100 font-bold text-xs sm:text-sm">$${parseFloat(p.price).toFixed(2)} <span class="text-[9px] sm:text-[10px] text-gray-500 font-normal">/ ${p.sale_unit_abbr || 'und'}</span></p>
                        </div>
                    </div>
                `;
                card.onclick = () => {
                    const editMode = window.posState.cart.some(i => i.id === p.id);
                    if (p.measurement_type === 'peso' || p.measurement_type === 'volumen') {
                        window.dispatchEvent(new CustomEvent('open-weight-modal', { detail: { product: p, existingItem: editMode } }));
                    } else {
                        window.posState.addProduct(p, 1);
                        if (typeof Alpine !== 'undefined') {
                            const notifEvent = new CustomEvent('notify', { detail: { message: `${p.name} añadido al carrito`, type: 'success' } });
                            window.dispatchEvent(notifEvent);
                        }
                    }
                };
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
    const selectMethod = document.getElementById('pay-method');
    const inputAmount = document.getElementById('pay-amount');

    // Auto-fill logic when selecting a payment method
    function autoFillRemaining() {
        const remainingUsd = window.posState.currentTotals?.remainingUsd || 0;
        if (remainingUsd <= 0) {
            inputAmount.value = '0.00';
            return;
        }

        const selectedOpt = selectMethod.options[selectMethod.selectedIndex];
        const currencyCode = selectedOpt.dataset.currency;
        const appliesIgtf = selectedOpt.dataset.igtf === '1';

        // Calculation: If applies IGTF, we literally just convert remaining * rate. No, wait, if applies IGTF, the igtf adds, so the amount we need to pay is smaller than remaining or remaining is everything? 
        // We'll just convert remainingUsd natively. If IGTF applies later it is added. But remainingUsd ALREADY includes IGTF from PREVIOUS payments. Wait, remaining is total - paid. If the total increases because we add an IGTF payment, it's a loop.
        // For simplicity, we just fill the direct conversion of remainingUsd.
        let val = 0;
        if (currencyCode === 'USD') val = remainingUsd;
        else if (currencyCode === 'VES') val = remainingUsd * window.posState.bcvRate;
        else if (currencyCode === 'EUR') val = remainingUsd * (window.posState.bcvRate / window.posState.eurRate); // Approx
        
        inputAmount.value = val.toFixed(2);
    }

    selectMethod.addEventListener('change', autoFillRemaining);
    // Also bind it to an event we can trigger when totals change
    window.addEventListener('pos-calculated', autoFillRemaining);

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

        if (!navigator.onLine) {
            // OFFLINE MODE: Save to Dexie instead of throwing network error
            if (window.offlineSync) {
                window.offlineSync.savePendingSale(payload);
                window.posState.emptyCart();
                // Optionally dispatch success to close modal if there's any
                window.dispatchEvent(new CustomEvent('sale-success', { detail: { sale_id: 'offline' } }));
            } else {
                alert('La librería offline no está cargada.');
            }
            btnProcess.disabled = false;
            btnProcess.innerHTML = '<i class="fas fa-check-circle mr-2"></i> Finalizar Venta';
            return;
        }

        fetch(BASE_URL + 'sales/process', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                window.dispatchEvent(new CustomEvent('sale-success', { detail: { sale_id: data.sale_id } }));
                window.posState.emptyCart();
                btnProcess.disabled = false;
                btnProcess.innerHTML = '<i class="fas fa-check-circle mr-2"></i> Finalizar Venta';
            } else {
                alert('Error crítico devuelto por servidor: ' + data.message);
                btnProcess.disabled = false;
                btnProcess.innerHTML = '<i class="fas fa-check-circle mr-2"></i> Finalizar Venta';
            }
        })
        .catch(err => {
            alert('Fallo de Red. Verifique la conexión con el servidor.');
            btnProcess.disabled = false;
            btnProcess.innerHTML = '<i class="fas fa-check-circle mr-2"></i> Finalizar Venta';
        });
    });

    renderProducts();
    // Iniciar con cálculo en cero
    window.posState.calculate();
});
