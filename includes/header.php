<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#10b981">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Tu Inventario">
    <link rel="icon" type="image/png" href="<?= BASE_URL ?>?serve_logo=1">
    <link rel="manifest" href="<?= BASE_URL ?>manifest.json">
    <link rel="apple-touch-icon" href="<?= BASE_URL ?>icons/icon-512x512.png">
    <title>Tu Inventario</title>
    <meta name="csrf-token" content="<?= $_SESSION['csrf_token'] ?? '' ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?? "" ?>css/tailwind.css">
    <script src="https://unpkg.com/htmx.org@1.9.11"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://unpkg.com/dexie@4.0.8/dist/dexie.js"></script>
    <script src="<?= BASE_URL ?>js/offline-sync.js"></script>
    <script>
        window.BASE_URL = "<?= BASE_URL ?>";
        if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }

        // Global CSRF Token Injection for AJAX/Fetch
        window.csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        const originalFetch = window.fetch;
        window.fetch = function() {
            let [resource, config] = arguments;
            if(config === undefined) config = {};
            if(config.method && ['POST', 'PUT', 'DELETE'].includes(config.method.toUpperCase())) {
                config.headers = {
                    ...config.headers,
                    'X-CSRF-Token': window.csrfToken
                };
            }
            return originalFetch(resource, config);
        };
        document.addEventListener('htmx:configRequest', function(evt) {
            evt.detail.headers['X-CSRF-Token'] = window.csrfToken;
        });
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        /* Gradient definitions (not expressible in Tailwind config) */
        .sidebar-scroll::-webkit-scrollbar { width: 4px; height: 6px; }
        .sidebar-scroll::-webkit-scrollbar-track { background: transparent; }
        .sidebar-scroll::-webkit-scrollbar-thumb { background: rgba(0,0,0,0.15); border-radius: 10px; }
        .sidebar-scroll::-webkit-scrollbar-thumb:hover { background: rgba(0,0,0,0.3); }
        .dark .sidebar-scroll::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.2); }
        .dark .sidebar-scroll::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,0.4); }
        .gradient-sidebar { background: linear-gradient(180deg, #064e3b 0%, #0e7490 100%); }
        .gradient-header { background: linear-gradient(135deg, #ecfdf5 0%, #ecfeff 100%); }
        .dark .gradient-header { background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); }

        /* Universal modal scroll fix (legacy support for existing modals) */
        .fixed.inset-0[class*="z-50"],
        .fixed.inset-0[class*="z-[60]"] {
            z-index: 9990 !important;
            overflow-y: auto !important;
            -webkit-overflow-scrolling: touch;
            height: 100dvh !important;
        }
        .fixed.inset-0 > .flex {
            min-height: 100% !important;
            align-items: flex-start !important;
            display: flex !important;
            padding: 2rem 1rem !important;
        }
        .fixed.inset-0 > .flex > .fixed.inset-0,
        .fixed.inset-0 > .flex > [class*="bg-gray-900"],
        .fixed.inset-0 > .flex > [class*="bg-slate-900"] {
            position: fixed !important;
            height: 100dvh !important;
            width: 100vw !important;
            top: 0 !important;
            left: 0 !important;
            z-index: -1 !important;
        }
        .fixed.inset-0 .inline-block.align-middle,
        .fixed.inset-0 > .flex > .relative[class*="max-w-"] {
            margin: auto !important;
            position: relative !important;
            height: auto !important;
            max-height: none !important;
            overflow: visible !important;
            width: 100% !important;
            z-index: 10 !important;
        }
        @media (max-width: 640px) {
            .fixed.inset-0 > .flex { padding: 1rem 0.5rem 3rem 0.5rem !important; }
            .fixed.inset-0 .inline-block.align-middle,
            .fixed.inset-0 > .flex > .relative[class*="max-w-"] { border-radius: 1rem !important; }
        }
    </style>
    <script>
        function urlBase64ToUint8Array(base64String) {
            const padding = '='.repeat((4 - base64String.length % 4) % 4);
            const base64 = (base64String + padding).replace(/\-/g, '+').replace(/_/g, '/');
            const rawData = window.atob(base64);
            const outputArray = new Uint8Array(rawData.length);
            for (let i = 0; i < rawData.length; ++i) outputArray[i] = rawData.charCodeAt(i);
            return outputArray;
        }

        async function subscribeToPush(silent = true) {
            if (!('serviceWorker' in navigator) || !('PushManager' in window)) return;
            try {
                const registration = await navigator.serviceWorker.ready;
                let subscription = await registration.pushManager.getSubscription();
                if (!subscription) {
                    const vapidPublicKey = "<?= VAPID_PUBLIC_KEY ?>";
                    if (!vapidPublicKey || vapidPublicKey.includes('VAPID_PUBLIC_KEY')) {
                        throw new Error("Clave VAPID no configurada");
                    }
                    subscription = await registration.pushManager.subscribe({
                        userVisibleOnly: true,
                        applicationServerKey: urlBase64ToUint8Array(vapidPublicKey)
                    });
                }
                
                await fetch('<?= BASE_URL ?>credits/push_subscribe', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(subscription)
                });
            } catch(e) { 
                console.error('Push error:', e);
                if (!silent && typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Error de Suscripción',
                        text: 'No pudimos activar las notificaciones. Verifica los permisos de tu navegador.',
                        confirmButtonColor: '#10b981'
                    });
                }
            }
        }

        function requestPushPermissionOnClick() {
            // Check for iOS Safari
            const isIos = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
            const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
            
            if (isIos && !isStandalone) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'info',
                        title: 'Instala la Aplicación',
                        html: 'Para recibir notificaciones en tu iPhone, primero debes instalar la app.<br><br>Toca el ícono de <b>Compartir</b> <svg style="display:inline; width:20px; height:20px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/><polyline points="16 6 12 2 8 6"/><line x1="12" y1="2" x2="12" y2="15"/></svg> y luego selecciona <b>Agregar a inicio</b>.',
                        confirmButtonColor: '#10b981',
                        confirmButtonText: 'Entendido'
                    });
                } else {
                    alert("Para recibir notificaciones en tu iPhone, debes agregar esta página a tu pantalla de inicio.");
                }
                return;
            }

            if (!('Notification' in window)) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Navegador No Soportado',
                        text: 'Tu navegador actual no soporta notificaciones push.',
                        confirmButtonColor: '#10b981'
                    });
                }
                return;
            }

            if (Notification.permission === 'default') {
                Notification.requestPermission().then(perm => {
                    if (perm === 'granted') {
                        subscribeToPush(false);
                    } else if (perm === 'denied' && typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Permiso Denegado',
                            text: 'Has bloqueado las notificaciones. Debes permitirlas desde la configuración de tu navegador.',
                            confirmButtonColor: '#10b981'
                        });
                    }
                });
            } else if (Notification.permission === 'granted') {
                subscribeToPush(false);
            } else if (Notification.permission === 'denied' && typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Permiso Denegado',
                    text: 'Las notificaciones están bloqueadas en tu navegador. Por favor actívalas manualmente.',
                    confirmButtonColor: '#10b981'
                });
            }
        }

        // Register PWA Service Worker
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('<?= BASE_URL ?>sw.js').then(reg => {
                    reg.update();
                    if ('Notification' in window && Notification.permission === 'granted') {
                        subscribeToPush(true);
                    }
                }).catch(() => {});
                
                navigator.serviceWorker.addEventListener('message', event => {
                    if (event.data && event.data.type === 'PLAY_NOTIFICATION_SOUND') {
                        // Refrescar el contador de notificaciones de HTMX (el cual disparará el sonido y toast visual)
                        document.body.dispatchEvent(new Event('notificationsUpdated'));
                    }
                });
            });
        }
        
        // ============================================================
        // IN-APP WHATSAPP-STYLE HEADS-UP TOAST NOTIFICATIONS
        // ============================================================
        window.lastNotifCount = undefined;

        function showInAppToast(title, msg) {
            const container = document.getElementById('toast-container');
            if(!container) return;
            
            const toast = document.createElement('div');
            toast.className = 'w-[90vw] sm:w-96 bg-white dark:bg-slate-800 rounded-2xl shadow-[0_10px_40px_-10px_rgba(0,0,0,0.3)] p-4 flex gap-4 transform -translate-y-full opacity-0 pointer-events-auto cursor-pointer border border-brand-500/30 mx-auto';
            toast.innerHTML = `
                <div class="w-10 h-10 bg-brand-100 dark:bg-brand-900/40 rounded-full flex items-center justify-center shrink-0 border border-brand-200 dark:border-brand-800">
                    <i class="fas fa-bell text-brand-500 text-lg"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <h4 class="text-sm font-bold text-gray-900 dark:text-white truncate">${title}</h4>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 line-clamp-2">${msg}</p>
                </div>
            `;
            
            toast.onclick = () => {
                const notifBtn = document.querySelector('[x-data="{ notifOpen: false }"] button');
                if(notifBtn) notifBtn.click();
                toast.classList.remove('translate-y-0', 'opacity-100');
                toast.classList.add('-translate-y-full', 'opacity-0');
                setTimeout(() => toast.remove(), 400);
            };

            container.appendChild(toast);
            
            requestAnimationFrame(() => {
                toast.classList.remove('-translate-y-full', 'opacity-0');
                toast.classList.add('translate-y-0', 'opacity-100');
                toast.style.transition = 'all 0.5s cubic-bezier(0.34, 1.56, 0.64, 1)';
            });
            
            setTimeout(() => {
                if(toast.parentElement) {
                    toast.classList.remove('translate-y-0', 'opacity-100');
                    toast.classList.add('-translate-y-full', 'opacity-0');
                    setTimeout(() => toast.remove(), 400);
                }
            }, 6000);
        }

        document.addEventListener('DOMContentLoaded', () => {
            document.body.addEventListener('htmx:afterSettle', (e) => {
                if (e.detail.target.id === 'notif-badge') {
                    let newCount = parseInt(e.detail.target.innerText) || 0;
                    
                    if (window.lastNotifCount === undefined) {
                        window.lastNotifCount = newCount;
                        return;
                    }
                    
                    if (newCount > window.lastNotifCount) {
                        showInAppToast("Notificación Entrante", "Tienes alertas nuevas. Toca aquí para ver de qué se trata.");
                        
                        // Play Synthesized sound
                        try {
                            const AudioContext = window.AudioContext || window.webkitAudioContext;
                            const ctx = new AudioContext();
                            const osc = ctx.createOscillator();
                            const gain = ctx.createGain();
                            osc.connect(gain);
                            gain.connect(ctx.destination);
                            osc.type = 'sine';
                            osc.frequency.setValueAtTime(800, ctx.currentTime);
                            osc.frequency.exponentialRampToValueAtTime(1200, ctx.currentTime + 0.1);
                            gain.gain.setValueAtTime(0.5, ctx.currentTime);
                            gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.3);
                            osc.start(ctx.currentTime);
                            osc.stop(ctx.currentTime + 0.3);
                            if (navigator.vibrate) navigator.vibrate([200, 100, 200]);
                        } catch(err) { console.warn('Audio Context blocked', err); }
                    }
                    window.lastNotifCount = newCount;
                }
            });
        });

        // Custom PWA Install Prompt
        let deferredPrompt;
        window.addEventListener('beforeinstallprompt', (e) => {
            // Prevent the mini-infobar from appearing on mobile
            e.preventDefault();
            // Stash the event so it can be triggered later.
            deferredPrompt = e;
            // Update UI notify the user they can install the PWA
            if (!localStorage.getItem('pwa_declined')) {
                const installBanner = document.getElementById('install-pwa-banner');
                if (installBanner) {
                    installBanner.classList.remove('hidden');
                    installBanner.classList.add('flex');
                }
            }
        });

        function installPWA() {
            const installBanner = document.getElementById('install-pwa-banner');
            if(installBanner) installBanner.classList.add('hidden');
            if (deferredPrompt) {
                deferredPrompt.prompt();
                deferredPrompt.userChoice.then((choiceResult) => {
                    deferredPrompt = null;
                });
            }
        }

        function installPWAManual() {
            if (deferredPrompt) {
                deferredPrompt.prompt();
                deferredPrompt.userChoice.then(() => { deferredPrompt = null; });
            } else {
                let msg = "Para instalar la App permanentemente:\n\n";
                if (/iPhone|iPad|iPod/.test(navigator.userAgent)) {
                    msg += "En Safari (iPhone/iPad): Toca el botón 'Compartir' (el cuadrado con la flecha hacia arriba) en la parte inferior, y luego selecciona 'Agregar a inicio'.";
                } else {
                    msg += "En Android/PC: Abre el menú de opciones (⋮) en la esquina superior derecha de tu navegador y selecciona 'Instalar aplicación' o 'Agregar a la pantalla principal'.";
                }
                alert(msg);
            }
        }

        function dismissInstallPWA() {
            const installBanner = document.getElementById('install-pwa-banner');
            if(installBanner) installBanner.classList.add('hidden');
            localStorage.setItem('pwa_declined', 'true');
        }
    </script>

    <?php
    require_once __DIR__ . '/../core/FlashMessage.php';
    if (FlashMessage::has()): 
        $flashMessages = FlashMessage::get();
    ?>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            <?php foreach ($flashMessages as $msg): ?>
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: '<?= htmlspecialchars($msg['type']) ?>',
                    title: '<?= addslashes(htmlspecialchars($msg['message'])) ?>',
                    showConfirmButton: false,
                    timer: 5000,
                    timerProgressBar: true,
                    showCloseButton: true
                });
            <?php endforeach; ?>
        });
    </script>
    <?php endif; ?>
</head>
<body class="bg-gray-50 text-gray-800 dark:bg-slate-950 dark:text-gray-100 antialiased flex flex-col overflow-hidden" style="height: 100vh; height: 100dvh;">

    <!-- Toast Container para notificaciones in-app -->
    <div id="toast-container" class="fixed top-4 left-0 right-0 z-[100000] pointer-events-none flex flex-col gap-2 transition-all"></div>

    <?php if(isset($_SESSION['superadmin_snapshot'])): ?>
    <div class="bg-red-600 text-white font-bold text-center py-2.5 px-4 shadow-[0_0_30px_rgba(220,38,38,0.8)] flex flex-col sm:flex-row justify-center sm:justify-between items-center gap-3 z-[9999999] relative">
        <span class="text-sm"><i class="fas fa-user-secret mr-2"></i> ⚠️ Estás suplantando a <b><?= htmlspecialchars($_SESSION['username']) ?></b>.</span>
        <a href="<?= BASE_URL ?>superadmin/unimpersonate" class="bg-white text-red-600 px-4 py-1.5 rounded-full hover:bg-gray-100 transition-colors text-xs shadow-md whitespace-nowrap"><i class="fas fa-sign-out-alt mr-1"></i> Volver al Modo Dios</a>
    </div>
    <?php endif; ?>

    <!-- PWA Install Banner -->
    <div id="install-pwa-banner" class="hidden fixed top-4 sm:top-auto sm:bottom-4 left-4 right-4 z-[99999] bg-gradient-to-r from-brand-600 to-brand-800 text-white p-4 rounded-2xl shadow-2xl flex-col sm:flex-row items-center justify-between gap-4 border border-brand-500">
        <div class="flex items-center gap-4 w-full sm:w-auto">
            <div class="w-12 h-12 bg-white rounded-xl flex items-center justify-center p-1.5 shrink-0 shadow-sm relative overflow-hidden">
                <img src="<?= BASE_URL ?>icons/icon-192x192.png" alt="App Icon" class="w-full h-full object-cover">
            </div>
            <div class="flex-1">
                <h4 class="font-bold text-sm leading-tight text-white">Instalar App TuInventario</h4>
                <p class="text-xs text-brand-100 mt-1 opacity-90">Experimenta navegación offline, ultra rapidez y notificaciones nativas.</p>
            </div>
        </div>
        <div class="flex items-center gap-2 w-full sm:w-auto mt-2 sm:mt-0">
            <button onclick="dismissInstallPWA()" class="flex-1 sm:flex-none border border-white/20 hover:bg-white/10 px-4 py-2 text-xs font-bold rounded-xl transition-colors">
                Ahora no
            </button>
            <button onclick="installPWA()" class="flex-1 sm:flex-none bg-white text-brand-700 hover:bg-gray-100 hover:scale-105 px-4 py-2 text-xs font-bold rounded-xl shadow-lg transition-all flex items-center justify-center">
                <i class="fas fa-download mr-2"></i> Instalar Ahora
            </button>
        </div>
    </div>

    <!-- ================================================================
         SISTEMA DE SEGURIDAD DE SESIÓN v2.0
         - Session Idle Timeout (Tiempo de Inactividad de Sesión)
         - Page Visibility API (Detección de Cierre de Pestaña)
         - BFCache Invalidation (Invalidación de Caché del Botón Atrás)
         ================================================================ -->
    <?php if(isset($_SESSION['user_id'])): ?>
    <div id="session-warning-modal" class="fixed inset-0 z-[99999] bg-slate-900/80 backdrop-blur-sm hidden items-center justify-center p-4">
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl max-w-sm w-full p-6 text-center transform scale-95 opacity-0 transition-all duration-300" id="session-warning-box">
            <div class="mb-4 inline-flex items-center justify-center w-16 h-16 rounded-full bg-yellow-100 text-yellow-600 dark:bg-yellow-900/30 dark:text-yellow-500">
                <i class="fas fa-lock text-3xl"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-800 dark:text-white mb-2">Inactividad Detectada</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Por su seguridad, su sesión se cerrará en <span id="session-countdown" class="font-bold text-red-500">30</span> segundos si no hay movimiento.</p>
            <button onclick="extendSession()" class="w-full bg-brand-600 hover:bg-brand-500 text-white font-bold py-3 rounded-xl transition-colors shadow-lg shadow-brand-500/30">
                <i class="fas fa-hand-pointer mr-2"></i> Mantener Sesión Activa
            </button>
        </div>
    </div>

    <script>
    (function() {
        'use strict';
        const LOGOUT_URL = '<?= BASE_URL ?>auth/logout';
        const AUTH_URL   = '<?= BASE_URL ?>auth';


        // ============================================================
        // 2. BACK BUTTON TRAP - Trampa Estricta del Botón Atrás
        //    Inyecta un estado artificial en el historial. Si el usuario
        //    toca las flechas de navegación (Atrás o Adelante), el evento
        //    'popstate' lo atrapa y destruye la sesión permanentemente.
        // ============================================================
        window.history.pushState({ noBack: true }, '', window.location.href);
        window.addEventListener('popstate', function(event) {
            // El usuario presionó Atrás o Adelante en su navegador o móvil
            fetch(LOGOUT_URL, { method: 'POST' }).finally(function() {
                window.location.replace(AUTH_URL);
            });
        });

        // BFCache Invalidation Backup
        window.addEventListener('pageshow', function(event) {
            if (event.persisted) {
                window.location.reload();
            }
        });

        // ============================================================
        // 3. SESSION IDLE TIMEOUT - Temporizador de Inactividad
        // ============================================================
        var inactivityTimer, warningTimer, countdownInterval;
        var WARNING_TIME = 270000; // 4:30 minutos
        var LOGOUT_TIME  = 300000; // 5:00 minutos

        function showWarningModal() {
            var modal = document.getElementById('session-warning-modal');
            var box   = document.getElementById('session-warning-box');
            var countSpan = document.getElementById('session-countdown');
            var secondsLeft = 30;

            modal.classList.remove('hidden');
            modal.classList.add('flex');
            setTimeout(function() {
                box.classList.remove('scale-95', 'opacity-0');
                box.classList.add('scale-100', 'opacity-100');
            }, 10);

            countdownInterval = setInterval(function() {
                secondsLeft--;
                countSpan.innerText = secondsLeft;
                if (secondsLeft <= 0) clearInterval(countdownInterval);
            }, 1000);
        }

        window.extendSession = function() {
            var modal = document.getElementById('session-warning-modal');
            var box   = document.getElementById('session-warning-box');

            box.classList.remove('scale-100', 'opacity-100');
            box.classList.add('scale-95', 'opacity-0');
            setTimeout(function() {
                modal.classList.remove('flex');
                modal.classList.add('hidden');
            }, 300);

            resetInactivityTimer();
        };

        function killSession() {
            fetch(LOGOUT_URL, { method: 'POST' }).finally(function() {
                window.location.href = AUTH_URL;
            });
        }

        function resetInactivityTimer() {
            clearTimeout(inactivityTimer);
            clearTimeout(warningTimer);
            clearInterval(countdownInterval);

            warningTimer  = setTimeout(showWarningModal, WARNING_TIME);
            inactivityTimer = setTimeout(killSession, LOGOUT_TIME);
        }

        // Eventos de interacción: Mouse, Teclado, Táctil (Móvil/Tablet/PC)
        ['mousemove', 'keydown', 'touchstart', 'scroll', 'click'].forEach(function(evt) {
            document.addEventListener(evt, resetInactivityTimer, { passive: true });
        });

        // Iniciar el temporizador
        resetInactivityTimer();
    })();
    </script>
    <?php endif; ?>
    <!-- FIN SISTEMA DE SEGURIDAD DE SESIÓN -->

    <!-- PRINT HEADER (only visible on Ctrl+P) -->
    <?php 
        // Fetch Tenant Business Info for Prints
        $printBizName = 'Tu Inventario';
        $printLogo = null;
        $printAddress = null;
        if(isset($_SESSION['business_id'])) {
            require_once __DIR__ . '/../config/Database.php';
            $dbPrint = Database::getInstance()->getConnection();
            $stmtPrint = $dbPrint->prepare("SELECT business_name, logo_base64, ticket_header FROM businesses WHERE id = ?");
            $stmtPrint->execute([$_SESSION['business_id']]);
            $bizDataPrint = $stmtPrint->fetch(PDO::FETCH_ASSOC);
            if($bizDataPrint) {
                $printBizName = $bizDataPrint['business_name'] ?: 'Tu Inventario';
                $printLogo = $bizDataPrint['logo_base64'];
                $printAddress = $bizDataPrint['ticket_header'];
            }
        }
    ?>
    <div class="print-header" style="display:none;">
        <?php if(!empty($printLogo)): ?>
            <img src="<?= $printLogo ?>" alt="Logo Print" style="max-height: 60px; margin: 0 auto 10px; display: block; object-fit: contain;">
        <?php endif; ?>
        <h1><?= htmlspecialchars($printBizName) ?></h1>
        <?php if(!empty($printAddress)): ?>
            <p style="white-space: pre-line; line-height: 1.2; margin-top: 5px; font-weight: bold;"><?= htmlspecialchars($printAddress) ?></p>
        <?php endif; ?>
        <p>Reporte generado el <?= date('d/m/Y H:i') ?> | Usuario: <?= htmlspecialchars($_SESSION['username'] ?? 'Sistema') ?></p>
    </div>

    <!-- Wrapper -->
    <div class="w-full flex-1 flex flex-col overflow-hidden" style="height: 100vh; height: 100dvh;">
        <!-- Top Header -->
        <header class="gradient-header relative z-[70] px-4 h-[68px] flex justify-between items-center transition-colors shrink-0">
            <div class="flex items-center min-w-0 flex-1">
                <div class="flex items-center space-x-3 min-w-0">
                    <div class="w-10 h-10 bg-white/50 dark:bg-slate-800/50 backdrop-blur rounded-xl flex items-center justify-center shadow-sm p-1 shrink-0">
                        <img src="<?= BASE_URL ?>?serve_logo=1" alt="Logo" class="w-full h-full object-contain">
                    </div>
                    <div class="flex flex-col -mt-0.5 max-w-[150px] sm:max-w-[300px] truncate">
                        <h1 class="text-[17px] font-extrabold tracking-tight leading-none mb-0.5 truncate">
                            <span class="text-slate-800 dark:text-white">Tu</span> <span class="bg-gradient-to-r from-brand-500 to-accent-500 bg-clip-text text-transparent">Inventario</span>
                        </h1>
                        <span class="text-[9px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.2em] leading-none">Control Total</span>
                    </div>
                </div>
            </div>

            <!-- Right side controls -->
            <div class="flex items-center space-x-2 sm:space-x-3 ml-auto shrink-0 pl-2">
                <!-- BCV Rate Badge -->
                <div class="hidden md:flex items-center bg-white dark:bg-slate-800 border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-1.5 text-xs font-bold text-gray-600 dark:text-gray-300 shadow-sm">
                    <i class="fas fa-coins text-brand-500 mr-2"></i>
                    BCV: Bs <?= number_format((float)Settings::getBcvRate(), 2) ?>
                </div>

                <!-- Ver mi tienda -->
                <?php 
                if (!empty($_SESSION['business_id'])) {
                    // Auto-hidratar el slug si falta en la sesión
                    if (empty($_SESSION['business_slug'])) {
                        require_once __DIR__ . '/../config/Database.php';
                        $db = Database::getInstance()->getConnection();
                        $stmt = $db->prepare("SELECT slug FROM businesses WHERE id = ?");
                        $stmt->execute([$_SESSION['business_id']]);
                        $fetchedSlug = $stmt->fetchColumn();
                        if ($fetchedSlug) {
                            $_SESSION['business_slug'] = $fetchedSlug;
                        } else {
                            $_SESSION['business_slug'] = $_SESSION['business_id'];
                        }
                    }
                ?>
                <a href="<?= BASE_URL ?>tienda/<?= $_SESSION['business_slug'] ?? $_SESSION['business_id'] ?>" target="_blank" class="hidden sm:flex items-center bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 text-white font-bold py-1.5 px-4 rounded-lg shadow-sm text-xs transition-all gap-2">
                    <i class="fas fa-store"></i>
                    Ver mi tienda
                </a>
                <?php } ?>

                <!-- Notification Bell (FASE 3) -->
                <div class="relative" x-data="{ notifOpen: false }" @click.away="notifOpen = false">
                    <button @click="requestPushPermissionOnClick(); notifOpen = !notifOpen; if(notifOpen) { htmx.trigger('#notif-list', 'load') }" class="relative text-gray-500 hover:bg-white dark:text-gray-400 dark:hover:bg-slate-800 w-9 h-9 rounded-lg flex items-center justify-center transition-all focus:outline-none shadow-sm border border-gray-200 dark:border-gray-700">
                        <i class="fas fa-bell text-sm"></i>
                        <span id="notif-badge" hx-get="<?= BASE_URL ?>credits/notifications_count" hx-trigger="load, every 30s, notificationsUpdated from:body" hx-swap="innerHTML"></span>
                    </button>

                    <!-- Dropdown Notifications -->
                    <div x-show="notifOpen" x-transition class="fixed left-2 right-2 top-[60px] sm:absolute sm:inset-auto sm:top-full sm:right-0 mt-1 sm:mt-2 w-auto sm:w-96 bg-white dark:bg-slate-800 rounded-xl shadow-2xl border border-gray-100 dark:border-gray-700 z-50 overflow-hidden" style="display: none;">
                        <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between bg-gray-50 dark:bg-gray-700/50">
                            <h4 class="text-sm font-bold text-gray-800 dark:text-white flex items-center gap-2">
                                <i class="fas fa-bell text-brand-500"></i> Notificaciones
                            </h4>
                            <button hx-post="<?= BASE_URL ?>credits/notifications_read_all" hx-swap="none" class="text-xs font-bold text-brand-600 hover:text-brand-700 dark:text-brand-400 transition-colors">
                                Marcar todas
                            </button>
                        </div>
                        <div id="notif-list" class="max-h-80 overflow-y-auto"
                             hx-get="<?= BASE_URL ?>credits/notifications" hx-trigger="load, notificationsUpdated from:body" hx-swap="innerHTML">
                            <div class="p-6 text-center text-gray-400"><i class="fas fa-spinner fa-spin"></i></div>
                        </div>
                    </div>
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
                            <?= strtoupper(substr($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'U', 0, 1)) ?>
                        </div>
                        <span class="font-semibold hidden sm:block text-sm"><?= htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Usuario') ?></span>
                        <i class="fas fa-chevron-down text-[10px] ml-0.5 text-gray-400"></i>
                    </button>
                    
                    <div x-show="open" x-transition class="absolute right-0 mt-2 w-52 bg-white dark:bg-slate-800 rounded-xl shadow-xl border border-gray-100 dark:border-gray-700 py-1.5 z-50">
                        <div class="px-4 py-2 border-b border-gray-100 dark:border-gray-700">
                            <p class="text-sm font-bold text-gray-800 dark:text-white"><?= htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Usuario') ?></p>
                            <p class="text-xs text-gray-400 capitalize"><?= $_SESSION['role'] ?? 'cajero' ?></p>
                        </div>
                        <a href="<?= BASE_URL ?><?= ($_SESSION['role'] ?? '') === 'super_admin' ? 'superadmin/profile' : 'users' ?>" class="flex items-center px-4 py-2.5 text-sm text-gray-700 dark:text-gray-200 hover:bg-brand-50 dark:hover:bg-brand-900/20 hover:text-brand-600 transition-colors">
                            <i class="fas fa-user-circle mr-3 text-gray-400 w-4"></i> Mi Perfil
                        </a>
                        <?php if(($_SESSION['role'] ?? '') === 'administrador'): ?>
                        <a href="<?= BASE_URL ?>settings" class="flex items-center px-4 py-2.5 text-sm text-gray-700 dark:text-gray-200 hover:bg-brand-50 dark:hover:bg-brand-900/20 hover:text-brand-600 transition-colors">
                            <i class="fas fa-cog mr-3 text-gray-400 w-4"></i> Configuración
                        </a>
                        <button onclick="installPWAManual()" class="w-full text-left flex items-center px-4 py-2.5 text-sm text-gray-700 dark:text-gray-200 hover:bg-brand-50 dark:hover:bg-brand-900/20 hover:text-brand-600 transition-colors">
                            <i class="fas fa-mobile-alt mr-3 text-gray-400 w-4"></i> Instalar como App
                        </button>
                        <?php endif; ?>
                        <div class="border-t border-gray-100 dark:border-gray-700 my-1"></div>
                        <a href="<?= BASE_URL ?>auth/logout" class="flex items-center px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors font-medium">
                            <i class="fas fa-sign-out-alt mr-3 w-4"></i> Cerrar Sesión
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <!-- Horizontal Nav Menu (Bubble/Subtle Indicator Style) -->
        <nav class="bg-white/95 backdrop-blur-md dark:bg-slate-900/95 border-t sm:border-t-0 sm:border-b border-gray-200 dark:border-gray-800 shrink-0 z-[60] fixed bottom-0 left-0 right-0 sm:relative sm:bottom-auto w-full" style="padding-bottom: max(0.5rem, env(safe-area-inset-bottom, 0px));">
            <div class="flex space-x-1 sm:space-x-2 overflow-x-auto whitespace-nowrap px-4 py-2 sidebar-scroll items-center min-h-[56px]">
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

                $userRole = $_SESSION['role'] ?? 'vendedor';

                if ($userRole === 'super_admin') {
                    $menuItems = [
                        ['superadmin', 'fa-globe', 'Visión General', null],
                        ['superadmin/tenants', 'fa-building', 'Negocios', null],
                        ['superadmin/users', 'fa-users-cog', 'Usuarios', null],
                        ['superadmin/backups', 'fa-database', 'Respaldos', null],
                        ['superadmin/profile', 'fa-user-shield', 'Identidad', null]
                    ];
                } else {
                    $menuItems = [
                        ['dashboard',  'fa-tachometer-alt', 'Panel de Control',  null],
                        ['inventory',  $inventoryIcon,      $inventoryLabel,     'inventory'],
                        ['restaurant', 'fa-utensils',       'Platos',            'inventory'],
                        ['sales',      $salesIcon,          $salesLabel,         'pos'],
                        ['purchases',  'fa-cart-arrow-down', 'Compras',          'inventory'],
                        ['suppliers',  'fa-truck',           'Proveedores',      'inventory'],
                        ['clients',    'fa-users',           'Clientes',         'clients'],
                        ['credits',    'fa-hand-holding-usd','Créditos',         'pos'],
                        ['expenses',   'fa-money-bill-wave', 'Gastos',           'reports'],
                        ['cashbox',    'fa-wallet',          'Arqueo de Caja',   'pos'],
                        ['reports',    'fa-chart-line',      'Reportes',         'reports'],
                    ];
                }
                
                $userPerms = $_SESSION['permissions'] ?? [];
                $isAdmin = ($userRole === 'administrador' || $userRole === 'super_admin');
                
                foreach ($menuItems as [$route, $icon, $label, $requiredPerm]):
                    if ($requiredPerm && !$isAdmin && !in_array($requiredPerm, $userPerms)) continue;
                    
                    $isActive = strpos($uri, $route) !== false || ($route === 'dashboard' && rtrim($uri, '/') === rtrim(BASE_URL, '/'));
                    
                    if ($isActive) {
                        $activeClass = 'bg-brand-50 text-brand-600 dark:bg-brand-900/40 dark:text-brand-300 font-bold rounded-xl transition-all text-sm px-4 py-2 border border-brand-100 dark:border-brand-800 shadow-sm';
                    } else {
                        $activeClass = 'text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 hover:bg-gray-50 dark:hover:bg-slate-800 font-medium rounded-xl transition-all text-sm px-4 py-2 border border-transparent';
                    }
                ?>
                <a href="<?= BASE_URL ?><?= $route ?>" class="inline-flex items-center <?= $activeClass ?>">
                    <i class="fas <?= $icon ?> mr-2"></i>
                    <?= $label ?>
                </a>
                <?php endforeach; ?>
                
                <?php if($userRole !== 'super_admin'): ?>
                <div class="h-6 w-px bg-gray-200 dark:bg-slate-700 mx-1 shrink-0"></div> <!-- Divider -->
                
                <?php if($isAdmin || in_array('inventory', $userPerms)): ?>
                <a href="<?= BASE_URL ?>reports?tab=kardex" class="inline-flex items-center text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 hover:bg-gray-50 dark:hover:bg-slate-800 font-medium rounded-xl transition-all text-sm px-4 py-2 border border-transparent">
                    <i class="fas fa-exchange-alt mr-2 hover:animate-spin"></i> Kardex
                </a>
                <?php endif; ?>

                <?php if($isAdmin || in_array('reports', $userPerms)): 
                    $auditoriaActive = strpos($uri, 'auditoria') !== false;
                    $auditoriaClass = $auditoriaActive 
                        ? 'bg-blue-50 text-blue-600 dark:bg-blue-900/40 dark:text-blue-300 font-bold border-blue-100 dark:border-blue-800 shadow-sm' 
                        : 'text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 hover:bg-gray-50 dark:hover:bg-slate-800 font-medium border-transparent';
                ?>
                <a href="<?= BASE_URL ?>reports/auditoria" class="inline-flex items-center rounded-xl transition-all text-sm px-4 py-2 border <?= $auditoriaClass ?>">
                    <i class="fas fa-history mr-2"></i> Auditoría
                </a>
                <?php endif; ?>
                
                <?php if($isAdmin || in_array('settings', $userPerms)): 
                    $storefrontActive = $uri === '/storefront' || $uri === '/storefront/';
                    $storefrontClass = $storefrontActive 
                        ? 'bg-purple-50 text-purple-600 dark:bg-purple-900/40 dark:text-purple-300 font-bold border-purple-100 dark:border-purple-800 shadow-sm' 
                        : 'text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 hover:bg-gray-50 dark:hover:bg-slate-800 font-medium border-transparent';
                        
                    $ordersActive = strpos($uri, 'storefront/orders') !== false;
                    $ordersClass = $ordersActive 
                        ? 'bg-yellow-50 text-yellow-600 dark:bg-yellow-900/40 dark:text-yellow-300 font-bold border-yellow-100 dark:border-yellow-800 shadow-sm' 
                        : 'text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 hover:bg-gray-50 dark:hover:bg-slate-800 font-medium border-transparent';
                        
                    $settingsActive = strpos($uri, 'settings') !== false;
                    $settingsClass = $settingsActive 
                        ? 'bg-brand-50 text-brand-600 dark:bg-brand-900/40 dark:text-brand-300 font-bold border-brand-100 dark:border-brand-800 shadow-sm' 
                        : 'text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 hover:bg-gray-50 dark:hover:bg-slate-800 font-medium border-transparent';
                ?>
                <a href="<?= BASE_URL ?>storefront" class="inline-flex items-center rounded-xl transition-all text-sm px-4 py-2 border <?= $storefrontClass ?>">
                    <i class="fas fa-store mr-2"></i> Mi Tienda
                </a>
                <a href="<?= BASE_URL ?>storefront/orders" class="inline-flex items-center rounded-xl transition-all text-sm px-4 py-2 border <?= $ordersClass ?>">
                    <i class="fas fa-shopping-bag mr-2"></i> Pedidos Tienda
                </a>
                <a href="<?= BASE_URL ?>settings" class="inline-flex items-center rounded-xl transition-all text-sm px-4 py-2 border <?= $settingsClass ?>">
                    <i class="fas fa-cog mr-2"></i> Configuración
                </a>
                <?php endif; ?>
                
                <?php if($userRole === 'administrador'): 
                    $suscActive = strpos($uri, 'suscription') !== false;
                    $suscClass = $suscActive 
                        ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-900/40 dark:text-emerald-300 font-bold border-emerald-100 dark:border-emerald-800 shadow-sm' 
                        : 'text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 hover:bg-gray-50 dark:hover:bg-slate-800 font-medium border-transparent';
                ?>
                <a href="<?= BASE_URL ?>suscription" class="inline-flex items-center rounded-xl transition-all text-sm px-4 py-2 border <?= $suscClass ?>">
                    <i class="fas fa-gem mr-2"></i> Suscripción
                </a>
                <?php endif; 
                endif; // end if not super_admin ?>

                
                <div class="pe-4 shrink-0 px-2 text-transparent">.</div>
            </div>
        </nav>

        <script>
            // Asegurar que el scroll horizontal siempre muestre la opción activa al cargar
            (function() {
                function centerNavScroll() {
                    const navScroll = document.querySelector('.sidebar-scroll');
                    const activeLink = navScroll ? Array.from(navScroll.querySelectorAll('a')).find(a => !a.className.includes('border-transparent')) : null;
                    if (navScroll && activeLink) {
                        setTimeout(() => {
                            navScroll.scrollTo({
                                left: activeLink.offsetLeft - (navScroll.clientWidth / 2) + (activeLink.clientWidth / 2),
                                behavior: 'auto'
                            });
                        }, 50);
                    }
                }
                
                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', centerNavScroll);
                } else {
                    centerNavScroll();
                }
            })();
        </script>

        <!-- Main Content Area -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100/80 dark:bg-slate-900 p-4 pb-[100px] sm:pb-4 md:p-6 transition-colors relative">
