<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TuInventario - ERP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/htmx.org@1.9.11"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
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
    </style>
</head>
<body class="bg-gray-50 text-gray-800 dark:bg-slate-950 dark:text-gray-100 antialiased h-screen flex overflow-hidden">
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
