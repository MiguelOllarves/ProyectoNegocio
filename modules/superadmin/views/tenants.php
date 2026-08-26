<?php include __DIR__ . '/../../../includes/header.php'; ?>

<div class="mb-6 flex justify-between items-center">
    <div>
        <h2 class="text-2xl font-extrabold text-gray-800 dark:text-white tracking-tight"><i class="fas fa-building text-brand-500 mr-2"></i> Negocios Registrados</h2>
        <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">Directorio global de inquilinos (Tenants) en la plataforma.</p>
    </div>
</div>

<div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden" x-data="superAdminTenants()">
    <div class="p-5 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center bg-gray-50 dark:bg-slate-800/80">
        <div class="flex space-x-2">
            <span class="bg-gray-200 dark:bg-slate-700 text-gray-700 dark:text-gray-300 px-3 py-1 text-xs font-bold rounded-lg uppercase tracking-wider">Total: <?= count($tenants) ?></span>
        </div>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm text-gray-600 dark:text-gray-400 border-collapse">
            <thead>
                <tr class="bg-white dark:bg-slate-800 uppercase text-[10px] tracking-wider text-gray-500 dark:text-gray-300 border-b border-gray-200 dark:border-gray-700 px-2">
                    <th class="py-4 px-6 font-bold">Negocio & Propietario</th>
                    <th class="py-4 px-6 font-bold">Documento / Email</th>
                    <th class="py-4 px-6 font-bold text-center">Tasa / Empleados</th>
                    <th class="py-4 px-6 font-bold text-center">Estado (Suscripción)</th>
                    <th class="py-4 px-6 font-bold text-right">Controles</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($tenants)): ?>
                <tr><td colspan="5" class="py-10 text-center text-gray-400">No hay negocios registrados.</td></tr>
                <?php else: foreach ($tenants as $b): ?>
                <tr class="border-b border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-slate-800/50 transition-colors">
                    <td class="py-4 px-6">
                        <div class="flex items-center">
                            <div class="w-10 h-10 rounded-full bg-brand-100 dark:bg-brand-900/30 flex items-center justify-center text-brand-600 dark:text-brand-400 font-black mr-3 uppercase">
                                <?= substr($b['business_name'], 0, 1) ?>
                            </div>
                            <div>
                                <span class="font-bold text-gray-800 dark:text-gray-200 block mb-0.5"><?= htmlspecialchars($b['business_name']) ?></span>
                                <span class="text-[11px] text-gray-400 font-medium tracking-wide uppercase"><i class="fas fa-user mr-1"></i> <?= htmlspecialchars($b['owner_name']) ?></span>
                            </div>
                        </div>
                    </td>
                    <td class="py-4 px-6">
                        <span class="font-mono text-xs font-bold text-slate-700 dark:text-slate-300"><?= htmlspecialchars($b['document_id'] ?? $b['rif']) ?></span><br>
                        <span class="text-xs text-gray-500 font-medium"><?= htmlspecialchars($b['email']) ?></span>
                    </td>
                    <td class="py-4 px-6 text-center">
                        <span class="block text-xs text-gray-500 mb-1"><i class="fas fa-layer-group text-slate-400"></i> Plan ID: <?= $b['plan_id'] ?? '-' ?></span>
                        <span class="bg-indigo-50 text-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-400 px-2 py-0.5 rounded text-[11px] font-bold">
                            <i class="fas fa-users mr-1"></i> <?= $b['subusers_count'] ?> usuarios
                        </span>
                    </td>
                    <td class="py-4 px-6 text-center">
                        <?php 
                            if($b['subscription_status'] === 'active') {
                                $badge = 'bg-emerald-100 text-emerald-800 border border-emerald-200';
                                $label = 'ACTIVO';
                            } else if ($b['subscription_status'] === 'trial') {
                                $badge = 'bg-blue-100 text-blue-800 border border-blue-200';
                                $label = 'TRIAL';
                            } else {
                                $badge = 'bg-red-100 text-red-800 border border-red-200';
                                $label = 'VENCIDO/BLOQUEADO';
                            }
                        ?>
                        <div class="px-2.5 py-1 text-[10px] font-extrabold rounded-md shadow-sm uppercase inline-block <?= $badge ?>">
                            <?= $label ?>
                        </div><br>
                        <span class="text-[10px] text-gray-400 font-medium">Expira: <?= date('d M, Y', strtotime($b['trial_ends_at'] ?? $b['created_at'])) ?></span>
                    </td>
                    <td class="py-4 px-6 text-right">
                        <button @click="openDetails(<?= htmlspecialchars(json_encode([
                            'id' => $b['id'],
                            'name' => $b['business_name'],
                            'owner' => $b['owner_name'],
                            'phone' => $b['owner_phone'],
                            'email' => $b['email'],
                            'created_at' => $b['created_at']
                        ])) ?>)" class="text-blue-500 hover:text-blue-700 bg-blue-50 hover:bg-blue-100 dark:bg-blue-900/20 dark:hover:bg-blue-900/40 p-2 rounded-lg transition-colors inline-block tooltip mr-1" title="Ver Detalles y Contraseñas">
                            <i class="fas fa-eye"></i>
                        </button>

                        <?php if($b['subscription_status'] !== 'expired'): ?>
                            <a href="<?= BASE_URL ?>tienda/<?= htmlspecialchars($b['slug'] ?? '') ?>" target="_blank" class="text-indigo-500 hover:text-indigo-700 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-900/20 dark:hover:bg-indigo-900/40 p-2 rounded-lg transition-colors inline-block tooltip mr-1" title="Infiltrarse en Tienda (Pública)">
                                <i class="fas fa-external-link-alt"></i>
                            </a>
                        
                            <button @click="toggleStatus(<?= $b['id'] ?>, 'suspend')" class="text-red-500 hover:text-red-700 bg-red-50 hover:bg-red-100 dark:bg-red-900/20 dark:hover:bg-red-900/40 p-2 rounded-lg transition-colors inline-block tooltip" title="Forzar Bloqueo (Suspender)">
                                <i class="fas fa-ban"></i>
                            </button>
                        <?php else: ?>
                            <button @click="toggleStatus(<?= $b['id'] ?>, 'activate')" class="text-emerald-500 hover:text-emerald-700 bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-900/20 dark:hover:bg-emerald-900/40 p-2 rounded-lg transition-colors inline-block tooltip" title="Quitar Bloqueo Manualmente">
                                <i class="fas fa-unlock"></i>
                            </button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Modal Detalles -->
    <div x-show="detailsModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4" x-cloak>
        <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] flex flex-col border border-gray-100 dark:border-gray-800" @click.away="detailsModal = false">
            <div class="p-5 border-b border-gray-100 dark:border-gray-800 flex justify-between items-center rounded-t-2xl shrink-0">
                <h3 class="font-black text-gray-800 dark:text-gray-100 text-lg"><i class="fas fa-address-card text-brand-500 mr-2"></i> Ficha del Inquilino</h3>
                <button @click="detailsModal = false" class="modal-close"><i class="fas fa-times"></i></button>
            </div>
            <div class="p-6 overflow-y-auto sidebar-scroll flex-1">
                <div class="mb-5 bg-gray-50 dark:bg-slate-800/50 p-4 rounded-xl border border-gray-100 dark:border-gray-700/50">
                    <p class="text-[10px] text-gray-500 uppercase tracking-widest font-bold mb-1">Negocio Registrado</p>
                    <p class="font-black text-gray-800 dark:text-gray-200 text-xl truncate" x-text="activeTenant.name"></p>
                </div>
                <div class="grid grid-cols-2 gap-4 mb-6">
                    <div class="bg-white dark:bg-slate-800 p-3 rounded-lg border border-gray-100 dark:border-gray-700">
                        <p class="text-[10px] text-gray-400 uppercase tracking-widest font-bold mb-1">Propietario / Representante</p>
                        <p class="text-sm font-bold text-gray-700 dark:text-gray-300 truncate" x-text="activeTenant.owner"></p>
                    </div>
                    <div class="bg-white dark:bg-slate-800 p-3 rounded-lg border border-gray-100 dark:border-gray-700">
                        <p class="text-[10px] text-gray-400 uppercase tracking-widest font-bold mb-1">Teléfono o WhatsApp</p>
                        <p class="text-sm font-bold text-gray-700 dark:text-gray-300 truncate" x-text="activeTenant.phone"></p>
                    </div>
                    <div class="col-span-2 bg-white dark:bg-slate-800 p-3 rounded-lg border border-gray-100 dark:border-gray-700">
                        <p class="text-[10px] text-gray-400 uppercase tracking-widest font-bold mb-1">Correo Electrónico (Login Maestro)</p>
                        <p class="text-sm font-bold text-brand-600 dark:text-brand-400 truncate" x-text="activeTenant.email"></p>
                    </div>
                    <div class="col-span-2 bg-white dark:bg-slate-800 p-3 rounded-lg border border-gray-100 dark:border-gray-700">
                        <p class="text-[10px] text-gray-400 uppercase tracking-widest font-bold mb-1">Fecha de Inscripción en Plataforma</p>
                        <p class="text-sm font-bold text-gray-700 dark:text-gray-300" x-text="activeTenant.created_at"></p>
                    </div>
                </div>

                <div class="border-t border-gray-100 dark:border-gray-800 pt-5">
                    <h4 class="text-sm font-bold text-red-600 dark:text-red-400 mb-2 flex items-center"><i class="fas fa-lock mr-2"></i> Restaurar Acceso Forzado</h4>
                    <p class="text-[11px] text-gray-500 dark:text-gray-400 mb-4 bg-red-50 dark:bg-red-900/10 p-2.5 rounded-lg border border-red-100 dark:border-red-900/20 text-red-700 dark:text-red-400">
                        <i class="fas fa-exclamation-circle mr-1"></i> Esta acción forzará el cambio de clave maestra sin confirmar con el inquilino.
                    </p>
                    <input type="text" x-model="newPassword" placeholder="Escribe la nueva contraseña..." class="w-full px-4 py-3 bg-white dark:bg-slate-900 border-2 border-gray-200 dark:border-gray-700 rounded-xl font-mono text-sm mb-4 focus:ring-red-500 focus:border-red-500 transition-all text-gray-800 dark:text-gray-200">
                    <button @click="resetPassword" class="w-full py-3 bg-red-600 hover:bg-red-700 rounded-xl text-white font-bold shadow-lg shadow-red-500/30 transition-all flex items-center justify-center gap-2">
                        <i class="fas fa-key"></i> Forzar Cambio de Contraseña
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('superAdminTenants', () => ({
        detailsModal: false,
        activeTenant: {},
        newPassword: '',

        openDetails(tenant) {
            this.activeTenant = tenant;
            this.newPassword = '';
            this.detailsModal = true;
        },

        async resetPassword() {
            if(!this.newPassword || this.newPassword.length < 6) {
                Swal.fire('Atención', 'La contraseña debe tener al menos 6 caracteres.', 'warning');
                return;
            }

            const fd = new FormData();
            fd.append('business_id', this.activeTenant.id);
            fd.append('new_password', this.newPassword);

            try {
                const res = await fetch('<?= BASE_URL ?>superadmin/force_password_reset', {
                    method: 'POST', body: fd
                });
                const data = await res.json();
                if(data.success) {
                    Swal.fire('¡Éxito!', 'Contraseña cambiada a: ' + this.newPassword, 'success');
                    this.newPassword = '';
                    this.detailsModal = false;
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            } catch(e) {
                Swal.fire('Error', 'Fallo de red', 'error');
            }
        },

        async toggleStatus(id, action) {
            const isSuspend = action === 'suspend';
            const result = await Swal.fire({
                title: isSuspend ? '¿Suspender Negocio?' : '¿Desbloquear Negocio?',
                text: isSuspend ? 'Se enviará a modo Solo-Lectura inmediatamente ignorando sus días restantes de suscripción.' : 'Volverá a estar activo manualmente.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: isSuspend ? '#ef4444' : '#10b981',
                confirmButtonText: 'Confirmar',
                cancelButtonText: 'Cancelar'
            });

            if (result.isConfirmed) {
                const fd = new FormData();
                fd.append('id', id);
                fd.append('action', action);

                try {
                    const res = await fetch('<?= BASE_URL ?>superadmin/toggle_tenant', {
                        method: 'POST',
                        body: fd
                    });
                    const data = await res.json();
                    if(data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Aplicado',
                            timer: 1000,
                            showConfirmButton: false
                        }).then(() => location.reload());
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                } catch(e) {
                    Swal.fire('Error', 'Fallo de conexión', 'error');
                }
            }
        }
    }))
})
</script>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>
