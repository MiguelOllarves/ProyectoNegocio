        </main>
    </div>

    <!-- Overlay for mobile sidebar -->
    <div id="sidebar-overlay" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-30 hidden lg:hidden transition-opacity"></div>

    <!-- PRINT FOOTER (only visible on Ctrl+P) -->
    <div class="print-footer" style="display:none;">
        Generado por Tu Inventario &mdash; <?= date('d/m/Y H:i') ?>
    </div>

    <!-- Global: Payment Approval Modal (FASE 3) -->
    <div id="approval-modal" x-data="{ show: false, paymentId: null, notifId: null }" 
         @open-approval.window="show = true; paymentId = $event.detail.paymentId; notifId = $event.detail.notifId; htmx.ajax('GET', '<?= BASE_URL ?>credits/payment_detail/' + $event.detail.paymentId, '#approval-body')"
         x-show="show" class="fixed inset-0 z-[60] overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4 py-8">
            <div x-show="show" x-transition.opacity class="fixed inset-0 bg-gray-900/75 backdrop-blur-sm" @click="show = false"></div>
            <div x-show="show" x-transition class="relative w-full max-w-md bg-white dark:bg-gray-800 rounded-2xl shadow-2xl overflow-hidden">
                <div class="p-5 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <i class="fas fa-clipboard-check text-brand-500"></i> Aprobar Pago
                    </h3>
                    <button @click="show = false" class="w-8 h-8 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center justify-center text-gray-400">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div id="approval-body" class="p-5">
                    <div class="p-6 text-center text-gray-400"><i class="fas fa-spinner fa-spin text-xl"></i></div>
                </div>
                <?php if (($_SESSION['role'] ?? '') === 'administrador'): ?>
                <div class="p-5 border-t border-gray-100 dark:border-gray-700 flex gap-3">
                    <form hx-post="<?= BASE_URL ?>credits/reject" hx-swap="none" class="flex-1"
                          @htmx:after-request="if($event.detail.successful) { show = false }">
                        <input type="hidden" name="payment_id" :value="paymentId">
                        <button type="submit" class="w-full px-4 py-2.5 rounded-xl font-bold text-red-600 bg-red-50 dark:bg-red-900/20 hover:bg-red-100 dark:hover:bg-red-900/40 transition-colors text-sm flex items-center justify-center gap-2">
                            <i class="fas fa-times"></i> Rechazar
                        </button>
                    </form>
                    <form hx-post="<?= BASE_URL ?>credits/approve" hx-swap="none" class="flex-1"
                          @htmx:after-request="if($event.detail.successful) { show = false }">
                        <input type="hidden" name="payment_id" :value="paymentId">
                        <input type="hidden" name="notification_id" :value="notifId">
                        <button type="submit" class="w-full px-4 py-2.5 rounded-xl font-bold text-white bg-emerald-600 hover:bg-emerald-700 transition-colors text-sm shadow-sm flex items-center justify-center gap-2">
                            <i class="fas fa-check"></i> Aprobar Pago
                        </button>
                    </form>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // ===== UNIFIED DOM INIT =====
        document.addEventListener('DOMContentLoaded', function() {
            // --- Sidebar Toggle ---
            var btnMenu = document.getElementById('mobile-menu-btn');
            var sidebar = document.getElementById('sidebar');
            var btnClose = document.getElementById('close-sidebar');
            var overlay = document.getElementById('sidebar-overlay');

            function openSidebar() {
                if (sidebar) sidebar.classList.remove('-translate-x-full');
                if (overlay) overlay.classList.remove('hidden');
                document.body.style.overflow = 'hidden';
            }
            function closeSidebar() {
                if (sidebar) sidebar.classList.add('-translate-x-full');
                if (overlay) overlay.classList.add('hidden');
                document.body.style.overflow = '';
            }

            if(btnMenu) btnMenu.addEventListener('click', openSidebar);
            if(btnClose) btnClose.addEventListener('click', closeSidebar);
            if(overlay) overlay.addEventListener('click', closeSidebar);

            if (sidebar) {
                sidebar.querySelectorAll('a[href]').forEach(function(link) {
                    link.addEventListener('click', function() {
                        if (window.innerWidth < 1024) closeSidebar();
                    });
                });
            }
            
            // --- Theme Toggle ---
            var themeBtn = document.getElementById('theme-toggle');
            if (themeBtn) {
                themeBtn.addEventListener('click', function() {
                    document.documentElement.classList.toggle('dark');
                    localStorage.setItem('theme', document.documentElement.classList.contains('dark') ? 'dark' : 'light');
                });
            }

            // --- Global Table Search and Pagination ---
            function initTableSearch() {
                document.querySelectorAll('[data-table-search]').forEach(function(input) {
                    if (input._searchBound) return;
                    input._searchBound = true;
                    input.addEventListener('input', function() {
                        var query = this.value.toLowerCase().trim();
                        var tbody = document.querySelector(this.getAttribute('data-table-search'));
                        if (!tbody) return;
                        
                        var rows = Array.from(tbody.querySelectorAll('tr:not(.no-search)'));
                        
                        rows.forEach(function(row) {
                            if (!query || row.textContent.toLowerCase().includes(query)) {
                                row.classList.remove('search-hidden');
                            } else {
                                row.classList.add('search-hidden');
                            }
                        });
                        
                        if(tbody.dataset.paginated) {
                            tbody.dataset.currentPage = "1"; // Reset to first page on search
                            paginateTable(tbody);
                        } else {
                            rows.forEach(function(row) {
                                row.style.display = row.classList.contains('search-hidden') ? 'none' : '';
                            });
                        }
                    });
                });
            }

            function setupPagination() {
                document.querySelectorAll('table tbody').forEach(function(tbody) {
                    if(tbody._paginationBound) return;
                    var rows = tbody.querySelectorAll('tr:not(.no-search)');
                    if (rows.length > 10 && !tbody.closest('.no-paginate')) {
                        tbody._paginationBound = true;
                        tbody.dataset.paginated = "true";
                        tbody.dataset.currentPage = "1";
                        tbody.dataset.pageSize = "10";
                        
                        var table = tbody.closest('table');
                        var paginator = document.createElement('div');
                        paginator.className = 'table-paginator flex flex-col sm:flex-row justify-between items-center px-4 py-3 border-t border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50';
                        table.parentNode.insertBefore(paginator, table.nextSibling);
                        tbody._paginatorEl = paginator;
                        
                        // Assign ID to tbody if not exists for referencing
                        if(!tbody.id) tbody.id = 'tbody-' + Math.random().toString(36).substr(2, 9);
                        paginator.dataset.targetTbody = tbody.id;
                        
                        paginateTable(tbody);
                    }
                });
            }
            
            window.paginateTable = function(tbody) {
                var rows = Array.from(tbody.querySelectorAll('tr:not(.no-search)')).filter(r => !r.classList.contains('search-hidden'));
                var pageSize = parseInt(tbody.dataset.pageSize) || 10;
                var currentPage = parseInt(tbody.dataset.currentPage) || 1;
                var totalPages = Math.ceil(rows.length / pageSize) || 1;
                
                if (currentPage > totalPages) currentPage = totalPages || 1;
                tbody.dataset.currentPage = currentPage;
                
                var start = (currentPage - 1) * pageSize;
                var end = start + pageSize;
                
                // Hide all first
                Array.from(tbody.querySelectorAll('tr:not(.no-search)')).forEach(r => {
                    r.style.display = 'none';
                });
                
                // Show paginated
                rows.slice(start, end).forEach(r => {
                    r.style.display = '';
                });
                
                // Render controls
                if (tbody._paginatorEl) {
                    if (rows.length === 0) {
                         tbody._paginatorEl.innerHTML = '<span class="text-sm text-gray-500 dark:text-gray-400">No se encontraron registros.</span>';
                         return;
                    }
                    var html = '<div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between w-full">';
                    html += '<div><p class="text-sm text-gray-700 dark:text-gray-300">Mostrando <span class="font-medium">' + (start + 1) + '</span> a <span class="font-medium">' + Math.min(end, rows.length) + '</span> de <span class="font-medium">' + rows.length + '</span> registros</p></div>';
                    html += '<div><nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">';
                    
                    html += '<button onclick="changePage(this, -1)" ' + (currentPage === 1 ? 'disabled class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-800 text-sm font-medium text-gray-300 dark:text-gray-600 cursor-not-allowed"' : 'class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm font-medium text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"') + '><i class="fas fa-chevron-left text-xs"></i></button>';
                    
                    for(var i=1; i<=totalPages; i++) {
                        if (i === 1 || i === totalPages || (i >= currentPage - 1 && i <= currentPage + 1)) {
                            if (i === currentPage) {
                                html += '<button class="relative inline-flex items-center px-4 py-2 border border-brand-500 bg-brand-50 dark:bg-brand-900/30 text-sm font-medium text-brand-600 dark:text-brand-400 z-10">' + i + '</button>';
                            } else {
                                html += '<button onclick="goToPage(this, ' + i + ')" class="relative inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">' + i + '</button>';
                            }
                        } else if (i === currentPage - 2 || i === currentPage + 2) {
                            html += '<span class="relative inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm font-medium text-gray-700 dark:text-gray-400">...</span>';
                        }
                    }
                    
                    html += '<button onclick="changePage(this, 1)" ' + (currentPage === totalPages ? 'disabled class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-800 text-sm font-medium text-gray-300 dark:text-gray-600 cursor-not-allowed"' : 'class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm font-medium text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"') + '><i class="fas fa-chevron-right text-xs"></i></button>';
                    
                    html += '</nav></div></div>';
                    
                    // Mobile view
                    html += '<div class="flex items-center justify-between sm:hidden w-full gap-2">';
                    html += '<button onclick="changePage(this, -1)" ' + (currentPage === 1 ? 'disabled class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-lg text-gray-400 dark:text-gray-600 bg-gray-100 dark:bg-gray-800 cursor-not-allowed"' : 'class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-lg text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700"') + '>Anterior</button>';
                    html += '<span class="text-sm font-bold text-gray-700 dark:text-gray-300 whitespace-nowrap">' + currentPage + ' / ' + totalPages + '</span>';
                    html += '<button onclick="changePage(this, 1)" ' + (currentPage === totalPages ? 'disabled class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-lg text-gray-400 dark:text-gray-600 bg-gray-100 dark:bg-gray-800 cursor-not-allowed"' : 'class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-lg text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700"') + '>Siguiente</button>';
                    html += '</div>';
                    
                    tbody._paginatorEl.innerHTML = html;
                }
            };
            
            window.changePage = function(btn, delta) {
                var tbodyId = btn.closest('.table-paginator').dataset.targetTbody;
                var tbody = document.getElementById(tbodyId);
                var currentPage = parseInt(tbody.dataset.currentPage);
                tbody.dataset.currentPage = currentPage + delta;
                paginateTable(tbody);
            };
            
            window.goToPage = function(btn, page) {
                var tbodyId = btn.closest('.table-paginator').dataset.targetTbody;
                var tbody = document.getElementById(tbodyId);
                tbody.dataset.currentPage = page;
                paginateTable(tbody);
            };

            initTableSearch();
            setupPagination();
            document.body.addEventListener('htmx:afterSwap', function() {
                initTableSearch();
                setupPagination();
            });

            // --- Global Loaders for Forms and Buttons ---
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
                    
                    setTimeout(function() {
                        btn.disabled = true;
                        btn.classList.add('opacity-75', 'cursor-wait');
                    }, 10);
                }
            });

            document.body.addEventListener('htmx:beforeRequest', function(e) {
                var elt = e.detail.elt;
                if (elt.tagName === 'BUTTON' || elt.tagName === 'A') {
                    if (elt.dataset.noLoader) return;
                    elt.dataset.originalText = elt.innerHTML;
                    if(elt.innerHTML.indexOf('fa-spinner') === -1) {
                        elt.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                        elt.disabled = true;
                        elt.classList.add('opacity-75', 'cursor-wait');
                    }
                } else if (elt.tagName === 'FORM') {
                    var btn = elt.querySelector('button[type="submit"]');
                    if (btn && !btn.disabled) {
                        btn.dataset.originalText = btn.innerHTML;
                        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Espere...';
                        btn.disabled = true;
                        btn.classList.add('opacity-75', 'cursor-wait');
                    }
                }
            });

            document.body.addEventListener('htmx:afterRequest', function(e) {
                var elt = e.detail.elt;
                if (elt.tagName === 'BUTTON' || elt.tagName === 'A') {
                    if (elt.dataset.originalText) {
                        elt.innerHTML = elt.dataset.originalText;
                        elt.disabled = false;
                        elt.classList.remove('opacity-75', 'cursor-wait');
                    }
                } else if (elt.tagName === 'FORM') {
                    var btn = elt.querySelector('button[type="submit"]');
                    if (btn && btn.dataset.originalText) {
                        btn.innerHTML = btn.dataset.originalText;
                        btn.disabled = false;
                        btn.classList.remove('opacity-75', 'cursor-wait');
                    }
                }
            });
        });

        // ===== TOAST NOTIFICATION SYSTEM =====
        window.ToastSystem = {
            container: null,
            init: function() {
                if (this.container) return;
                this.container = document.createElement('div');
                this.container.id = 'toast-container';
                this.container.style.cssText = 'position:fixed;top:1rem;right:1rem;z-index:10000;display:flex;flex-direction:column;gap:0.5rem;pointer-events:none;max-width:380px;width:100%;';
                document.body.appendChild(this.container);
            },
            show: function(message, type, duration) {
                this.init();
                type = type || 'info';
                duration = duration || 4000;
                
                var colors = {
                    success: 'border-emerald-500 bg-emerald-50 text-emerald-800 dark:bg-emerald-900/80 dark:text-emerald-200 dark:border-emerald-600',
                    error:   'border-red-500 bg-red-50 text-red-800 dark:bg-red-900/80 dark:text-red-200 dark:border-red-600',
                    warning: 'border-amber-500 bg-amber-50 text-amber-800 dark:bg-amber-900/80 dark:text-amber-200 dark:border-amber-600',
                    info:    'border-sky-500 bg-sky-50 text-sky-800 dark:bg-sky-900/80 dark:text-sky-200 dark:border-sky-600'
                };
                var icons = { success: 'fa-check-circle', error: 'fa-exclamation-circle', warning: 'fa-exclamation-triangle', info: 'fa-info-circle' };
                
                var toast = document.createElement('div');
                toast.className = 'pointer-events-auto flex items-center gap-3 px-4 py-3 rounded-xl border-l-4 shadow-lg backdrop-blur-sm text-sm font-medium transition-all duration-300 ' + (colors[type] || colors.info);
                toast.style.cssText = 'transform:translateX(120%);opacity:0;';
                toast.innerHTML = '<i class="fas ' + (icons[type] || icons.info) + ' text-base flex-shrink-0"></i><span class="flex-1">' + message + '</span><button onclick="this.parentElement.remove()" class="ml-2 opacity-50 hover:opacity-100 transition-opacity flex-shrink-0"><i class="fas fa-times text-xs"></i></button>';
                
                this.container.appendChild(toast);
                
                // Animate in
                requestAnimationFrame(function() {
                    toast.style.transform = 'translateX(0)';
                    toast.style.opacity = '1';
                });
                
                // Auto-remove
                setTimeout(function() {
                    toast.style.transform = 'translateX(120%)';
                    toast.style.opacity = '0';
                    setTimeout(function() { if(toast.parentElement) toast.remove(); }, 350);
                }, duration);
            }
        };

        // Listen for HTMX-triggered toasts via response headers
        document.body.addEventListener('htmx:afterRequest', function(e) {
            var msg = e.detail.xhr && e.detail.xhr.getResponseHeader('X-Toast-Message');
            var type = e.detail.xhr && e.detail.xhr.getResponseHeader('X-Toast-Type');
            if (msg) ToastSystem.show(msg, type || 'info');
        });

        // Global helper
        function showToast(msg, type, duration) { ToastSystem.show(msg, type, duration); }

        // ===== PRINT HELPER =====
        function printPage() {
            window.print();
        }

        // =========================================================
        // SISTEMA GLOBAL DE ALERTAS DE CRÉDITOS (en TODAS las páginas)
        // Polling cada 5 minutos + notificaciones push del navegador
        // =========================================================
        (function() {
            // Pedir permiso de notificaciones push al cargar cualquier página
            if ('Notification' in window && Notification.permission === 'default') {
                // Esperar 3 segundos para no molestar al cargar
                setTimeout(function() {
                    Notification.requestPermission();
                }, 3000);
            }

            // Polling: Verificar alertas de créditos cada 5 minutos
            function checkCreditAlerts() {
                fetch('<?= BASE_URL ?>credits/check_alerts', {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (!data.alerts || data.alerts.length === 0) return;

                    // Actualizar el badge de la campanita
                    htmx.trigger('#notif-badge', 'notificationsUpdated');

                    // Enviar notificaciones push nativas del navegador
                    if ('Notification' in window && Notification.permission === 'granted') {
                        var dangerAlerts = data.alerts.filter(function(a) { return a.alert_level === 'danger'; });
                        if (dangerAlerts.length > 0 && dangerAlerts.length <= 3) {
                            dangerAlerts.forEach(function(alert) {
                                var n = new Notification(alert.title, {
                                    body: alert.message,
                                    icon: '<?= BASE_URL ?>?serve_logo=1',
                                    tag: 'credit-poll-' + alert.credit_id,
                                    requireInteraction: true
                                });
                                n.onclick = function() {
                                    window.focus();
                                    window.location.href = '<?= BASE_URL ?>credits/detail/' + alert.credit_id;
                                };
                            });
                        } else if (dangerAlerts.length > 3) {
                            new Notification('🚨 ' + dangerAlerts.length + ' Créditos Vencidos', {
                                body: 'Hay créditos atrasados que requieren atención urgente.',
                                icon: '<?= BASE_URL ?>?serve_logo=1',
                                tag: 'credit-poll-summary',
                                requireInteraction: true
                            }).onclick = function() {
                                window.focus();
                                window.location.href = '<?= BASE_URL ?>credits';
                            };
                        }
                    }
                })
                .catch(function() {}); // Silenciar errores de red
            }

            // Ejecutar la primera verificación después de 10 segundos
            setTimeout(checkCreditAlerts, 10000);
            // Luego cada 5 minutos (300000 ms)
            setInterval(checkCreditAlerts, 300000);
            
            // Global CSRF Error Handler
            document.body.addEventListener('csrfError', function(evt) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Sesión Expirada',
                        text: evt.detail.value || 'Tu sesión ha expirado o el token de seguridad es inválido. Por favor recarga la página e intenta de nuevo.',
                        confirmButtonColor: '#10b981',
                        confirmButtonText: 'Recargar Página',
                        allowOutsideClick: false
                    }).then(function(result) {
                        if (result.isConfirmed) {
                            window.location.reload();
                        }
                    });
                } else {
                    alert('Sesión Expirada. Por favor recarga la página e intenta de nuevo.');
                    window.location.reload();
                }
            });
        })();
    </script>
</body>
</html>
