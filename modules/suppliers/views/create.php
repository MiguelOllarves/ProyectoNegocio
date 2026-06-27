<?php include __DIR__ . '/../../../includes/header.php'; $isEdit = isset($supplier); ?>
<div class="mb-6">
    <p class="text-sm text-gray-500 dark:text-gray-400">Proveedores > <?= $isEdit ? 'Editar' : 'Nuevo' ?></p>
    <h2 class="text-2xl font-bold text-gray-800 dark:text-white"><?= $isEdit ? 'Editar Proveedor' : 'Registrar Proveedor' ?></h2>
</div>
<form action="<?= $isEdit ? BASE_URL.'suppliers/edit/'.$supplier['id'] : BASE_URL.'suppliers/create' ?>" method="POST" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 sm:p-8 space-y-4 max-w-2xl">
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nombre de Empresa *</label>
        <input type="text" name="name" required value="<?= htmlspecialchars($supplier['name'] ?? '') ?>" class="w-full rounded-md border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500">
    </div>
    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Persona de Contacto</label>
            <input type="text" name="contact_name" value="<?= htmlspecialchars($supplier['contact_name'] ?? '') ?>" class="w-full rounded-md border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Teléfono</label>
            <input type="text" name="phone" value="<?= htmlspecialchars($supplier['phone'] ?? '') ?>" class="w-full rounded-md border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500">
        </div>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email</label>
        <input type="email" name="email" value="<?= htmlspecialchars($supplier['email'] ?? '') ?>" class="w-full rounded-md border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500">
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Dirección</label>
        <textarea name="address" rows="2" class="w-full rounded-md border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500"><?= htmlspecialchars($supplier['address'] ?? '') ?></textarea>
    </div>
    <div class="flex justify-end gap-3 pt-4">
        <a href="<?= BASE_URL ?>suppliers" class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">Cancelar</a>
        <button type="submit" class="px-6 py-2 bg-brand-600 hover:bg-brand-700 text-white rounded-lg font-medium transition-colors shadow-sm"><i class="fas fa-save mr-2"></i><?= $isEdit ? 'Actualizar' : 'Guardar' ?></button>
    </div>
</form>
<?php include __DIR__ . '/../../../includes/footer.php'; ?>
