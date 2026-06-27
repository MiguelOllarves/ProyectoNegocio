<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TuInventario — Sistema de Gestión</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: { 50:'#ecfdf5',100:'#d1fae5',200:'#a7f3d0',300:'#6ee7b7',400:'#34d399',500:'#10b981',600:'#059669',700:'#047857',800:'#065f46',900:'#064e3b' },
                        accent: { 50:'#ecfeff',100:'#cffafe',200:'#a5f3fc',300:'#67e8f9',400:'#22d3ee',500:'#06b6d4',600:'#0891b2',700:'#0e7490',800:'#155e75',900:'#164e63' }
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .hero-gradient { background: linear-gradient(135deg, #064e3b 0%, #0e7490 50%, #155e75 100%); }
        .glass-card { background: rgba(255,255,255,0.08); backdrop-filter: blur(20px); border: 1px solid rgba(255,255,255,0.12); }
        .feature-glow:hover { box-shadow: 0 0 30px rgba(16,185,129,0.15); }
        @keyframes float { 0%,100% { transform: translateY(0px); } 50% { transform: translateY(-10px); } }
        .float-anim { animation: float 6s ease-in-out infinite; }
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
        .fade-up { animation: fadeInUp 0.8s ease forwards; }
        .fade-up-d1 { animation: fadeInUp 0.8s ease 0.1s forwards; opacity: 0; }
        .fade-up-d2 { animation: fadeInUp 0.8s ease 0.2s forwards; opacity: 0; }
        .fade-up-d3 { animation: fadeInUp 0.8s ease 0.3s forwards; opacity: 0; }
        .fade-up-d4 { animation: fadeInUp 0.8s ease 0.4s forwards; opacity: 0; }
    </style>
</head>
<body class="bg-slate-950 text-white antialiased">

    <!-- Hero / Landing Section -->
    <div class="hero-gradient min-h-screen flex flex-col relative overflow-hidden">
        <!-- Decorative circles -->
        <div class="absolute top-[-200px] right-[-200px] w-[500px] h-[500px] rounded-full bg-brand-500/10 blur-3xl"></div>
        <div class="absolute bottom-[-100px] left-[-150px] w-[400px] h-[400px] rounded-full bg-accent-500/10 blur-3xl"></div>

        <!-- Nav -->
        <nav class="relative z-10 flex items-center justify-between px-6 md:px-12 py-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-white/10 backdrop-blur rounded-xl flex items-center justify-center">
                    <i class="fas fa-cube text-brand-300 text-xl"></i>
                </div>
                <span class="text-xl font-extrabold tracking-tight">TuInventario</span>
            </div>
            <a href="#login" class="bg-white/10 hover:bg-white/20 backdrop-blur border border-white/20 px-5 py-2 rounded-lg text-sm font-bold transition-all hover:scale-105">
                Iniciar Sesión <i class="fas fa-arrow-right ml-2"></i>
            </a>
        </nav>

        <!-- Hero Content -->
        <div class="flex-1 flex flex-col lg:flex-row items-center justify-center px-6 md:px-12 py-10 gap-12 relative z-10">
            <!-- Left: Text -->
            <div class="lg:w-1/2 max-w-xl">
                <div class="inline-flex items-center bg-white/10 backdrop-blur px-4 py-1.5 rounded-full text-xs font-bold text-brand-300 mb-6 border border-brand-500/20 fade-up">
                    <i class="fas fa-rocket mr-2"></i> Sistema Demo Profesional v2.0
                </div>
                <h1 class="text-4xl md:text-5xl lg:text-6xl font-black leading-tight mb-6 fade-up-d1">
                    Gestiona tu negocio con
                    <span class="bg-gradient-to-r from-brand-300 to-accent-300 bg-clip-text text-transparent"> inteligencia</span>
                </h1>
                <p class="text-lg text-white/60 mb-8 leading-relaxed fade-up-d2">
                    Control de inventario, punto de venta profesional, reportes analíticos y gestión fiscal integrada. Todo en una aplicación web moderna y potente.
                </p>
                <div class="flex flex-wrap gap-4 fade-up-d3">
                    <a href="#login" class="bg-gradient-to-r from-brand-500 to-accent-500 hover:from-brand-400 hover:to-accent-400 text-white font-bold px-8 py-3.5 rounded-xl shadow-lg shadow-brand-500/25 transition-all hover:scale-105 hover:shadow-brand-500/40 text-sm">
                        <i class="fas fa-sign-in-alt mr-2"></i> Acceder al Demo del Sistema
                    </a>
                    <a href="#features" class="bg-white/5 hover:bg-white/10 border border-white/15 text-white font-bold px-8 py-3.5 rounded-xl transition-all text-sm">
                        <i class="fas fa-info-circle mr-2"></i> Conocer Más
                    </a>
                </div>
            </div>

            <!-- Right: Visual -->
            <div class="lg:w-1/2 flex justify-center fade-up-d4">
                <div class="relative float-anim">
                    <div class="glass-card rounded-2xl p-6 w-80 md:w-96 shadow-2xl">
                        <div class="flex items-center gap-3 mb-5 pb-4 border-b border-white/10">
                            <div class="w-3 h-3 rounded-full bg-red-400"></div>
                            <div class="w-3 h-3 rounded-full bg-yellow-400"></div>
                            <div class="w-3 h-3 rounded-full bg-green-400"></div>
                            <span class="text-xs text-white/30 ml-2 font-mono">dashboard.erp</span>
                        </div>
                        <div class="space-y-3">
                            <div class="bg-white/5 rounded-lg p-3 flex justify-between items-center">
                                <div><p class="text-[10px] text-white/40 uppercase font-bold">Ventas Hoy</p><p class="text-xl font-black text-brand-300">$2,847</p></div>
                                <div class="w-10 h-10 bg-brand-500/20 rounded-lg flex items-center justify-center"><i class="fas fa-chart-line text-brand-400"></i></div>
                            </div>
                            <div class="bg-white/5 rounded-lg p-3 flex justify-between items-center">
                                <div><p class="text-[10px] text-white/40 uppercase font-bold">Productos</p><p class="text-xl font-black text-accent-300">1,245</p></div>
                                <div class="w-10 h-10 bg-accent-500/20 rounded-lg flex items-center justify-center"><i class="fas fa-boxes text-accent-400"></i></div>
                            </div>
                            <div class="bg-white/5 rounded-lg p-3 flex justify-between items-center">
                                <div><p class="text-[10px] text-white/40 uppercase font-bold">Ganancia Neta</p><p class="text-xl font-black text-green-300">+34.2%</p></div>
                                <div class="w-10 h-10 bg-green-500/20 rounded-lg flex items-center justify-center"><i class="fas fa-arrow-trend-up text-green-400"></i></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Features Section -->
        <section id="features" class="relative z-10 px-6 md:px-12 pb-20">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 max-w-6xl mx-auto">
                <?php
                $features = [
                    ['fa-boxes', 'Control Total', 'Gestión inteligente de inventario con alertas automáticas de stock crítico.', 'from-brand-500/20 to-brand-600/20', 'text-brand-400'],
                    ['fa-shopping-cart', 'POS Profesional', 'Punto de venta rápido con pagos mixtos, IVA e IGTF automáticos.', 'from-accent-500/20 to-accent-600/20', 'text-accent-400'],
                    ['fa-chart-pie', 'Reportes Analíticos', 'Dashboards con métricas de rentabilidad, ventas y tendencias.', 'from-purple-500/20 to-purple-600/20', 'text-purple-400'],
                    ['fa-shield-halved', 'Seguro y Auditable', 'Logs de actividad, control de roles y respaldos automáticos.', 'from-amber-500/20 to-amber-600/20', 'text-amber-400'],
                ];
                foreach ($features as [$icon, $title, $desc, $gradient, $textColor]): ?>
                <div class="glass-card rounded-xl p-5 transition-all duration-300 feature-glow hover:-translate-y-1">
                    <div class="w-12 h-12 bg-gradient-to-br <?= $gradient ?> rounded-xl flex items-center justify-center mb-4">
                        <i class="fas <?= $icon ?> <?= $textColor ?> text-lg"></i>
                    </div>
                    <h3 class="font-bold text-white mb-2"><?= $title ?></h3>
                    <p class="text-sm text-white/50 leading-relaxed"><?= $desc ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
    </div>

    <!-- Login Section -->
    <section id="login" class="bg-slate-950 py-20 px-6 flex items-center justify-center min-h-screen">
        <div class="w-full max-w-md">
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-br from-brand-500 to-accent-500 mb-4 shadow-lg shadow-brand-500/20">
                    <i class="fas fa-cube text-3xl text-white"></i>
                </div>
                <h2 class="text-3xl font-extrabold text-white">Bienvenido</h2>
                <p class="text-sm text-gray-500 mt-2">Ingresa tus credenciales para acceder al sistema</p>
            </div>

            <?php if (isset($error)): ?>
                <div class="bg-red-500/10 text-red-400 p-4 rounded-xl text-sm mb-6 border border-red-500/20 flex items-center font-medium">
                    <i class="fas fa-exclamation-triangle mr-3"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form action="<?= BASE_URL ?>auth/login" method="POST" class="glass-card rounded-2xl p-8 shadow-2xl">
                <div class="mb-5">
                    <label class="block text-sm font-bold text-gray-400 mb-2">Usuario</label>
                    <div class="relative">
                        <span class="absolute left-4 top-3 text-gray-500"><i class="fas fa-user"></i></span>
                        <input type="text" name="username" value="admin" required class="w-full pl-12 pr-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white placeholder-gray-600 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition-all font-medium" placeholder="ej. admin">
                    </div>
                </div>
                
                <div class="mb-6" x-data="{ show: false }">
                    <label class="block text-sm font-bold text-gray-400 mb-2">Contraseña</label>
                    <div class="relative">
                        <span class="absolute left-4 top-3 text-gray-500"><i class="fas fa-lock"></i></span>
                        <input :type="show ? 'text' : 'password'" name="password" value="admin123" required class="w-full pl-12 pr-12 py-3 bg-white/5 border border-white/10 rounded-xl text-white placeholder-gray-600 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition-all font-medium" placeholder="••••••••">
                        <button type="button" @click="show = !show" class="absolute right-4 top-3 text-gray-500 hover:text-white transition-colors">
                            <i class="fas" :class="show ? 'fa-eye-slash' : 'fa-eye'"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="w-full bg-gradient-to-r from-brand-600 to-accent-600 hover:from-brand-500 hover:to-accent-500 text-white font-bold py-3.5 rounded-xl shadow-lg shadow-brand-600/25 transition-all hover:shadow-brand-500/40 hover:scale-[1.02] flex items-center justify-center text-sm">
                    <i class="fas fa-sign-in-alt mr-2"></i> Iniciar Sesión
                </button>
            </form>

            <div class="mt-8 text-center bg-white/5 border border-white/10 p-5 rounded-2xl">
                <h4 class="text-brand-300 font-bold mb-2">¿Tienes alguna idea o requerimiento nuevo?</h4>
                <p class="text-xs text-gray-400 mb-4 leading-relaxed">Este es un demo interactivo. Inicia sesión para probarlo. Si crees que se puede mejorar o deseas agregar más requerimientos, contáctanos directamente o deja tu comentario por WhatsApp.</p>
                <a href="https://wa.me/584145176772" target="_blank" class="inline-flex justify-center items-center px-4 py-2 bg-green-500/20 text-green-400 border border-green-500/30 rounded-lg hover:bg-green-500/30 transition-all text-sm font-bold">
                    <i class="fab fa-whatsapp text-lg mr-2"></i> WhatsApp: 0414 5176772
                </a>
            </div>

            <div class="text-center mt-8 text-xs text-gray-600">
                TuInventario v2.0 — <?= date('Y') ?>
            </div>
        </div>
    </section>

<script>
// Smooth scroll for anchor links
document.querySelectorAll('a[href^="#"]').forEach(a => {
    a.addEventListener('click', e => {
        e.preventDefault();
        document.querySelector(a.getAttribute('href'))?.scrollIntoView({ behavior: 'smooth' });
    });
});
</script>
</body>
</html>
