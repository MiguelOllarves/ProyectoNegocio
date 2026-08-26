<?php include __DIR__ . '/../../../includes/header.php'; ?>

<div class="p-2 sm:p-6" x-data="{ createModal: false, editModal: false }">
    <div class="page-header">
        <div>
            <h2 class="page-title">Gastos y Egresos</h2>
            <p class="page-subtitle">Registro y control de todos los egresos del negocio</p>
        </div>
        <button @click="createModal = true" class="btn-gradient w-full sm:w-auto">
            <i class="fas fa-plus mr-2"></i> Nuevo Gasto
        </button>
    </div>

    <!-- Modal Crear -->
    <div x-show="createModal" x-cloak class="modal-wrapper" style="display: none;">
        <div class="modal-container">
            <div x-show="createModal" x-transition.opacity class="modal-backdrop" @click="createModal = false"></div>
            <div x-show="createModal" x-transition class="modal-card modal-card-md animate-fade-in-up">
                <div class="modal-header">
                    <h3 class="modal-title">
                        <div class="w-8 h-8 rounded-lg bg-brand-50 dark:bg-brand-900/30 text-brand-600 dark:text-brand-400 flex items-center justify-center"><i class="fas fa-receipt"></i></div>
                        Registrar Gasto
                    </h3>
                    <button @click="createModal = false" class="modal-close"><i class="fas fa-times"></i></button>
                </div>
                <form hx-post="<?= BASE_URL ?>expenses/create" hx-swap="none" @htmx:after-request="if($event.detail.successful) { createModal = false; $el.reset(); Swal.fire({title: '¡Gasto Registrado!', text: 'El gasto ha sido guardado correctamente.', icon: 'success', timer: 2000, showConfirmButton: true, confirmButtonText: 'Continuar', confirmButtonColor: '#10b981', customClass: { popup: 'rounded-2xl' }}).then(() => { htmx.ajax('GET', '<?= BASE_URL ?>expenses/list?t=' + new Date().getTime(), {target: '#expenses-tbody'}); }); }">
                    <div class="modal-body space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="form-label">Fecha *</label>
                                <input type="date" name="expense_date" required value="<?= date('Y-m-d') ?>" class="form-input">
                            </div>
                            <div>
                                <label class="form-label">Categoría *</label>
                                <select name="category" required class="form-select">
                                    <option value="Servicios">Servicios (Agua, Luz, Internet)</option>
                                    <option value="Nómina">Nómina / Salarios</option>
                                    <option value="Alquiler">Alquiler / Arrendamiento</option>
                                    <option value="Otro">Otro</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="form-label">Monto ($) *</label>
                            <input type="number" step="0.01" min="0.01" name="amount" required class="form-input">
                        </div>
                        <div>
                            <label class="form-label">Descripción *</label>
                            <textarea name="description" rows="2" required class="form-input"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer justify-end">
                        <button type="button" @click="createModal = false" class="btn-secondary">Cancelar</button>
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
                        Editar Gasto
                    </h3>
                    <button @click="editModal = false" class="modal-close"><i class="fas fa-times"></i></button>
                </div>
                <form id="edit-expense-form" hx-post="<?= BASE_URL ?>expenses/edit/" hx-swap="none" @htmx:after-request="if($event.detail.successful) { editModal = false; $el.reset(); Swal.fire({title: '¡Actualización Exitosa!', text: 'El gasto ha sido actualizado.', icon: 'success', timer: 2000, showConfirmButton: true, confirmButtonText: 'Continuar', confirmButtonColor: '#10b981', customClass: { popup: 'rounded-2xl' }}).then(() => { htmx.ajax('GET', '<?= BASE_URL ?>expenses/list?t=' + new Date().getTime(), {target: '#expenses-tbody'}); }); }">
                    <div class="modal-body space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="form-label">Fecha *</label>
                                <input type="date" id="edit-date" name="expense_date" required class="form-input">
                            </div>
                            <div>
                                <label class="form-label">Categoría *</label>
                                <select id="edit-category" name="category" required class="form-select">
                                    <option value="Servicios">Servicios (Agua, Luz, Internet)</option>
                                    <option value="Nómina">Nómina / Salarios</option>
                                    <option value="Alquiler">Alquiler / Arrendamiento</option>
                                    <option value="Otro">Otro</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="form-label">Monto ($) *</label>
                            <input type="number" id="edit-amount" step="0.01" min="0.01" name="amount" required class="form-input">
                        </div>
                        <div>
                            <label class="form-label">Descripción *</label>
                            <textarea id="edit-description" name="description" rows="2" required class="form-input"></textarea>
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

    <div class="card">
        <div class="table-wrap">
            <table class="min-w-[600px] w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead>
                    <tr class="table-head-row">
                        <th class="px-4 py-3 text-left">Fecha</th>
                        <th class="px-4 py-3 text-left">Categoría</th>
                        <th class="px-4 py-3 text-left">Descripción</th>
                        <th class="px-4 py-3 text-left">Monto</th>
                        <th class="px-4 py-3 text-left">Usuario</th>
                        <th class="px-4 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800" id="expenses-tbody" hx-get="<?= BASE_URL ?>expenses/list" hx-trigger="load">
                    <tr><td colspan="6" class="px-6 py-8 text-center text-gray-400 dark:text-gray-500"><i class="fas fa-circle-notch fa-spin text-2xl mb-3 block opacity-50"></i>Cargando...</td></tr>
                </tbody>
            </table>
        </div>
    </div>

<script>
    function openEditExpenseModal(id) {
        fetch(`<?= BASE_URL ?>expenses/edit/${id}?json=true`, { headers: { 'Accept': 'application/json' } })
            .then(res => res.json())
            .then(data => {
                if (data.expense) {
                    document.getElementById('edit-date').value = data.expense.expense_date.split(' ')[0];
                    document.getElementById('edit-category').value = data.expense.category || 'Otro';
                    document.getElementById('edit-amount').value = data.expense.amount || 0;
                    document.getElementById('edit-description').value = data.expense.description || '';
                    
                    document.getElementById('edit-expense-form').setAttribute('hx-post', `<?= BASE_URL ?>expenses/edit/${id}`);
                    htmx.process(document.getElementById('edit-expense-form'));
                    
                    document.querySelector('[x-data]').__x.$data.editModal = true;
                } else {
                    Swal.fire('Error', 'No se pudo cargar el gasto', 'error');
                }
            })
            .catch(() => Swal.fire('Error', 'Problema de red', 'error'));
    }
    function confirmDeleteExpense(id) {
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
                fetch('<?= BASE_URL ?>expenses/delete', { method: 'POST', body: formData })
                    .then(() => {
                        Swal.fire({
                            title: '¡Eliminado!',
                            text: 'El gasto ha sido eliminado.',
                            icon: 'success',
                            timer: 1500,
                            showConfirmButton: false,
                            customClass: { popup: 'rounded-2xl' }
                        }).then(() => {
                            htmx.ajax('GET', '<?= BASE_URL ?>expenses/list?t=' + new Date().getTime(), {target: '#expenses-tbody'});
                        });
                    });
            }
        });
    }
</script>

</div>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>
