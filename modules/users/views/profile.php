<?php include __DIR__ . '/../../../includes/header.php'; ?>

<div class="mb-6">
    <h2 class="text-2xl font-extrabold text-gray-800 dark:text-white tracking-tight">Mi Perfil</h2>
    <p class="text-gray-500 dark:text-gray-400 text-sm font-medium mt-1">Gestiona tu información personal, seguridad y actividad de conexión</p>
</div>

<?php if (!empty($success)): ?>
<div class="bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-400 px-4 py-3 rounded-lg mb-6 flex items-center shadow-sm">
    <i class="fas fa-check-circle mr-3 text-lg"></i>
    <span class="font-medium text-sm"><?= htmlspecialchars($success) ?></span>
</div>
<?php endif; ?>

<?php if (!empty($error)): ?>
<div class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-400 px-4 py-3 rounded-lg mb-6 flex items-center shadow-sm">
    <i class="fas fa-exclamation-circle mr-3 text-lg"></i>
    <span class="font-medium text-sm"><?= htmlspecialchars($error) ?></span>
</div>
<?php endif; ?>

<!-- ═══════════════════════════════════════════════════════ -->
<!-- SECCIÓN 1: Tarjeta de identidad + Información personal -->
<!-- ═══════════════════════════════════════════════════════ -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">

    <!-- Columna Izquierda: Avatar -->
    <div class="lg:col-span-1">
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-8 text-center h-full flex flex-col justify-center items-center">
            <div class="w-28 h-28 rounded-full bg-gradient-to-br from-brand-500 to-accent-500 mx-auto flex items-center justify-center text-4xl font-black text-white shadow-lg mb-5 ring-4 ring-brand-100 dark:ring-brand-900/30">
                <?= strtoupper(substr($user['full_name'] ?: $user['username'], 0, 1)) ?>
            </div>
            <h3 class="text-xl font-bold text-gray-800 dark:text-white"><?= htmlspecialchars($user['full_name'] ?: $user['username']) ?></h3>
            <p class="text-sm font-bold text-brand-600 dark:text-brand-400 uppercase tracking-widest mt-2"><?= htmlspecialchars($user['role']) ?></p>
            <div class="inline-flex px-4 py-1.5 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 text-green-600 dark:text-green-400 rounded-full text-xs font-bold items-center mt-4">
                <i class="fas fa-circle text-[8px] mr-2 animate-pulse"></i> Cuenta Activa
            </div>
            
            <?php if (!empty($user['email'])): ?>
            <div class="mt-5 w-full border-t border-gray-100 dark:border-gray-700 pt-4">
                <p class="text-xs text-gray-400 uppercase font-bold mb-1">Correo</p>
                <p class="text-sm text-gray-600 dark:text-gray-300 font-medium truncate"><?= htmlspecialchars($user['email']) ?></p>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($user['document_id'])): ?>
            <div class="mt-3 w-full">
                <p class="text-xs text-gray-400 uppercase font-bold mb-1">Cédula</p>
                <p class="text-sm text-gray-600 dark:text-gray-300 font-medium"><?= htmlspecialchars($user['document_id']) ?></p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Columna Derecha: Formulario de Información -->
    <div class="lg:col-span-2">
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-6 h-full">
            <h4 class="text-sm font-bold text-gray-800 dark:text-white mb-6 border-b border-gray-100 dark:border-gray-800 pb-3 flex items-center">
                <i class="fas fa-id-card text-brand-500 mr-2"></i>Información Personal y del Negocio
            </h4>
            
            <form action="<?= BASE_URL ?>users/updateProfile" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-400 mb-1.5">Nombre y Apellidos *</label>
                        <input type="text" name="full_name" value="<?= htmlspecialchars($user['full_name'] ?? '') ?>" placeholder="Ej. Miguel Ollarves" required class="w-full bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-2.5 text-sm text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-500 transition-all">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-400 mb-1.5">Cédula de Identidad *</label>
                        <input type="text" name="username" value="<?= htmlspecialchars(($user['role'] ?? '') === 'administrador' ? ($user['document_id'] ?? $user['username']) : $user['username']) ?>" required class="w-full bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-2.5 text-sm text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-500 transition-all">
                    </div>
                    
                    <?php if (($user['role'] ?? '') === 'administrador'): ?>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-400 mb-1.5">Correo Electrónico</label>
                        <input type="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" class="w-full bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-2.5 text-sm text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-500 transition-all">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-400 mb-1.5">Teléfono Personal</label>
                        <input type="text" name="owner_phone" value="<?= htmlspecialchars($user['owner_phone'] ?? '') ?>" class="w-full bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-2.5 text-sm text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-500 transition-all">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-400 mb-1.5">Nombre del Negocio</label>
                        <input type="text" name="business_name" value="<?= htmlspecialchars($user['business_name'] ?? '') ?>" class="w-full bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-2.5 text-sm text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-500 transition-all">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-400 mb-1.5">Teléfono del Negocio</label>
                        <input type="text" name="business_phone" value="<?= htmlspecialchars($user['business_phone'] ?? '') ?>" class="w-full bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-2.5 text-sm text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-500 transition-all">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-400 mb-1.5">RIF</label>
                        <input type="text" name="rif" value="<?= htmlspecialchars($user['rif'] ?? '') ?>" class="w-full bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-2.5 text-sm text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-500 transition-all">
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="pt-5 flex justify-end border-t border-gray-100 dark:border-gray-800 mt-6">
                    <button type="submit" class="bg-brand-600 hover:bg-brand-700 text-white font-bold py-2.5 px-6 rounded-lg shadow-sm transition-all text-sm hover:-translate-y-0.5">
                        <i class="fas fa-save mr-2"></i> Guardar Información
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ======================================================= -->
<!-- GRID SIDE-BY-SIDE: SEGURIDAD E HISTORIAL               -->
<!-- ======================================================= -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6 items-start">

    <!-- LADO IZQUIERDO: Seguridad (Cambiar Contraseña) -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-6 flex flex-col">
        <h4 class="text-sm font-bold text-gray-800 dark:text-white mb-6 border-b border-gray-100 dark:border-gray-800 pb-3 flex items-center shrink-0">
            <i class="fas fa-shield-alt text-brand-500 mr-2"></i>Seguridad de la Cuenta
        </h4>
        
        <form action="<?= BASE_URL ?>users/updatePassword" method="POST" class="flex flex-col">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
            <div class="flex flex-col gap-5">
                <div x-data="{ show: false }">
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-400 mb-1.5">Nueva Contraseña *</label>
                    <div class="relative">
                        <input x-bind:type="show ? 'text' : 'password'" name="new_password" required minlength="4" class="w-full bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-gray-700 rounded-lg pl-3 pr-10 py-2.5 text-sm text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-500 transition-all">
                        <button type="button" @click="show = !show" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 focus:outline-none">
                            <i class="fas" :class="show ? 'fa-eye-slash' : 'fa-eye'"></i>
                        </button>
                    </div>
                </div>
                <div x-data="{ show: false }">
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-400 mb-1.5">Confirmar Contraseña *</label>
                    <div class="relative">
                        <input x-bind:type="show ? 'text' : 'password'" name="confirm_password" required minlength="8" class="w-full bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-gray-700 rounded-lg pl-3 pr-10 py-2.5 text-sm text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-500 transition-all">
                        <button type="button" @click="show = !show" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 focus:outline-none">
                            <i class="fas" :class="show ? 'fa-eye-slash' : 'fa-eye'"></i>
                        </button>
                    </div>
                </div>
            </div>
            <p class="text-[10px] text-gray-500 dark:text-gray-400 mt-2 leading-tight">Mínimo 8 caracteres. Debe incluir al menos una mayúscula, una minúscula y un número.</p>
            <div class="mt-6 pt-6 border-t border-gray-100 dark:border-gray-800">
                <button type="submit" class="w-full bg-gray-800 hover:bg-gray-900 dark:bg-gray-700 dark:hover:bg-gray-600 text-white font-bold py-2.5 px-6 rounded-lg shadow-sm transition-all text-sm hover:-translate-y-0.5">
                    <i class="fas fa-key mr-2"></i> Actualizar Contraseña
                </button>
            </div>
        </form>
    </div>

    <!-- LADO DERECHO: Historial de Conexiones -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 flex flex-col h-[400px] overflow-hidden">
        <div class="p-6 border-b border-gray-100 dark:border-gray-800 shrink-0">
            <div class="flex items-center justify-between">
            <div>
                <h4 class="text-sm font-bold text-gray-800 dark:text-white flex items-center">
                    <i class="fas fa-history text-brand-500 mr-2"></i>Historial de Conexiones
                </h4>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Últimas <?= count($loginSessions ?? []) ?> sesiones registradas en tu cuenta</p>
            </div>
            <div class="flex items-center gap-3">
                <button type="button" onclick="closeOtherSessions()" title="Cierra la sesión abierta en otras computadoras o dispositivos y deja este equipo como único activo" class="inline-flex items-center px-3 py-1.5 bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-900/40 rounded-full text-xs font-bold border border-red-200 dark:border-red-800 transition-colors cursor-pointer">
                    <i class="fas fa-power-off mr-1.5"></i> Cerrar sesiones remotas
                </button>
                <span class="inline-flex items-center px-3 py-1 bg-brand-50 dark:bg-brand-900/20 text-brand-600 dark:text-brand-400 rounded-full text-xs font-bold border border-brand-200 dark:border-brand-800">
                    <i class="fas fa-wifi mr-1.5"></i> <?= count($loginSessions ?? []) ?> sesiones
                </span>
            </div>
        </div>
    </div>

    <?php if (empty($loginSessions)): ?>
    <div class="p-12 text-center text-gray-400 flex-1 overflow-y-auto">
        <i class="fas fa-satellite-dish text-5xl mb-4 opacity-30"></i>
        <p class="font-bold text-base">Sin historial de conexiones</p>
        <p class="text-xs text-gray-500 mt-1">Cierra sesión y vuelve a entrar para que el sistema comience a registrar tu actividad.</p>
    </div>
    <?php else: ?>
    <div class="divide-y divide-gray-100 dark:divide-gray-800/50 flex-1 overflow-y-auto min-h-0 custom-scrollbar">
        <?php foreach ($loginSessions as $i => $session): ?>
        <?php
            $isFirst = ($i === 0);
            $ipDisplay = ($session['ip_address'] === '::1') ? '127.0.0.1 (Local)' : ($session['ip_address'] ?: 'Desconocida');
            
            // Icono según tipo de dispositivo
            $deviceIcon = 'fa-desktop';
            $deviceColor = 'text-blue-500';
            if ($session['device_type'] === 'Teléfono') { $deviceIcon = 'fa-mobile-alt'; $deviceColor = 'text-green-500'; }
            elseif ($session['device_type'] === 'Tablet') { $deviceIcon = 'fa-tablet-alt'; $deviceColor = 'text-purple-500'; }
            
            // Icono según navegador
            $browserIcon = 'fa-globe';
            if (stripos($session['browser_name'], 'Chrome') !== false) $browserIcon = 'fa-brands fa-chrome';
            elseif (stripos($session['browser_name'], 'Firefox') !== false) $browserIcon = 'fa-brands fa-firefox';
            elseif (stripos($session['browser_name'], 'Safari') !== false) $browserIcon = 'fa-brands fa-safari';
            elseif (stripos($session['browser_name'], 'Edge') !== false) $browserIcon = 'fa-brands fa-edge';
            elseif (stripos($session['browser_name'], 'Opera') !== false) $browserIcon = 'fa-brands fa-opera';
            
            // Formatear fecha
            $dateObj = new DateTime($session['logged_in_at']);
            $dateFormatted = $dateObj->format('d/m/Y');
            $timeFormatted = $dateObj->format('h:i A');
        ?>
        <div class="p-4 md:p-5 hover:bg-gray-50/50 dark:hover:bg-slate-700/30 transition-colors <?= $isFirst ? 'bg-brand-50/30 dark:bg-brand-900/10' : '' ?>">
            <div class="flex flex-col md:flex-row md:items-center gap-4">
                
                <!-- Icono Dispositivo -->
                <div class="flex-shrink-0">
                    <div class="w-12 h-12 rounded-xl <?= $isFirst ? 'bg-brand-100 dark:bg-brand-900/30' : 'bg-gray-100 dark:bg-slate-700' ?> flex items-center justify-center">
                        <i class="fas <?= $deviceIcon ?> text-xl <?= $isFirst ? 'text-brand-600' : $deviceColor ?>"></i>
                    </div>
                </div>

                <!-- Info Principal -->
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 mb-1">
                        <p class="text-sm font-bold text-gray-800 dark:text-white">
                            <?= htmlspecialchars($session['device_type']) ?> — <?= htmlspecialchars($session['os_name']) ?>
                        </p>
                        <?php if ($isFirst): ?>
                        <span class="px-2 py-0.5 bg-brand-500 text-white rounded-full text-[10px] font-bold uppercase">Actual</span>
                        <?php endif; ?>
                    </div>
                    <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-gray-500 dark:text-gray-400">
                        <span class="flex items-center gap-1.5">
                            <i class="<?= $browserIcon ?> text-sm"></i>
                            <?= htmlspecialchars($session['browser_name']) ?>
                        </span>
                        <span class="flex items-center gap-1.5">
                            <i class="fas fa-network-wired text-xs"></i>
                            <?= htmlspecialchars($ipDisplay) ?>
                        </span>
                        <?php if (!empty($session['location'])): ?>
                        <span class="flex items-center gap-1.5">
                            <i class="fas fa-map-marker-alt text-xs"></i>
                            <?php
                                $coords = explode(',', $session['location']);
                                if (count($coords) === 2 && is_numeric(trim($coords[0]))) {
                                    echo 'Lat: ' . round(trim($coords[0]), 4) . ', Lon: ' . round(trim($coords[1]), 4);
                                } else {
                                    echo htmlspecialchars($session['location']);
                                }
                            ?>
                        </span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Fecha y Hora -->
                <div class="flex-shrink-0 text-right">
                    <p class="text-sm font-bold text-gray-700 dark:text-gray-200"><?= $dateFormatted ?></p>
                    <p class="text-xs text-gray-400 font-medium"><?= $timeFormatted ?></p>
                </div>
            </div>
            
            <?php
                // Mini mapa si hay coordenadas válidas
                if (!empty($session['location'])) {
                    $coords = explode(',', $session['location']);
                    if (count($coords) === 2 && is_numeric(trim($coords[0])) && is_numeric(trim($coords[1]))) {
                        $lat = trim($coords[0]);
                        $lon = trim($coords[1]);
            ?>
            <div class="mt-3 rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700 shadow-sm h-24 w-full md:w-72 md:ml-16">
                <iframe width="100%" height="100%" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" loading="lazy" src="https://www.openstreetmap.org/export/embed.html?bbox=<?= $lon-0.008 ?>%2C<?= $lat-0.008 ?>%2C<?= $lon+0.008 ?>%2C<?= $lat+0.008 ?>&amp;layer=mapnik&amp;marker=<?= $lat ?>%2C<?= $lon ?>"></iframe>
            </div>
            <?php
                    }
                }
            ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    </div> <!-- Close Right Card -->
</div> <!-- Close Grid -->

<!-- ═══════════════════════════════════════════════════════ -->
<!-- SECCIÓN 4: Gestión de Personal (Si aplica)             -->
<!-- ═══════════════════════════════════════════════════════ -->
<?php if (isset($subUsers)): ?>
<div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 flex flex-col mb-6" x-data="{
        openCreate: false,
        isEditing: false,
        currentId: null,
        form: { username: '', full_name: '', password: '', confirm_password: '', role: 'vendedor', permissions: ['pos'] },
        resetForm() {
            this.form = { username: '', full_name: '', password: '', confirm_password: '', role: 'vendedor', permissions: ['pos'] };
            this.isEditing = false;
            this.currentId = null;
        },
        openNew() {
            this.resetForm();
            this.openCreate = true;
        },
        submitForm() {
            if (this.form.password !== this.form.confirm_password) {
                showToast('Las contraseñas no coinciden.', 'error');
                return;
            }
            let fd = new URLSearchParams();
            fd.append('username', this.form.username);
            fd.append('full_name', this.form.full_name);
            if (this.form.password) fd.append('password', this.form.password);
            if (this.form.confirm_password) fd.append('confirm_password', this.form.confirm_password);
            fd.append('role', this.form.role);
            this.form.permissions.forEach(p => fd.append('permissions[]', p));
            
            let url = this.isEditing ? '<?= BASE_URL ?>users/updateSubUser/' + this.currentId : '<?= BASE_URL ?>users/storeSubUser';
            
            fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: fd
            }).then(r => r.json()).then(res => {
                if(res.success) {
                    showToast(res.message, 'success');
                    setTimeout(() => window.location.reload(), 1000);
                } else showToast(res.message, 'error');
            });
        },
        toggleStatus(id) {
            if(!confirm('¿Cambiar estado del usuario?')) return;
            fetch('<?= BASE_URL ?>users/toggleStatus/'+id, {method:'POST'})
            .then(r => r.json()).then(res => {
                if(res.success) window.location.reload();
            });
        },
        editUser(id) {
            fetch('<?= BASE_URL ?>users/getSubUser/' + id)
            .then(r => r.json()).then(res => {
                if(res.success) {
                    this.isEditing = true;
                    this.currentId = id;
                    this.form.username = res.user.username;
                    this.form.full_name = res.user.full_name;
                    this.form.role = res.user.role;
                    this.form.password = '';
                    this.form.confirm_password = '';
                    try {
                        this.form.permissions = JSON.parse(res.user.permissions_json) || [];
                    } catch(e) { this.form.permissions = []; }
                    this.openCreate = true;
                } else {
                    showToast(res.message, 'error');
                }
            });
        },
        deleteUser(id) {
            if(!confirm('¿Estás SEGURO de que deseas eliminar este usuario de forma permanente? Esta acción no se puede deshacer.')) return;
            fetch('<?= BASE_URL ?>users/deleteSubUser/' + id, {method:'POST'})
            .then(r => r.json()).then(res => {
                if(res.success) {
                    showToast(res.message, 'success');
                    setTimeout(() => window.location.reload(), 1000);
                } else showToast(res.message, 'error');
            });
        }
    }">
    
    <div class="p-5 flex justify-between items-center border-b border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-slate-800/50 rounded-t-2xl">
        <div>
            <h3 class="text-sm font-bold text-gray-800 dark:text-white flex items-center"><i class="fas fa-users-cog mr-2 text-brand-500"></i>Gestión de Personal</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Cajeros, Empleados y Vendedores de tu negocio</p>
        </div>
        <button @click="openNew()" class="bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold px-4 py-2 rounded-lg transition-colors flex items-center shadow-sm">
            <i class="fas fa-plus"></i>
            <span class="ml-2">Nuevo Usuario</span>
        </button>
    </div>

    <!-- Modal de creación -->
    <div x-show="openCreate" class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-6 bg-slate-900/60 backdrop-blur-sm" x-cloak>
        <div @click.outside="openCreate = false" class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl w-full max-w-3xl text-left flex flex-col overflow-hidden max-h-[90vh]">
            <!-- Header Modal -->
            <div class="px-6 py-5 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center bg-gray-50/50 dark:bg-slate-800/50 shrink-0">
                <div>
                    <h3 class="font-bold text-lg text-gray-800 dark:text-white flex items-center">
                        <div class="w-8 h-8 rounded-full bg-brand-100 dark:bg-brand-900/30 flex items-center justify-center text-brand-600 mr-3">
                            <i class="fas" :class="isEditing ? 'fa-user-edit' : 'fa-user-plus'" class="text-sm"></i>
                        </div>
                        <span x-text="isEditing ? 'Editar Usuario' : 'Registrar Nuevo Usuario'"></span>
                    </h3>
                    <p class="text-xs text-gray-500 mt-1 ml-11" x-text="isEditing ? 'Modifica los datos y permisos de este empleado.' : 'Agrega personal a tu negocio y define qué áreas pueden acceder.'"></p>
                </div>
                <button @click="openCreate = false" class="modal-close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form @submit.prevent="submitForm" class="flex-1 overflow-y-auto custom-scrollbar p-6 space-y-6">
                
                <!-- Datos Personales -->
                <div>
                    <h4 class="text-sm font-bold text-gray-800 dark:text-gray-200 mb-4 flex items-center">
                        <i class="fas fa-id-card mr-2 text-gray-400"></i> Datos del Usuario
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 p-4 bg-gray-50 dark:bg-slate-900/50 rounded-xl border border-gray-100 dark:border-gray-700">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-400 mb-1.5">Nombres y Apellidos *</label>
                            <input x-model="form.full_name" required placeholder="Ej. Juan Pérez" class="w-full bg-white dark:bg-slate-800 border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-2 text-sm text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-500">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-400 mb-1.5">Cédula de Identidad *</label>
                            <input x-model="form.username" required placeholder="Ej. 18224757" class="w-full bg-white dark:bg-slate-800 border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-2 text-sm text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-500">
                            <p class="text-[10px] text-gray-500 mt-1">El usuario usará su cédula para iniciar sesión.</p>
                        </div>
                    </div>
                </div>

                <!-- Seguridad y Rol -->
                <div>
                    <h4 class="text-sm font-bold text-gray-800 dark:text-gray-200 mb-4 flex items-center">
                        <i class="fas fa-lock mr-2 text-gray-400"></i> Seguridad y Rol
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5 p-4 bg-gray-50 dark:bg-slate-900/50 rounded-xl border border-gray-100 dark:border-gray-700">
                        <div class="md:col-span-1">
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-400 mb-1.5">Rol del Sistema *</label>
                            <select x-model="form.role" class="w-full bg-white dark:bg-slate-800 border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-2 text-sm text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-500">
                                <option value="vendedor">Vendedor</option>
                                <option value="empleado">Empleado</option>
                                <option value="cajero">Cajero</option>
                                <option value="administrador">Administrador Pleno</option>
                            </select>
                        </div>
                        <div class="md:col-span-1" x-data="{ show: false }">
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-400 mb-1.5">Contraseña <span x-show="!isEditing">*</span></label>
                            <div class="relative">
                                <input x-bind:type="show ? 'text' : 'password'" x-model="form.password" :required="!isEditing" minlength="8" class="w-full bg-white dark:bg-slate-800 border border-gray-200 dark:border-gray-700 rounded-lg pl-3 pr-9 py-2 text-sm text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-500">
                                <button type="button" @click="show = !show" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                    <i class="fas" :class="show ? 'fa-eye-slash' : 'fa-eye'"></i>
                                </button>
                            </div>
                            <p class="text-[10px] text-gray-500 mt-1 leading-tight">Mínimo 8 caracteres (mayúscula, minúscula y número). <span x-show="isEditing">Dejar en blanco para conservar actual</span></p>
                        </div>
                        <div class="md:col-span-1" x-data="{ show: false }">
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-400 mb-1.5">Confirmar Contraseña <span x-show="!isEditing">*</span></label>
                            <div class="relative">
                                <input x-bind:type="show ? 'text' : 'password'" x-model="form.confirm_password" :required="!isEditing" minlength="8" class="w-full bg-white dark:bg-slate-800 border border-gray-200 dark:border-gray-700 rounded-lg pl-3 pr-9 py-2 text-sm text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-500" :class="{'border-red-500': form.confirm_password && form.password !== form.confirm_password}">
                                <button type="button" @click="show = !show" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                    <i class="fas" :class="show ? 'fa-eye-slash' : 'fa-eye'"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Permisos (Checkboxes) -->
                <div>
                    <h4 class="text-sm font-bold text-gray-800 dark:text-gray-200 mb-4 flex items-center">
                        <i class="fas fa-shield-alt mr-2 text-gray-400"></i> Permisos de Módulos (Visibilidad)
                    </h4>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 p-4 bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-gray-700">
                        <label class="flex items-center space-x-3 text-sm text-gray-700 dark:text-gray-300 p-3 rounded-lg bg-gray-50 dark:bg-slate-900/50 cursor-pointer border border-transparent hover:border-brand-200 transition-all">
                            <input type="checkbox" value="pos" x-model="form.permissions" class="w-4 h-4 rounded text-brand-600 focus:ring-brand-500 bg-white border-gray-300">
                            <div class="flex flex-col">
                                <span class="font-bold">Módulo de Venta (POS)</span>
                                <span class="text-[10px] text-gray-500 mt-0.5">Facturación, creación de pedidos rápidos</span>
                            </div>
                        </label>
                        <label class="flex items-center space-x-3 text-sm text-gray-700 dark:text-gray-300 p-3 rounded-lg bg-gray-50 dark:bg-slate-900/50 cursor-pointer border border-transparent hover:border-brand-200 transition-all">
                            <input type="checkbox" value="inventory" x-model="form.permissions" class="w-4 h-4 rounded text-brand-600 focus:ring-brand-500 bg-white border-gray-300">
                            <div class="flex flex-col">
                                <span class="font-bold">Almacén e Inventario</span>
                                <span class="text-[10px] text-gray-500 mt-0.5">Crear productos, control de stock, kardex</span>
                            </div>
                        </label>
                        <label class="flex items-center space-x-3 text-sm text-gray-700 dark:text-gray-300 p-3 rounded-lg bg-gray-50 dark:bg-slate-900/50 cursor-pointer border border-transparent hover:border-brand-200 transition-all">
                            <input type="checkbox" value="clients" x-model="form.permissions" class="w-4 h-4 rounded text-brand-600 focus:ring-brand-500 bg-white border-gray-300">
                            <div class="flex flex-col">
                                <span class="font-bold">Base de Datos de Clientes</span>
                                <span class="text-[10px] text-gray-500 mt-0.5">Ver y gestionar historial de clientes</span>
                            </div>
                        </label>
                        <label class="flex items-center space-x-3 text-sm text-gray-700 dark:text-gray-300 p-3 rounded-lg bg-gray-50 dark:bg-slate-900/50 cursor-pointer border border-transparent hover:border-brand-200 transition-all">
                            <input type="checkbox" value="reports" x-model="form.permissions" class="w-4 h-4 rounded text-brand-600 focus:ring-brand-500 bg-white border-gray-300">
                            <div class="flex flex-col">
                                <span class="font-bold">Auditoría y Reportes</span>
                                <span class="text-[10px] text-gray-500 mt-0.5">Acceso a finanzas y estadísticas del negocio</span>
                            </div>
                        </label>
                        <label class="md:col-span-2 flex items-center space-x-3 text-sm text-gray-700 dark:text-gray-300 p-3 rounded-lg bg-gray-50 dark:bg-slate-900/50 cursor-pointer border border-transparent hover:border-brand-200 transition-all">
                            <input type="checkbox" value="settings" x-model="form.permissions" class="w-4 h-4 rounded text-brand-600 focus:ring-brand-500 bg-white border-gray-300">
                            <div class="flex flex-col">
                                <span class="font-bold">Configuración Plena</span>
                                <span class="text-[10px] text-gray-500 mt-0.5">Ajustes de tienda, impuestos y diseño público</span>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="pt-2 flex justify-end gap-3 border-t border-gray-100 dark:border-gray-700 shrink-0">
                    <button type="button" @click="openCreate = false" class="px-5 py-2.5 text-sm font-bold text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-700 rounded-lg transition-colors">Cancelar</button>
                    <button type="submit" class="bg-gray-800 hover:bg-gray-900 dark:bg-brand-600 dark:hover:bg-brand-700 text-white font-bold py-2.5 px-6 rounded-lg shadow-sm transition-all text-sm flex items-center">
                        <i class="fas" :class="isEditing ? 'fa-save' : 'fa-check-circle'" class="mr-2"></i> 
                        <span x-text="isEditing ? 'Guardar Cambios' : 'Crear Usuario'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla de Personal -->
    <div class="overflow-x-auto w-full">
        <table class="w-full text-left border-collapse whitespace-nowrap">
            <thead>
                <tr class="bg-gray-50/80 dark:bg-slate-900/80 border-b border-gray-200 dark:border-gray-700/50 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                    <th class="p-4 rounded-tl-lg">Usuario</th>
                    <th class="p-4">Rol</th>
                    <th class="p-4">Estado</th>
                    <th class="p-4 text-right rounded-tr-lg">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800/50 text-sm">
                <?php if(empty($subUsers)): ?>
                    <tr>
                        <td colspan="4" class="p-10 text-center text-gray-400">
                            <i class="fas fa-users-slash text-4xl mb-3 opacity-40"></i>
                            <p class="font-medium text-base">No has agregado personal aún.</p>
                            <p class="text-xs text-gray-500 mt-1">Podrás darles permisos exclusivos de acceso aquí.</p>
                        </td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($subUsers as $u): ?>
                <tr class="hover:bg-gray-50 dark:hover:bg-slate-800/50 transition-colors group">
                    <td class="p-4 font-medium text-gray-800 dark:text-gray-200">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-full bg-brand-100 text-brand-600 dark:bg-brand-900/30 flex justify-center items-center font-bold text-sm">
                                <?= strtoupper(substr($u['full_name'] ?: $u['username'], 0, 1)) ?>
                            </div>
                            <div>
                                <p class="text-sm"><?= htmlspecialchars($u['full_name'] ?: $u['username']) ?></p>
                                <p class="text-xs text-gray-400 font-normal">@<?= htmlspecialchars($u['username']) ?></p>
                            </div>
                        </div>
                    </td>
                    <td class="p-4 text-brand-600 dark:text-brand-400 capitalize text-xs font-bold"><?= $u['role'] ?></td>
                    <td class="p-4">
                        <?php if ($u['status'] == 1): ?>
                            <span class="px-2.5 py-1 bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-400 rounded-full text-[10px] font-bold uppercase border border-green-200 dark:border-green-800">Activo</span>
                        <?php else: ?>
                            <span class="px-2.5 py-1 bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-400 rounded-full text-[10px] font-bold uppercase border border-red-200 dark:border-red-800">Inactivo</span>
                        <?php endif; ?>
                    </td>
                    <td class="p-4 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <button @click="editUser(<?= $u['id'] ?>)" class="text-gray-400 hover:text-blue-500 transition-colors p-2 rounded-full hover:bg-blue-50 dark:hover:bg-slate-700" title="Editar">
                                <i class="fas fa-edit text-lg"></i>
                            </button>
                            <button @click="toggleStatus(<?= $u['id'] ?>)" class="text-gray-400 hover:text-brand-500 transition-colors p-2 rounded-full hover:bg-gray-100 dark:hover:bg-slate-700" title="Activar/Desactivar">
                                <i class="fas <?= $u['status'] == 1 ? 'fa-ban' : 'fa-check-circle' ?> text-lg"></i>
                            </button>
                            <button @click="deleteUser(<?= $u['id'] ?>)" class="text-gray-400 hover:text-red-500 transition-colors p-2 rounded-full hover:bg-red-50 dark:hover:bg-slate-700" title="Eliminar Permanente">
                                <i class="fas fa-trash-alt text-lg"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<script>
function closeOtherSessions() {
    if (!confirm('¿Cerrar la sesión abierta en otras computadoras o dispositivos? Tendrás que volver a iniciar sesión allí.')) return;
    fetch('<?= BASE_URL ?>users/closeOtherSessions', { method: 'POST' })
    .then(r => r.json()).then(res => {
        if (res.success) {
            showToast(res.message, 'success');
            setTimeout(() => window.location.reload(), 1500);
        } else {
            showToast(res.message || 'Error al cerrar las sesiones remotas', 'error');
        }
    }).catch(() => showToast('Error de conexión', 'error'));
}
</script>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>
