<?php include __DIR__ . '/../../../includes/header.php'; ?>

<main class="page-transition p-4 sm:p-6 lg:p-8 max-w-7xl mx-auto space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Registro de Auditoría</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">Rastreo de actividades (Creación, Modificación, Eliminación) de datos.</p>
        </div>
        <div class="flex flex-col sm:flex-row items-center gap-4">
            <form method="GET" action="<?= BASE_URL ?>reports/auditoria" class="flex items-center gap-2">
                <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Mostrar:</label>
                <select name="limit" onchange="this.form.submit()" class="px-3 py-1.5 bg-white dark:bg-slate-900 border border-gray-200 dark:border-gray-700 rounded-lg text-sm text-gray-800 dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-brand-500">
                    <option value="10" <?= (isset($limit) && $limit == 10) ? 'selected' : '' ?>>10 filas</option>
                    <option value="50" <?= (!isset($limit) || $limit == 50) ? 'selected' : '' ?>>50 filas</option>
                    <option value="100" <?= (isset($limit) && $limit == 100) ? 'selected' : '' ?>>100 filas</option>
                    <option value="500" <?= (isset($limit) && $limit == 500) ? 'selected' : '' ?>>500 filas</option>
                </select>
            </form>
            <a href="<?= BASE_URL ?>reports/print_audit" target="_blank" class="no-print bg-white dark:bg-slate-800 border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300 px-4 py-2 rounded-lg hover:bg-gray-50 transition-colors shadow-sm font-medium text-sm flex items-center justify-center gap-2">
                <i class="fas fa-print"></i> Exportar PDF
            </a>
        </div>
    </div>

    <!-- Logs Table -->
    <div class="bg-white dark:bg-gray-800 shadow-sm border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden mt-6">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-gray-50 dark:bg-gray-900/50 text-gray-600 dark:text-gray-300 font-medium border-b border-gray-200 dark:border-gray-700">
                    <tr>
                        <th class="px-6 py-4">Fecha (UTC)</th>
                        <th class="px-6 py-4">Usuario</th>
                        <th class="px-6 py-4">Acción</th>
                        <th class="px-6 py-4">Módulo/Tabla</th>
                        <th class="px-6 py-4">ID Ref.</th>
                        <th class="px-6 py-4">Detalles (JSON)</th>
                    </tr>
                </thead>
                <tbody id="audit-tbody" class="divide-y divide-gray-100 dark:divide-gray-700/50">
                    <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500">No hay registros de auditoría aún.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach($logs as $log): 
                            $actionColor = match(strtoupper($log['action'])) {
                                'CREATE' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                                'UPDATE' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
                                'DELETE' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                                default => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-400'
                            };
                        ?>
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-750 transition-colors group">
                            <td class="px-6 py-3 whitespace-nowrap text-gray-600 dark:text-gray-400 text-xs">
                                <?php echo date('d/m/Y H:i:s', strtotime($log['created_at'])); ?>
                            </td>
                            <td class="px-6 py-3 font-medium text-gray-900 dark:text-white">
                                <?php echo htmlspecialchars($log['user_name'] ?? 'Sistema Web'); ?>
                            </td>
                            <td class="px-6 py-3">
                                <span class="px-2 py-1 rounded-md text-xs font-semibold <?php echo $actionColor; ?>">
                                    <?php echo $log['action']; ?>
                                </span>
                            </td>
                            <td class="px-6 py-3 text-gray-700 dark:text-gray-400 font-mono text-xs" colspan="2">
                                <?php echo htmlspecialchars($log['target'] ?? 'N/A'); ?>
                            </td>
                            <td class="px-6 py-3 max-w-xs truncate text-xs text-brand-600 dark:text-brand-400 font-mono cursor-pointer" onclick="Swal.fire({title: 'Detalle JSON', html: `<pre class='text-left text-xs bg-gray-100 dark:bg-gray-900 p-4 rounded-lg overflow-auto max-h-96' style='max-width: 100%; white-space: pre-wrap; word-break: break-all;'>${this.dataset.json.replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/\n/g, '<br>')}</pre>`, confirmButtonText: 'Cerrar'})" data-json='<?php echo addslashes($log['details']); ?>'>
                                <?php echo htmlspecialchars(substr($log['details'], 0, 50)) . '...'; ?>
                                <i class="fas fa-external-link-alt ml-1 opacity-0 group-hover:opacity-100 transition-opacity"></i>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php 
                            $totalRecords = $totalLogs ?? 0;
                            $colspan = 6;
                            $hxTarget = '#audit-tbody';
                            require __DIR__ . '/../../../includes/pagination.php';
                        ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>
