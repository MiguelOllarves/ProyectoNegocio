<?php include __DIR__ . '/../../../includes/header.php'; ?>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<div class="page-header" x-data="{ openModal: false, editModal: false, viewModal: false, modeCreateWp: '', showCustomCreateWp: false, modeEditWp: '', showCustomEditWp: false }" @open-view-modal.window="viewModal = true" @open-edit-modal.window="editModal = true" @set-edit-wp.window="modeEditWp = $event.detail; showCustomEditWp = (modeEditWp === 'new'); if(modeEditWp !== 'new') { document.getElementById('edit-workplace-custom').value = ''; }">
    <div class="flex-1">
        <h2 class="page-title">Clientes</h2>
        <p class="page-subtitle">Gestiona tu cartera de clientes sin recargar la página (<span class="text-brand-500 font-bold">SPA</span>)</p>
    </div>
    <button @click="openModal = true" class="btn-gradient w-full sm:w-auto">
        <i class="fas fa-plus mr-2"></i> Nuevo Cliente
    </button>

    <!-- Modal Alpine -->
    <div x-show="openModal" class="modal-wrapper" style="display: none;" x-cloak>
        <div class="modal-container">
            <div x-show="openModal" class="modal-backdrop" @click="openModal = false"></div>

            <div x-show="openModal" class="modal-card modal-card-lg animate-fade-in-up">
                <div class="flex justify-between items-center mb-4 pb-3 border-b border-gray-100 dark:border-gray-700">
                    <h3 class="modal-title">Registrar Cliente</h3>
                    <button @click="openModal = false" class="modal-close">
                        <i class="fas fa-times text-lg"></i>
                    </button>
                </div>
                
                <div class="modal-body pb-0">
                    <form id="create-client-form" hx-post="<?= BASE_URL ?>clients/create" hx-swap="none" hx-trigger="submit-with-gps" @htmx:after-request="if($event.detail.successful) { openModal = false; $el.reset(); Swal.fire({title: '¡Registro Exitoso!', text: 'El cliente ha sido guardado correctamente.', icon: 'success', timer: 2000, showConfirmButton: true, confirmButtonText: 'Continuar', confirmButtonColor: '#10b981', customClass: { popup: 'rounded-2xl' }}).then(() => { htmx.ajax('GET', '<?= BASE_URL ?>clients/list?t=' + new Date().getTime(), {target: '#clients-tbody'}); }); }" class="space-y-4">
                        <input type="hidden" name="gps_location" value="">
                        <div>
                            <label class="form-label">Nombre *</label>
                            <input type="text" name="name" required class="form-input">
                        </div>
                        <div>
                            <label class="form-label">Documento (C.I / RIF)</label>
                            <input type="text" name="document" oninput="this.value = this.value.replace(/[^0-9]/g, '')" class="form-input">
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="form-label">Teléfono</label>
                                <input type="text" name="phone" oninput="this.value = this.value.replace(/[^0-9]/g, '')" class="form-input">
                            </div>
                            <div>
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-input">
                            </div>
                        </div>
                        <div>
                            <label class="form-label">Dirección Residencial</label>
                            <textarea name="address" class="form-input" rows="2"></textarea>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="form-label">Lugar de Trabajo</label>
                                <select name="workplace_select" x-model="modeCreateWp" @change="if(modeCreateWp === 'new') { showCustomCreateWp = true; } else { showCustomCreateWp = false; }" class="form-select mb-2">
                                    <option value="">Seleccione o escriba...</option>
                                    <?php foreach ($workplaces ?? [] as $wp): ?>
                                        <option value="<?= htmlspecialchars($wp) ?>"><?= htmlspecialchars($wp) ?></option>
                                    <?php endforeach; ?>
                                    <option value="new" class="font-bold text-brand-600">+ Añadir Nuevo</option>
                                </select>
                                <div x-show="showCustomCreateWp" x-transition.opacity>
                                    <input type="text" name="workplace_custom" placeholder="Institución, Empresa..." class="form-input">
                                </div>
                            </div>
                            <div>
                                <label class="form-label">Departamento / Área</label>
                                <input type="text" name="workplace_component" placeholder="Ej. Ventas, Logística..." class="form-input">
                            </div>
                            <div>
                                <label class="form-label">Cargo</label>
                                <input type="text" name="workplace_detail" placeholder="Ej. Gerente, Sargento, Supervisor.." class="form-input">
                            </div>
                            <div>
                                <label class="form-label">Ingreso Mensual ($)</label>
                                <input type="number" step="0.01" min="0" name="monthly_income" placeholder="0.00" class="form-input">
                            </div>
                        </div>
                        <div>
                            <label class="form-label">Dirección del Trabajo</label>
                            <textarea name="workplace_address" class="form-input" rows="2"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" @click="openModal = false" class="btn-secondary flex-1">
                        Cancelar
                    </button>
                    <button type="button" onclick="captureGpsAndSubmit('create-client-form')" class="btn-gradient flex-1">
                        <i class="fas fa-save mr-2 mt-1"></i> Guardar Cliente
                    </button>
                </div>
            </div>
        </div>
    </div>
    <!-- Modal Editar Alpine -->
    <div x-show="editModal" class="modal-wrapper" style="display: none;" x-cloak>
        <div class="modal-container">
            <div x-show="editModal" class="modal-backdrop" @click="editModal = false"></div>

            <div x-show="editModal" class="modal-card modal-card-lg animate-fade-in-up">
                <div class="flex justify-between items-center mb-4 pb-3 border-b border-gray-100 dark:border-gray-700">
                    <h3 class="modal-title">Editar Cliente</h3>
                    <button @click="editModal = false" class="modal-close">
                        <i class="fas fa-times text-lg"></i>
                    </button>
                </div>
                
                <div class="modal-body pb-0">
                    <form id="edit-client-form" hx-post="<?= BASE_URL ?>clients/edit/" hx-swap="none" hx-trigger="submit-with-gps" @htmx:after-request="if($event.detail.successful) { editModal = false; $el.reset(); Swal.fire({title: '¡Actualización Exitosa!', text: 'El cliente ha sido actualizado.', icon: 'success', timer: 2000, showConfirmButton: true, confirmButtonText: 'Continuar', confirmButtonColor: '#10b981', customClass: { popup: 'rounded-2xl' }}).then(() => { htmx.ajax('GET', '<?= BASE_URL ?>clients/list?t=' + new Date().getTime(), {target: '#clients-tbody'}); }); }" class="space-y-4">
                        <input type="hidden" name="gps_location" id="edit-gps-location" value="">
                        <div>
                            <label class="form-label">Nombre *</label>
                            <input type="text" id="edit-name" name="name" required class="form-input">
                        </div>
                        <div>
                            <label class="form-label">Documento (CI / RIF)</label>
                            <input type="text" id="edit-document" name="document" oninput="this.value = this.value.replace(/[^0-9]/g, '')" class="form-input">
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="form-label">Teléfono</label>
                                <input type="text" id="edit-phone" name="phone" oninput="this.value = this.value.replace(/[^0-9]/g, '')" class="form-input">
                            </div>
                            <div>
                                <label class="form-label">Email</label>
                                <input type="email" id="edit-email" name="email" class="form-input">
                            </div>
                        </div>
                        <div>
                            <label class="form-label">Dirección Residencial</label>
                            <textarea id="edit-address" name="address" class="form-input" rows="2"></textarea>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="form-label">Lugar de Trabajo</label>
                                <select name="workplace_select" id="edit-workplace-select" x-model="modeEditWp" @change="if(modeEditWp === 'new') { showCustomEditWp = true; } else { showCustomEditWp = false; }" class="form-select mb-2">
                                    <option value="">Seleccione o escriba...</option>
                                    <?php foreach ($workplaces ?? [] as $wp): ?>
                                        <option value="<?= htmlspecialchars($wp) ?>"><?= htmlspecialchars($wp) ?></option>
                                    <?php endforeach; ?>
                                    <option value="new" class="font-bold text-brand-600">+ Añadir Nuevo</option>
                                </select>
                                <div x-show="showCustomEditWp" x-transition.opacity>
                                    <input type="text" name="workplace_custom" id="edit-workplace-custom" placeholder="Institución, Empresa..." class="form-input">
                                </div>
                            </div>
                            <div>
                                <label class="form-label">Departamento / Área</label>
                                <input type="text" id="edit-workplace-component" name="workplace_component" placeholder="Ej. Ventas, Logística..." class="form-input">
                            </div>
                            <div>
                                <label class="form-label">Cargo / Grado</label>
                                <input type="text" id="edit-workplace-detail" name="workplace_detail" placeholder="Ej. Gerente, Sargento..." class="form-input">
                            </div>
                            <div>
                                <label class="form-label">Ingreso Mensual ($)</label>
                                <input type="number" step="0.01" min="0" id="edit-monthly-income" name="monthly_income" placeholder="0.00" class="form-input">
                            </div>
                        </div>
                        <div>
                            <label class="form-label">Dirección del Trabajo</label>
                            <textarea id="edit-workplace-address" name="workplace_address" class="form-input" rows="2"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" @click="editModal = false" class="btn-secondary flex-1">
                        Cancelar
                    </button>
                    <button type="button" onclick="captureGpsAndSubmit('edit-client-form')" class="btn-gradient flex-1">
                        <i class="fas fa-save mr-2 mt-1"></i> Actualizar Cliente
                    </button>
                </div>
            </div>
        </div>
    </div>
    <!-- Modal Ver Detalles -->
    <div x-show="viewModal" class="modal-wrapper" style="display: none;" x-cloak>
        <div class="modal-container">
            <div x-show="viewModal" class="modal-backdrop" @click="viewModal = false"></div>

            <div x-show="viewModal" class="modal-card modal-card-xl animate-fade-in-up">
                <div class="flex justify-between items-center mb-4 pb-3 border-b border-gray-100 dark:border-gray-700">
                    <h3 class="modal-title"><i class="fas fa-id-card text-brand-600 dark:text-brand-400 mr-2"></i> Detalles del Cliente</h3>
                    <button @click="viewModal = false" class="modal-close">
                        <i class="fas fa-times text-lg"></i>
                    </button>
                </div>
                
                <div class="modal-body max-h-[70vh] overflow-y-auto pr-2 space-y-6">
                    <!-- Personal Info -->
                    <div class="bg-gray-50 dark:bg-gray-700/50 p-4 rounded-xl border border-gray-100 dark:border-gray-600">
                        <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Información Personal</h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <span class="block text-xs text-gray-500">Nombre Completo</span>
                                <span class="font-bold text-gray-800 dark:text-white" id="view-name"></span>
                            </div>
                            <div>
                                <span class="block text-xs text-gray-500">Cédula / Documento</span>
                                <span class="font-bold text-gray-800 dark:text-white" id="view-document"></span>
                            </div>
                            <div>
                                <span class="block text-xs text-gray-500">Teléfono Principal</span>
                                <span class="font-bold text-gray-800 dark:text-white" id="view-phone"></span>
                            </div>
                            <div>
                                <span class="block text-xs text-gray-500">Correo Electrónico</span>
                                <span class="font-bold text-gray-800 dark:text-white" id="view-email"></span>
                            </div>
                            <div class="sm:col-span-2">
                                <span class="block text-xs text-gray-500">Dirección Residencial</span>
                                <span class="font-bold text-gray-800 dark:text-white" id="view-address"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Work Info -->
                    <div class="bg-brand-50 dark:bg-brand-900/20 p-4 rounded-xl border border-brand-100 dark:border-brand-800/30">
                        <h4 class="text-xs font-bold text-brand-600 dark:text-brand-400 uppercase tracking-wider mb-3">Información Laboral (Créditos)</h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <span class="block text-xs text-brand-700/70 dark:text-brand-300/70">Lugar de Trabajo</span>
                                <span class="font-bold text-brand-900 dark:text-brand-100" id="view-workplace"></span>
                            </div>
                            <div>
                                <span class="block text-xs text-brand-700/70 dark:text-brand-300/70">Componente</span>
                                <span class="font-bold text-brand-900 dark:text-brand-100" id="view-workplace-component"></span>
                            </div>
                            <div>
                                <span class="block text-xs text-brand-700/70 dark:text-brand-300/70">Grado / Jerarquía</span>
                                <span class="font-bold text-brand-900 dark:text-brand-100" id="view-workplace-detail"></span>
                            </div>
                            <div>
                                <span class="block text-xs text-brand-700/70 dark:text-brand-300/70">Ingreso Declarado</span>
                                <span class="font-bold text-green-600 dark:text-green-400" id="view-monthly-income"></span>
                            </div>
                            <div class="sm:col-span-2">
                                <span class="block text-xs text-brand-700/70 dark:text-brand-300/70">Dirección Laboral</span>
                                <span class="font-bold text-brand-900 dark:text-brand-100" id="view-workplace-address"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Tracking Info -->
                    <div class="bg-gray-50 dark:bg-gray-700/50 p-4 rounded-xl border border-gray-100 dark:border-gray-600">
                        <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Seguridad y Rastreo</h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                            <div>
                                <span class="block text-xs text-gray-500">Dirección IP</span>
                                <span class="font-bold text-gray-800 dark:text-white" id="view-ip"></span>
                            </div>
                            <div>
                                <span class="block text-xs text-gray-500">Dispositivo (User Agent)</span>
                                <span class="font-bold text-gray-800 dark:text-white text-[10px] break-words" id="view-ua"></span>
                            </div>
                        </div>
                        
                        <!-- Mapa GPS -->
                        <div id="gps-container" class="hidden flex-col gap-2">
                            <span class="block text-xs font-bold text-red-500"><i class="fas fa-map-marker-alt mr-1"></i> Ubicación GPS exacta capturada</span>
                            <div id="client-map" class="w-full h-48 rounded-xl shadow-inner border border-gray-200 z-10"></div>
                        </div>
                        <div id="no-gps-msg" class="text-xs text-gray-400 italic">
                            No se capturó ubicación GPS.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    let mapInstance = null;
    let markerInstance = null;

    function openViewClientModal(id) {
        fetch(`<?= BASE_URL ?>clients/edit/${id}?json=true`, { 
            headers: { 'Accept': 'application/json' },
            cache: 'no-store'
        })
            .then(res => {
                if (!res.ok) throw new Error('HTTP ' + res.status);
                const ct = res.headers.get('content-type') || '';
                if (!ct.includes('application/json')) {
                    return res.text().then(txt => { 
                        console.error('No-JSON response:', txt.substring(0, 500));
                        throw new Error('La respuesta del servidor no es JSON. Posible sesión expirada.');
                    });
                }
                return res.json();
            })
            .then(data => {
                if (data.client) {
                    const c = data.client;
                    document.getElementById('view-name').textContent = c.name || '-';
                    document.getElementById('view-document').textContent = c.document || '-';
                    document.getElementById('view-phone').textContent = c.phone || '-';
                    document.getElementById('view-email').textContent = c.email || '-';
                    document.getElementById('view-address').textContent = c.address || '-';
                    
                    document.getElementById('view-workplace').textContent = c.workplace || '-';
                    document.getElementById('view-workplace-component').textContent = c.workplace_component || '-';
                    document.getElementById('view-workplace-detail').textContent = c.workplace_detail || '-';
                    document.getElementById('view-workplace-address').textContent = c.workplace_address || '-';
                    document.getElementById('view-monthly-income').textContent = c.monthly_income ? `$${c.monthly_income}` : '-';
                    
                    document.getElementById('view-ip').textContent = c.ip_address || '-';
                    document.getElementById('view-ua').textContent = c.user_agent || '-';

                    // GPS Handling
                    const gpsContainer = document.getElementById('gps-container');
                    const noGpsMsg = document.getElementById('no-gps-msg');
                    
                    if (c.gps_location) {
                        try {
                            const gps = JSON.parse(c.gps_location);
                            if (gps && gps.lat && gps.lng) {
                                gpsContainer.classList.remove('hidden');
                                noGpsMsg.classList.add('hidden');
                                
                                window.dispatchEvent(new CustomEvent('open-view-modal'));
                                
                                setTimeout(() => {
                                    if (!mapInstance) {
                                        mapInstance = L.map('client-map').setView([gps.lat, gps.lng], 15);
                                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                                            attribution: '© OpenStreetMap contributors'
                                        }).addTo(mapInstance);
                                    } else {
                                        mapInstance.setView([gps.lat, gps.lng], 15);
                                    }
                                    
                                    if (markerInstance) {
                                        mapInstance.removeLayer(markerInstance);
                                    }
                                    markerInstance = L.marker([gps.lat, gps.lng]).addTo(mapInstance);
                                    mapInstance.invalidateSize();
                                }, 300);
                                
                                return;
                            }
                        } catch(e) {}
                    }
                    
                    gpsContainer.classList.add('hidden');
                    noGpsMsg.classList.remove('hidden');
                    window.dispatchEvent(new CustomEvent('open-view-modal'));
                    
                } else {
                    Swal.fire('Error', data.error || 'No se pudo cargar el cliente', 'error');
                }
            })
            .catch(err => {
                console.error('openViewClientModal error:', err);
                Swal.fire('Error', err.message || 'Problema de red al cargar detalles', 'error');
            });
    }

    function openEditClientModal(id) {
        fetch(`<?= BASE_URL ?>clients/edit/${id}?json=true`, { 
            headers: { 'Accept': 'application/json' },
            cache: 'no-store'
        })
            .then(res => {
                if (!res.ok) throw new Error('HTTP ' + res.status);
                const ct = res.headers.get('content-type') || '';
                if (!ct.includes('application/json')) {
                    return res.text().then(txt => { 
                        console.error('No-JSON response:', txt.substring(0, 500));
                        throw new Error('La respuesta del servidor no es JSON. Posible sesión expirada.');
                    });
                }
                return res.json();
            })
            .then(data => {
                if (data.client) {
                    document.getElementById('edit-name').value = data.client.name || '';
                    document.getElementById('edit-document').value = data.client.document || '';
                    document.getElementById('edit-phone').value = data.client.phone || '';
                    document.getElementById('edit-email').value = data.client.email || '';
                    document.getElementById('edit-address').value = data.client.address || '';
                    if (data.client.workplace) {
                        const sel = document.getElementById('edit-workplace-select');
                        const opt = Array.from(sel.options).find(o => o.value === data.client.workplace);
                        if (opt) {
                            window.dispatchEvent(new CustomEvent('set-edit-wp', {detail: data.client.workplace}));
                        } else {
                            window.dispatchEvent(new CustomEvent('set-edit-wp', {detail: 'new'}));
                            document.getElementById('edit-workplace-custom').value = data.client.workplace;
                        }
                    } else {
                        window.dispatchEvent(new CustomEvent('set-edit-wp', {detail: ''}));
                    }
                    document.getElementById('edit-workplace-component').value = data.client.workplace_component || '';
                    document.getElementById('edit-workplace-detail').value = data.client.workplace_detail || '';
                    document.getElementById('edit-workplace-address').value = data.client.workplace_address || '';
                    document.getElementById('edit-monthly-income').value = data.client.monthly_income || '';
                    
                    document.getElementById('edit-client-form').setAttribute('hx-post', `<?= BASE_URL ?>clients/edit/${id}`);
                    htmx.process(document.getElementById('edit-client-form'));
                    
                    window.dispatchEvent(new CustomEvent('open-edit-modal'));
                } else {
                    Swal.fire('Error', data.error || 'No se pudo cargar el cliente', 'error');
                }
            })
            .catch(err => {
                console.error('openEditClientModal error:', err);
                Swal.fire('Error', err.message || 'Problema de red al cargar datos', 'error');
            });
    }
</script>

<!-- Search + Actions Bar -->
<div class="flex flex-col sm:flex-row items-center gap-3 mt-4 mb-4">
    <div class="relative flex-1 w-full flex gap-2 items-center">
        <div class="relative flex-1">
            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
            <input type="text" data-table-search="#clients-tbody" placeholder="Buscar cliente..." class="form-input pl-10 h-10 w-full rounded-xl">
        </div>
        <form method="GET" action="<?= BASE_URL ?>clients" class="flex items-center gap-2" hx-get="<?= BASE_URL ?>clients/list" hx-target="#clients-tbody">
            <label class="text-sm font-medium text-gray-500 dark:text-gray-400 hidden sm:block">Mostrar:</label>
            <select name="limit" onchange="htmx.trigger(this.form, 'submit')" class="px-3 h-10 bg-white dark:bg-slate-900 border border-gray-200 dark:border-gray-700 rounded-xl text-sm text-gray-800 dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-brand-500">
                <option value="5">5 filas</option>
                <option value="10">10 filas</option>
                <option value="50">50 filas</option>
                <option value="100">100 filas</option>
            </select>
        </form>
    </div>
    <div class="flex gap-2 w-full sm:w-auto overflow-x-auto pb-1 sm:pb-0">
        <button onclick="triggerImportClients()" class="no-print btn-secondary flex-1 sm:flex-none justify-center">
            <i class="fas fa-file-import mr-2"></i> Importar
        </button>
        <a href="<?= BASE_URL ?>clients/print" target="_blank" class="no-print btn-secondary flex-1 sm:flex-none justify-center">
            <i class="fas fa-print mr-2"></i> Imprimir
        </a>
        <a href="<?= BASE_URL ?>clients/export_csv" class="no-print btn-secondary flex-1 sm:flex-none justify-center text-green-600 dark:text-green-500 border-green-200 dark:border-green-800 hover:bg-green-50 dark:hover:bg-green-900/20">
            <i class="fas fa-file-excel mr-2"></i> Excel
        </a>
    </div>
</div>

<div class="card">
    <div class="table-wrap">
        <table class="min-w-[600px] w-full text-left border-collapse">
            <thead>
                <tr class="table-head-row">
                    <th class="p-4">Nombre</th>
                    <th class="p-4">Documento</th>
                    <th class="p-4">Teléfono</th>
                    <th class="p-4">Email</th>
                    <th class="p-4 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800" hx-get="<?= BASE_URL ?>clients/list" hx-trigger="load" id="clients-tbody">
                <!-- Se cargará dinámicamente con HTMX -->
                <tr><td colspan="5" class="p-8 text-center text-gray-400 dark:text-gray-500"><i class="fas fa-spinner fa-spin text-2xl mb-3 block"></i>Cargando...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<script>
    function confirmDeleteClient(id) {
        Swal.fire({
            title: '¿Estás seguro?',
            text: "¡No podrás revertir esto!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#10b981',
            cancelButtonColor: '#ef4444',
            confirmButtonText: '<i class="fas fa-trash mr-1"></i> Sí, eliminar',
            cancelButtonText: 'Cancelar',
            customClass: { popup: 'rounded-2xl' }
        }).then((result) => {
            if (result.isConfirmed) {
                let formData = new FormData();
                formData.append('id', id);
                fetch('<?= BASE_URL ?>clients/delete', { method: 'POST', body: formData, headers: { 'HX-Request': 'true' }})
                    .then(async r => {
                        let data;
                        try { data = await r.json(); } catch(e) {}
                        
                        if (r.ok && data && data.success) {
                            Swal.fire({
                                title: '¡Eliminado!',
                                text: 'El cliente ha sido eliminado.',
                                icon: 'success',
                                timer: 1500,
                                showConfirmButton: false,
                                customClass: { popup: 'rounded-2xl' }
                            }).then(() => {
                                htmx.ajax('GET', '<?= BASE_URL ?>clients/list?t=' + new Date().getTime(), {target: '#clients-tbody'});
                            });
                        } else {
                            Swal.fire('No se pudo eliminar', data ? data.message : 'Error desconocido de red.', 'error');
                        }
                    })
                    .catch(() => Swal.fire('Error', 'Problema de red al eliminar', 'error'));
            }
        });
    }

    function captureGpsAndSubmit(formId) {
        const form = document.getElementById(formId);
        
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        if (navigator.geolocation) {
            Swal.fire({
                title: 'Obteniendo ubicación...',
                text: 'Por favor permite el acceso a tu ubicación GPS obligatoria para créditos.',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });
            
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    Swal.close();
                    const gpsInput = document.querySelector(`#${formId} input[name=gps_location]`);
                    if (gpsInput) {
                        gpsInput.value = JSON.stringify({lat: position.coords.latitude, lng: position.coords.longitude});
                    }
                    htmx.trigger(`#${formId}`, 'submit-with-gps');
                },
                (error) => {
                    Swal.fire('Ubicación Requerida', 'Debes permitir el acceso a la ubicación GPS para registrar o actualizar al cliente para créditos.', 'error');
                },
                { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
            );
        } else {
            Swal.fire('Error', 'Tu navegador no soporta geolocalización.', 'error');
        }
    }
</script>

<!-- SheetJS for Excel/CSV Parsing -->
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
<input type="file" id="bulkUploadInputClients" class="hidden" accept=".csv, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel" onchange="handleBulkImportClients(event)">

<script>
function triggerImportClients() {
    document.getElementById('bulkUploadInputClients').click();
}

function handleBulkImportClients(e) {
    const file = e.target.files[0];
    if (!file) return;
    
    Swal.fire({
        title: 'Analizando archivo...',
        text: 'Por favor, espere mientras leemos los datos.',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });
    
    const reader = new FileReader();
    reader.onload = function(e) {
        try {
            const data = new Uint8Array(e.target.result);
            const workbook = XLSX.read(data, {type: 'array'});
            const firstSheetName = workbook.SheetNames[0];
            const worksheet = workbook.Sheets[firstSheetName];
            
            // Expected columns: Nombre, Documento, Telefono, Email, Direccion, Trabajo, IngresoMensual
            const json = XLSX.utils.sheet_to_json(worksheet, {defval: ""});
            
            if (json.length === 0) {
                Swal.fire('Error', 'El archivo parece estar vacío.', 'error');
                return;
            }
            
            // Check if essential columns exist (at least 'Nombre')
            if (!json[0].hasOwnProperty('Nombre')) {
                Swal.fire('Formato Inválido', 'El archivo debe contener una columna llamada "Nombre".', 'error');
                return;
            }
            
            Swal.fire({
                title: 'Confirmar Importación',
                text: `Se encontraron ${json.length} clientes listos para procesar. ¿Desea iniciar la carga masiva?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#10b981',
                cancelButtonColor: '#ef4444',
                confirmButtonText: '<i class="fas fa-check mr-1"></i> Iniciar Importación',
                cancelButtonText: 'Cancelar',
                customClass: { popup: 'rounded-2xl' }
            }).then((result) => {
                if (result.isConfirmed) {
                    processBulkImportClients(json);
                } else {
                    document.getElementById('bulkUploadInputClients').value = '';
                }
            });
            
        } catch(err) {
            console.error(err);
            Swal.fire('Error', 'No se pudo leer el archivo. Asegurese de que sea un Excel o CSV válido.', 'error');
        }
    };
    reader.readAsArrayBuffer(file);
}

function processBulkImportClients(dataArray) {
    Swal.fire({
        title: 'Importando Clientes...',
        text: 'Enviando datos al servidor, esto puede tomar unos momentos...',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });
    
    fetch('<?= BASE_URL ?>clients/bulk_import', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(dataArray)
    })
    .then(async r => {
        if (!r.ok) throw new Error('Network error');
        return r.json();
    })
    .then(res => {
        document.getElementById('bulkUploadInputClients').value = '';
        if (res.success) {
            Swal.fire({
                title: 'Importación Completada',
                html: `Se importaron <b class="text-brand-600">${res.imported}</b> clientes.<br>` + 
                      (res.errors > 0 ? `<span class="text-red-500">Hubo ${res.errors} errores (omitidos o vacíos).</span>` : ''),
                icon: 'success',
                timer: 4000,
                customClass: { popup: 'rounded-2xl' }
            }).then(() => {
                htmx.ajax('GET', '<?= BASE_URL ?>clients/list?t=' + new Date().getTime(), {target: '#clients-tbody'});
            });
        } else {
            Swal.fire('Error en la importación', res.message || 'Hubo un error del servidor', 'error');
        }
    })
    .catch(err => {
        document.getElementById('bulkUploadInputClients').value = '';
        Swal.fire('Error', 'Fallo de conexión al importar. ' + err.message, 'error');
    });
}
</script>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>
