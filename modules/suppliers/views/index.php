<?php include __DIR__ . '/../../../includes/header.php'; ?>
<div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3" x-data="{ openModal: false }">
    <div>
        <h2 class="text-xl sm:text-2xl font-bold text-gray-800 dark:text-white">Proveedores</h2>
        <p class="text-gray-600 dark:text-gray-400 text-sm">Gestiona tus proveedores de mercancía (<span class="text-brand-500 font-bold">SPA</span>)</p>
    </div>
    <button @click="openModal = true" class="bg-brand-600 hover:bg-brand-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors shadow-sm flex items-center w-full sm:w-auto justify-center">
        <i class="fas fa-plus mr-2"></i> Nuevo Proveedor
    </button>

    <!-- Modal Alpine -->
    <div x-show="openModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <div x-show="openModal" x-transition.opacity class="fixed inset-0 transition-opacity bg-gray-900 bg-opacity-75 backdrop-blur-sm" @click="openModal = false"></div>

            <div x-show="openModal" x-transition class="relative inline-block w-full max-w-lg p-6 overflow-hidden text-left align-middle transition-all transform bg-white dark:bg-gray-800 rounded-xl shadow-xl">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Registrar Proveedor</h3>
                
                <form hx-post="<?= BASE_URL ?>suppliers/create" hx-swap="none" @htmx:after-request="if($event.detail.successful) { openModal = false; $el.reset(); }" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Empresa *</label>
                        <input type="text" name="name" required class="mt-1 block w-full rounded-md shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm px-3 py-2 border bg-white dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Contacto</label>
                        <input type="text" name="contact_name" class="mt-1 block w-full rounded-md shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm px-3 py-2 border bg-white dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Teléfono</label>
                            <input type="text" name="phone" class="mt-1 block w-full rounded-md shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm px-3 py-2 border bg-white dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
                            <input type="email" name="email" class="mt-1 block w-full rounded-md shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm px-3 py-2 border bg-white dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        </div>
                    </div>
                    <div class="mt-5 sm:mt-6 sm:flex sm:flex-row-reverse">
                        <button type="submit" class="w-full inline-flex justify-center rounded-md px-4 py-2 bg-brand-600 text-base font-medium text-white hover:bg-brand-700 sm:ml-3 sm:w-auto sm:text-sm">
                            Guardar Proveedor
                        </button>
                        <button type="button" @click="openModal = false" class="mt-3 w-full inline-flex justify-center rounded-md px-4 py-2 bg-white text-base font-medium text-gray-700 border hover:bg-gray-50 focus:outline-none sm:mt-0 sm:w-auto sm:text-sm">
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-[600px] w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50 dark:bg-gray-700 border-b text-gray-500 dark:text-gray-300 uppercase text-xs tracking-wider">
                    <th class="p-4 font-semibold">Empresa</th>
                    <th class="p-4 font-semibold">Contacto</th>
                    <th class="p-4 font-semibold">Teléfono</th>
                    <th class="p-4 font-semibold text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700" hx-get="<?= BASE_URL ?>suppliers/list" hx-trigger="load, suppliersUpdated from:body" id="suppliers-tbody">
                <!-- HTMX render -->
                <tr><td colspan="4" class="p-8 text-center text-gray-400 dark:text-gray-500"><i class="fas fa-spinner fa-spin text-2xl mb-3 block"></i>Cargando...</td></tr>
            </tbody>
        </table>
    </div>
</div>
<?php include __DIR__ . '/../../../includes/footer.php'; ?>
