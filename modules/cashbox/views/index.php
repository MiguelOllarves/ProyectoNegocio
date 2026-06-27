<?php include __DIR__ . '/../../../includes/header.php'; ?>

<div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4" x-data="{ openModal: false, closeCajaModal: false }">
    <div>
        <h2 class="text-2xl font-bold text-gray-800 dark:text-white"><i class="fas fa-cash-register mr-2 text-brand-500"></i>Arqueo de Caja</h2>
        <p class="text-gray-600 dark:text-gray-400 text-sm">Apertura, cierre y cuadre de turnos</p>
    </div>

    <?php if (!$openSession): ?>
        <button @click="openModal = true" class="bg-green-600 hover:bg-green-700 text-white px-5 py-2.5 rounded-lg shadow-sm transition-all flex items-center font-medium">
            <i class="fas fa-lock-open mr-2"></i> Abrir Caja
        </button>
    <?php else: ?>
        <button @click="closeCajaModal = true" class="bg-red-600 hover:bg-red-700 text-white px-5 py-2.5 rounded-lg shadow-sm transition-all flex items-center font-medium animate-pulse">
            <i class="fas fa-lock mr-2"></i> Cerrar Caja
        </button>
    <?php endif; ?>

    <!-- ═══════ Modal: Apertura de Caja ═══════ -->
    <?php if (!$openSession): ?>
    <div x-show="openModal" class="fixed inset-0 z-50 overflow-y-auto" style="display:none;">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div x-show="openModal" x-transition.opacity class="fixed inset-0 bg-gray-900/75 backdrop-blur-sm" @click="openModal = false"></div>
            <div x-show="openModal" x-transition class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-md p-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-1">Apertura de Caja</h3>
                <p class="text-sm text-gray-500 mb-4">Ingresa el monto base con el que inicias la jornada</p>
                <form action="<?= BASE_URL ?>cashbox/open" method="POST" class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Base USD ($)</label>
                            <input type="number" step="0.01" name="monto_inicial_usd" required min="0" value="0.00" class="mt-1 block w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md p-2 focus:ring-brand-500 focus:border-brand-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Base Bs.</label>
                            <input type="number" step="0.01" name="monto_inicial_bs" min="0" value="0.00" class="mt-1 block w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md p-2 focus:ring-brand-500 focus:border-brand-500">
                        </div>
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="openModal = false" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-md hover:bg-gray-200 font-medium">Cancelar</button>
                        <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 font-medium"><i class="fas fa-lock-open mr-1"></i>Confirmar Apertura</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ═══════ Modal: Cierre de Caja ═══════ -->
    <?php if ($openSession): ?>
    <div x-show="closeCajaModal" class="fixed inset-0 z-50 overflow-y-auto" style="display:none;">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div x-show="closeCajaModal" x-transition.opacity class="fixed inset-0 bg-gray-900/75 backdrop-blur-sm" @click="closeCajaModal = false"></div>
            <div x-show="closeCajaModal" x-transition class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-lg p-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-1">Cierre y Cuadre de Caja</h3>
                <p class="text-sm text-gray-500 mb-4">Declara lo que cuentas físicamente. El sistema evaluará la diferencia.</p>
                
                <form action="<?= BASE_URL ?>cashbox/close" method="POST" class="space-y-4">
                    <input type="hidden" name="session_id" value="<?= $openSession['id'] ?>">
                    <input type="hidden" name="ventas_usd" value="<?= $ventasUsd ?>">
                    <input type="hidden" name="ventas_bs" value="<?= $ventasBs ?>">

                    <!-- Resumen del Sistema -->
                    <div class="grid grid-cols-2 gap-3">
                        <div class="bg-blue-50 dark:bg-blue-900/30 p-3 rounded-lg border border-blue-100 dark:border-blue-800">
                            <div class="text-xs text-blue-600 dark:text-blue-400 uppercase font-bold">Esperado USD</div>
                            <div class="text-xl font-bold text-blue-900 dark:text-blue-200">$<?= number_format($esperadoUsd, 2) ?></div>
                            <div class="text-xs text-blue-500 mt-1">Base $<?= number_format($openSession['monto_inicial_usd'], 2) ?> + Ventas $<?= number_format($ventasUsd, 2) ?></div>
                        </div>
                        <div class="bg-amber-50 dark:bg-amber-900/30 p-3 rounded-lg border border-amber-100 dark:border-amber-800">
                            <div class="text-xs text-amber-600 dark:text-amber-400 uppercase font-bold">Esperado Bs</div>
                            <div class="text-xl font-bold text-amber-900 dark:text-amber-200">Bs.<?= number_format($esperadoBs, 2) ?></div>
                            <div class="text-xs text-amber-500 mt-1">Base Bs.<?= number_format($openSession['monto_inicial_bs'], 2) ?> + Ventas Bs.<?= number_format($ventasBs, 2) ?></div>
                        </div>
                    </div>

                    <!-- Conteo Físico -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Conteo Físico USD ($)</label>
                            <input type="number" step="0.01" name="declarado_usd" required min="0" class="mt-1 block w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md p-2">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Conteo Físico Bs.</label>
                            <input type="number" step="0.01" name="declarado_bs" required min="0" class="mt-1 block w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md p-2">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Notas / Justificación</label>
                        <textarea name="notes" rows="2" class="mt-1 block w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md p-2" placeholder="Ej: Se usó efectivo para comprar insumos..."></textarea>
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="closeCajaModal = false" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-md hover:bg-gray-200 font-medium">Cancelar</button>
                        <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 font-medium"><i class="fas fa-lock mr-1"></i>Ejecutar Cierre</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- ═══════ Tarjetas de Estado ═══════ -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-5 mb-8">
    <div class="bg-white dark:bg-gray-800 p-5 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
        <h4 class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Estado</h4>
        <?php if ($openSession): ?>
            <div class="text-xl font-bold text-green-600 mt-2 flex items-center"><i class="fas fa-lock-open mr-2"></i>ABIERTA</div>
            <div class="text-xs text-gray-500 mt-1">Desde: <?= htmlspecialchars($openSession['fecha_apertura']) ?></div>
        <?php else: ?>
            <div class="text-xl font-bold text-gray-400 mt-2 flex items-center"><i class="fas fa-lock mr-2"></i>CERRADA</div>
            <div class="text-xs text-gray-500 mt-1">Sin turno activo</div>
        <?php endif; ?>
    </div>
    <div class="bg-white dark:bg-gray-800 p-5 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
        <h4 class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Base Inicial</h4>
        <div class="text-xl font-bold text-gray-800 dark:text-gray-100 mt-2">$<?= number_format($openSession['monto_inicial_usd'] ?? 0, 2) ?></div>
        <div class="text-sm text-gray-500 mt-1">Bs. <?= number_format($openSession['monto_inicial_bs'] ?? 0, 2) ?></div>
    </div>
    <div class="bg-white dark:bg-gray-800 p-5 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
        <h4 class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Ventas del Turno</h4>
        <div class="text-xl font-bold text-brand-600 mt-2">$<?= number_format($ventasUsd, 2) ?></div>
        <div class="text-sm text-gray-500 mt-1">Bs. <?= number_format($ventasBs, 2) ?></div>
    </div>
    <div class="bg-white dark:bg-gray-800 p-5 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
        <h4 class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total Esperado</h4>
        <div class="text-xl font-bold text-green-600 mt-2 border-b border-green-100 pb-1">$<?= number_format($esperadoUsd, 2) ?></div>
        <div class="text-sm text-gray-500 mt-1">Bs. <?= number_format($esperadoBs, 2) ?></div>
    </div>
</div>

<!-- ═══════ Historial de Arqueos ═══════ -->
<div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-700/30">
        <h3 class="font-bold text-gray-800 dark:text-white"><i class="fas fa-history mr-2 text-gray-400"></i>Historial de Turnos</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr class="bg-gray-50 dark:bg-gray-700 text-gray-500 dark:text-gray-300 uppercase text-xs tracking-wider border-b dark:border-gray-600">
                    <th class="p-4 font-semibold">Apertura / Cierre</th>
                    <th class="p-4 font-semibold">Usuario</th>
                    <th class="p-4 font-semibold text-right">Esperado USD</th>
                    <th class="p-4 font-semibold text-right">Declarado USD</th>
                    <th class="p-4 font-semibold text-right">Diferencia</th>
                    <th class="p-4 font-semibold text-center">Estado</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                <?php if (empty($sessions)): ?>
                    <tr><td colspan="6" class="p-8 text-center text-gray-400"><i class="fas fa-inbox text-3xl block mb-2 opacity-30"></i>No hay sesiones registradas.</td></tr>
                <?php else: foreach ($sessions as $s): ?>
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                    <td class="p-4 text-sm">
                        <div class="font-bold text-gray-900 dark:text-gray-100"><i class="fas fa-sign-in-alt text-green-400 mr-1"></i><?= htmlspecialchars($s['fecha_apertura']) ?></div>
                        <?php if ($s['fecha_cierre']): ?>
                            <div class="text-xs text-gray-500 mt-1"><i class="fas fa-sign-out-alt text-red-400 mr-1"></i><?= htmlspecialchars($s['fecha_cierre']) ?></div>
                        <?php else: ?>
                            <div class="text-xs text-green-500 mt-1 animate-pulse font-bold">⏳ En progreso...</div>
                        <?php endif; ?>
                    </td>
                    <td class="p-4 font-bold text-gray-800 dark:text-gray-200"><?= htmlspecialchars($s['username'] ?? 'N/A') ?></td>
                    <td class="p-4 text-right text-sm">
                        <?php if ($s['estado'] === 'cerrada'): ?>
                            <div class="font-bold text-gray-700 dark:text-gray-300">$<?= number_format($s['monto_inicial_usd'] + $s['ventas_usd'], 2) ?></div>
                        <?php else: echo '-'; endif; ?>
                    </td>
                    <td class="p-4 text-right text-sm">
                        <?php if ($s['estado'] === 'cerrada'): ?>
                            <div class="font-bold text-gray-800 dark:text-gray-100">$<?= number_format($s['declarado_usd'], 2) ?></div>
                        <?php else: echo '-'; endif; ?>
                    </td>
                    <td class="p-4 text-right font-bold">
                        <?php if ($s['estado'] === 'cerrada'):
                            $d = $s['diferencia_usd'];
                            if ($d < 0) echo "<span class='text-red-600'>-$" . number_format(abs($d), 2) . "</span>";
                            elseif ($d > 0) echo "<span class='text-green-600'>+$" . number_format($d, 2) . "</span>";
                            else echo "<span class='text-gray-400'>$0.00</span>";
                        else: echo '-'; endif; ?>
                    </td>
                    <td class="p-4 text-center">
                        <?php if ($s['estado'] === 'abierta'): ?>
                            <span class="bg-green-100 text-green-700 px-2.5 py-1 rounded-full text-xs font-bold uppercase"><i class="fas fa-lock-open mr-1"></i>Abierta</span>
                        <?php else: ?>
                            <span class="bg-gray-200 dark:bg-gray-600 text-gray-600 dark:text-gray-300 px-2.5 py-1 rounded-full text-xs font-bold uppercase"><i class="fas fa-lock mr-1"></i>Cerrada</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>
