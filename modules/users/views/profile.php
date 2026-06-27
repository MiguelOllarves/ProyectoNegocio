<?php include __DIR__ . '/../../../includes/header.php'; ?>

<div class="mb-6">
    <h2 class="text-2xl font-extrabold text-gray-800 dark:text-white tracking-tight">Mi Perfil</h2>
    <p class="text-gray-500 dark:text-gray-400 text-sm font-medium mt-1">Preferencias y Seguridad de la Cuenta</p>
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

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Columna Izquierda: Opciones de Perfil y Preferencias -->
    <div class="lg:col-span-1 space-y-6">
        
        <!-- Tarjeta de Identidad -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-800 p-6 text-center transform transition-all hover:shadow-md">
            <div class="w-24 h-24 rounded-full bg-gradient-to-r from-brand-500 to-accent-500 mx-auto flex items-center justify-center text-3xl font-black text-white shadow-inner mb-4">
                <?= strtoupper(substr($user['username'], 0, 1)) ?>
            </div>
            <h3 class="text-xl font-bold text-gray-800 dark:text-white">@<?= htmlspecialchars($user['username']) ?></h3>
            <p class="text-sm font-medium text-brand-600 dark:text-brand-400 uppercase tracking-widest mt-1 mb-4"><?= htmlspecialchars($user['role']) ?></p>
            <div class="inline-flex px-3 py-1 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 text-green-600 dark:text-green-400 rounded-full text-xs font-bold items-center">
                <i class="fas fa-circle text-[8px] mr-1.5"></i> Cuenta Activa
            </div>
        </div>

        <!-- Preferencias -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-800 p-6">
            <h4 class="text-sm font-bold text-gray-800 dark:text-white mb-4 border-b border-gray-100 dark:border-gray-800 pb-2"><i class="fas fa-sliders-h text-brand-500 mr-2"></i>Preferencias de Interfaz</h4>
            
            <div class="flex items-center justify-between mt-4">
                <div>
                    <p class="text-sm font-bold text-gray-700 dark:text-gray-300">Modo Oscuro</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Mejora la visibilidad en entornos oscuros</p>
                </div>
                <button onclick="toggleTheme()" class="w-12 h-6 rounded-full bg-gray-200 dark:bg-brand-500 relative transition-colors focus:outline-none">
                    <div class="w-5 h-5 bg-white rounded-full absolute top-0.5 left-0.5 dark:left-6 transition-all shadow-sm"></div>
                </button>
            </div>
        </div>

        <!-- Seguridad -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-800 p-6">
            <h4 class="text-sm font-bold text-gray-800 dark:text-white mb-4 border-b border-gray-100 dark:border-gray-800 pb-2"><i class="fas fa-lock text-brand-500 mr-2"></i>Cambiar Contraseña</h4>
            
            <form action="<?= BASE_URL ?>users/updatePassword" method="POST" class="space-y-4 pt-2">
                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-400 mb-1">Contraseña Actual *</label>
                    <input type="password" name="current_password" required class="w-full bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-2 text-sm text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-400 mb-1">Nueva Contraseña *</label>
                    <input type="password" name="new_password" required class="w-full bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-2 text-sm text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-400 mb-1">Confirmar Nueva Contraseña *</label>
                    <input type="password" name="confirm_password" required class="w-full bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-2 text-sm text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-500">
                </div>
                <button type="submit" class="w-full bg-gray-800 hover:bg-gray-900 dark:bg-gray-700 dark:hover:bg-gray-600 text-white font-bold py-2.5 px-4 rounded-lg shadow-sm transition-all text-sm mt-2">
                    Actualizar Contraseña
                </button>
            </form>
        </div>
    </div>

    <!-- Columna Derecha: Historial de Sesión -->
    <div class="lg:col-span-2">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-800 flex flex-col h-[740px]">
            <div class="p-5 border-b border-gray-100 dark:border-gray-800 flex justify-between items-center bg-gray-50/50 dark:bg-slate-800/50">
                <h3 class="text-sm font-bold text-gray-800 dark:text-white"><i class="fas fa-satellite-dish text-brand-500 mr-2"></i>Historial de Actividad Reciente</h3>
                <span class="text-xs font-bold text-brand-600 dark:text-brand-400 bg-brand-50 dark:bg-brand-900/30 px-2.5 py-1 rounded-md uppercase tracking-wider">Últimos 30 eventos</span>
            </div>
            
            <div class="p-5 flex-1 overflow-y-auto space-y-4">
                <?php if (empty($auditLogs)): ?>
                    <div class="text-center text-gray-400 mt-20">
                        <i class="fas fa-folder-open text-4xl block mb-3 opacity-30"></i>
                        <p class="text-sm font-medium">No hay actividad registrada bajo tu cuenta.</p>
                    </div>
                <?php else: ?>
                    <div class="relative border-l border-gray-200 dark:border-gray-700 ml-3 space-y-6 pb-4 cursor-default">
                        <?php foreach($auditLogs as $log): ?>
                        <div class="relative pl-6 hover:bg-gray-50 dark:hover:bg-slate-800 p-2 -ml-2 rounded-lg transition-colors group">
                            <!-- Timeline Dot -->
                            <div class="absolute w-3 h-3 bg-brand-500 rounded-full -left-[1.35rem] top-[0.6rem] ring-4 ring-white dark:ring-slate-800 group-hover:scale-125 transition-transform"></div>
                            
                            <!-- Audit Content -->
                            <div class="flex justify-between items-start mb-1">
                                <h4 class="text-sm font-bold text-gray-800 dark:text-white uppercase tracking-wider">Acción: <?= htmlspecialchars($log['action']) ?></h4>
                                <span class="text-xs font-semibold text-gray-400 dark:text-gray-500"><?= date('d M, Y H:i', strtotime($log['created_at'])) ?></span>
                            </div>
                            
                            <p class="text-xs font-medium text-gray-600 dark:text-gray-300 mt-1">
                                En sección: <span class="text-brand-600 dark:text-brand-400 font-bold"><?= htmlspecialchars($log['table_name']) ?></span> (ID: <?= $log['record_id'] ?>)
                            </p>
                            
                            <?php if(!empty($log['details']) && $log['details'] !== '[]'): ?>
                            <div class="mt-2 bg-gray-50 dark:bg-slate-900 border border-gray-100 dark:border-gray-700 rounded p-2 overflow-x-auto max-h-24 overflow-y-auto w-full">
                                <pre class="text-[10px] text-gray-500 dark:text-gray-400 font-mono"><?= htmlspecialchars(json_encode(json_decode($log['details']), JSON_PRETTY_PRINT)) ?></pre>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>
