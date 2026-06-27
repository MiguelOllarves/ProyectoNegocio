<?php include __DIR__ . '/../../../includes/header.php'; $isEdit = isset($client); ?>

<div class="mb-6">
    <p class="text-sm text-gray-500 dark:text-gray-400">Clientes > <?= $isEdit ? 'Editar' : 'Nuevo' ?></p>
    <h2 class="text-2xl font-bold text-gray-800 dark:text-white"><?= $isEdit ? 'Editar Cliente' : 'Registrar Nuevo Cliente' ?></h2>
</div>

<form action="<?= $isEdit ? BASE_URL.'clients/edit/'.$client['id'] : BASE_URL.'clients/create' ?>" method="POST" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 sm:p-8 space-y-4 max-w-2xl">
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nombre Completo *</label>
        <input type="text" name="name" required value="<?= htmlspecialchars($client['name'] ?? '') ?>" class="w-full rounded-md border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500">
    </div>
    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Documento / CI / RIF</label>
            <input type="text" name="document" value="<?= htmlspecialchars($client['document'] ?? '') ?>" class="w-full rounded-md border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Teléfono</label>
            <input type="text" name="phone" value="<?= htmlspecialchars($client['phone'] ?? '') ?>" class="w-full rounded-md border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500">
        </div>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email</label>
        <input type="email" name="email" value="<?= htmlspecialchars($client['email'] ?? '') ?>" class="w-full rounded-md border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500">
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Dirección</label>
        <textarea name="address" rows="2" class="w-full rounded-md border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500"><?= htmlspecialchars($client['address'] ?? '') ?></textarea>
    </div>
    <div class="flex justify-end gap-3 pt-4">
        <a href="<?= BASE_URL ?>clients" class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">Cancelar</a>
        <button type="submit" class="px-6 py-2 bg-brand-600 hover:bg-brand-700 text-white rounded-lg font-medium transition-colors shadow-sm">
            <i class="fas fa-save mr-2"></i><?= $isEdit ? 'Actualizar' : 'Guardar' ?>
        </button>
    </div>
</form>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>
