        </main>
    </div>

    <!-- Overlay for mobile sidebar -->
    <div id="sidebar-overlay" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-30 hidden lg:hidden transition-opacity"></div>

    <!-- PRINT FOOTER (only visible on Ctrl+P) -->
    <div class="print-footer" style="display:none;">
        Generado por TuInventario ERP &mdash; <?= date('d/m/Y H:i') ?>
    </div>

    <script>
        // ===== Sidebar Toggle Logic =====
        const btnMenu = document.getElementById('mobile-menu-btn');
        const sidebar = document.getElementById('sidebar');
        const btnClose = document.getElementById('close-sidebar');
        const overlay = document.getElementById('sidebar-overlay');

        function openSidebar() {
            sidebar.classList.remove('-translate-x-full');
            overlay.classList.remove('hidden');
        }
        function closeSidebar() {
            sidebar.classList.add('-translate-x-full');
            overlay.classList.add('hidden');
        }

        if(btnMenu) btnMenu.addEventListener('click', openSidebar);
        if(btnClose) btnClose.addEventListener('click', closeSidebar);
        if(overlay) overlay.addEventListener('click', closeSidebar);
        
        // ===== Theme Toggle =====
        const themeBtn = document.getElementById('theme-toggle');
        if (themeBtn) {
            themeBtn.addEventListener('click', () => {
                document.documentElement.classList.toggle('dark');
                localStorage.setItem('theme', document.documentElement.classList.contains('dark') ? 'dark' : 'light');
            });
        }

        // ===== GLOBAL LOADER (HTMX + Fetch) =====
        const loader = document.getElementById('global-loader');
        function showLoader() { if(loader) loader.classList.add('active'); }
        function hideLoader() { if(loader) loader.classList.remove('active'); }

        // Hook into HTMX lifecycle
        document.body.addEventListener('htmx:beforeRequest', showLoader);
        document.body.addEventListener('htmx:afterRequest', hideLoader);
        document.body.addEventListener('htmx:responseError', hideLoader);

        // Hook into native fetch for Alpine/JS calls
        (function() {
            const origFetch = window.fetch;
            window.fetch = function() {
                showLoader();
                return origFetch.apply(this, arguments)
                    .then(r => { hideLoader(); return r; })
                    .catch(e => { hideLoader(); throw e; });
            };
        })();

        // ===== GLOBAL TABLE SEARCH =====
        // Usage: <input data-table-search="#my-tbody" placeholder="Buscar...">
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('[data-table-search]').forEach(function(input) {
                input.addEventListener('input', function() {
                    const query = this.value.toLowerCase().trim();
                    const targetId = this.getAttribute('data-table-search');
                    const tbody = document.querySelector(targetId);
                    if (!tbody) return;
                    tbody.querySelectorAll('tr').forEach(function(row) {
                        const text = row.textContent.toLowerCase();
                        row.style.display = (!query || text.includes(query)) ? '' : 'none';
                    });
                });
            });

            // Also re-attach after HTMX swaps
            document.body.addEventListener('htmx:afterSwap', function() {
                document.querySelectorAll('[data-table-search]').forEach(function(input) {
                    input.dispatchEvent(new Event('input'));
                });
            });
        });

        // ===== PRINT HELPER =====
        function printPage() {
            window.print();
        }
    </script>
</body>
</html>
