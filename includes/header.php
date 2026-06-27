<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#10b981">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="TuInventario">
    <link rel="manifest" href="<?= BASE_URL ?>manifest.json">
    <link rel="apple-touch-icon" href="<?= BASE_URL ?>icons/icon-512x512.png">
    <title>TuInventario - ERP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/htmx.org@1.9.11"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
    <script>
        if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
        
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50:  '#ecfdf5',
                            100: '#d1fae5',
                            200: '#a7f3d0',
                            300: '#6ee7b7',
                            400: '#34d399',
                            500: '#10b981',
                            600: '#059669',
                            700: '#047857',
                            800: '#065f46',
                            900: '#064e3b',
                        },
                        accent: {
                            50:  '#ecfeff',
                            100: '#cffafe',
                            200: '#a5f3fc',
                            300: '#67e8f9',
                            400: '#22d3ee',
                            500: '#06b6d4',
                            600: '#0891b2',
                            700: '#0e7490',
                            800: '#155e75',
                            900: '#164e63',
                        }
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .sidebar-scroll::-webkit-scrollbar { width: 4px; }
        .sidebar-scroll::-webkit-scrollbar-track { background: transparent; }
        .sidebar-scroll::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.15); border-radius: 10px; }
        .sidebar-scroll::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,0.3); }
        .gradient-sidebar { background: linear-gradient(180deg, #064e3b 0%, #0e7490 100%); }
        .gradient-header { background: linear-gradient(135deg, #ecfdf5 0%, #ecfeff 100%); }
        .dark .gradient-header { background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); }

        /* ===== GLOBAL MOBILE RESPONSIVE ===== */
        @media (max-width: 640px) {
            /* Tables: ensure horizontal scroll */
            .overflow-x-auto { -webkit-overflow-scrolling: touch; }
            table { font-size: 0.8rem; }
            table th, table td { padding: 0.5rem 0.75rem !important; }
            
            /* Modals: full screen on mobile */
            .fixed.inset-0 .max-w-4xl,
            .fixed.inset-0 .max-w-xl,
            .fixed.inset-0 .max-w-sm,
            .fixed.inset-0 .max-w-lg {
                max-width: 100% !important;
                margin: 0 !important;
                border-radius: 0 !important;
                min-height: 100vh;
            }
            
            /* Text scaling */
            h1, .text-3xl { font-size: 1.5rem !important; }
            h2, .text-2xl { font-size: 1.25rem !important; }
            
            /* KPI cards: smaller padding */
            .grid > div > .p-5 { padding: 0.75rem; }
            
            /* Touch targets: minimum 44px */
            button, a, select, input[type="submit"] { min-height: 40px; }
        }

        /* Smooth transitions for PWA feel */
        * { -webkit-tap-highlight-color: transparent; }
        input, select, textarea { font-size: 16px !important; } /* Prevent zoom on iOS */

        /* ===== GLOBAL LOADER ===== */
        #global-loader {
            position: fixed; inset: 0; z-index: 9999;
            background: rgba(15,23,42,0.6);
            backdrop-filter: blur(6px);
            display: none;
            align-items: center; justify-content: center;
            flex-direction: column;
            transition: opacity 0.3s;
        }
        #global-loader.active { display: flex; animation: loaderFadeIn 0.25s ease; }
        @keyframes loaderFadeIn { from { opacity: 0; } to { opacity: 1; } }
        .loader-spinner {
            width: 48px; height: 48px;
            border: 4px solid rgba(16,185,129,0.2);
            border-top-color: #10b981;
            border-radius: 50%;
            animation: spin 0.7s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        .loader-pulse { 
            margin-top: 16px; color: #d1fae5; font-size: 13px; font-weight: 600;
            animation: pulse 1.5s ease-in-out infinite;
        }
        @keyframes pulse { 0%,100% { opacity: 1; } 50% { opacity: 0.4; } }

        /* ===== PROFESSIONAL PRINT STYLES ===== */
        @media print {
            body { background: white !important; color: black !important; overflow: visible !important; height: auto !important; display: block !important; }
            #sidebar, #sidebar-overlay, #mobile-menu-btn, #theme-toggle, #global-loader,
            header, .gradient-header, .no-print, button, a[href*="logout"],
            .fixed.inset-0 { display: none !important; }
            main { padding: 0 !important; margin: 0 !important; overflow: visible !important; height: auto !important; }
            .flex-1.flex.flex-col { display: block !important; height: auto !important; overflow: visible !important; }
            table { width: 100% !important; border-collapse: collapse !important; font-size: 11px !important; page-break-inside: auto; }
            table th { background: #f3f4f6 !important; color: #111 !important; font-weight: 700 !important; border: 1px solid #d1d5db !important; padding: 6px 10px !important; }
            table td { border: 1px solid #e5e7eb !important; padding: 5px 10px !important; color: #333 !important; }
            tr { page-break-inside: avoid; }
            .print-header { display: block !important; text-align: center; margin-bottom: 20px; padding-bottom: 12px; border-bottom: 2px solid #10b981; }
            .print-header h1 { font-size: 20px; font-weight: 900; color: #064e3b; }
            .print-header p { font-size: 11px; color: #666; margin-top: 2px; }
            .print-footer { display: block !important; text-align: center; margin-top: 20px; padding-top: 10px; border-top: 1px solid #e5e7eb; font-size: 9px; color: #999; }
            .bg-white, .dark\\:bg-slate-800, .dark\\:bg-gray-800, .dark\\:bg-slate-900 { background: white !important; }
            .shadow-sm, .shadow-md, .shadow-lg, .shadow-xl, .shadow-2xl { box-shadow: none !important; }
            .rounded-xl, .rounded-2xl, .rounded-lg { border-radius: 0 !important; }
            * { color: #111 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
    <script>
        // Register PWA Service Worker
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('<?= BASE_URL ?>sw.js').catch(() => {});
            });
        }
    </script>
</head>
<body class="bg-gray-50 text-gray-800 dark:bg-slate-950 dark:text-gray-100 antialiased h-screen flex overflow-hidden">
    <!-- GLOBAL LOADER -->
    <div id="global-loader">
        <div class="loader-spinner"></div>
        <p class="loader-pulse">Procesando...</p>
    </div>

    <!-- PRINT HEADER (only visible on Ctrl+P) -->
    <div class="print-header" style="display:none;">
        <h1><?= htmlspecialchars(Settings::get('business_name', 'TuInventario ERP')) ?></h1>
        <p>Reporte generado el <?= date('d/m/Y H:i') ?></p>
    </div>

    <!-- Main Sidebar -->
    <?php include 'sidebar.php'; ?>
    
    <!-- Wrapper -->
    <div class="flex-1 flex flex-col h-screen overflow-hidden">
        <!-- Top Header -->
        <header class="gradient-header shadow-sm z-10 px-4 py-3 flex justify-between items-center transition-colors border-b border-gray-200/50 dark:border-gray-800">
            <div class="flex items-center">
                <button id="mobile-menu-btn" class="lg:hidden text-gray-500 hover:text-brand-600 dark:text-gray-400 dark:hover:text-brand-400 focus:outline-none mr-3">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <h1 class="font-extrabold text-lg bg-gradient-to-r from-brand-600 to-accent-600 bg-clip-text text-transparent lg:hidden">TuInventario</h1>
            </div>

            <!-- Right side controls -->
            <div class="flex items-center space-x-3 ml-auto">
                <!-- BCV Rate Badge -->
                <div class="hidden md:flex items-center bg-white dark:bg-slate-800 border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-1.5 text-xs font-bold text-gray-600 dark:text-gray-300 shadow-sm">
                    <i class="fas fa-coins text-brand-500 mr-2"></i>
                    BCV: Bs <?= number_format((float)Settings::get('bcv_rate', 622.21), 2) ?>
                </div>

                <!-- Theme Toggle -->
                <button id="theme-toggle" class="text-gray-500 hover:bg-white dark:text-gray-400 dark:hover:bg-slate-800 w-9 h-9 rounded-lg flex items-center justify-center transition-all focus:outline-none shadow-sm border border-gray-200 dark:border-gray-700">
                    <i class="fas fa-moon dark:hidden text-sm"></i>
                    <i class="fas fa-sun hidden dark:block text-sm text-yellow-400"></i>
                </button>

                <!-- Alpine Dropdown -->
                <div class="relative" x-data="{ open: false }" @click.away="open = false">
                    <button @click="open = !open" class="flex items-center space-x-2 text-gray-700 dark:text-gray-300 focus:outline-none hover:bg-white dark:hover:bg-slate-800 p-1.5 rounded-lg transition-all border border-transparent hover:border-gray-200 dark:hover:border-gray-700">
                        <div class="w-8 h-8 bg-gradient-to-br from-brand-500 to-accent-500 text-white rounded-lg flex items-center justify-center font-bold shadow-sm text-sm">
                            <?= strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1)) ?>
                        </div>
                        <span class="font-semibold hidden sm:block text-sm"><?= htmlspecialchars($_SESSION['username'] ?? 'Usuario') ?></span>
                        <i class="fas fa-chevron-down text-[10px] ml-0.5 text-gray-400"></i>
                    </button>
                    
                    <div x-show="open" x-transition class="absolute right-0 mt-2 w-52 bg-white dark:bg-slate-800 rounded-xl shadow-xl border border-gray-100 dark:border-gray-700 py-1.5 z-50">
                        <div class="px-4 py-2 border-b border-gray-100 dark:border-gray-700">
                            <p class="text-sm font-bold text-gray-800 dark:text-white"><?= htmlspecialchars($_SESSION['username'] ?? 'Usuario') ?></p>
                            <p class="text-xs text-gray-400 capitalize"><?= $_SESSION['role'] ?? 'cajero' ?></p>
                        </div>
                        <a href="<?= BASE_URL ?>users" class="flex items-center px-4 py-2.5 text-sm text-gray-700 dark:text-gray-200 hover:bg-brand-50 dark:hover:bg-brand-900/20 hover:text-brand-600 transition-colors">
                            <i class="fas fa-user-circle mr-3 text-gray-400 w-4"></i> Mi Perfil
                        </a>
                        <?php if(($_SESSION['role'] ?? '') === 'admin'): ?>
                        <a href="<?= BASE_URL ?>settings" class="flex items-center px-4 py-2.5 text-sm text-gray-700 dark:text-gray-200 hover:bg-brand-50 dark:hover:bg-brand-900/20 hover:text-brand-600 transition-colors">
                            <i class="fas fa-cog mr-3 text-gray-400 w-4"></i> Configuración
                        </a>
                        <?php endif; ?>
                        <div class="border-t border-gray-100 dark:border-gray-700 my-1"></div>
                        <a href="<?= BASE_URL ?>auth/logout" class="flex items-center px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors font-medium">
                            <i class="fas fa-sign-out-alt mr-3 w-4"></i> Cerrar Sesión
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content Area -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100/80 dark:bg-slate-900 p-4 md:p-6 lg:p-8 transition-colors">
