<!DOCTYPE html>
<html lang="es" class="scroll-smooth">
<head>
    <!-- Google Tag Manager -->
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
    new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
    j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
    'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
    })(window,document,'script','dataLayer','GTM-NHRNGKB2');</script>
    <!-- End Google Tag Manager -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Tu Inventario - Toma el control absoluto de tu negocio</title>
    <link rel="icon" type="image/png" href="<?= BASE_URL ?>?serve_logo=1">
    <link rel="manifest" href="<?= BASE_URL ?>manifest.json">
    
    <!-- Optimización: Preconexiones CDN para ganar milisegundos -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">

    <!-- CSS (Tailwind nativo) -->
    <link rel="stylesheet" href="<?= BASE_URL ?>css/tailwind.css">
    
    <!-- Optimización: FontAwesome en modo asíncrono para no bloquear pintura -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" media="print" onload="this.media='all'">
    <noscript><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"></noscript>
    
    <!-- Optimización: Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        
        /* Fondo degradado suave (Verde pastel #A8DDCF a blanco/cian) */
        .bg-gradient-animated {
            background: linear-gradient(135deg, #A8DDCF 0%, #e0f2fe 50%, #A8DDCF 100%);
            background-size: 200% 200%;
            animation: gradientBG 15s ease infinite;
        }
        @keyframes gradientBG {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* Utilidades para Tarjetas Glassmorphism */
        .glass-panel {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 1);
            box-shadow: 0 10px 40px -10px rgba(0, 0, 0, 0.08);
        }
        .glass-float {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(54, 178, 145, 0.1);
            box-shadow: 0 20px 40px -10px rgba(54, 178, 145, 0.15);
        }

        /* Mockup Teléfono Moderno (CSS Puro) */
        .phone-mockup {
            border: 10px solid #1f2937;
            border-radius: 44px;
            overflow: hidden;
            background-color: white;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.3);
            position: relative;
        }
        .phone-mockup::before {
            content: '';
            position: absolute;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 140px;
            height: 28px;
            background-color: #1f2937;
            border-bottom-left-radius: 20px;
            border-bottom-right-radius: 20px;
            z-index: 10;
        }

        /* Scrollbar oculta para secciones horizontales */
        .hide-scrollbar::-webkit-scrollbar { display: none; }
        .hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }

        /* Animaciones */
        .animate-float-slow { animation: float 6s ease-in-out infinite; }
        .animate-float-fast { animation: float 4s ease-in-out infinite; }
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-12px); }
        }
        @keyframes waterWaveHorizontal {
            0% { background-position-x: 0, 0; }
            100% { background-position-x: 200px, 0; }
        }
        @keyframes waterWaveVertical {
            0% { background-position-y: 100px, 0; }
            100% { background-position-y: 0px, 0; }
        }
        .animate-water {
            background-image: 
                url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 120 28'%3E%3Cpath d='M0 15 Q 30 0, 60 15 T 120 15 L 120 28 L 0 28 Z' fill='%2336B291'/%3E%3C/svg%3E"),
                linear-gradient(to right, #a7f3d0, #10b981);
            background-repeat: repeat-x, no-repeat;
            background-size: 200px 100px, 100% 100%;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            color: transparent;
            animation: waterWaveHorizontal 3s linear infinite, waterWaveVertical 5s ease-in-out infinite alternate;
        }

        /* Custom positioning and rotation for phone mockups to match references perfectly */
        @keyframes slideUpBottomSheet {
            from { transform: translateY(100%); }
            to { transform: translateY(0); }
        }
        @keyframes scaleUpModal {
            from { transform: scale(0.95); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }
        
        .phone-left-custom {
            position: absolute;
            top: 6%;
            left: 2%;
            width: 240px;
            height: 500px;
            transform: rotate(-6deg) scale(0.9);
            z-index: 10;
            transition: transform 0.7s ease;
        }
        .phone-left-custom:hover {
            transform: rotate(-2deg) scale(0.95);
            z-index: 30;
        }
        
        .phone-right-custom {
            position: absolute;
            bottom: 4%;
            right: -2%;
            width: 250px;
            height: 520px;
            transform: rotate(8deg) scale(0.95);
            z-index: 20;
            transition: transform 0.7s ease;
        }
        .phone-right-custom:hover {
            transform: rotate(2deg) scale(1.0);
        }

        .card-pos-custom {
            position: absolute;
            top: 20%;
            left: 22%;
            width: 260px;
            z-index: 30;
        }
        
        .card-inventario-custom {
            position: absolute;
            bottom: 12%;
            right: -5%;
            width: 240px;
            z-index: 30;
        }

        @media (min-width: 640px) {
            .phone-left-custom {
                top: 8%;
                left: 2%;
                width: 280px;
                height: 580px;
                transform: rotate(-6deg) scale(0.95);
            }
            .phone-right-custom {
                bottom: 8%;
                right: -5%;
                width: 290px;
                height: 600px;
                transform: rotate(8deg);
            }
            .card-pos-custom {
                top: 22%;
                left: 28%;
            }
            .card-inventario-custom {
                bottom: 18%;
                right: -2%;
            }
        }

        @media (min-width: 1024px) {
            .phone-left-custom {
                left: 0%;
                top: 10%;
            }
            .phone-right-custom {
                right: -8%;
                bottom: 10%;
            }
            .card-pos-custom {
                left: 28%;
            }
            .card-inventario-custom {
                right: -5%;
            }
        }

        @media (min-width: 1280px) {
            .phone-left-custom {
                left: -2%;
                top: 8%;
            }
            .phone-right-custom {
                right: -10%;
                bottom: 8%;
            }
            .card-pos-custom {
                left: 30%;
            }
            .card-inventario-custom {
                right: -8%;
            }
        }

        /* Estilo personalizado para el H1 principal (Hero Title) para garantizar tamaño gigante y llamativo */
        .hero-title-custom {
            font-size: 2.8rem;
            font-weight: 900;
            line-height: 1.05;
            letter-spacing: -0.03em;
            color: #0f172a; /* slate-900 */
            margin-bottom: 1.5rem;
        }
        @media (min-width: 640px) {
            .hero-title-custom {
                font-size: 4rem;
            }
        }
        @media (min-width: 1024px) {
            .hero-title-custom {
                font-size: 4.2rem;
            }
        }
        @media (min-width: 1280px) {
            .hero-title-custom {
                font-size: 5rem;
            }
        }

        /* Premium styles for WhatsApp and Telegram buttons */
        .btn-whatsapp-premium {
            background: linear-gradient(135deg, #25D366 0%, #128C7E 100%) !important;
            box-shadow: 0 12px 30px -10px rgba(37, 211, 102, 0.6) !important;
            transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1) !important;
            border: 2px solid rgba(255, 255, 255, 0.2) !important;
            color: white !important;
        }
        .btn-whatsapp-premium:hover {
            background: linear-gradient(135deg, #2ae771 0%, #159c8d 100%) !important;
            box-shadow: 0 18px 40px -12px rgba(37, 211, 102, 0.8) !important;
            transform: translateY(-4px) scale(1.05) !important;
        }
        .btn-whatsapp-premium:active {
            transform: translateY(-2px) scale(1.02) !important;
        }

        .btn-telegram-premium {
            background: linear-gradient(135deg, #0088cc 0%, #006699 100%) !important;
            box-shadow: 0 12px 30px -10px rgba(0, 136, 204, 0.6) !important;
            transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1) !important;
            border: 2px solid rgba(255, 255, 255, 0.2) !important;
            color: white !important;
        }
        .btn-telegram-premium:hover {
            background: linear-gradient(135deg, #0099e6 0%, #0077b3 100%) !important;
            box-shadow: 0 18px 40px -12px rgba(0, 136, 204, 0.8) !important;
            transform: translateY(-4px) scale(1.05) !important;
        }
        .btn-telegram-premium:active {
            transform: translateY(-2px) scale(1.02) !important;
        }

        /* PWA Standalone Mode CSS overrides */
        .pwa-standalone-mode body > div:first-of-type {
            display: none !important;
        }
        .pwa-standalone-mode #login-modal {
            display: flex !important;
            position: fixed !important;
            inset: 0 !important;
            background-color: #ebfbf1 !important;
            z-index: 99999 !important;
            align-items: center;
            justify-content: center;
        }
        .pwa-standalone-mode #login-modal > div:first-of-type {
            display: none !important;
        }
        .pwa-standalone-mode #login-modal .relative.bg-white {
            width: 100% !important;
            max-width: 100% !important;
            height: 100% !important;
            max-height: 100% !important;
            border-radius: 0px !important;
            box-shadow: none !important;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 2.5rem !important;
            background-color: #ebfbf1 !important;
        }
        .pwa-standalone-mode #login-modal button.absolute.top-4.right-4 {
            display: none !important;
        }
    </style>
    <script>
        // Detectar si está instalado y ejecutándose como PWA standalone
        if (window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true) {
            document.documentElement.classList.add('pwa-standalone-mode');
        }
    </script>
</head>
<body class="bg-[#ebfbf1] text-slate-800 antialiased selection:bg-[#36B291] selection:text-white min-h-screen relative overflow-x-hidden">
    <!-- Google Tag Manager (noscript) -->
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-NHRNGKB2"
    height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
    <!-- End Google Tag Manager (noscript) -->

    <!-- Contenedor Principal -->
    <div class="relative z-10 w-full flex flex-col">
        
        <!-- ========================================== -->
        <!-- SECCIÓN 1: ENCABEZADO (HEADER) -->
        <!-- ========================================== -->
        <header id="main-header" class="fixed top-0 left-0 z-[100] w-full bg-transparent transition-all duration-300">
            <div class="max-w-[1400px] mx-auto w-full px-6 py-4 lg:px-10 lg:py-5 flex items-center justify-between transition-all duration-300" id="header-container">
                
                <!-- Izquierda: Logo + Enlaces -->
                <div class="flex items-center gap-6 xl:gap-12">
                    <!-- Logo -->
                    <a href="#inicio" class="flex items-center gap-2 cursor-pointer group shrink-0">
                        <div class="w-10 h-10 bg-white rounded-xl flex items-center justify-center shadow-sm border border-gray-100 group-hover:shadow-md transition-shadow">
                            <img src="<?= BASE_URL ?>?serve_logo=1" alt="Logo" class="w-6 h-6 object-contain">
                        </div>
                        <span class="text-xl lg:text-2xl font-black text-slate-800 tracking-tight">
                            Tu<span class="text-[#36B291]">Inventario</span>
                        </span>
                    </a>

                    <!-- Navegación Desktop -->
                    <nav class="hidden lg:flex items-center gap-5 xl:gap-8 text-[15px] font-bold text-slate-700">
                        <a href="#inicio" class="hover:text-[#36B291] transition-colors whitespace-nowrap">Inicio</a>
                        <a href="#funcionalidades" class="hover:text-[#36B291] transition-colors whitespace-nowrap">Características</a>
                        <a href="<?= BASE_URL ?>qrmenu" class="hover:text-[#36B291] transition-colors whitespace-nowrap flex items-center gap-2"><i class="fas fa-qrcode"></i> Menú QR</a>
                        <a href="#contacto" class="hover:text-[#36B291] transition-colors whitespace-nowrap">Contacto</a>
                    </nav>
                </div>

                <!-- Derecha: Botón de Registro -->
                <div class="hidden lg:flex items-center gap-6">
                    <button onclick="document.getElementById('login-modal').classList.remove('hidden'); document.getElementById('login-modal').classList.add('flex')" class="text-[15px] font-bold text-slate-700 hover:text-[#36B291] transition-colors whitespace-nowrap focus:outline-none">Acceder</button>
                    <a href="<?= BASE_URL ?>auth/register" class="bg-[#36B291] hover:bg-[#2c967a] text-white text-[15px] font-bold py-2.5 px-6 rounded-full transition-all shadow-[0_8px_20px_-6px_rgba(54,178,145,0.5)] transform hover:-translate-y-0.5 flex items-center gap-2 whitespace-nowrap">
                        Regístrate <i class="fas fa-arrow-right text-[12px] ml-1"></i>
                    </a>
                </div>

                <!-- Botón Menú Móvil -->
                <button class="lg:hidden text-slate-700 hover:text-[#36B291] focus:outline-none" id="mobile-menu-btn">
                    <i class="fas fa-bars text-2xl"></i>
                </button>
            </div>
        </header>

        <!-- Menú Móvil Desplegable -->
        <div id="mobile-menu" class="hidden lg:hidden flex-col bg-white/95 backdrop-blur-xl absolute top-[70px] left-0 w-full p-6 shadow-2xl border-b border-gray-100 z-50 rounded-b-3xl">
            <a href="#inicio" class="py-3 text-lg font-semibold text-slate-700 border-b border-gray-50">Inicio</a>
            <a href="#funcionalidades" class="py-3 text-lg font-semibold text-slate-700 border-b border-gray-50">Soluciones</a>
            <a href="#contacto" class="py-3 text-lg font-semibold text-slate-700 border-b border-gray-50 mb-4">Contacto</a>
            <button onclick="document.getElementById('login-modal').classList.remove('hidden'); document.getElementById('login-modal').classList.add('flex')" class="w-full text-center py-3 text-lg font-bold text-[#36B291] bg-teal-50 rounded-xl mb-3 focus:outline-none">Inicia Sesión</button>
            <button onclick="document.getElementById('login-modal').classList.remove('hidden'); document.getElementById('login-modal').classList.add('flex')" class="w-full text-center py-3 text-lg font-bold text-white bg-[#36B291] rounded-xl shadow-lg focus:outline-none">Regístrate</button>
        </div>


        <!-- ========================================== -->
        <!-- SECCIÓN 2: HERO SECTION -->
        <!-- ========================================== -->
        <section id="inicio" class="flex flex-col lg:flex-row items-center w-full px-6 py-12 lg:px-10 lg:py-16 gap-12 lg:gap-8 relative max-w-[1400px] mx-auto min-h-[85vh] pt-[100px] lg:pt-[110px]">
            <!-- Círculo decorativo hero -->
            <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-[800px] h-[800px] rounded-full border border-[#36B291]/10 border-dashed animate-[spin_40s_linear_infinite] pointer-events-none z-0"></div>

            <!-- Izquierda: Textos -->
            <div class="w-full lg:w-[48%] flex flex-col justify-center text-center lg:text-left z-20 lg:pl-10">
                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-emerald-50 border border-emerald-100 text-emerald-700 text-xs font-bold mb-6 mx-auto lg:mx-0 w-max">
                    <i class="fas fa-bolt text-amber-500"></i> La plataforma que tu negocio necesita
                </div>
                <h1 class="hero-title-custom drop-shadow-sm">
                    Toma el control<br>
                    <span class="animate-water">absoluto</span> de tu Negocio. <span class="inline-block text-[0.85em] align-middle">😊</span>
                </h1>
                <p class="text-lg sm:text-xl text-slate-600 mb-10 max-w-xl mx-auto lg:mx-0 leading-relaxed font-medium">
                    Gestiona tu Inventario, Punto de Venta (POS), Facturación, Crédito, Reportes y Tienda Online desde un solo lugar. Diseñado para que domines tus ganancias, gastos, clientes y proveedores sin complicaciones.
                </p>
                <div class="flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-4">
                    <button onclick="document.getElementById('login-modal').classList.remove('hidden'); document.getElementById('login-modal').classList.add('flex')" class="w-full sm:w-auto bg-[#36B291] hover:bg-[#2c967a] text-white font-bold py-4 px-10 rounded-full transition-all shadow-[0_10px_30px_-10px_rgba(54,178,145,0.6)] transform hover:-translate-y-1 text-center text-lg focus:outline-none">
                        Prueba el Sistema
                    </button>
                    <a href="#funcionalidades" class="w-full sm:w-auto bg-transparent border-2 border-[#36B291] text-[#36B291] hover:bg-teal-50/50 font-bold py-4 px-10 rounded-full transition-all text-center text-lg focus:outline-none">
                        Explorar Módulos
                    </a>
                </div>
                <div class="mt-8 flex items-center justify-center lg:justify-start gap-4 text-sm font-bold text-slate-600">
                    <div><i class="fas fa-check-circle text-[#36B291] mr-1"></i> Fácil de usar</div>
                    <div><i class="fas fa-check-circle text-[#36B291] mr-1"></i> Soporte 24/7</div>
                </div>
            </div>

            <!-- Derecha: Gráficos (Teléfonos flotantes UI nítida) -->
            <div class="w-full lg:w-[52%] relative h-[500px] sm:h-[750px] z-10 flex justify-center items-center perspective-1000">
                
                <!-- Teléfono 1: Productos (Atrás/Izquierda) -->
                <div class="phone-left-custom phone-mockup shadow-2xl">
                    <div class="w-full h-full bg-slate-50 pt-9 flex flex-col relative">
                        <div class="bg-[#36B291] p-4 pb-6 text-white rounded-b-3xl shadow-sm relative z-10">
                            <div class="flex justify-between items-center mb-3">
                                <i class="fas fa-bars opacity-80"></i>
                                <span class="text-sm font-bold tracking-wide">Tu Inventario</span>
                                <div class="w-6 h-6 bg-white/20 rounded-full flex items-center justify-center"><i class="fas fa-user text-xs"></i></div>
                            </div>
                            <div class="bg-white/20 rounded-full py-1.5 px-3 flex items-center gap-2 text-xs backdrop-blur-sm">
                                <i class="fas fa-search"></i> Buscar productos...
                            </div>
                        </div>
                        <div class="p-4 flex-1 -mt-4 relative z-0">
                            <div class="grid grid-cols-2 gap-3 pt-6">
                                <div class="bg-white p-2 rounded-2xl shadow-sm border border-gray-100 flex flex-col items-center">
                                    <div class="w-full h-20 bg-cover bg-center rounded-xl mb-2" style="background-image: url('https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=200&fit=crop');"></div>
                                    <span class="text-[10px] font-bold text-slate-800 text-center leading-tight">Burger Clásica</span>
                                    <span class="text-xs font-black text-[#36B291] mt-1">$12.50</span>
                                </div>
                                <div class="bg-white p-2 rounded-2xl shadow-sm border border-gray-100 flex flex-col items-center">
                                    <div class="w-full h-20 bg-cover bg-center rounded-xl mb-2" style="background-image: url('https://images.unsplash.com/photo-1573080496219-bb080dd4f877?w=200&fit=crop');"></div>
                                    <span class="text-[10px] font-bold text-slate-800 text-center leading-tight">Papas Fritas</span>
                                    <span class="text-xs font-black text-[#36B291] mt-1">$4.00</span>
                                </div>
                                <div class="bg-white p-2 rounded-2xl shadow-sm border border-gray-100 flex flex-col items-center">
                                    <div class="w-full h-20 bg-cover bg-center rounded-xl mb-2" style="background-image: url('https://images.unsplash.com/photo-1622483767028-3f66f32aef97?w=200&fit=crop');"></div>
                                    <span class="text-[10px] font-bold text-slate-800 text-center leading-tight">Coca Cola</span>
                                    <span class="text-xs font-black text-[#36B291] mt-1">$2.50</span>
                                </div>
                                <div class="bg-white p-2 rounded-2xl shadow-sm border border-gray-100 flex flex-col items-center">
                                    <div class="w-full h-20 bg-cover bg-center rounded-xl mb-2" style="background-image: url('https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=200&fit=crop');"></div>
                                    <span class="text-[10px] font-bold text-slate-800 text-center leading-tight">Burger Doble</span>
                                    <span class="text-xs font-black text-[#36B291] mt-1">$18.00</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Teléfono 2: Ticket (Frente/Derecha) -->
                <div class="phone-right-custom phone-mockup shadow-[0_30px_60px_-15px_rgba(0,0,0,0.5)]">
                    <div class="w-full h-full bg-slate-50 pt-9 flex flex-col">
                        <div class="bg-slate-900 p-5 text-white flex flex-col items-center justify-center relative">
                            <div class="absolute top-2 right-4 text-[10px] opacity-60"><i class="fas fa-wifi mr-1"></i><i class="fas fa-battery-full"></i></div>
                            <div class="absolute top-4 left-1/2 -translate-x-1/2 bg-white text-slate-800 text-[10px] font-bold px-3 py-1 rounded-full flex items-center gap-1 shadow-md">
                                <i class="fas fa-check-circle text-[#36B291]"></i> Sincronizado
                            </div>
                            <span class="text-[10px] font-bold text-slate-400 mt-6 uppercase tracking-widest">Total a Cobrar</span>
                            <span class="text-4xl font-black text-white mt-1">$43.58</span>
                        </div>
                        <div class="flex-1 bg-white p-5 flex flex-col">
                            <div class="flex justify-between items-center text-sm font-bold text-slate-800 border-b border-gray-100 pb-3 mb-3">
                                <span>Ticket (3 items)</span>
                                <span class="bg-[#36B291]/10 text-[#36B291] px-2 py-0.5 rounded text-[10px]">Mesa 4</span>
                            </div>
                            <div class="space-y-4 flex-1">
                                <div class="flex justify-between items-center">
                                    <div class="flex flex-col"><span class="text-xs font-bold text-slate-700">1x Burger Doble</span><span class="text-[9px] text-slate-400">Sin cebolla</span></div>
                                    <span class="text-sm font-black text-slate-800">$18.00</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <div class="flex flex-col"><span class="text-xs font-bold text-slate-700">2x Papas Grandes</span></div>
                                    <span class="text-sm font-black text-slate-800">$5.58</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <div class="flex flex-col"><span class="text-xs font-bold text-slate-700">1x Coca Cola 1.5L</span></div>
                                    <span class="text-sm font-black text-slate-800">$20.00</span>
                                </div>
                            </div>
                            <button class="w-full bg-[#36B291] text-white font-black py-4 rounded-2xl text-lg shadow-[0_8px_20px_-6px_rgba(54,178,145,0.6)]">
                                PROCESAR PAGO
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Burbuja Flotante 1: POS -->
                <div class="hidden lg:flex glass-float card-pos-custom p-4 sm:p-5 rounded-2xl items-center gap-4 shadow-2xl animate-float-slow">
                    <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-xl bg-gradient-to-br from-teal-100 to-emerald-100 flex items-center justify-center text-[#36B291] shadow-inner shrink-0">
                        <i class="fas fa-cash-register text-2xl"></i>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-sm font-black text-slate-800">Punto de Venta (POS)</span>
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-0.5">Venta Hoy</span>
                        <span class="text-xl font-black text-slate-900 leading-none mt-1">$1,243.58</span>
                    </div>
                </div>

                <!-- Burbuja Flotante 2: Inventario -->
                <div class="hidden lg:flex glass-float card-inventario-custom p-5 rounded-2xl flex-col shadow-2xl animate-float-fast" style="animation-delay: 1.5s;">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-10 h-10 rounded-full bg-[#36B291]/10 flex items-center justify-center text-[#36B291]">
                            <i class="fas fa-boxes text-lg"></i>
                        </div>
                        <h4 class="text-sm font-bold text-slate-800 leading-tight">Control de<br>Inventario</h4>
                    </div>
                    <p class="text-[11px] text-slate-500 font-medium leading-relaxed">
                        Gestión de stock, multialmacén y alertas automáticas en tiempo real.
                    </p>
                    <div class="mt-3 flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-[#36B291] animate-pulse"></span>
                        <span class="text-[10px] font-bold text-[#36B291] uppercase tracking-wider">Stock Óptimo</span>
                    </div>
                </div>

            </div>
        </section>


        <!-- ========================================== -->
        <!-- SECCIÓN 3: PRODUCTOS MÁS VENDIDOS -->
        <!-- ========================================== -->
        <?php $tasa_bcv = 794.99; ?>
        <section id="productos" class="w-full px-6 py-6 lg:px-16 border-t border-gray-100 bg-white/40">
            <h2 class="text-3xl font-black text-slate-800 mb-8 text-center sm:text-left max-w-[1400px] mx-auto w-full">Publica tus productos</h2>
            
            <div class="flex overflow-x-auto gap-6 pb-6 hide-scrollbar snap-x max-w-[1400px] mx-auto w-full">
                <!-- Card 1 -->
                <div class="min-w-[260px] flex-1 bg-white p-5 rounded-3xl shadow-[0_8px_20px_-10px_rgba(0,0,0,0.05)] border border-gray-50 snap-center hover:shadow-[0_20px_30px_-10px_rgba(54,178,145,0.15)] transition-all group">
                    <div class="w-full h-36 bg-slate-50 rounded-2xl mb-4 overflow-hidden flex items-center justify-center p-2">
                        <img src="https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=400&fit=crop" class="w-full h-full object-cover rounded-xl group-hover:scale-105 transition-transform duration-500" alt="iPhone 15">
                    </div>
                    <h3 class="text-[15px] font-bold text-slate-800 leading-tight mb-3">iPhone 15 Pro Max (256GB)</h3>
                    <div class="flex flex-col">
                        <span class="text-2xl font-black text-[#36B291] leading-none">$1099.00</span>
                        <span class="text-[11px] font-bold text-slate-400 mt-1">Bs. <?= number_format(1099.00 * $tasa_bcv, 2, ',', '.') ?></span>
                    </div>
                </div>

                <!-- Card 2 -->
                <div class="min-w-[260px] flex-1 bg-white p-5 rounded-3xl shadow-[0_8px_20px_-10px_rgba(0,0,0,0.05)] border border-gray-50 snap-center hover:shadow-[0_20px_30px_-10px_rgba(54,178,145,0.15)] transition-all group">
                    <div class="w-full h-36 bg-slate-50 rounded-2xl mb-4 overflow-hidden flex items-center justify-center p-2">
                        <img src="https://images.unsplash.com/photo-1596755094514-f87e34085b2c?w=400&fit=crop" class="w-full h-full object-cover rounded-xl group-hover:scale-105 transition-transform duration-500" alt="Camisa Casual">
                    </div>
                    <h3 class="text-[15px] font-bold text-slate-800 leading-tight mb-3">Camisa Casual Premium</h3>
                    <div class="flex flex-col">
                        <span class="text-2xl font-black text-[#36B291] leading-none">$29.99</span>
                        <span class="text-[11px] font-bold text-slate-400 mt-1">Bs. <?= number_format(29.99 * $tasa_bcv, 2, ',', '.') ?></span>
                    </div>
                </div>

                <!-- Card 3 -->
                <div class="min-w-[260px] flex-1 bg-white p-5 rounded-3xl shadow-[0_8px_20px_-10px_rgba(0,0,0,0.05)] border border-gray-50 snap-center hover:shadow-[0_20px_30px_-10px_rgba(54,178,145,0.15)] transition-all group">
                    <div class="w-full h-36 bg-slate-50 rounded-2xl mb-4 overflow-hidden flex items-center justify-center p-2">
                        <img src="https://images.unsplash.com/photo-1622483767028-3f66f32aef97?w=400&fit=crop" class="w-full h-full object-cover rounded-xl group-hover:scale-105 transition-transform duration-500" alt="Coca Cola">
                    </div>
                    <h3 class="text-[15px] font-bold text-slate-800 leading-tight mb-3">Refresco Coca-Cola (Lata)</h3>
                    <div class="flex flex-col">
                        <span class="text-2xl font-black text-[#36B291] leading-none">$1.50</span>
                        <span class="text-[11px] font-bold text-slate-400 mt-1">Bs. <?= number_format(1.50 * $tasa_bcv, 2, ',', '.') ?></span>
                    </div>
                </div>

                <!-- Card 4 -->
                <div class="min-w-[260px] flex-1 bg-white p-5 rounded-3xl shadow-[0_8px_20px_-10px_rgba(0,0,0,0.05)] border border-gray-50 snap-center hover:shadow-[0_20px_30px_-10px_rgba(54,178,145,0.15)] transition-all group hidden md:block">
                    <div class="w-full h-36 bg-slate-50 rounded-2xl mb-4 overflow-hidden flex items-center justify-center p-2">
                        <img src="https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=400&fit=crop" class="w-full h-full object-cover rounded-xl group-hover:scale-105 transition-transform duration-500" alt="Hamburguesas">
                    </div>
                    <h3 class="text-[15px] font-bold text-slate-800 leading-tight mb-3">Pack Hamburguesas Gourmet</h3>
                    <div class="flex flex-col">
                        <span class="text-2xl font-black text-[#36B291] leading-none">$14.90</span>
                        <span class="text-[11px] font-bold text-slate-400 mt-1">Bs. <?= number_format(14.90 * $tasa_bcv, 2, ',', '.') ?></span>
                    </div>
                </div>
            </div>
        </section>


        <!-- ========================================== -->
        <!-- SECCIÓN 4: FUNCIONALIDADES POTENTES -->
        <!-- ========================================== -->
        <section id="funcionalidades" class="w-full px-6 py-10 lg:px-16 bg-white/60 backdrop-blur-md">
            <div class="text-center max-w-3xl mx-auto mb-10">
                <h2 class="text-3xl sm:text-4xl font-black text-slate-800 mt-2 mb-4 uppercase tracking-tight">FUNCIONALIDADES POTENTES</h2>
                <p class="text-slate-600 text-lg font-medium">Todo lo que necesitas para operar, vender y crecer tu negocio, diseñado en una interfaz impecable.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 max-w-[1400px] w-full mx-auto">
                <!-- Func 1: Inventario -->
                <div class="glass-float p-6 rounded-[30px] flex flex-col hover:-translate-y-2 transition-transform duration-300">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-[#A8DDCF] to-[#36B291] flex items-center justify-center shadow-lg shadow-[#36B291]/20 shrink-0">
                            <i class="fas fa-boxes text-white text-xl"></i>
                        </div>
                        <h3 class="text-lg font-bold text-slate-800 leading-tight">Inventario</h3>
                    </div>
                    <p class="text-slate-600 text-sm font-medium leading-relaxed text-justify">Control total en tiempo real. Conoce exactamente qué tienes, qué te falta y qué se vende más. Evita pérdidas, optimiza tu stock de manera inteligente y toma decisiones basadas en datos reales.</p>
                </div>
                
                <!-- Func 2: POS -->
                <div class="glass-float p-6 rounded-[30px] flex flex-col hover:-translate-y-2 transition-transform duration-300">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-blue-100 to-blue-400 flex items-center justify-center shadow-lg shadow-blue-400/20 shrink-0">
                            <i class="fas fa-cash-register text-white text-xl"></i>
                        </div>
                        <h3 class="text-lg font-bold text-slate-800 leading-tight">Punto de Venta (POS)</h3>
                    </div>
                    <p class="text-slate-600 text-sm font-medium leading-relaxed text-justify">Ventas rápidas, fluidas y sin complicaciones. Una interfaz moderna e intuitiva que agiliza tus cobros, mejora la experiencia de tus clientes y actualiza tu inventario al instante con cada transacción.</p>
                </div>

                <!-- Func 3: Compras -->
                <div class="glass-float p-6 rounded-[30px] flex flex-col hover:-translate-y-2 transition-transform duration-300">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-orange-100 to-orange-400 flex items-center justify-center shadow-lg shadow-orange-400/20 shrink-0">
                            <i class="fas fa-shopping-cart text-white text-xl"></i>
                        </div>
                        <h3 class="text-lg font-bold text-slate-800 leading-tight">Compras</h3>
                    </div>
                    <p class="text-slate-600 text-sm font-medium leading-relaxed text-justify">Abastecimiento inteligente. Registra tus adquisiciones fácilmente, controla tus costos operativos y mantén el flujo de tus productos sin interrupciones para que nunca te quedes sin stock.</p>
                </div>

                <!-- Func 4: Proveedores -->
                <div class="glass-float p-6 rounded-[30px] flex flex-col hover:-translate-y-2 transition-transform duration-300">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-purple-100 to-purple-400 flex items-center justify-center shadow-lg shadow-purple-400/20 shrink-0">
                            <i class="fas fa-truck text-white text-xl"></i>
                        </div>
                        <h3 class="text-lg font-bold text-slate-800 leading-tight">Proveedores</h3>
                    </div>
                    <p class="text-slate-600 text-sm font-medium leading-relaxed text-justify">Gestión centralizada de tus aliados. Mantén siempre a la mano el historial de compras, datos de contacto y cuentas por pagar. Construye relaciones comerciales sólidas y negocia mejor.</p>
                </div>

                <!-- Func 5: Clientes -->
                <div class="glass-float p-6 rounded-[30px] flex flex-col hover:-translate-y-2 transition-transform duration-300">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-pink-100 to-pink-400 flex items-center justify-center shadow-lg shadow-pink-400/20 shrink-0">
                            <i class="fas fa-users text-white text-xl"></i>
                        </div>
                        <h3 class="text-lg font-bold text-slate-800 leading-tight">Clientes</h3>
                    </div>
                    <p class="text-slate-600 text-sm font-medium leading-relaxed text-justify">Conoce a quienes te hacen crecer. Crea perfiles, analiza historiales de compra y fomenta la fidelización ofreciendo un servicio personalizado que los haga volver a tu negocio.</p>
                </div>

                <!-- Func 6: Arqueo de Caja -->
                <div class="glass-float p-6 rounded-[30px] flex flex-col hover:-translate-y-2 transition-transform duration-300">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-yellow-100 to-yellow-400 flex items-center justify-center shadow-lg shadow-yellow-400/20 shrink-0">
                            <i class="fas fa-calculator text-white text-xl"></i>
                        </div>
                        <h3 class="text-lg font-bold text-slate-800 leading-tight">Arqueo de Caja</h3>
                    </div>
                    <p class="text-slate-600 text-sm font-medium leading-relaxed text-justify">Cierres de turno transparentes y sin estrés. Cuadra tus ingresos diarios, detecta cualquier discrepancia de efectivo al instante y cierra tu jornada con la tranquilidad de que cada centavo está en su lugar.</p>
                </div>

                <!-- Func 7: Kardex -->
                <div class="glass-float p-6 rounded-[30px] flex flex-col hover:-translate-y-2 transition-transform duration-300">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-indigo-100 to-indigo-400 flex items-center justify-center shadow-lg shadow-indigo-400/20 shrink-0">
                            <i class="fas fa-clipboard-list text-white text-xl"></i>
                        </div>
                        <h3 class="text-lg font-bold text-slate-800 leading-tight">Kardex</h3>
                    </div>
                    <p class="text-slate-600 text-sm font-medium leading-relaxed text-justify">La trazabilidad absoluta. El "historial médico" de tus productos. Un registro detallado que monitorea cada entrada, salida y movimiento físico de tu mercancía para evitar robos, extravíos o desajustes.</p>
                </div>

                <!-- Func 8: Tienda Online -->
                <div class="glass-float p-6 rounded-[30px] flex flex-col hover:-translate-y-2 transition-transform duration-300">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-emerald-100 to-emerald-400 flex items-center justify-center shadow-lg shadow-emerald-400/20 shrink-0">
                            <i class="fas fa-store text-white text-xl"></i>
                        </div>
                        <h3 class="text-lg font-bold text-slate-800 leading-tight">Tienda Online</h3>
                    </div>
                    <p class="text-slate-600 text-sm font-medium leading-relaxed text-justify">Tu negocio abierto 24/7. Lleva tus ventas al mundo digital con una plataforma de e-commerce integrada que se sincroniza automáticamente con tu inventario físico. Vende más allá de las fronteras de tu local.</p>
                </div>
            </div>
        </section>


        <!-- ========================================== -->
        <!-- SECCIÓN 5: VIDEO DEMO -->
        <!-- ========================================== -->
        <section class="w-full px-6 py-20 lg:px-16 bg-[#0c1a16] text-white overflow-hidden relative">
            <div class="absolute inset-0 opacity-20 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')] mix-blend-overlay"></div>
            <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-full h-[500px] bg-[#36B291]/30 filter blur-[100px] rounded-full z-0 pointer-events-none"></div>

            <div class="relative z-10 text-center max-w-3xl mx-auto mb-12">
                <h2 class="text-3xl sm:text-5xl font-black text-white mb-4">Mira Cómo Funciona</h2>
                <p class="text-gray-300 text-lg font-medium">Descubre la potencia de administrar tu negocio de manera inteligente en menos de 2 minutos.</p>
            </div>

            <!-- Contenedor del reproductor de video -->
            <div class="relative max-w-5xl mx-auto rounded-[30px] sm:rounded-[40px] overflow-hidden shadow-2xl border border-white/10 aspect-video bg-gray-900 flex items-center justify-center z-10">
                <video src="<?= BASE_URL ?>assets/video/Meta.mp4" controls class="absolute inset-0 w-full h-full object-cover"></video>
            </div>
        </section>

        <!-- ========================================== -->
        <!-- SECCIÓN 6: CONTACTO & CTA -->
        <!-- ========================================== -->
        <section id="contacto" class="w-full px-6 py-20 lg:px-16 bg-gradient-to-b from-[#0c1a16] to-[#ebfbf1] relative overflow-hidden">
            <!-- Glow decorativo -->
            <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-[#36B291]/15 filter blur-[120px] rounded-full pointer-events-none z-0"></div>

            <div class="max-w-[1000px] mx-auto relative z-10 glass-panel p-8 sm:p-12 md:p-16 rounded-[40px] border border-white/40 shadow-2xl text-center">
                <!-- Icono decorativo -->
                <div class="w-16 h-16 bg-[#36B291]/10 text-[#36B291] rounded-3xl flex items-center justify-center mx-auto mb-6 text-2xl shadow-inner animate-float-slow">
                    <i class="fas fa-paper-plane"></i>
                </div>

                <h2 class="text-4xl sm:text-6xl font-black text-slate-900 tracking-tight mb-6">
                    Moderniza tu negocio hoy mismo
                </h2>
                
                <p class="text-slate-600 text-xl sm:text-2xl font-medium max-w-3xl mx-auto leading-relaxed mb-12">
                    Deja atrás los procesos manuales, el desorden y las pérdidas. Únete a <span class="text-[#36B291] font-bold">Tu Inventario</span> y dale a tu empresa la tecnología que merece para escalar sin límites.
                </p>

                <!-- Canales de contacto directos -->
                <div class="flex flex-col sm:flex-row items-center justify-center gap-6 sm:gap-10 max-w-4xl mx-auto">
                    <!-- WhatsApp Button -->
                    <a href="https://wa.me/584145176772" target="_blank" class="btn-whatsapp-premium w-full sm:w-[320px] font-black py-5 rounded-full flex items-center justify-center gap-4 text-xl tracking-wide">
                        <i class="fab fa-whatsapp text-3xl"></i> <span>WhatsApp</span>
                    </a>

                    <!-- Telegram Button -->
                    <a href="https://t.me/MaomOllarves" target="_blank" class="btn-telegram-premium w-full sm:w-[320px] font-black py-5 rounded-full flex items-center justify-center gap-4 text-xl tracking-wide">
                        <i class="fab fa-telegram-plane text-3xl"></i> <span>Telegram</span>
                    </a>
                </div>
            </div>
        </section>


        <!-- ========================================== -->
        <!-- SECCIÓN 8: PIE DE PÁGINA (FOOTER) -->
        <!-- ========================================== -->
        <footer class="w-full px-6 py-12 lg:px-16 bg-[#A8DDCF]/20 border-t border-white/50 mt-auto rounded-b-[40px]">
            <div class="max-w-6xl mx-auto flex flex-col md:flex-row justify-between items-center gap-8">
                <!-- Logo & Copyright -->
                <div class="flex flex-col items-center md:items-start">
                    <div class="flex items-center gap-2 mb-2">
                        <img src="<?= BASE_URL ?>?serve_logo=1" alt="Logo" class="w-6 h-6 object-contain grayscale opacity-70">
                        <span class="text-xl font-black text-slate-800 tracking-tight">Tu<span class="text-[#36B291]">Inventario</span></span>
                    </div>
                    <p class="text-sm font-medium text-slate-500">© 2026 Tu Inventario. Todos los derechos reservados.</p>
                </div>
                
                <!-- Links Rapidos (Solo Términos y Privacidad) -->
                <div class="flex gap-6 text-sm font-semibold text-slate-600">
                    <button onclick="document.getElementById('terms-modal').classList.remove('hidden'); document.getElementById('terms-modal').classList.add('flex')" class="hover:text-[#36B291] transition-colors focus:outline-none cursor-pointer">Términos y Condiciones</button>
                    <button onclick="document.getElementById('privacy-modal').classList.remove('hidden'); document.getElementById('privacy-modal').classList.add('flex')" class="hover:text-[#36B291] transition-colors focus:outline-none cursor-pointer">Políticas de Privacidad</button>
                </div>
            </div>
        </footer>

    </div>

    <!-- Modal Iniciar Sesión (UI) -->
    <div id="login-modal" class="hidden fixed inset-0 z-[200] items-end sm:items-center justify-center p-0 sm:p-4">
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-md transition-opacity" onclick="document.getElementById('login-modal').classList.add('hidden'); document.getElementById('login-modal').classList.remove('flex')"></div>
        
        <!-- Modal Content (Bottom Sheet on Mobile, Centered Modal on Desktop) -->
        <div class="relative bg-white/95 backdrop-blur-2xl w-full sm:w-[90%] max-w-md rounded-t-[2rem] sm:rounded-[2rem] rounded-b-none sm:rounded-b-[2rem] shadow-[0_-10px_40px_rgba(0,0,0,0.1)] sm:shadow-2xl p-6 sm:p-8 pb-10 sm:pb-8 z-10 animate-[slideUpBottomSheet_0.4s_cubic-bezier(0.16,1,0.3,1)] sm:animate-[scaleUpModal_0.3s_cubic-bezier(0.16,1,0.3,1)] border border-white">
            
            <!-- Mobile Drag Handle -->
            <div class="w-12 h-1.5 bg-slate-200 rounded-full mx-auto mb-6 sm:hidden"></div>

            <button onclick="document.getElementById('login-modal').classList.add('hidden'); document.getElementById('login-modal').classList.remove('flex')" class="modal-close absolute top-5 right-5 sm:flex hidden w-8 h-8 items-center justify-center bg-slate-100 hover:bg-slate-200 text-slate-500 rounded-full transition-colors z-10 focus:outline-none">
                <i class="fas fa-times text-sm"></i>
            </button>
            
            <div class="text-center mb-8 mt-2 sm:mt-0">
                <div class="w-20 h-20 bg-gradient-to-tr from-teal-50 to-teal-100/50 rounded-3xl mx-auto flex items-center justify-center mb-5 shadow-[inset_0_2px_10px_rgba(255,255,255,1),0_5px_15px_-3px_rgba(54,178,145,0.15)] ring-1 ring-teal-900/5">
                    <img src="<?= BASE_URL ?>?serve_logo=1" alt="Logo" class="w-12 h-12 object-contain drop-shadow-sm">
                </div>
                <h3 class="text-3xl font-black text-slate-800 tracking-tight">Bienvenido</h3>
                <p class="text-slate-500 font-medium text-base mt-2">Ingresa a tu panel de control</p>
            </div>
            
            <form action="<?= BASE_URL ?>auth/login" method="POST" class="space-y-5" id="loginForm">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                <input type="hidden" name="fingerprint" id="fingerprint">
                <input type="hidden" name="geolocation" id="geolocation">
                
                <?php if (!empty($_SESSION['login_error'])): ?>
                    <div class="bg-red-50 text-red-600 p-3.5 rounded-2xl text-sm font-semibold mb-4 text-center border border-red-100 shadow-sm">
                        <?= $_SESSION['login_error'] ?>
                    </div>
                    <button type="submit" name="force_close" value="1" class="w-full mb-4 bg-amber-50 hover:bg-amber-100 text-amber-600 border border-amber-200 font-bold py-3 rounded-2xl transition-colors text-sm flex items-center justify-center gap-2 cursor-pointer shadow-sm">
                        <i class="fas fa-power-off"></i> Cerrar sesión remota e iniciar aquí
                    </button>
                    <?php unset($_SESSION['login_error']); ?>
                <?php endif; ?>
                
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1.5 ml-1">Cédula de Identidad</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400">
                            <i class="far fa-id-card text-lg"></i>
                        </div>
                        <input type="text" name="username" required maxlength="9" inputmode="numeric" pattern="[0-9]*" oninput="this.value = this.value.replace(/[^0-9]/g, ''); if(this.value.length > 9) this.value = this.value.slice(0,9);" class="w-full bg-slate-50/50 border-2 border-slate-100 text-slate-800 rounded-2xl focus:bg-white focus:ring-0 focus:border-[#36B291] block p-3.5 pl-12 outline-none transition-all font-semibold text-lg shadow-sm" placeholder="12345678" value="00000000">
                    </div>
                </div>
                <div>
                    <div class="flex justify-between items-center mb-1.5 ml-1 mr-1">
                        <label class="block text-sm font-bold text-slate-700">Contraseña</label>
                        <a href="#" class="text-xs font-bold text-[#36B291] hover:text-teal-700 transition-colors">¿Olvidaste tu contraseña?</a>
                    </div>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400">
                            <i class="fas fa-lock text-lg"></i>
                        </div>
                        <input type="password" id="login_password" name="password" required class="w-full bg-slate-50/50 border-2 border-slate-100 text-slate-800 rounded-2xl focus:bg-white focus:ring-0 focus:border-[#36B291] block p-3.5 pl-12 pr-12 outline-none transition-all font-bold text-lg shadow-sm tracking-widest placeholder:tracking-normal" placeholder="••••••••" value="demo12345">
                        <button type="button" onclick="const p = document.getElementById('login_password'); const i = this.querySelector('i'); if(p.type === 'password'){ p.type = 'text'; i.classList.remove('fa-eye'); i.classList.add('fa-eye-slash'); p.classList.remove('tracking-widest'); } else { p.type = 'password'; i.classList.remove('fa-eye-slash'); i.classList.add('fa-eye'); p.classList.add('tracking-widest'); }" class="absolute inset-y-0 right-0 pr-4 flex items-center text-slate-400 hover:text-[#36B291] transition-colors focus:outline-none">
                            <i class="fas fa-eye text-lg"></i>
                        </button>
                    </div>
                </div>
                
                <button type="submit" class="w-full text-white font-bold py-4 rounded-2xl transition-all shadow-[0_8px_25px_-8px_rgba(54,178,145,0.6)] transform hover:-translate-y-1 text-lg flex items-center justify-center gap-2 mt-2" style="background: linear-gradient(to right, #36B291, #2c967a);">
                    Ingresar <i class="fas fa-arrow-right text-sm"></i>
                </button>
            </form>

            <!-- Fingerprint y Geolocation simplificados para evitar bloqueos y errores 408 -->
            <script defer>
                // Generar un ID básico local si se requiere, sin bloqueos externos
                try {
                    let fp = localStorage.getItem('local_device_fp');
                    if(!fp) { fp = Math.random().toString(36).substring(2) + Date.now().toString(36); localStorage.setItem('local_device_fp', fp); }
                    document.getElementById('fingerprint').value = fp;
                } catch(e) {}
            </script>
            
            <div class="mt-6 text-center text-sm font-medium text-slate-500">
                ¿No tienes una cuenta? <a href="<?= BASE_URL ?>auth/register" class="text-[#36B291] font-bold hover:underline">Regístrate gratis</a>
            </div>
        </div>
    </div>

    <!-- Modal Términos y Condiciones -->
    <div id="terms-modal" class="hidden fixed inset-0 z-[200] items-center justify-center">
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" onclick="document.getElementById('terms-modal').classList.add('hidden'); document.getElementById('terms-modal').classList.remove('flex')"></div>
        
        <!-- Modal Content -->
        <div class="relative bg-white w-[95%] max-w-2xl max-h-[85vh] rounded-[30px] shadow-2xl p-8 md:p-10 z-10 overflow-y-auto flex flex-col">
            <button onclick="document.getElementById('terms-modal').classList.add('hidden'); document.getElementById('terms-modal').classList.remove('flex')" class="modal-close absolute top-4 right-4 z-10">
                <i class="fas fa-times"></i>
            </button>
            
            <div class="flex items-center gap-3 mb-6">
                <div class="w-12 h-12 rounded-2xl bg-teal-50 text-[#36B291] flex items-center justify-center text-xl">
                    <i class="fas fa-file-contract"></i>
                </div>
                <h3 class="text-2xl font-black text-slate-800">Términos y Condiciones</h3>
            </div>

            <div class="text-slate-600 space-y-4 text-sm sm:text-base leading-relaxed pr-2 overflow-y-auto text-justify">
                <p class="font-bold text-slate-800">1. Aceptación de los Términos</p>
                <p>Al acceder y utilizar la plataforma <strong>Tu Inventario</strong>, usted acepta y se obliga a cumplir con los presentes Términos y Condiciones de Uso. Si no está de acuerdo con alguna parte, no deberá utilizar nuestros servicios.</p>

                <p class="font-bold text-slate-800">2. Descripción del Servicio</p>
                <p>Tu Inventario proporciona una plataforma en la nube para la gestión de inventario, punto de venta (POS), facturación, control de créditos, reportes financieros y e-commerce. Nos reservamos el derecho de modificar o discontinuar el servicio en cualquier momento.</p>

                <p class="font-bold text-slate-800">3. Registro de Cuenta y Seguridad</p>
                <p>Para utilizar las funciones de la plataforma, debe registrarse y mantener una cuenta activa. Usted es responsable de mantener la confidencialidad de su contraseña y de todas las actividades que ocurran bajo su cuenta.</p>

                <p class="font-bold text-slate-800">4. Propiedad Intelectual</p>
                <p>Todo el contenido de la plataforma, incluyendo software, logos, diseños, textos y gráficos, está protegido por derechos de propiedad intelectual propiedad de Tu Inventario. Queda prohibida su reproducción o distribución sin autorización previa.</p>

                <p class="font-bold text-slate-800">5. Limitación de Responsabilidad</p>
                <p>Tu Inventario no se hace responsable por pérdidas de datos, lucro cesante o daños indirectos resultantes de fallos técnicos, mal uso de la plataforma por parte del usuario o interrupciones en el servicio de internet.</p>
            </div>

            <div class="mt-8 pt-4 border-t border-gray-100 flex justify-end">
                <button onclick="document.getElementById('terms-modal').classList.add('hidden'); document.getElementById('terms-modal').classList.remove('flex')" class="bg-[#36B291] hover:bg-[#2c967a] text-white font-bold py-2.5 px-6 rounded-full transition-all text-sm focus:outline-none cursor-pointer">
                    Entendido
                </button>
            </div>
        </div>
    </div>

    <!-- Modal Políticas de Privacidad -->
    <div id="privacy-modal" class="hidden fixed inset-0 z-[200] items-center justify-center">
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" onclick="document.getElementById('privacy-modal').classList.add('hidden'); document.getElementById('privacy-modal').classList.remove('flex')"></div>
        
        <!-- Modal Content -->
        <div class="relative bg-white w-[95%] max-w-2xl max-h-[85vh] rounded-[30px] shadow-2xl p-8 md:p-10 z-10 overflow-y-auto flex flex-col">
            <button onclick="document.getElementById('privacy-modal').classList.add('hidden'); document.getElementById('privacy-modal').classList.remove('flex')" class="modal-close absolute top-4 right-4 z-10">
                <i class="fas fa-times"></i>
            </button>
            
            <div class="flex items-center gap-3 mb-6">
                <div class="w-12 h-12 rounded-2xl bg-teal-50 text-[#36B291] flex items-center justify-center text-xl">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h3 class="text-2xl font-black text-slate-800">Políticas de Privacidad</h3>
            </div>

            <div class="text-slate-600 space-y-4 text-sm sm:text-base leading-relaxed pr-2 overflow-y-auto text-justify">
                <p class="font-bold text-slate-800">1. Información que Recopilamos</p>
                <p>Recopilamos información personal necesaria para el funcionamiento de su cuenta (nombre, correo electrónico, teléfono) e información comercial relacionada con su negocio (productos, precios, transacciones, clientes) para proveer nuestros servicios de gestión.</p>

                <p class="font-bold text-slate-800">2. Uso de la Información</p>
                <p>La información recopilada se utiliza exclusivamente para: proveer y mantener la plataforma, personalizar su experiencia, procesar ventas y facturas, generar reportes de rendimiento y enviarle notificaciones críticas de la cuenta.</p>

                <p class="font-bold text-slate-800">3. Protección y Seguridad de Datos</p>
                <p>Implementamos medidas de seguridad técnicas, administrativas y físicas de primer nivel (incluyendo encriptación SSL y copias de seguridad automáticas) para proteger sus datos contra acceso no autorizado, alteración o pérdida.</p>

                <p class="font-bold text-slate-800">4. Confidencialidad y Compartición de Datos</p>
                <p>Tu Inventario garantiza que bajo ninguna circunstancia venderá, rentará ni compartirá sus datos comerciales o personales con terceros, excepto bajo requerimiento legal explícito.</p>

                <p class="font-bold text-slate-800">5. Derechos del Usuario</p>
                <p>Usted conserva todos los derechos sobre sus datos, incluyendo el derecho a exportar toda la información de su negocio (inventario, clientes, ventas) o solicitar la eliminación total de su cuenta y sus registros asociados en cualquier momento.</p>
            </div>

            <div class="mt-8 pt-4 border-t border-gray-100 flex justify-end">
                <button onclick="document.getElementById('privacy-modal').classList.add('hidden'); document.getElementById('privacy-modal').classList.remove('flex')" class="bg-[#36B291] hover:bg-[#2c967a] text-white font-bold py-2.5 px-6 rounded-full transition-all text-sm focus:outline-none cursor-pointer">
                    Entendido
                </button>
            </div>
        </div>
    </div>

    <!-- Script para Menú Móvil -->
    <script>
        document.body.addEventListener('submit', function(e) {
            var form = e.target;
            if (form.dataset.noLoader) return;
            
            var btn = e.submitter || form.querySelector('button[type="submit"]');
            if (btn) {
                // Preservar name y value del botón si los tiene, ya que al deshabilitarlo no se enviarán
                if (btn.name) {
                    var hidden = document.createElement('input');
                    hidden.type = 'hidden';
                    hidden.name = btn.name;
                    hidden.value = btn.value;
                    form.appendChild(hidden);
                }
                
                if (btn.dataset.loadingText) {
                    btn.dataset.originalText = btn.innerHTML;
                    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> ' + btn.dataset.loadingText;
                } else {
                    btn.dataset.originalText = btn.innerHTML;
                    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Procesando...';
                }
                
                // En lugar de usar btn.disabled = true, usamos pointer-events-none 
                // para no interrumpir el ciclo nativo de envío del formulario en móviles.
                setTimeout(function() {
                    btn.classList.add('opacity-75', 'cursor-wait', 'pointer-events-none');
                }, 10);
            }
        });

        document.getElementById('mobile-menu-btn').addEventListener('click', function() {
            const menu = document.getElementById('mobile-menu');
            if (menu.classList.contains('hidden')) {
                menu.classList.remove('hidden');
                menu.classList.add('flex');
            } else {
                menu.classList.add('hidden');
                menu.classList.remove('flex');
            }
        });
        
        // Cerrar menú móvil al hacer click en un enlace
        document.querySelectorAll('#mobile-menu a').forEach(link => {
            link.addEventListener('click', () => {
                document.getElementById('mobile-menu').classList.add('hidden');
                document.getElementById('mobile-menu').classList.remove('flex');
            });
        });

        // Efecto Header Flotante al Scrollear
        window.addEventListener('scroll', () => {
            const header = document.getElementById('main-header');
            if (window.scrollY > 50) {
                header.classList.remove('bg-transparent');
                header.classList.add('bg-white/70', 'backdrop-blur-md', 'shadow-sm', 'border-b', 'border-white/50');
            } else {
                header.classList.add('bg-transparent');
                header.classList.remove('bg-white/70', 'backdrop-blur-md', 'shadow-sm', 'border-b', 'border-white/50');
            }
        });

        // Auto-abrir modal si viene con ?login=1 en la URL o si hay error de credenciales
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('login') || document.querySelector('#loginForm .bg-red-50')) {
            document.getElementById('login-modal').classList.remove('hidden');
            document.getElementById('login-modal').classList.add('flex');
            
            // Limpiar la URL para evitar que se vuelva a abrir al recargar la página
            if (urlParams.has('login')) {
                urlParams.delete('login');
                const newUrl = window.location.pathname + (urlParams.toString() ? '?' + urlParams.toString() : '') + window.location.hash;
                window.history.replaceState({}, document.title, newUrl);
            }
        }
    </script>
    <script>
        // Registrar Service Worker para PWA
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('<?= BASE_URL ?>sw.js')
                    .then(registration => {
                        console.log('SW registrado con éxito:', registration.scope);
                    })
                    .catch(error => {
                        console.log('Fallo al registrar el SW:', error);
                    });
            });
            
            // Manejar la solicitud de instalación PWA
            window.addEventListener('beforeinstallprompt', (e) => {
                // No llamamos preventDefault para dejar que el navegador muestre su prompt nativo en Android/Desktop si lo soporta.
            });
        }
    </script>
</body>
</html>
