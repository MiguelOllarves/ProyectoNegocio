<!-- Sidebar for Desktop & Mobile -->
<aside id="sidebar" class="gradient-sidebar text-white w-64 flex-shrink-0 flex flex-col h-full transition-transform duration-300 transform -translate-x-full lg:translate-x-0 fixed lg:relative z-40 shadow-xl">
    <!-- Brand -->
    <div class="p-5 flex items-center justify-between border-b border-white/10">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-white/10 backdrop-blur rounded-xl flex items-center justify-center shadow-inner">
                <i class="fas fa-cube text-brand-300 text-xl"></i>
            </div>
            <div>
                <h2 class="text-lg font-extrabold tracking-tight leading-tight">TuInventario</h2>
                <span class="text-[10px] font-bold text-white/40 uppercase tracking-widest">ERP v2.0</span>
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
        $menuItems = [
            ['dashboard',  'fa-tachometer-alt', 'Panel de Control',  null],
            ['inventory',  'fa-boxes',          'Inventario',        null],
            ['sales',      'fa-shopping-cart',   'Punto de Venta',   null],
            ['purchases',  'fa-cart-arrow-down', 'Compras',          'admin'],
            ['suppliers',  'fa-truck',           'Proveedores',      'admin'],
            ['expenses',   'fa-money-bill-wave', 'Gastos',           'admin'],
            ['cashbox',    'fa-cash-register',   'Arqueo de Caja',   null],
            ['reports',    'fa-chart-line',      'Reportes',         'admin'],
        ];
        
        $userRole = $_SESSION['role'] ?? 'cajero';
        
        foreach ($menuItems as [$route, $icon, $label, $requiredRole]):
            if ($requiredRole && $userRole !== $requiredRole) continue;
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
        
        <!-- Kardex (acceso directo solicitado) -->
        <a href="<?= BASE_URL ?>reports?tab=kardex" class="flex items-center px-3 py-2.5 rounded-lg transition-all text-sm font-medium border-l-[3px] border-transparent text-white/70 hover:bg-white/10 hover:text-white">
            <i class="fas fa-exchange-alt w-5 text-center mr-3 text-sm"></i>
            <span>Kardex</span>
        </a>

        <?php if($userRole === 'admin'): ?>
        <a href="<?= BASE_URL ?>settings" class="flex items-center px-3 py-2.5 rounded-lg transition-all text-sm font-medium <?= strpos($uri, 'settings') !== false ? 'bg-white/15 border-l-[3px] border-brand-300 text-white shadow-sm' : 'border-l-[3px] border-transparent text-white/70 hover:bg-white/10 hover:text-white' ?>">
            <i class="fas fa-cog w-5 text-center mr-3 text-sm"></i>
            <span>Configuración</span>
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
                <p class="text-[10px] text-white/40 uppercase font-bold tracking-wider"><?= $_SESSION['role'] ?? 'cajero' ?></p>
            </div>
            <i class="fas fa-cog text-white/30 group-hover:text-white/70 text-xs transition-colors"></i>
        </div>
    </a>
</aside>
