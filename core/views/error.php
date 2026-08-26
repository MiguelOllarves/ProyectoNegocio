<?php
$errorCode = http_response_code();
$errorMessages = [
    403 => [
        'title' => 'Acceso Denegado',
        'subtitle' => 'No tienes permisos para ver esta página.',
        'icon' => 'fa-shield-alt',
        'gradient' => 'from-red-500 to-orange-500',
    ],
    404 => [
        'title' => 'Página No Encontrada',
        'subtitle' => 'La página que buscas no existe o ha sido movida.',
        'icon' => 'fa-map-signs',
        'gradient' => 'from-purple-500 to-indigo-500',
    ],
    500 => [
        'title' => 'Error del Servidor',
        'subtitle' => 'Algo salió mal. Estamos trabajando en ello.',
        'icon' => 'fa-server',
        'gradient' => 'from-gray-600 to-gray-800',
    ],
];

$error = $errorMessages[$errorCode] ?? $errorMessages[404];
$baseUrl = defined('BASE_URL') ? BASE_URL : '/';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$isSuperAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'super_admin';
$panelRoute = $isSuperAdmin ? 'superadmin' : 'dashboard';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $errorCode ?> - <?= $error['title'] ?> | Tu Inventario</title>
    <link rel="stylesheet" href="<?= BASE_URL ?? "" ?>css/tailwind.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;900&display=swap" rel="stylesheet">
    <script>
        if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
        tailwind.config = { darkMode: 'class' }
    </script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .glass {
            background: rgba(255,255,255,0.08);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid rgba(255,255,255,0.12);
        }
        .dark .glass {
            background: rgba(0,0,0,0.25);
            border: 1px solid rgba(255,255,255,0.08);
        }
        .float-anim {
            animation: float 6s ease-in-out infinite;
        }
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-18px); }
        }
        .glow {
            box-shadow: 0 0 60px rgba(16,185,129,0.15), 0 0 120px rgba(6,182,212,0.08);
        }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-50 via-gray-100 to-gray-200 dark:from-slate-950 dark:via-slate-900 dark:to-slate-950 min-h-screen flex items-center justify-center p-4 overflow-hidden relative">

    <!-- Background decorative blobs -->
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
        <div class="absolute -top-40 -left-40 w-96 h-96 bg-gradient-to-br <?= $error['gradient'] ?> rounded-full opacity-10 blur-3xl"></div>
        <div class="absolute -bottom-40 -right-40 w-96 h-96 bg-gradient-to-br from-brand-500 to-cyan-500 rounded-full opacity-10 blur-3xl"></div>
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-gradient-to-br from-emerald-500/5 to-teal-500/5 rounded-full blur-3xl"></div>
    </div>

    <div class="relative z-10 text-center max-w-lg mx-auto">
        <!-- Error code with glassmorphism card -->
        <div class="glass rounded-3xl p-10 glow float-anim">
            <!-- Icon -->
            <div class="w-24 h-24 mx-auto mb-6 bg-gradient-to-br <?= $error['gradient'] ?> rounded-2xl flex items-center justify-center shadow-2xl rotate-3 hover:rotate-0 transition-transform duration-500">
                <i class="fas <?= $error['icon'] ?> text-white text-4xl"></i>
            </div>

            <!-- Error Code -->
            <h1 class="text-8xl font-black bg-gradient-to-r <?= $error['gradient'] ?> bg-clip-text text-transparent mb-2 tracking-tighter">
                <?= $errorCode ?>
            </h1>

            <!-- Title -->
            <h2 class="text-2xl font-bold text-gray-800 dark:text-white mb-3">
                <?= $error['title'] ?>
            </h2>

            <!-- Subtitle -->
            <p class="text-gray-500 dark:text-gray-400 mb-8 text-sm leading-relaxed">
                <?= $error['subtitle'] ?>
            </p>

            <!-- Action Buttons -->
            <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
                <a href="<?= $baseUrl ?><?= $panelRoute ?>" class="inline-flex items-center gap-2 bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-600 hover:to-teal-600 text-white font-bold py-3 px-6 rounded-xl shadow-lg hover:shadow-xl transition-all text-sm">
                    <i class="fas fa-home"></i>
                    Ir al Panel
                </a>
                <button onclick="history.back()" class="inline-flex items-center gap-2 bg-white dark:bg-slate-800 text-gray-700 dark:text-gray-300 font-semibold py-3 px-6 rounded-xl shadow-md hover:shadow-lg border border-gray-200 dark:border-gray-700 transition-all text-sm">
                    <i class="fas fa-arrow-left"></i>
                    Volver Atrás
                </button>
            </div>
        </div>

        <!-- Branding -->
        <p class="mt-8 text-xs font-semibold text-gray-400 dark:text-gray-600 tracking-wider uppercase">
            <i class="fas fa-cube mr-1"></i> Tu Inventario
        </p>
    </div>
</body>
</html>
