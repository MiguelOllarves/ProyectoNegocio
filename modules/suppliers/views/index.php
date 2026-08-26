<?php include __DIR__ . '/../../../includes/header.php'; ?>
<div x-data="{ openModal: false, editModal: false }">
    <div class="page-header">
        <div>
            <h2 class="page-title">Proveedores</h2>
            <p class="page-subtitle">Gestiona tus proveedores de mercancía</p>
        </div>
        <button @click="openModal = true" class="btn-gradient w-full sm:w-auto">
            <i class="fas fa-plus mr-2"></i> Nuevo Proveedor
        </button>
    </div>

    <!-- Modal Crear -->
    <div x-show="openModal" x-cloak class="modal-wrapper" style="display: none;">
        <div class="modal-container">
            <div x-show="openModal" x-transition.opacity class="modal-backdrop" @click="openModal = false"></div>
            <div x-show="openModal" x-transition class="modal-card modal-card-md animate-fade-in-up">
                <div class="modal-header">
                    <h3 class="modal-title">
                        <div class="w-8 h-8 rounded-lg bg-brand-50 dark:bg-brand-900/30 text-brand-600 dark:text-brand-400 flex items-center justify-center"><i class="fas fa-truck"></i></div>
                        Registrar Proveedor
                    </h3>
                    <button @click="openModal = false" class="modal-close"><i class="fas fa-times"></i></button>
                </div>
                <form hx-post="<?= BASE_URL ?>suppliers/create" hx-swap="none" @htmx:after-request="if($event.detail.successful) { openModal = false; $el.reset(); Swal.fire({title: '¡Registro Exitoso!', text: 'El proveedor ha sido guardado correctamente.', icon: 'success', timer: 2000, showConfirmButton: true, confirmButtonText: 'Continuar', confirmButtonColor: '#10b981', customClass: { popup: 'rounded-2xl' }}).then(() => { htmx.ajax('GET', '<?= BASE_URL ?>suppliers/list?t=' + new Date().getTime(), {target: '#suppliers-tbody'}); }); }">
                    <div class="modal-body space-y-4">
                        <div>
                            <label class="form-label">Empresa *</label>
                            <input type="text" name="name" required class="form-input">
                        </div>
                        <div>
                            <label class="form-label">Contacto</label>
                            <input type="text" name="contact_name" class="form-input">
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="form-label">Teléfono</label>
                                <input type="text" name="phone" class="form-input">
                            </div>
                            <div>
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-input">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer justify-end">
                        <button type="button" @click="openModal = false" class="btn-secondary">Cancelar</button>
                        <button type="submit" class="btn-gradient"><i class="fas fa-save mr-2"></i> Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Editar -->
    <div x-show="editModal" x-cloak class="modal-wrapper" style="display: none;">
        <div class="modal-container">
            <div x-show="editModal" x-transition.opacity class="modal-backdrop" @click="editModal = false"></div>
            <div x-show="editModal" x-transition class="modal-card modal-card-md animate-fade-in-up">
                <div class="modal-header">
                    <h3 class="modal-title">
                        <div class="w-8 h-8 rounded-lg bg-brand-50 dark:bg-brand-900/30 text-brand-600 dark:text-brand-400 flex items-center justify-center"><i class="fas fa-edit"></i></div>
                        Editar Proveedor
                    </h3>
                    <button @click="editModal = false" class="modal-close"><i class="fas fa-times"></i></button>
                </div>
                <form id="edit-supplier-form" hx-post="<?= BASE_URL ?>suppliers/edit/" hx-swap="none" @htmx:after-request="if($event.detail.successful) { editModal = false; $el.reset(); Swal.fire({title: '¡Actualización Exitosa!', text: 'El proveedor ha sido actualizado.', icon: 'success', timer: 2000, showConfirmButton: true, confirmButtonText: 'Continuar', confirmButtonColor: '#10b981', customClass: { popup: 'rounded-2xl' }}).then(() => { htmx.ajax('GET', '<?= BASE_URL ?>suppliers/list?t=' + new Date().getTime(), {target: '#suppliers-tbody'}); }); }">
                    <div class="modal-body space-y-4">
                        <div>
                            <label class="form-label">Empresa *</label>
                            <input type="text" id="edit-name" name="name" required class="form-input">
                        </div>
                        <div>
                            <label class="form-label">Contacto</label>
                            <input type="text" id="edit-contact-name" name="contact_name" class="form-input">
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="form-label">Teléfono</label>
                                <input type="text" id="edit-phone" name="phone" class="form-input">
                            </div>
                            <div>
                                <label class="form-label">Email</label>
                                <input type="email" id="edit-email" name="email" class="form-input">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer justify-end">
                        <button type="button" @click="editModal = false" class="btn-secondary">Cancelar</button>
                        <button type="submit" class="btn-gradient"><i class="fas fa-save mr-2"></i> Actualizar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function openEditSupplierModal(id) {
        fetch(`<?= BASE_URL ?>suppliers/edit/${id}?json=true`, { headers: { 'Accept': 'application/json' } })
            .then(res => res.json())
            .then(data => {
                if (data.supplier) {
                    document.getElementById('edit-name').value = data.supplier.name || '';
                    document.getElementById('edit-contact-name').value = data.supplier.contact_name || '';
                    document.getElementById('edit-phone').value = data.supplier.phone || '';
                    document.getElementById('edit-email').value = data.supplier.email || '';
                    
                    document.getElementById('edit-supplier-form').setAttribute('hx-post', `<?= BASE_URL ?>suppliers/edit/${id}`);
                    htmx.process(document.getElementById('edit-supplier-form'));
                    
                    document.querySelector('[x-data]').__x.$data.editModal = true;
                } else {
                    Swal.fire('Error', 'No se pudo cargar el proveedor', 'error');
                }
            })
            .catch(() => Swal.fire('Error', 'Problema de red', 'error'));
    }
</script>

<div class="card mt-4">
    <div class="table-wrap">
        <table class="min-w-[600px] w-full text-left border-collapse">
            <thead>
                <tr class="table-head-row">
                    <th class="p-4">Empresa</th>
                    <th class="p-4">Contacto</th>
                    <th class="p-4">Teléfono</th>
                    <th class="p-4 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800" hx-get="<?= BASE_URL ?>suppliers/list" hx-trigger="load" id="suppliers-tbody">
                <tr><td colspan="4" class="p-8 text-center text-gray-400 dark:text-gray-500"><i class="fas fa-circle-notch fa-spin text-2xl mb-3 block opacity-50"></i>Cargando...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<script>
    function confirmDeleteSupplier(id) {
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
                fetch('<?= BASE_URL ?>suppliers/delete', { method: 'POST', body: formData, headers: { 'HX-Request': 'true' }})
                    .then(async r => {
                        if (r.ok) {
                            Swal.fire({
                                title: '¡Eliminado!',
                                text: 'El proveedor ha sido eliminado.',
                                icon: 'success',
                                timer: 1500,
                                showConfirmButton: false,
                                customClass: { popup: 'rounded-2xl' }
                            }).then(() => {
                                htmx.ajax('GET', '<?= BASE_URL ?>suppliers/list?t=' + new Date().getTime(), {target: '#suppliers-tbody'});
                            });
                        }
                    });
            }
        });
    }
</script>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>
