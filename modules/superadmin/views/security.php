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
    <a href="<?= BASE_URL ?>superadmin/users" class="px-6 py-3 border-b-2 border-transparent font-bold text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 hover:border-gray-300 transition-all whitespace-nowrap">
        <i class="fas fa-users-cog mr-2"></i> Usuarios (Gran Hermano)
    </a>
    <a href="<?= BASE_URL ?>superadmin/security" class="px-6 py-3 border-b-2 font-bold text-sm border-brand-500 text-brand-600 dark:text-brand-400 whitespace-nowrap flex items-center">
        <i class="fas fa-shield-alt mr-2"></i> Centro de Seguridad
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- COLUMNA IZQUIERDA: BLOQUEO DE IPs -->
    <div class="lg:col-span-1 space-y-6">
        
        <!-- Formulario de Baneo -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-red-200 dark:border-red-900/50 p-6 relative overflow-hidden">
            <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-red-500 to-rose-600"></div>
            <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-4 flex items-center">
                <i class="fas fa-ban text-red-500 mr-2"></i> Bloqueo Manual de IP
            </h3>
            
            <form onsubmit="banIp(event)">
                <div class="mb-4">
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Dirección IP (IPv4 o IPv6)</label>
                    <input type="text" id="banIpInput" required class="w-full bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white rounded-lg p-3 focus:ring-2 focus:ring-red-500 focus:border-red-500 font-mono" placeholder="Ej: 192.168.1.100">
                </div>
                <div class="mb-4">
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Motivo del Bloqueo</label>
                    <input type="text" id="banReasonInput" required class="w-full bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white rounded-lg p-3 focus:ring-2 focus:ring-red-500 focus:border-red-500" placeholder="Ej: Intento de SQL Injection">
                </div>
                <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-3 rounded-xl transition-all shadow-lg shadow-red-500/30 flex justify-center items-center">
                    <i class="fas fa-gavel mr-2"></i> Aplicar Bloqueo Permanente
                </button>
            </form>
        </div>

        <!-- Lista de Baneos -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="p-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-slate-800/80 flex justify-between items-center">
                <h4 class="font-bold text-gray-800 dark:text-white text-sm">IPs Bloqueadas (Lista Negra)</h4>
                <span class="text-xs bg-red-100 text-red-700 font-bold px-2 py-1 rounded-full"><?= count($banned_ips) ?></span>
            </div>
            <div class="max-h-80 overflow-y-auto">
                <ul class="divide-y divide-gray-100 dark:divide-gray-800">
                    <?php foreach($banned_ips as $bip): ?>
                    <li class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors flex justify-between items-start group">
                        <div>
                            <p class="font-mono font-bold text-red-600 dark:text-red-400"><?= htmlspecialchars($bip['ip_address']) ?></p>
                            <p class="text-xs text-gray-500 mt-1"><?= htmlspecialchars($bip['reason']) ?></p>
                            <p class="text-[10px] text-gray-400 mt-1"><?= date('d/m/Y H:i:s', strtotime($bip['banned_at'])) ?></p>
                        </div>
                        <button onclick="unbanIp('<?= htmlspecialchars($bip['ip_address']) ?>')" class="text-gray-400 hover:text-green-500 p-2 opacity-0 group-hover:opacity-100 transition-all" title="Desbloquear IP">
                            <i class="fas fa-unlock"></i>
                        </button>
                    </li>
                    <?php endforeach; ?>
                    <?php if(empty($banned_ips)): ?>
                    <li class="p-6 text-center text-gray-400 text-sm italic">La lista negra está vacía.</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>

    <!-- COLUMNA DERECHA: LOG DE AUDITORÍA -->
    <div class="lg:col-span-2">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 h-full flex flex-col">
            <div class="p-5 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center bg-gray-50 dark:bg-slate-800/80">
                <h3 class="text-base font-bold text-gray-800 dark:text-white"><i class="fas fa-clipboard-list text-brand-500 mr-2"></i> Log Global de Auditoría (Alertas)</h3>
            </div>
            <div class="p-0 overflow-x-auto flex-1">
                <table class="w-full text-left text-sm text-gray-600 dark:text-gray-400">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-800/50 uppercase text-[10px] tracking-wider text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700">
                            <th class="py-3 px-6 font-bold">Fecha / Hora</th>
                            <th class="py-3 px-6 font-bold">Tipo</th>
                            <th class="py-3 px-6 font-bold">IP Origen</th>
                            <th class="py-3 px-6 font-bold">Detalles de la Alerta</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        <?php foreach($alerts as $al): ?>
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                            <td class="py-3 px-6 font-medium whitespace-nowrap"><?= date('d/m H:i:s', strtotime($al['created_at'])) ?></td>
                            <td class="py-3 px-6">
                                <?php if($al['type'] === 'IMPERSONATION'): ?>
                                    <span class="px-2 py-1 text-[10px] uppercase font-bold rounded-full bg-blue-100 text-blue-700">Modo Dios</span>
                                <?php elseif($al['type'] === 'LOGIN_FAILED'): ?>
                                    <span class="px-2 py-1 text-[10px] uppercase font-bold rounded-full bg-amber-100 text-amber-700">Brute Force</span>
                                <?php else: ?>
                                    <span class="px-2 py-1 text-[10px] uppercase font-bold rounded-full bg-red-100 text-red-700"><?= htmlspecialchars($al['type']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="py-3 px-6 font-mono text-xs"><?= htmlspecialchars($al['ip_address']) ?></td>
                            <td class="py-3 px-6 text-xs text-gray-500 dark:text-gray-400"><?= htmlspecialchars($al['details']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($alerts)): ?>
                        <tr>
                            <td colspan="4" class="py-10 text-center text-gray-500">
                                <i class="fas fa-check-circle text-4xl text-green-500 mb-3 opacity-50 block"></i>
                                Todo limpio. No hay alertas de seguridad recientes.
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function banIp(e) {
    e.preventDefault();
    const ip = document.getElementById('banIpInput').value;
    const reason = document.getElementById('banReasonInput').value;
    
    const fd = new FormData();
    fd.append('ip_address', ip);
    fd.append('reason', reason);
    
    fetch('<?= BASE_URL ?>superadmin/banIp', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(res => {
        if(res.success) location.reload();
        else alert(res.message);
    });
}

function unbanIp(ip) {
    if(confirm(`¿Desbloquear la IP ${ip}? Podrá acceder nuevamente al sistema.`)) {
        const fd = new FormData();
        fd.append('ip_address', ip);
        
        fetch('<?= BASE_URL ?>superadmin/unbanIp', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            if(res.success) location.reload();
        });
    }
}
</script>
