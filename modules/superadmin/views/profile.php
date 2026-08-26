<?php include __DIR__ . '/../../../includes/header.php'; ?>

<div class="mb-6 flex justify-between items-center">
    <div>
        <h2 class="text-2xl font-extrabold text-gray-800 dark:text-white tracking-tight"><i class="fas fa-user-shield text-brand-500 mr-2"></i> Identidad y Seguridad</h2>
        <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">Configura las credenciales exclusivas de acceso para la cuenta matriz.</p>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6" x-data="superAdminProfile()">
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="p-5 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-slate-800/80">
            <h3 class="text-sm font-bold text-gray-800 dark:text-white uppercase tracking-wider"><i class="fas fa-key text-blue-500 mr-2"></i> Credenciales de Acceso Múltiple</h3>
        </div>
        <div class="p-6">
            <form @submit.prevent="updateProfile">
                <div class="mb-5">
                    <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Identificador Cómodo (Cédula / Usuario)</label>
                    <input type="text" x-model="form.username" class="w-full px-4 py-3 bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-brand-500 font-bold dark:text-white transition-all shadow-sm" required>
                    <p class="text-[11px] text-gray-400 mt-2"><i class="fas fa-info-circle mr-1"></i> Puedes colocar tu número de cédula, la palabra "desarrollador", o lo que tú desees. Con esto iniciarás sesión en vez del correo clásico.</p>
                </div>

                <div class="mb-5" x-data="{ show: false }">
                    <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Nueva Contraseña (Opcional)</label>
                    <div class="relative">
                        <input :type="show ? 'text' : 'password'" x-model="form.password" class="w-full px-4 py-3 bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-brand-500 font-bold dark:text-white transition-all shadow-sm pr-12" placeholder="••••••••">
                        <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400 hover:text-brand-500 focus:outline-none">
                            <i class="fas" :class="show ? 'fa-eye-slash' : 'fa-eye'"></i>
                        </button>
                    </div>
                    <p class="text-[11px] text-gray-400 mt-2">Déjalo en blanco si no quieres cambiar tu contraseña actual.</p>
                </div>

                <div class="pt-2">
                    <button type="submit" class="w-full bg-brand-600 hover:bg-brand-700 disabled:bg-gray-400 text-white font-bold py-3 px-4 rounded-xl transition-colors shadow-lg flex items-center justify-center" :disabled="loading">
                        <i class="fas fa-save mr-2" x-show="!loading"></i>
                        <i class="fas fa-circle-notch fa-spin mr-2" x-show="loading" x-cloak></i>
                        <span x-text="loading ? 'Guardando...' : 'Aplicar Cambios Críticos'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <div class="bg-gray-50 dark:bg-slate-800/50 rounded-xl border border-gray-200 dark:border-gray-700 p-8 text-center flex flex-col justify-center opacity-80">
        <i class="fas fa-fingerprint text-6xl text-slate-300 dark:text-slate-600 mx-auto mb-4"></i>
        <h3 class="text-xl font-bold text-gray-600 dark:text-gray-300 mb-2">Auditoría (Próximamente)</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400">Aquí se mostrará el panel para monitorear qué IPs y en qué momento preciso ha ingresado cada negocio a la plataforma.</p>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('superAdminProfile', () => ({
        form: {
            username: '<?= htmlspecialchars($user['username'] ?? '') ?>',
            password: ''
        },
        loading: false,

        async updateProfile() {
            if(!this.form.username) {
                Swal.fire('Atención', 'El identificador no puede estar vacío', 'warning');
                return;
            }

            this.loading = true;
            const fd = new FormData();
            fd.append('username', this.form.username);
            fd.append('password', this.form.password);

            try {
                const res = await fetch('<?= BASE_URL ?>superadmin/update_profile', {
                    method: 'POST',
                    body: fd
                });
                const data = await res.json();
                
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Identidad Actualizada',
                        text: 'Tus credenciales de ingreso han sido actualizadas. Usa tu nuevo usuario en tu próximo ingreso.',
                        confirmButtonColor: '#10b981'
                    }).then(() => {
                        this.form.password = '';
                    });
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            } catch (err) {
                Swal.fire('Error', 'Problema de conexión al procesar cambios', 'error');
            } finally {
                this.loading = false;
            }
        }
    }));
});
</script>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>
