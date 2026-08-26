<?php include __DIR__ . '/../../../includes/header.php'; ?>

<div class="page-header" x-data="{ openModal: false, closeCajaModal: false }">
    <div>
        <h2 class="page-title"><i class="fas fa-cash-register mr-2 text-brand-500"></i>Arqueo de Caja</h2>
        <p class="page-subtitle">Apertura, cierre y cuadre de turnos</p>
    </div>

    <?php if (!$openSession): ?>
        <button @click="openModal = true" class="btn-primary w-full sm:w-auto">
            <i class="fas fa-lock-open mr-2"></i> Abrir Caja
        </button>
    <?php else: ?>
        <button @click="closeCajaModal = true" class="btn-danger w-full sm:w-auto animate-pulse">
            <i class="fas fa-lock mr-2"></i> Cerrar Caja
        </button>
    <?php endif; ?>

    <!-- ═══════ Modal: Apertura de Caja ═══════ -->
    <?php if (!$openSession): ?>
    <div x-show="openModal" x-cloak class="modal-wrapper" style="display:none;">
        <div class="modal-container">
            <div x-show="openModal" x-transition.opacity class="modal-backdrop" @click="openModal = false"></div>
            <div x-show="openModal" x-transition class="modal-card modal-card-sm animate-fade-in-up">
                <div class="modal-header">
                    <h3 class="modal-title">
                        <div class="w-8 h-8 rounded-lg bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 flex items-center justify-center"><i class="fas fa-lock-open"></i></div>
                        Apertura de Caja
                    </h3>
                    <button @click="openModal = false" class="modal-close"><i class="fas fa-times"></i></button>
                </div>
                <form action="<?= BASE_URL ?>cashbox/open" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                    <div class="modal-body space-y-4">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Ingresa el monto base con el que inicias la jornada</p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="form-label">Base USD ($)</label>
                                <input type="number" step="0.01" name="monto_inicial_usd" required min="0" value="0.00" class="form-input">
                            </div>
                            <div>
                                <label class="form-label">Base Bs.</label>
                                <input type="number" step="0.01" name="monto_inicial_bs" min="0" value="0.00" class="form-input">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer justify-end">
                        <button type="button" @click="openModal = false" class="btn-secondary">Cancelar</button>
                        <button type="submit" class="btn-primary"><i class="fas fa-lock-open mr-2"></i>Confirmar Apertura</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ═══════ Modal: Cierre de Caja ═══════ -->
    <?php if ($openSession): ?>
    <div x-show="closeCajaModal" x-cloak class="modal-wrapper" style="display:none;">
        <div class="modal-container">
            <div x-show="closeCajaModal" x-transition.opacity class="modal-backdrop" @click="closeCajaModal = false"></div>
            <div x-show="closeCajaModal" x-transition class="modal-card modal-card-md animate-fade-in-up">
                <div class="modal-header">
                    <h3 class="modal-title">
                        <div class="w-8 h-8 rounded-lg bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400 flex items-center justify-center"><i class="fas fa-lock"></i></div>
                        Cierre y Cuadre de Caja
                    </h3>
                    <button @click="closeCajaModal = false" class="modal-close"><i class="fas fa-times"></i></button>
                </div>
                <form action="<?= BASE_URL ?>cashbox/close" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                    <div class="modal-body space-y-4">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Declara lo que cuentas físicamente. El sistema evaluará la diferencia.</p>
                        <input type="hidden" name="session_id" value="<?= $openSession['id'] ?>">
                        <input type="hidden" name="ventas_usd" value="<?= $ventasUsd ?>">
                        <input type="hidden" name="ventas_bs" value="<?= $ventasBs ?>">

                        <!-- Resumen del Sistema -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
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
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="form-label">Conteo Físico USD ($)</label>
                                <input type="number" step="0.01" name="declarado_usd" required min="0" class="form-input">
                            </div>
                            <div>
                                <label class="form-label">Conteo Físico Bs.</label>
                                <input type="number" step="0.01" name="declarado_bs" required min="0" class="form-input">
                            </div>
                        </div>

                        <div>
                            <label class="form-label">Notas / Justificación</label>
                            <textarea name="notes" rows="2" class="form-input" placeholder="Ej: Se usó efectivo para comprar insumos..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer justify-end">
                        <button type="button" @click="closeCajaModal = false" class="btn-secondary">Cancelar</button>
                        <button type="submit" class="btn-danger"><i class="fas fa-lock mr-2"></i>Ejecutar Cierre</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- ═══════ Tarjetas de Estado ═══════ -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
    <div class="kpi-card">
        <div>
            <h4 class="kpi-label">Estado</h4>
            <?php if ($openSession): ?>
                <div class="text-xl font-bold text-green-600 mt-2 flex items-center"><i class="fas fa-lock-open mr-2"></i>ABIERTA</div>
                <div class="text-xs text-gray-500 mt-1">Desde: <?= htmlspecialchars($openSession['fecha_apertura']) ?></div>
            <?php else: ?>
                <div class="text-xl font-bold text-gray-400 mt-2 flex items-center"><i class="fas fa-lock mr-2"></i>CERRADA</div>
                <div class="text-xs text-gray-500 mt-1">Sin turno activo</div>
            <?php endif; ?>
        </div>
    </div>
    <div class="kpi-card">
        <div>
            <h4 class="kpi-label">Base Inicial</h4>
            <div class="text-xl font-bold text-gray-800 dark:text-gray-100 mt-2">$<?= number_format($openSession['monto_inicial_usd'] ?? 0, 2) ?></div>
            <div class="text-sm text-gray-500 mt-1">Bs. <?= number_format($openSession['monto_inicial_bs'] ?? 0, 2) ?></div>
        </div>
    </div>
    <div class="kpi-card">
        <div>
            <h4 class="kpi-label">Ventas del Turno</h4>
            <div class="text-xl font-bold text-brand-600 mt-2">$<?= number_format($ventasUsd, 2) ?></div>
            <div class="text-sm text-gray-500 mt-1">Bs. <?= number_format($ventasBs, 2) ?></div>
        </div>
    </div>
    <div class="kpi-card">
        <div>
            <h4 class="kpi-label">Total Esperado</h4>
            <div class="text-xl font-bold text-green-600 mt-2 border-b border-green-100 pb-1">$<?= number_format($esperadoUsd, 2) ?></div>
            <div class="text-sm text-gray-500 mt-1">Bs. <?= number_format($esperadoBs, 2) ?></div>
        </div>
    </div>
</div>

<!-- ═══════ Historial de Arqueos ═══════ -->
<div class="card">
    <div class="card-header">
        <h3 class="font-bold text-gray-800 dark:text-white"><i class="fas fa-history mr-2 text-gray-400"></i>Historial de Turnos</h3>
    </div>
    <div class="table-wrap">
        <table class="min-w-[600px] w-full text-left">
            <thead>
                <tr class="table-head-row">
                    <th class="p-4">Apertura / Cierre</th>
                    <th class="p-4">Usuario</th>
                    <th class="p-4 text-right">Esperado USD</th>
                    <th class="p-4 text-right">Declarado USD</th>
                    <th class="p-4 text-right">Diferencia</th>
                    <th class="p-4 text-center">Estado</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
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
                            <span class="badge-success"><i class="fas fa-lock-open mr-1"></i>Abierta</span>
                        <?php else: ?>
                            <span class="badge bg-gray-200 dark:bg-gray-600 text-gray-600 dark:text-gray-300"><i class="fas fa-lock mr-1"></i>Cerrada</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>
