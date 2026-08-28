<!-- Sidebar for Desktop & Mobile -->
<aside id="sidebar" class="gradient-sidebar text-white w-64 flex-shrink-0 flex flex-col h-full transition-transform duration-300 transform -translate-x-full lg:translate-x-0 fixed lg:relative z-40 shadow-xl">
    <!-- Brand -->
    <div class="h-[68px] px-4 flex items-center justify-between border-b border-white/10 shrink-0">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-white/10 backdrop-blur rounded-xl flex items-center justify-center shadow-inner p-1">
                <img src="<?= BASE_URL ?>?serve_logo=1" alt="Logo" class="w-full h-full object-contain">
            </div>
            <div class="flex flex-col -mt-0.5">
                <h2 class="text-[17px] font-extrabold tracking-tight leading-none mb-0.5"><span class="text-white">Tu</span> <span class="bg-gradient-to-r from-brand-300 to-brand-500 bg-clip-text text-transparent">Inventario</span></h2>
                <span class="text-[9px] font-bold text-white/50 uppercase tracking-[0.2em] leading-none">Control Total</span>
            </div>
        </div>
        <button id="close-sidebar" class="lg:hidden text-white/50 hover:text-white transition-colors">
            <i class="fas fa-times text-lg"></i>
        </button>
    </div>
    
    <!-- Navigation (Scrollable) -->
    <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto sidebar-scroll">
        <?php
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $category = $_SESSION['business_category'] ?? 'general';
        
        $inventoryLabel = 'Inventario';
        $inventoryIcon = 'fa-boxes';
        $salesLabel = 'Punto de Venta';
        $salesIcon = 'fa-shopping-cart';

        if ($category === 'bienes_raices') {
            $inventoryLabel = 'Inmuebles';
            $inventoryIcon = 'fa-building';
            $salesLabel = 'Ventas / Contratos';
            $salesIcon = 'fa-file-signature';
        } elseif ($category === 'vehiculos') {
            $inventoryLabel = 'Vehículos';
            $inventoryIcon = 'fa-car';
        } elseif ($category === 'gastronomia') {
            $inventoryLabel = 'Platos e Insumos';
            $inventoryIcon = 'fa-utensils';
            $salesLabel = 'Caja Registradora';
            $salesIcon = 'fa-cash-register';
        } elseif ($category === 'repuestos') {
            $inventoryIcon = 'fa-cogs';
        }

        $menuItems = [
            ['dashboard',  'fa-tachometer-alt', 'Panel de Control',  null],
            ['inventory',  $inventoryIcon,      $inventoryLabel,     'inventory'],
        ];
        // Módulo de Platos para gastronomía y general
        if ($category === 'gastronomia' || $category === 'general') {
            $menuItems[] = ['restaurant', 'fa-utensils', 'Platos', 'inventory'];
        }
        $menuItems = array_merge($menuItems, [
            ['sales',      $salesIcon,          $salesLabel,         'pos'],
            ['purchases',  'fa-cart-arrow-down', 'Compras',          'inventory'],
            ['suppliers',  'fa-truck',           'Proveedores',      'inventory'],
            ['clients',    'fa-users',           'Clientes',         'clients'],
            ['credits',    'fa-hand-holding-usd','Créditos',         'pos'],
            ['expenses',   'fa-money-bill-wave', 'Gastos',           'reports'],
            ['cashbox',    'fa-wallet',          'Arqueo de Caja',   'pos'],
            ['reports',    'fa-chart-line',      'Reportes',         'reports'],
        ]);
        
        $userRole = $_SESSION['role'] ?? 'vendedor';
        $userPerms = $_SESSION['permissions'] ?? [];
        $isAdmin = ($userRole === 'administrador' || $userRole === 'super_admin');
        
        foreach ($menuItems as [$route, $icon, $label, $requiredPerm]):
            // Si requiere un permiso específico y no es admin, verificamos
            if ($requiredPerm && !$isAdmin && !in_array($requiredPerm, $userPerms)) continue;
            
            $isActive = strpos($uri, $route) !== false || ($route === 'dashboard' && rtrim($uri, '/') === rtrim(BASE_URL, '/'));
            $activeClass = $isActive 
                ? 'bg-white/15 border-l-[3px] border-brand-300 text-white shadow-sm' 
                : 'border-l-[3px] border-transparent text-white/70 hover:bg-white/10 hover:text-white';
        ?>
        <a href="<?= BASE_URL ?><?= $route ?>" class="flex items-center px-3 py-2.5 rounded-lg transition-all text-sm font-medium <?= $activeClass ?>">
            <i class="fas <?= $icon ?> w-5 text-center mr-3 text-sm"></i>
            <span><?= $label ?></span>
        </a>
        <?php endforeach; ?>

        <!-- Divider -->
        <div class="border-t border-white/10 my-3"></div>
        
        <?php if($isAdmin || in_array('inventory', $userPerms)): ?>
        <!-- Kardex (acceso directo solicitado) -->
        <a href="<?= BASE_URL ?>reports?tab=kardex" class="flex items-center px-3 py-2.5 rounded-lg transition-all text-sm font-medium border-l-[3px] border-transparent text-white/70 hover:bg-white/10 hover:text-white">
            <i class="fas fa-exchange-alt w-5 text-center mr-3 text-sm"></i>
            <span>Kardex</span>
        </a>
        <?php endif; ?>

        <?php if($isAdmin || in_array('reports', $userPerms)): ?>
        <!-- Auditoría -->
        <a href="<?= BASE_URL ?>reports/auditoria" class="flex items-center px-3 py-2.5 rounded-lg transition-all text-sm font-medium <?= strpos($uri, 'auditoria') !== false ? 'bg-white/15 border-l-[3px] border-blue-300 text-white shadow-sm' : 'border-l-[3px] border-transparent text-white/70 hover:bg-white/10 hover:text-white' ?>">
            <i class="fas fa-history w-5 text-center mr-3 text-sm"></i>
            <span>Auditoría</span>
        </a>
        <?php endif; ?>

        <?php if($isAdmin || in_array('settings', $userPerms)): ?>
        <a href="<?= BASE_URL ?>storefront" class="flex items-center px-3 py-2.5 rounded-lg transition-all text-sm font-medium <?= $uri === '/storefront' || $uri === '/storefront/' ? 'bg-white/15 border-l-[3px] border-purple-300 text-white shadow-sm' : 'border-l-[3px] border-transparent text-white/70 hover:bg-white/10 hover:text-white' ?>">
            <i class="fas fa-store w-5 text-center mr-3 text-sm"></i>
            <span>Mi Tienda Pública</span>
        </a>
        <a href="<?= BASE_URL ?>storefront/orders" class="flex items-center px-3 py-2.5 rounded-lg transition-all text-sm font-medium <?= strpos($uri, 'storefront/orders') !== false ? 'bg-white/15 border-l-[3px] border-yellow-300 text-white shadow-sm' : 'border-l-[3px] border-transparent text-white/70 hover:bg-white/10 hover:text-white' ?>">
            <i class="fas fa-shopping-bag w-5 text-center mr-3 text-sm"></i>
            <span>Pedidos Tienda</span>
        </a>
        <a href="<?= BASE_URL ?>settings" class="flex items-center px-3 py-2.5 rounded-lg transition-all text-sm font-medium <?= strpos($uri, 'settings') !== false ? 'bg-white/15 border-l-[3px] border-brand-300 text-white shadow-sm' : 'border-l-[3px] border-transparent text-white/70 hover:bg-white/10 hover:text-white' ?>">
            <i class="fas fa-cog w-5 text-center mr-3 text-sm"></i>
            <span>Configuración</span>
        </a>
        <?php endif; ?>
        <?php if($userRole === 'administrador' || $userRole === 'super_admin'): ?>
        <!-- Suscripción -->
        <a href="<?= BASE_URL ?>suscription" class="flex items-center px-3 py-2.5 rounded-lg transition-all text-sm font-medium <?= strpos($uri, 'suscription') !== false ? 'bg-white/15 border-l-[3px] border-emerald-300 text-white shadow-sm' : 'border-l-[3px] border-transparent text-white/70 hover:bg-white/10 hover:text-white' ?>">
            <i class="fas fa-gem w-5 text-center mr-3 text-sm"></i>
            <span>Suscripción Paga</span>
        </a>
        <?php endif; ?>

        <?php if($userRole === 'super_admin'): ?>
        <!-- Panel de Desarrollador / Super Admin -->
        <a href="<?= BASE_URL ?>superadmin" class="flex items-center px-3 py-2.5 rounded-lg transition-all text-sm font-medium <?= strpos($uri, 'superadmin') !== false ? 'bg-gradient-to-r from-amber-600/30 to-amber-500/20 border-l-[3px] border-amber-400 text-white shadow-sm' : 'border-l-[3px] border-transparent text-amber-500/70 hover:bg-amber-600/20 hover:text-amber-400' ?>">
            <i class="fas fa-crown w-5 text-center mr-3 text-sm shadow-amber-500"></i>
            <span class="font-bold text-amber-400 tracking-wide">SysAdmin</span>
        </a>
        <?php endif; ?>
    </nav>

    <!-- Footer del sidebar -->
    <a href="<?= BASE_URL ?>users" class="block p-4 border-t border-white/10 hover:bg-white/5 transition-colors group">
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 bg-gradient-to-br from-brand-400 to-accent-400 rounded-lg flex items-center justify-center text-white font-bold text-xs shadow-sm group-hover:scale-105 transition-transform">
                <?= strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1)) ?>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-bold truncate group-hover:text-brand-300 transition-colors"><?= htmlspecialchars($_SESSION['username'] ?? 'Usuario') ?></p>
                <p class="text-[10px] text-white/40 uppercase font-bold tracking-wider"><?= $_SESSION['role'] ?? 'vendedor' ?></p>
            </div>
            <i class="fas fa-cog text-white/30 group-hover:text-white/70 text-xs transition-colors"></i>
        </div>
    </a>
</aside>
