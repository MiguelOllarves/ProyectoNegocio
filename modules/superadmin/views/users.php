<?php include __DIR__ . '/../../../includes/header.php'; ?>

<div class="mb-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
    <div>
        <h2 class="text-2xl font-extrabold text-gray-800 dark:text-white tracking-tight"><i class="fas fa-crown text-amber-500 mr-2"></i> Panel de Control (Modo Dios)</h2>
        <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">Gestión Absoluta del Ecosistema Tu Inventario.</p>
    </div>
</div>

<!-- NAVEGACIÓN MODO DIOS -->
<div class="flex border-b border-gray-200 dark:border-gray-700 mb-8 overflow-x-auto hide-scrollbar">
    <a href="<?= BASE_URL ?>superadmin" class="px-6 py-3 border-b-2 border-transparent font-bold text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 hover:border-gray-300 transition-all whitespace-nowrap">
        <i class="fas fa-chart-pie mr-2"></i> Dashboard & Analytics
    </a>
    <a href="<?= BASE_URL ?>superadmin/users" class="px-6 py-3 border-b-2 font-bold text-sm border-brand-500 text-brand-600 dark:text-brand-400 whitespace-nowrap">
        <i class="fas fa-users-cog mr-2"></i> Usuarios (Gran Hermano)
    </a>
    <a href="<?= BASE_URL ?>superadmin/security" class="px-6 py-3 border-b-2 border-transparent font-bold text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 hover:border-gray-300 transition-all whitespace-nowrap flex items-center">
        <i class="fas fa-shield-alt mr-2"></i> Centro de Seguridad
    </a>
</div>

<div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
    <div class="p-5 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center bg-gray-50 dark:bg-slate-800/80">
        <h3 class="text-base font-bold text-gray-800 dark:text-white"><i class="fas fa-users text-brand-500 mr-2"></i> Base de Datos Global de Usuarios</h3>
        <span class="text-sm text-gray-500 font-bold bg-gray-200 dark:bg-gray-700 px-3 py-1 rounded-full"><?= count($users) ?> Registros</span>
    </div>
    
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm text-gray-600 dark:text-gray-400">
            <thead>
                <tr class="bg-gray-50 dark:bg-gray-800/50 uppercase text-[10px] tracking-wider text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700">
                    <th class="py-3 px-6 font-bold">ID</th>
                    <th class="py-3 px-6 font-bold">Negocio</th>
                    <th class="py-3 px-6 font-bold">Cédula/User</th>
                    <th class="py-3 px-6 font-bold">Nombre Completo</th>
                    <th class="py-3 px-6 font-bold">Rol</th>
                    <th class="py-3 px-6 font-bold text-right">Acciones Peligrosas</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                <?php foreach($users as $u): ?>
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                    <td class="py-3 px-6 font-bold text-gray-800 dark:text-white">#<?= $u['id'] ?></td>
                    <td class="py-3 px-6">
                        <?php if($u['business_name']): ?>
                            <span class="font-bold text-brand-600 dark:text-brand-400"><?= htmlspecialchars($u['business_name']) ?></span>
                        <?php else: ?>
                            <span class="text-gray-400 italic">Global (<?= htmlspecialchars($u['role']) ?>)</span>
                        <?php endif; ?>
                    </td>
                    <td class="py-3 px-6 font-medium"><?= htmlspecialchars($u['username']) ?></td>
                    <td class="py-3 px-6"><?= htmlspecialchars($u['full_name']) ?></td>
                    <td class="py-3 px-6">
                        <span class="px-2 py-1 text-[10px] uppercase font-bold rounded-full <?= $u['role'] === 'super_admin' ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300' ?>">
                            <?= htmlspecialchars($u['role']) ?>
                        </span>
                    </td>
                    <td class="py-3 px-6 text-right space-x-2">
                        <?php if($u['role'] !== 'super_admin'): ?>
                            <button onclick="impersonateUser(<?= $u['id'] ?>, '<?= htmlspecialchars($u['username']) ?>')" class="text-blue-500 hover:text-blue-700 bg-blue-50 hover:bg-blue-100 dark:bg-blue-900/30 dark:hover:bg-blue-800 p-2 rounded-lg transition-colors" title="Iniciar Sesión como este usuario">
                                <i class="fas fa-sign-in-alt"></i>
                            </button>
                            <button onclick="changePassword(<?= $u['id'] ?>, '<?= htmlspecialchars($u['username']) ?>')" class="text-orange-500 hover:text-orange-700 bg-orange-50 hover:bg-orange-100 dark:bg-orange-900/30 dark:hover:bg-orange-800 p-2 rounded-lg transition-colors" title="Cambiar Contraseña">
                                <i class="fas fa-key"></i>
                            </button>
                        <?php else: ?>
                            <span class="text-xs text-gray-400 italic">No editable</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if(empty($users)): ?>
                <tr>
                    <td colspan="6" class="py-8 text-center text-gray-500">No hay usuarios registrados.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Cambiar Contraseña -->
<div id="pwdModal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm items-center justify-center z-50">
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl w-full max-w-md p-6 border border-gray-100 dark:border-gray-700 transform scale-95 transition-all">
        <h3 class="text-xl font-bold text-gray-800 dark:text-white mb-2">Cambiar Contraseña a la Fuerza</h3>
        <p class="text-gray-500 dark:text-gray-400 text-sm mb-6">Vas a cambiar la clave del usuario: <strong id="pwdTargetUser" class="text-brand-600"></strong></p>
        
        <input type="hidden" id="pwdUserId">
        <div class="mb-6">
            <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Nueva Contraseña</label>
            <input type="text" id="pwdNewInput" class="w-full bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white rounded-lg p-3 font-mono text-center text-lg focus:ring-2 focus:ring-brand-500 focus:border-brand-500" placeholder="Escribe aquí..." autocomplete="off">
            <p class="text-[10px] text-gray-500 dark:text-gray-400 mt-2 leading-tight text-center">Mínimo 8 caracteres. Debe incluir al menos una mayúscula, una minúscula y un número.</p>
        </div>
        
        <div class="flex justify-end gap-3">
            <button onclick="closePwdModal()" class="px-5 py-2.5 text-sm font-bold text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl transition-all">Cancelar</button>
            <button onclick="submitPwdChange()" class="px-5 py-2.5 text-sm font-bold text-white bg-orange-500 hover:bg-orange-600 rounded-xl shadow-lg shadow-orange-500/30 transition-all flex items-center">
                <i class="fas fa-save mr-2"></i> Aplicar Cambio
            </button>
        </div>
    </div>
</div>

<script>
function impersonateUser(id, username) {
    if(confirm(`⚠️ ATENCIÓN: Vas a iniciar sesión como "${username}".\nTu sesión actual de Superadmin se cerrará y entrarás al sistema como si fueras este usuario.\n\n¿Continuar?`)) {
        const fd = new FormData();
        fd.append('user_id', id);
        
        fetch('<?= BASE_URL ?>superadmin/impersonate', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            if(res.success) {
                window.location.href = '<?= BASE_URL ?>';
            } else {
                alert(res.message || 'Error al impersonar');
            }
        });
    }
}

const pwdModal = document.getElementById('pwdModal');
function changePassword(id, username) {
    document.getElementById('pwdUserId').value = id;
    document.getElementById('pwdTargetUser').innerText = username;
    document.getElementById('pwdNewInput').value = '';
    pwdModal.classList.remove('hidden');
    pwdModal.classList.add('flex');
    pwdModal.children[0].classList.replace('scale-95', 'scale-100');
    setTimeout(() => document.getElementById('pwdNewInput').focus(), 100);
}

function closePwdModal() {
    pwdModal.children[0].classList.replace('scale-100', 'scale-95');
    setTimeout(() => {
        pwdModal.classList.remove('flex');
        pwdModal.classList.add('hidden');
    }, 200);
}

function submitPwdChange() {
    const id = document.getElementById('pwdUserId').value;
    const pwd = document.getElementById('pwdNewInput').value;
    
    if(pwd.length < 4) { alert('La contraseña debe tener al menos 4 caracteres.'); return; }
    
    const fd = new FormData();
    fd.append('user_id', id);
    fd.append('new_password', pwd);
    
    fetch('<?= BASE_URL ?>superadmin/changeUserPassword', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(res => {
        if(res.success) {
            alert('¡Contraseña cambiada exitosamente!');
            closePwdModal();
        } else {
            alert(res.message || 'Ocurrió un error.');
        }
    });
}
</script>
