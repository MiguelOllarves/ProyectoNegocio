<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="<?= htmlspecialchars($config['primary_color'] ?? '#C41E3A') ?>">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="csrf-token" content="<?= $_SESSION['csrf_token'] ?? '' ?>">
    <?php 
    $storeName = $config['store_name'] ?? $business['business_name'] ?? 'Tienda'; 
    $storeDesc = $config['hero_subtitle'] ?? 'Los mejores productos de Calidad';
    $storeLogo = !empty($business['logo_base64']) ? BASE_URL . '?serve_logo=1&tenant=' . $business['id'] : BASE_URL . 'icons/icon-512x512.png';
    $storeUrl = BASE_URL . 'tienda/' . ($business['slug'] ?? $business['id']);
    ?>
    <title><?= htmlspecialchars($storeName) ?> — Tienda Online</title>
    <meta name="description" content="<?= htmlspecialchars($storeDesc) ?> — <?= htmlspecialchars($storeName) ?>">
    
    <!-- Open Graph / Social Media Meta Tags -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= htmlspecialchars($storeUrl) ?>">
    <meta property="og:title" content="<?= htmlspecialchars($storeName) ?> — Tienda Online">
    <meta property="og:description" content="<?= htmlspecialchars($storeDesc) ?>">
    <meta property="og:image" content="<?= htmlspecialchars($storeLogo) ?>">
    
    <!-- Twitter Meta Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="<?= htmlspecialchars($storeUrl) ?>">
    <meta name="twitter:title" content="<?= htmlspecialchars($storeName) ?> — Tienda Online">
    <meta name="twitter:description" content="<?= htmlspecialchars($storeDesc) ?>">
    <meta name="twitter:image" content="<?= htmlspecialchars($storeLogo) ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?? "" ?>css/tailwind.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.3/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <style>
        * { font-family: 'Inter', system-ui, sans-serif; -webkit-tap-highlight-color: transparent; }
        body { overscroll-behavior-y: none; }

        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 4px; height: 4px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 99px; }

        /* Category scroll — no scrollbar */
        .category-scroll::-webkit-scrollbar { display: none; }
        .category-scroll { -ms-overflow-style: none; scrollbar-width: none; }

        /* Card hover micro-animation */
        .product-card { transition: transform 0.25s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.25s ease; }
        .product-card:active { transform: scale(0.97); }
        @media (hover: hover) {
            .product-card:hover { transform: translateY(-4px); box-shadow: 0 16px 40px rgba(0,0,0,0.08); }
        }

        /* Add button pulse */
        @keyframes addPulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.25); }
            100% { transform: scale(1); }
        }
        .add-pulse { animation: addPulse 0.35s ease; }

        /* Slide up animation */
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-slide-up { animation: slideUp 0.4s ease forwards; }

        /* Skeleton loading shimmer */
        @keyframes shimmer {
            0% { background-position: -200% 0; }
            100% { background-position: 200% 0; }
        }
        .skeleton {
            background: linear-gradient(90deg, #f3f4f6 25%, #e5e7eb 50%, #f3f4f6 75%);
            background-size: 200% 100%;
            animation: shimmer 1.5s infinite;
        }

        /* Floating button shadow */
        .float-shadow {
            box-shadow: 0 8px 30px rgba(196, 30, 58, 0.35), 0 4px 12px rgba(0,0,0,0.1);
        }

        /* Glass effect */
        .glass { background: rgba(255,255,255,0.85); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); }

        /* Safe area padding for iOS */
        .safe-bottom { padding-bottom: max(1rem, env(safe-area-inset-bottom)); }
    </style>
</head>
<body class="bg-gray-50 min-h-screen" x-data="storeApp()">

    <?php
        $primaryColor = htmlspecialchars($config['primary_color'] ?? '#C41E3A');
        $showPrices = $config['show_prices'] ?? 1;
        $heroSubtitle = htmlspecialchars($config['hero_subtitle'] ?? 'Los mejores productos de Calidad');

        // Extract unique categories from products
        $categories = [];
        foreach ($products as &$p) {
            if (empty($p['category_name']) && !empty($p['is_dish'])) {
                $p['category_name'] = 'Menús / Platos';
            }
            $cat = $p['category_name'] ?? null;
            if ($cat && !in_array($cat, $categories)) {
                $categories[] = $cat;
            }
        }
        unset($p);
        sort($categories);
    ?>

    <!-- ============================= -->
    <!-- HEADER — Sticky Top Bar       -->
    <!-- ============================= -->
    <header class="sticky top-0 z-40 glass border-b border-gray-100 safe-top">
        <div class="px-4 py-3">
            <!-- Row 1: Logo + Cart -->
            <div class="flex items-center justify-between mb-1">
                <!-- Logo & Brand -->
                <div class="flex items-center gap-3">
                    <?php if (!empty($config['logo_url'])): 
                        $logoStr = $config['logo_url'];
                        $isBase64OrHttp = (strpos($logoStr, 'data:image') === 0 || strpos($logoStr, 'http') === 0);
                        $logoSrc = $isBase64OrHttp ? $logoStr : BASE_URL . ltrim($logoStr, '/');
                    ?>
                        <img src="<?= $logoSrc ?>" 
                             alt="<?= htmlspecialchars($storeName) ?>" 
                             class="h-10 w-auto object-contain rounded-lg">
                    <?php else: ?>
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center text-white font-black text-lg shadow-md bg-gradient-to-r from-emerald-600 to-cyan-600">
                            <?= strtoupper(substr($business['business_name'] ?? 'T', 0, 1)) ?>
                        </div>
                    <?php endif; ?>
                    <div class="min-w-0">
                        <h1 class="text-base font-extrabold text-gray-900 leading-tight truncate">
                            <?= htmlspecialchars($config['hero_title'] ?? $storeName) ?>
                        </h1>
                        <p class="text-[11px] text-gray-400 font-medium leading-tight truncate">
                            <?= $heroSubtitle ?>
                        </p>
                    </div>
                </div>

                <!-- Action Buttons: Register & Cart -->
                <div class="flex items-center gap-2">
                    <button @click="registerModalOpen = true" class="text-xs font-bold text-gray-700 bg-gray-100 hover:bg-gray-200 px-3 py-2 rounded-xl transition-colors border border-gray-200 flex items-center gap-1.5">
                        <i class="fas fa-address-card text-brand-600"></i> <span class="hidden sm:inline">Solicitar Crédito</span>
                    </button>
                    <!-- Cart Icon -->
                    <button @click="cartOpen = true" class="relative text-white w-9 h-9 rounded-xl flex items-center justify-center transition-colors shadow bg-gradient-to-r from-emerald-600 to-cyan-600 border-none" id="cart-toggle">
                        <i class="fas fa-shopping-cart text-white text-lg"></i>
                        <span x-show="itemCount > 0" 
                              x-transition.scale
                          class="absolute -top-1.5 -right-1.5 text-white text-[10px] font-black min-w-[20px] h-5 flex items-center justify-center rounded-full shadow-md px-1 bg-red-500" 
                          x-text="itemCount"></span>
                </button>
            </div>
        </div>

        <!-- Row 2: Search Bar -->
        <div class="px-4 pb-3">
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                    <i class="fas fa-search text-gray-400 text-sm"></i>
                </div>
                <input type="text" 
                       x-model="searchQuery" 
                       @input="filterProducts()" 
                       placeholder="Buscar productos..." 
                       class="w-full pl-10 pr-10 py-2.5 bg-gray-100 border border-gray-200 rounded-xl text-sm font-medium text-gray-700 outline-none focus:ring-2 focus:ring-red-200 focus:border-red-300 transition-all placeholder:text-gray-400"
                       id="search-input">
                <button x-show="searchQuery.length > 0" 
                        x-transition.scale
                        @click="searchQuery = ''; filterProducts()" 
                        class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-gray-400 hover:text-gray-600 transition-colors">
                    <i class="fas fa-times-circle text-sm"></i>
                </button>
            </div>
        </div>

        <!-- Row 3: Category Filters -->
        <?php if (!empty($categories)): ?>
        <div class="category-scroll overflow-x-auto flex gap-2 px-4 pb-3">
            <button @click="activeCategory = ''; filterProducts()" 
                    :class="activeCategory === '' ? 'text-white font-bold shadow-md bg-gradient-to-r from-emerald-600 to-cyan-600' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50 font-medium'"
                    class="px-4 py-2 rounded-full text-xs whitespace-nowrap transition-all flex-shrink-0">
                Todos
            </button>
            <?php foreach ($categories as $cat): ?>
            <button @click="activeCategory = '<?= addslashes($cat) ?>'; filterProducts()" 
                    :class="activeCategory === '<?= addslashes($cat) ?>' ? 'text-white font-bold shadow-md bg-gradient-to-r from-emerald-600 to-cyan-600' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50 font-medium'"
                    class="px-4 py-2 rounded-full text-xs whitespace-nowrap transition-all flex-shrink-0">
                <?= htmlspecialchars($cat) ?>
            </button>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </header>


    <!-- ============================= -->
    <!-- MAIN — Product Grid           -->
    <!-- ============================= -->
    <main class="px-4 pt-4 pb-28">
        <!-- Results count -->
        <div class="flex items-center justify-between mb-3">
            <p class="text-xs font-semibold text-gray-400">
                <span x-text="visibleCount"></span> producto<span x-show="visibleCount !== 1">s</span>
            </p>
        </div>

        <!-- Products Grid (2 columns) -->
        <?php if (empty($products)): ?>
            <div class="text-center py-20">
                <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-5">
                    <i class="fas fa-box-open text-3xl text-gray-300"></i>
                </div>
                <h2 class="text-lg font-bold text-gray-400 mb-1">Sin productos disponibles</h2>
                <p class="text-sm text-gray-400">Pronto actualizaremos nuestro catálogo.</p>
            </div>
        <?php else: ?>
            <div id="products-grid" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-3 md:gap-4">
                <?php foreach ($products as $idx => $prod): 
                    $prodCategory = addslashes($prod['category_name'] ?? '');
                    $prodName = htmlspecialchars($prod['name']);
                    $prodNameJS = addslashes($prod['name']);
                    $prodPrice = number_format($prod['price'], 2);
                    $prodImage = '';
                    if(!empty($prod['image'])){
                        $prodImage = (strpos($prod['image'], 'data:image') === 0 || strpos($prod['image'], 'http') === 0) ? $prod['image'] : BASE_URL . ltrim($prod['image'], '/');
                    }
                    $isLowStock = ($prod['stock'] > 0 && $prod['stock'] <= ($prod['min_stock'] ?? 5));
                ?>
                <div class="product-card bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden flex flex-col animate-slide-up" 
                     data-name="<?= strtolower($prodName) ?>" 
                     data-category="<?= $prodCategory ?>"
                     style="animation-delay: <?= min($idx * 50, 400) ?>ms; opacity: 0;">
                    
                    <!-- Product Image -->
                    <div class="aspect-square bg-gray-50 flex items-center justify-center overflow-hidden relative">
                        <?php if ($prodImage): ?>
                            <img src="<?= $prodImage ?>" alt="<?= $prodName ?>" 
                                 class="w-full h-full object-cover transition-transform duration-500 hover:scale-105"
                                 loading="lazy">
                        <?php else: ?>
                            <div class="text-gray-200 flex flex-col items-center gap-1">
                                <i class="fas fa-image text-3xl"></i>
                            </div>
                        <?php endif; ?>

                        <?php if ($isLowStock): ?>
                            <div class="absolute top-2 left-2 text-white text-[9px] font-black uppercase tracking-wider px-2 py-0.5 rounded-md shadow bg-gradient-to-r from-emerald-600 to-cyan-600">
                                ¡Últimos!
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Product Info -->
                    <div class="p-3 flex-1 flex flex-col">
                        <?php if (!empty($prod['category_name'])): ?>
                            <span class="text-[9px] uppercase tracking-widest font-bold mb-1 truncate" style="color: <?= $primaryColor ?>">
                                <?= htmlspecialchars($prod['category_name']) ?>
                            </span>
                        <?php endif; ?>

                        <h3 class="product-name text-[13px] font-bold text-gray-800 leading-snug mb-1 line-clamp-2">
                            <?= $prodName ?>
                        </h3>

                        <p class="text-[11px] text-gray-400 leading-relaxed line-clamp-2 mb-2 flex-1">
                            <?= htmlspecialchars($prod['category_name'] ?? '') ?> de <?= htmlspecialchars($storeName) ?>.
                        </p>

                        <!-- Price + Add Button -->
                        <div class="flex items-center justify-between mt-auto pt-2 border-t border-gray-50">
                            <?php if ($showPrices): ?>
                                <p class="text-lg font-black text-gray-900">
                                    $<?= $prodPrice ?>
                                </p>
                            <?php else: ?>
                                <p class="text-xs font-bold text-gray-400">Consultar</p>
                            <?php endif; ?>

                            <button @click="addToCart({ id: <?= $prod['id'] ?>, name: '<?= $prodNameJS ?>', price: <?= $prod['price'] ?> }, $event)" 
                                    :class="isInCart(<?= $prod['id'] ?>) ? 'bg-green-500' : 'bg-gradient-to-r from-emerald-600 to-cyan-600'"
                                    class="w-9 h-9 rounded-xl text-white flex items-center justify-center transition-all active:scale-90 shadow-md text-sm"
                                    id="add-btn-<?= $prod['id'] ?>">
                                <i :class="isInCart(<?= $prod['id'] ?>) ? 'fas fa-check' : 'fas fa-plus'" class="text-xs"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- No results message -->
            <div x-show="visibleCount === 0" x-transition class="text-center py-16" style="display: none;">
                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-search text-2xl text-gray-300"></i>
                </div>
                <p class="text-sm font-bold text-gray-400">No se encontraron productos</p>
                <p class="text-xs text-gray-400 mt-1">Prueba con otro término de búsqueda</p>
            </div>
        <?php endif; ?>
    </main>


    <!-- ============================= -->
    <!-- FLOATING — "Ver mi pedido"    -->
    <!-- ============================= -->
    <div x-show="itemCount > 0" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="translate-y-full opacity-0" x-transition:enter-end="translate-y-0 opacity-100" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="translate-y-0 opacity-100" x-transition:leave-end="translate-y-full opacity-0" class="fixed bottom-0 left-0 right-0 z-40 px-4 pb-4 safe-bottom" style="display: none;">
        <button @click="cartOpen = true" class="w-full text-white font-bold py-4 rounded-2xl float-shadow transition-all active:scale-[0.98] flex items-center justify-between px-6 bg-gradient-to-r from-emerald-600 to-cyan-600 border-none">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 bg-white/20 rounded-lg flex items-center justify-center">
                    <i class="fas fa-shopping-bag text-sm"></i>
                </div>
                <span class="text-sm font-extrabold">Ver mi pedido (<span x-text="itemCount"></span>)</span>
            </div>
            <div class="text-right flex flex-col items-end leading-tight">
                <span class="text-base font-black" x-text="formatMoney(total)"></span>
                <span class="text-[10px] font-bold opacity-90" x-text="formatMoneyBs(total)"></span>
            </div>
        </button>
    </div>


    <!-- ============================= -->
    <!-- CART SIDEBAR (Slide-over)     -->
    <!-- ============================= -->
    <div x-show="cartOpen" class="fixed inset-0 z-50 flex justify-end" style="display: none;">
        <!-- Backdrop -->
        <div x-show="cartOpen" x-transition.opacity @click="cartOpen = false" class="absolute inset-0 bg-black/40 backdrop-blur-sm"></div>

        <!-- Panel -->
        <div x-show="cartOpen" 
             x-transition:enter="transition ease-out duration-300" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0" 
             x-transition:leave="transition ease-in duration-200" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
             class="relative w-full max-w-md bg-white h-full shadow-2xl flex flex-col">

            <!-- Cart Header -->
            <div class="p-5 border-b border-gray-100 flex items-center justify-between bg-gray-50">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center text-white bg-gradient-to-r from-emerald-600 to-cyan-600">
                        <i class="fas fa-shopping-bag"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-extrabold text-gray-900">Tu Pedido</h2>
                        <p class="text-xs text-gray-400 font-medium"><span x-text="itemCount"></span> artículo(s)</p>
                    </div>
                </div>
                <button @click="cartOpen = false" class="modal-close">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- Cart Items -->
            <div class="flex-1 overflow-y-auto p-5 space-y-3">
                <!-- Empty State -->
                <template x-if="items.length === 0">
                    <div class="text-center py-16 text-gray-400">
                        <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-shopping-basket text-3xl text-gray-300"></i>
                        </div>
                        <p class="font-bold text-base mb-1">Tu carrito está vacío</p>
                        <p class="text-xs text-gray-400">Agrega productos para realizar tu pedido</p>
                    </div>
                </template>

                <!-- Items List -->
                <template x-for="item in items" :key="item.id">
                    <div class="flex items-center gap-3 bg-white p-3.5 rounded-xl border border-gray-100 shadow-sm">
                        <div class="flex-1 min-w-0">
                            <h4 class="font-bold text-sm text-gray-800 leading-tight truncate" x-text="item.name"></h4>
                            <div class="flex items-center gap-2 mt-1">
                                <p class="text-sm font-black" style="color: <?= $primaryColor ?>" x-text="formatMoney(item.price)"></p>
                                <span class="text-[10px] text-gray-400 font-medium" x-show="item.qty > 1" x-text="'× ' + item.qty + ' = ' + formatMoney(item.price * item.qty)"></span>
                            </div>
                        </div>
                        <!-- Quantity Controls -->
                        <div class="flex items-center gap-1 bg-gray-50 rounded-xl p-1 border border-gray-200 flex-shrink-0">
                            <button @click="updateQty(item.id, -1)" class="w-8 h-8 flex items-center justify-center text-gray-500 hover:bg-white hover:shadow-sm rounded-lg transition-all">
                                <i class="fas fa-minus text-[10px]"></i>
                            </button>
                            <span class="w-6 text-center text-sm font-bold text-gray-800" x-text="item.qty"></span>
                            <button @click="updateQty(item.id, 1)" class="w-8 h-8 flex items-center justify-center text-gray-500 hover:bg-white hover:shadow-sm rounded-lg transition-all">
                                <i class="fas fa-plus text-[10px]"></i>
                            </button>
                        </div>
                    </div>
                </template>


            </div>

            <!-- Cart Footer: Total + Checkout -->
            <div class="p-5 bg-gray-50 border-t border-gray-200 safe-bottom">
                <div class="flex justify-between items-center mb-4">
                    <span class="text-gray-500 font-bold text-base">Total a Pagar</span>
                    <div class="text-right">
                        <span class="text-2xl font-black text-gray-900 block leading-none" x-text="formatMoney(total)"></span>
                        <span class="text-xs font-bold text-gray-500" x-text="formatMoneyBs(total)"></span>
                    </div>
                </div>
                <button @click="openCheckout()" 
                        :disabled="items.length === 0" 
                        class="w-full text-white font-bold py-4 rounded-2xl shadow-lg transition-all flex justify-center items-center gap-3 disabled:opacity-50 disabled:shadow-none active:scale-[0.98] bg-gradient-to-r from-emerald-600 to-cyan-600 border-none">
                    <i class="fab fa-whatsapp text-xl"></i> 
                    Enviar Pedido por WhatsApp
                </button>
            </div>
        </div>
    </div>


    <!-- ============================= -->
    <!-- CHECKOUT MODAL                -->
    <!-- ============================= -->
    <div x-show="checkoutModalOpen" class="fixed inset-0 z-[60] flex items-end sm:items-center justify-center p-0 sm:p-4" style="display: none;">
        <div x-show="checkoutModalOpen" x-transition.opacity @click="checkoutModalOpen = false" class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>

        <div x-show="checkoutModalOpen" 
             x-transition:enter="transition ease-out duration-300" x-transition:enter-start="translate-y-full sm:translate-y-0 sm:scale-95 sm:opacity-0" x-transition:enter-end="translate-y-0 sm:scale-100 sm:opacity-100"
             x-transition:leave="transition ease-in duration-200" x-transition:leave-start="translate-y-0 sm:scale-100 sm:opacity-100" x-transition:leave-end="translate-y-full sm:translate-y-0 sm:scale-95 sm:opacity-0"
             class="bg-white rounded-t-3xl sm:rounded-3xl shadow-2xl w-full sm:max-w-lg relative z-10 overflow-hidden flex flex-col max-h-[90vh]">
            
            <!-- Modal Header -->
            <div class="p-5 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                <div>
                    <h3 class="text-lg font-extrabold text-gray-900">Datos de Entrega</h3>
                    <p class="text-xs text-gray-400 font-medium mt-0.5">Completa tus datos para enviar el pedido</p>
                </div>
                <button @click="checkoutModalOpen = false" class="modal-close">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="p-5 overflow-y-auto space-y-4">
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1.5">
                        <i class="fas fa-id-card mr-1 text-gray-400"></i> Cédula de Identidad *
                    </label>
                    <input type="text" x-model="customer.document" @input="customer.document = customer.document.replace(/[^0-9]/g, '')" placeholder="Ej. 12345678" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm outline-none focus:ring-2 focus:ring-red-200 focus:border-red-300 font-medium transition-all">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1.5">
                        <i class="fas fa-user mr-1 text-gray-400"></i> Nombre y Apellido *
                    </label>
                    <input type="text" x-model="customer.name" placeholder="Ej. Juan Pérez" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm outline-none focus:ring-2 focus:ring-red-200 focus:border-red-300 font-medium transition-all">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1.5">
                        <i class="fas fa-phone mr-1 text-gray-400"></i> Teléfono (WhatsApp) *
                    </label>
                    <input type="tel" x-model="customer.phone" @input="customer.phone = customer.phone.replace(/[^0-9]/g, '')" placeholder="Ej. 04141234567" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm outline-none focus:ring-2 focus:ring-red-200 focus:border-red-300 font-medium transition-all">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1.5">
                        <i class="fas fa-map-marker-alt mr-1 text-gray-400"></i> Dirección de Entrega *
                    </label>
                    <textarea x-model="customer.address" rows="2" placeholder="Ej. Av. Principal, Edificio El Sol, Apto 4B" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm outline-none focus:ring-2 focus:ring-red-200 focus:border-red-300 font-medium resize-none transition-all"></textarea>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1.5">
                        <i class="fas fa-sticky-note mr-1 text-gray-400"></i> Notas Adicionales <span class="text-gray-400 font-normal">(Opcional)</span>
                    </label>
                    <textarea x-model="customer.notes" rows="2" placeholder="Alguna indicación especial..." class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm outline-none focus:ring-2 focus:ring-red-200 focus:border-red-300 font-medium resize-none transition-all"></textarea>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1.5">
                        <i class="fas fa-credit-card mr-1 text-gray-400"></i> Método de Pago *
                    </label>
                    <select x-model="customer.paymentMethod" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm outline-none focus:ring-2 focus:ring-red-200 focus:border-red-300 font-medium transition-all appearance-none cursor-pointer">
                        <option value="">Selecciona cómo vas a pagar</option>
                        <?php foreach ($paymentMethods ?? [] as $pm): ?>
                            <option value="<?= htmlspecialchars($pm['name']) ?>"><?= htmlspecialchars($pm['name']) ?></option>
                        <?php endforeach; ?>
                        <option value="Crédito (Fiado)">Crédito (Fiado) - Solo clientes registrados</option>
                    </select>
                </div>

                <!-- Order Summary Mini -->
                <div class="bg-gray-50 rounded-xl p-4 border border-gray-100 mt-2">
                    <div class="flex justify-between items-center">
                        <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Resumen</span>
                        <span class="text-xs text-gray-400" x-text="itemCount + ' artículo(s)'"></span>
                    </div>
                    <div class="flex justify-between items-center mt-2">
                        <span class="text-sm font-bold text-gray-700">Total a Pagar</span>
                        <div class="text-right">
                            <span class="text-xl font-black text-gray-900 block leading-none" x-text="formatMoney(total)"></span>
                            <span class="text-xs font-bold text-gray-500" x-text="formatMoneyBs(total)"></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="p-5 bg-gray-50 border-t border-gray-100 flex gap-3 safe-bottom">
                <button @click="checkoutModalOpen = false" class="flex-1 px-4 py-3.5 rounded-xl font-bold text-gray-600 bg-white border border-gray-200 hover:bg-gray-100 transition-colors text-sm">
                    Cancelar
                </button>
                <button @click="finalizeOrder()" class="flex-1 px-4 py-3.5 rounded-xl font-bold text-white shadow-lg bg-gradient-to-r from-emerald-600 to-cyan-600 border-none transition-all flex justify-center items-center gap-2 text-sm active:scale-[0.98]">
                    <i class="fab fa-whatsapp text-lg"></i> Enviar Pedido
                </button>
            </div>
        </div>
    </div>

    <!-- ============================= -->
    <!-- REGISTER MODAL (Créditos)     -->
    <!-- ============================= -->
    <div x-show="registerModalOpen" class="fixed inset-0 z-[60] flex items-end sm:items-center justify-center p-0 sm:p-4" style="display: none;">
        <div x-show="registerModalOpen" x-transition.opacity @click="registerModalOpen = false" class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>

        <div x-show="registerModalOpen" 
             x-transition:enter="transition ease-out duration-300" x-transition:enter-start="translate-y-full sm:translate-y-0 sm:scale-95 sm:opacity-0" x-transition:enter-end="translate-y-0 sm:scale-100 sm:opacity-100"
             x-transition:leave="transition ease-in duration-200" x-transition:leave-start="translate-y-0 sm:scale-100 sm:opacity-100" x-transition:leave-end="translate-y-full sm:translate-y-0 sm:scale-95 sm:opacity-0"
             class="bg-white rounded-t-3xl sm:rounded-3xl shadow-2xl w-full sm:max-w-lg relative z-10 flex flex-col h-[95vh] sm:h-[85vh] sm:max-h-[800px]">
            
            <!-- Modal Header -->
            <div class="p-5 border-b border-gray-100 flex justify-between items-center bg-gray-50 rounded-t-3xl sm:rounded-t-3xl shrink-0">
                <div>
                    <h3 class="text-lg font-extrabold text-brand-700">Solicitar Crédito</h3>
                    <p class="text-xs text-gray-500 font-medium mt-0.5">Regístrate como cliente de confianza</p>
                </div>
                <button @click="registerModalOpen = false" class="modal-close">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="p-5 overflow-y-auto space-y-4 flex-1">
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1.5"><i class="fas fa-id-card mr-1 text-gray-400"></i> Cédula de Identidad *</label>
                    <input type="text" x-model="regForm.document" @input="regForm.document = regForm.document.replace(/[^0-9]/g, '')" placeholder="Ej. 12345678" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm outline-none focus:ring-2 focus:ring-brand-200 focus:border-brand-300 font-medium transition-all">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1.5"><i class="fas fa-user mr-1 text-gray-400"></i> Nombres y Apellidos *</label>
                    <input type="text" x-model="regForm.name" placeholder="Ej. María Elena Pérez" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm outline-none focus:ring-2 focus:ring-brand-200 focus:border-brand-300 font-medium transition-all">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1.5"><i class="fas fa-envelope mr-1 text-gray-400"></i> Correo Electrónico *</label>
                    <input type="email" x-model="regForm.email" placeholder="Ej. maria@ejemplo.com" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm outline-none focus:ring-2 focus:ring-brand-200 focus:border-brand-300 font-medium transition-all">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1.5"><i class="fas fa-phone mr-1 text-gray-400"></i> Teléfono Móvil *</label>
                    <input type="tel" x-model="regForm.phone" @input="regForm.phone = regForm.phone.replace(/[^0-9]/g, '')" placeholder="Ej. 04141234567" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm outline-none focus:ring-2 focus:ring-brand-200 focus:border-brand-300 font-medium transition-all">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1.5"><i class="fas fa-map-marker-alt mr-1 text-gray-400"></i> Dirección de Residencia *</label>
                    <textarea x-model="regForm.address" rows="2" placeholder="Dirección completa y exacta" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm outline-none focus:ring-2 focus:ring-brand-200 focus:border-brand-300 font-medium resize-none transition-all"></textarea>
                </div>
                
                <hr class="border-gray-100">
                
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1.5"><i class="fas fa-briefcase mr-1 text-gray-400"></i> Lugar de Trabajo *</label>
                    <select x-model="regForm.workplace" @change="showCustomWorkplace = (regForm.workplace === 'new'); if (!showCustomWorkplace) regForm.customWorkplace = '';" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm outline-none focus:ring-2 focus:ring-brand-200 focus:border-brand-300 font-medium transition-all appearance-none cursor-pointer mb-2">
                        <option value="">Selecciona una opción</option>
                        <?php foreach ($workplaces ?? [] as $wp): ?>
                            <option value="<?= htmlspecialchars($wp) ?>"><?= htmlspecialchars($wp) ?></option>
                        <?php endforeach; ?>
                        <option value="new" class="font-bold text-brand-600">+ Añadir Nuevo Lugar de Trabajo</option>
                    </select>

                    <div x-show="showCustomWorkplace" x-transition.opacity>
                        <input type="text" x-model="regForm.customWorkplace" placeholder="Escribe el nombre del nuevo lugar..." class="w-full bg-white border border-brand-300 rounded-xl px-4 py-3 text-sm outline-none focus:ring-2 focus:ring-brand-200 font-medium transition-all">
                    </div>
                </div>
                
                <div x-show="regForm.workplace !== ''" x-transition.opacity class="space-y-4 pt-2">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1.5"><i class="fas fa-shield-alt mr-1 text-gray-400"></i> Departamento / Área *</label>
                        <input type="text" x-model="regForm.workplaceComponent" placeholder="Ej. Ventas, Logística, Administrativo..." class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm outline-none focus:ring-2 focus:ring-brand-200 focus:border-brand-300 font-medium transition-all">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1.5"><i class="fas fa-star mr-1 text-gray-400"></i> Cargo / Grado *</label>
                        <input type="text" x-model="regForm.workplaceDetail" placeholder="Ej. Gerente, Supervisor..." class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm outline-none focus:ring-2 focus:ring-brand-200 focus:border-brand-300 font-medium transition-all">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1.5"><i class="fas fa-building mr-1 text-gray-400"></i> Dirección donde trabaja *</label>
                        <textarea x-model="regForm.workplaceAddress" rows="2" placeholder="Dirección exacta del trabajo" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm outline-none focus:ring-2 focus:ring-brand-200 focus:border-brand-300 font-medium resize-none transition-all"></textarea>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1.5"><i class="fas fa-wallet mr-1 text-gray-400"></i> Ingreso Mensual Estimado ($) *</label>
                    <input type="number" x-model="regForm.monthlyIncome" placeholder="Ej. 300" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm outline-none focus:ring-2 focus:ring-brand-200 focus:border-brand-300 font-medium transition-all">
                </div>
                
                <div class="bg-blue-50 text-blue-800 text-xs p-4 rounded-xl border border-blue-100 flex gap-3 mt-4">
                    <i class="fas fa-info-circle mt-0.5 text-blue-500"></i>
                    <p>Al enviar esta solicitud, tu navegador pedirá permiso para acceder a tu ubicación. <b>Es obligatorio aceptarlo</b> por medidas de seguridad. Tu IP y tipo de dispositivo quedarán registrados.</p>
                </div>
            </div>

            <!-- Footer -->
            <div class="p-5 border-t border-gray-100 bg-gray-50 safe-bottom">
                <button @click="submitRegistration()" class="w-full text-white font-bold py-4 rounded-2xl shadow-lg transition-all flex justify-center items-center gap-3 active:scale-[0.98] bg-gradient-to-r from-emerald-600 to-cyan-600 border-none">
                    <span x-show="!isRegistering">Enviar Solicitud de Crédito</span>
                    <span x-show="isRegistering"><i class="fas fa-spinner fa-spin mr-2"></i> Procesando...</span>
                </button>
            </div>
        </div>
    </div>


    <!-- ============================= -->
    <!-- FOOTER                        -->
    <!-- ============================= -->
    <footer class="text-center py-5 text-gray-400 text-[11px] font-medium border-t border-gray-100 mx-4 mb-16">
        <!-- Social Links -->
        <?php 
        $hasSocial = !empty($config['whatsapp']) || !empty($config['instagram']) || !empty($config['facebook']) || !empty($config['tiktok']) || !empty($config['twitter']);
        if ($hasSocial): ?>
        <div class="flex items-center justify-center gap-3 mb-4">
            <?php if (!empty($config['whatsapp'])): ?>
            <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $config['whatsapp']) ?>" target="_blank" class="w-9 h-9 rounded-full bg-green-500 hover:bg-green-400 text-white flex items-center justify-center text-sm shadow-md hover:-translate-y-0.5 transition-all" title="WhatsApp">
                <i class="fab fa-whatsapp"></i>
            </a>
            <?php endif; ?>
            <?php if (!empty($config['instagram'])): ?>
            <a href="https://instagram.com/<?= ltrim(htmlspecialchars($config['instagram']), '@') ?>" target="_blank" class="w-9 h-9 rounded-full bg-gradient-to-tr from-yellow-400 via-pink-500 to-purple-500 text-white flex items-center justify-center text-sm shadow-md hover:-translate-y-0.5 transition-all" title="Instagram">
                <i class="fab fa-instagram"></i>
            </a>
            <?php endif; ?>
            <?php if (!empty($config['facebook'])): ?>
            <a href="<?= htmlspecialchars($config['facebook']) ?>" target="_blank" class="w-9 h-9 rounded-full bg-blue-600 hover:bg-blue-500 text-white flex items-center justify-center text-sm shadow-md hover:-translate-y-0.5 transition-all" title="Facebook">
                <i class="fab fa-facebook-f"></i>
            </a>
            <?php endif; ?>
            <?php if (!empty($config['tiktok'])): ?>
            <a href="https://tiktok.com/<?= ltrim(htmlspecialchars($config['tiktok']), '@') ?>" target="_blank" class="w-9 h-9 rounded-full bg-black hover:bg-zinc-800 text-white flex items-center justify-center text-sm shadow-md hover:-translate-y-0.5 transition-all" title="TikTok">
                <i class="fab fa-tiktok"></i>
            </a>
            <?php endif; ?>
            <?php if (!empty($config['twitter'])): ?>
            <a href="https://twitter.com/<?= ltrim(htmlspecialchars($config['twitter']), '@') ?>" target="_blank" class="w-9 h-9 rounded-full bg-sky-500 hover:bg-sky-400 text-white flex items-center justify-center text-sm shadow-md hover:-translate-y-0.5 transition-all" title="Twitter / X">
                <i class="fab fa-twitter"></i>
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        <p>&copy; <?= date('Y') ?> <?= htmlspecialchars($storeName) ?> — Proporcionado por <span class="font-bold text-gray-500">Tu Inventario</span></p>
    </footer>


    <!-- ============================= -->
    <!-- COOKIE CONSENT                -->
    <!-- ============================= -->
    <div x-data="cookieConsent()" x-show="visible" 
         x-transition:enter="transition ease-out duration-300" x-transition:enter-start="translate-y-full opacity-0" x-transition:enter-end="translate-y-0 opacity-100"
         x-transition:leave="transition ease-in duration-200" x-transition:leave-start="translate-y-0 opacity-100" x-transition:leave-end="translate-y-full opacity-0"
         class="fixed bottom-0 left-0 right-0 z-[70] p-4 pointer-events-none" style="display: none;">
        <div class="max-w-lg mx-auto bg-white/95 backdrop-blur-xl rounded-2xl shadow-2xl border border-gray-200 p-4 pointer-events-auto">
            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 mb-1">
                        <i class="fas fa-cookie-bite text-amber-500"></i>
                        <h4 class="font-bold text-gray-800 text-xs">Política de Cookies</h4>
                    </div>
                    <p class="text-[11px] text-gray-500 leading-relaxed">
                        Usamos cookies para mejorar tu experiencia y guardar tu carrito de compras.
                    </p>
                </div>
                <div class="flex gap-2 w-full sm:w-auto flex-shrink-0">
                    <button @click="accept('essential')" class="flex-1 sm:flex-initial px-3 py-2 rounded-xl text-[11px] font-bold text-gray-600 bg-gray-100 hover:bg-gray-200 transition-colors whitespace-nowrap">
                        Solo esenciales
                    </button>
                    <button @click="accept('all')" class="flex-1 sm:flex-initial px-3 py-2 rounded-xl text-[11px] font-bold text-white shadow-md transition-all hover:opacity-90 whitespace-nowrap bg-gradient-to-r from-emerald-600 to-cyan-600 border-none">
                        Aceptar todo
                    </button>
                </div>
            </div>
        </div>
    </div>


    <!-- ============================= -->
    <!-- TOAST NOTIFICATION            -->
    <!-- ============================= -->
    <div x-show="toast.show" 
         x-transition:enter="transition ease-out duration-300" x-transition:enter-start="translate-y-4 opacity-0" x-transition:enter-end="translate-y-0 opacity-100"
         x-transition:leave="transition ease-in duration-200" x-transition:leave-start="translate-y-0 opacity-100" x-transition:leave-end="-translate-y-4 opacity-0"
         class="fixed top-20 left-1/2 transform -translate-x-1/2 z-[80] pointer-events-none" style="display: none;">
        <div class="bg-gray-900 text-white px-5 py-3 rounded-2xl shadow-2xl flex items-center gap-3 text-sm font-bold pointer-events-auto">
            <i class="fas fa-check-circle text-green-400"></i>
            <span x-text="toast.message"></span>
        </div>
    </div>


    <!-- ============================= -->
    <!-- JAVASCRIPT — App Logic        -->
    <!-- ============================= -->
    <script>
        function cookieConsent() {
            return {
                visible: false,
                init() {
                    if (!localStorage.getItem('cookie_consent')) {
                        setTimeout(() => { this.visible = true; }, 2000);
                    }
                },
                accept(level) {
                    localStorage.setItem('cookie_consent', level);
                    localStorage.setItem('cookie_consent_date', new Date().toISOString());
                    this.visible = false;
                }
            }
        }

        function storeApp() {
            return {
                cartOpen: false,
                checkoutModalOpen: false,
                searchQuery: '',
                activeCategory: '',
                visibleCount: <?= count($products) ?>,
                items: JSON.parse(localStorage.getItem('store_cart_<?= $business['id'] ?>') || '[]'),
                whatsappNumber: '<?= preg_replace('/[^0-9]/', '', $config['whatsapp'] ?? '') ?>',
                
                bcvRate: <?= (float)($bcvRate ?? 36.5) ?>,
                customer: { document: '', name: '', phone: '', address: '', notes: '', paymentMethod: '' },
                
                registerModalOpen: false,
                isRegistering: false,
                showCustomWorkplace: false,
                regForm: {
                    document: '', name: '', email: '', phone: '', address: '', workplace: '', customWorkplace: '', workplaceComponent: '', workplaceDetail: '', workplaceAddress: '', monthlyIncome: ''
                },
                
                toast: { show: false, message: '' },

                get itemCount() {
                    return this.items.reduce((acc, item) => acc + item.qty, 0);
                },
                get total() {
                    return this.items.reduce((acc, item) => acc + (item.price * item.qty), 0);
                },

                formatMoney(amount) {
                    return '$' + parseFloat(amount).toFixed(2);
                },

                formatMoneyBs(amount) {
                    return 'Bs. ' + parseFloat(amount * this.bcvRate).toFixed(2);
                },

                saveCart() {
                    localStorage.setItem('store_cart_<?= $business['id'] ?>', JSON.stringify(this.items));
                },

                isInCart(productId) {
                    return this.items.some(i => i.id === productId);
                },

                addToCart(product, event) {
                    const existing = this.items.find(i => i.id === product.id);
                    if (existing) {
                        existing.qty++;
                    } else {
                        this.items.push({ ...product, qty: 1 });
                    }
                    this.saveCart();

                    // Micro-animation on button
                    const btn = event?.currentTarget;
                    if (btn) {
                        btn.classList.add('add-pulse');
                        setTimeout(() => btn.classList.remove('add-pulse'), 350);
                    }

                    // Toast notification
                    this.showToast(`${product.name} agregado al carrito`);
                },

                updateQty(id, delta) {
                    const item = this.items.find(i => i.id === id);
                    if (item) {
                        item.qty += delta;
                        if (item.qty <= 0) {
                            this.items = this.items.filter(i => i.id !== id);
                        }
                    }
                    this.saveCart();
                },

                showToast(message) {
                    this.toast.message = message;
                    this.toast.show = true;
                    setTimeout(() => { this.toast.show = false; }, 2000);
                },

                filterProducts() {
                    const query = this.searchQuery.toLowerCase().trim();
                    const category = this.activeCategory;
                    const cards = document.querySelectorAll('.product-card');
                    let visible = 0;

                    cards.forEach(card => {
                        const name = card.dataset.name || '';
                        const cat = card.dataset.category || '';
                        const matchesSearch = !query || name.includes(query);
                        const matchesCategory = !category || cat === category;

                        if (matchesSearch && matchesCategory) {
                            card.style.display = '';
                            visible++;
                        } else {
                            card.style.display = 'none';
                        }
                    });

                    this.visibleCount = visible;
                },

                openCheckout() {
                    if (this.items.length === 0) return;
                    if (!this.whatsappNumber) {
                        alert("Este negocio no tiene un número de WhatsApp configurado para recibir pedidos.");
                        return;
                    }
                    this.cartOpen = false;
                    setTimeout(() => { this.checkoutModalOpen = true; }, 200);
                },

                async submitRegistration() {
                    const missing = [];
                    if (!this.regForm.document) missing.push("Cédula de Identidad");
                    if (!this.regForm.name) missing.push("Nombres y Apellidos");
                    if (!this.regForm.email) missing.push("Correo Electrónico");
                    if (!this.regForm.phone) missing.push("Teléfono Móvil");
                    if (!this.regForm.address) missing.push("Dirección de Residencia");
                    
                    const isNewWorkplace = this.regForm.workplace === 'new';
                    const hasWorkplace = isNewWorkplace ? this.regForm.customWorkplace : this.regForm.workplace;
                    if (!hasWorkplace) missing.push("Lugar de Trabajo");

                    if (!this.regForm.workplaceComponent) missing.push("Departamento / Área");
                    if (!this.regForm.workplaceDetail) missing.push("Cargo / Grado");
                    if (!this.regForm.workplaceAddress) missing.push("Dirección donde trabaja");
                    if (!this.regForm.monthlyIncome) missing.push("Ingreso Mensual Estimado ($)");

                    if (missing.length > 0) {
                        alert("Por favor completa los siguientes campos obligatorios:\n- " + missing.join("\n- "));
                        return;
                    }

                    this.isRegistering = true;
                    let position = null;

                    try {
                        // Solicitar GPS (Opcional, si falla no bloquea la peticion)
                        position = await new Promise((resolve, reject) => {
                            if (navigator.geolocation) {
                                navigator.geolocation.getCurrentPosition(resolve, reject, { timeout: 8000 });
                            } else {
                                reject(new Error("No geolocation"));
                            }
                        });
                    } catch (err) {
                        console.warn("Permiso de GPS denegado o expirado. Se procederá sin ubicación.");
                    }

                    const payload = {
                        business_id: <?= $business['id'] ?>,
                        ...this.regForm,
                        gpsLat: position ? position.coords.latitude : null,
                        gpsLng: position ? position.coords.longitude : null,
                        userAgent: navigator.userAgent
                    };

                    try {
                        let response = await fetch('/tienda/registerClient', {
                            method: 'POST',
                            credentials: 'include',
                            headers: { 
                                'Content-Type': 'application/json',
                                'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                            },
                            body: JSON.stringify(payload)
                        });
                        let data = await response.json();
                        
                        if (data.success) {
                            if (typeof Swal !== 'undefined') {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Solicitud Enviada',
                                    text: 'Tu solicitud de crédito ha sido registrada. Ahora puedes intentar hacer compras usando la opción de Crédito (Fiado).'
                                });
                            } else {
                                alert("Solicitud registrada con éxito.");
                            }
                            this.registerModalOpen = false;
                            this.regForm = { document: '', name: '', email: '', phone: '', address: '', workplace: '', customWorkplace: '', workplaceComponent: '', workplaceDetail: '', workplaceAddress: '', monthlyIncome: '' };
                            this.showCustomWorkplace = false;
                        } else {
                            alert(data.message || "Error al procesar la solicitud.");
                        }
                    } catch (e) {
                        alert("Error de conexión. Intenta de nuevo.");
                    }
                    this.isRegistering = false;
                },

                async finalizeOrder() {
                    if (!this.customer.document.trim() || !this.customer.name.trim() || !this.customer.phone.trim() || !this.customer.address.trim() || !this.customer.paymentMethod) {
                        alert("Por favor, completa los campos obligatorios: Cédula, Nombre, Teléfono, Dirección y Método de Pago.");
                        return;
                    }

                    // 1. Enviar al Backend (Sistema Interno)
                    const payload = {
                        business_id: <?= $business['id'] ?>,
                        customer_document: this.customer.document,
                        customer_name: this.customer.name,
                        customer_phone: this.customer.phone,
                        customer_address: this.customer.address,
                        notes: this.customer.notes,
                        payment_method: this.customer.paymentMethod,
                        total_usd: this.total,
                        total_bs: this.total * this.bcvRate,
                        items: this.items
                    };

                    try {
                        let response = await fetch('/tienda/checkout', {
                            method: 'POST',
                            credentials: 'include',
                            headers: { 
                                'Content-Type': 'application/json',
                                'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                            },
                            body: JSON.stringify(payload)
                        });
                        let data = await response.json();
                        
                        if (data.success) {
                            // 2. Construir mensaje de WhatsApp (Opcional para el cliente)
                            let text = "👋 ¡Hola! Acabo de realizar un pedido web.\n\n";
                            text += "*DATOS DEL CLIENTE:*\n";
                            text += `👤 Nombre: ${this.customer.name}\n`;
                            text += `📞 Teléfono: ${this.customer.phone}\n`;
                            text += `📍 Dirección: ${this.customer.address}\n`;
                            if (this.customer.notes.trim()) text += `📝 Notas: ${this.customer.notes}\n`;
                            text += `💳 Método de Pago: ${this.customer.paymentMethod}\n`;
                            text += "\n*DETALLE DEL PEDIDO:*\n";
                            this.items.forEach(item => {
                                text += `▫️ ${item.qty}x ${item.name} (${this.formatMoney(item.price)}) = ${this.formatMoney(item.qty * item.price)}\n`;
                            });
                            text += `\n*TOTAL A PAGAR: ${this.formatMoney(this.total)} (Aprox ${this.formatMoneyBs(this.total)})*\n\n`;

                            let phone = this.whatsappNumber;
                            if (phone.startsWith('0')) phone = '58' + phone.substring(1);
                            else if (phone.length === 10) phone = '58' + phone;

                            let isMobileOptions = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
                            const url = isMobileOptions 
                                ? `https://api.whatsapp.com/send?phone=${phone}&text=${encodeURIComponent(text)}`
                                : `https://web.whatsapp.com/send?phone=${phone}&text=${encodeURIComponent(text)}`;
                            
                            // 3. Limpiar carrito
                            this.items = [];
                            this.saveCart();
                            this.checkoutModalOpen = false;

                            // 4. Mostrar mensaje de éxito en UI
                            if (typeof Swal !== 'undefined') {
                                Swal.fire({
                                    icon: 'success',
                                    title: '¡Pedido enviado con éxito!',
                                    text: 'Serás redirigido a WhatsApp para enviar los detalles de tu pedido.',
                                    confirmButtonText: 'Continuar',
                                    confirmButtonColor: '#25D366',
                                    allowOutsideClick: false
                                }).then((result) => {
                                    window.open(url, '_blank');
                                });
                            } else {
                                alert("¡Pedido enviado con éxito! Presiona OK para continuar a WhatsApp.");
                                window.open(url, '_blank');
                            }
                        } else {
                            if (typeof Swal !== 'undefined') {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Atención',
                                    text: data.message || "Hubo un error al procesar tu pedido."
                                });
                            } else {
                                alert(data.message || "Hubo un error al procesar tu pedido.");
                            }
                        }
                    } catch (e) {
                        alert("Error de red. Verifica tu conexión e intenta nuevamente.");
                    }
                }
            }
        }
    </script>

    <!-- Protección Anticopia/Anti-Inspección -->
    <script>
        document.addEventListener('contextmenu', event => event.preventDefault());
        document.addEventListener('keydown', event => {
            if (event.key === 'F12' || 
               (event.ctrlKey && event.shiftKey && (event.key === 'I' || event.key === 'C' || event.key === 'J')) || 
               (event.ctrlKey && event.key === 'U')) {
                event.preventDefault();
                return false;
            }
        });
    </script>
</body>
</html>
