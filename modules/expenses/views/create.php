<?php include __DIR__ . '/../../../includes/header.php'; ?>

<div class="p-6 max-w-2xl mx-auto">
    <div class="flex items-center space-x-3 mb-6">
        <a href="<?= BASE_URL ?>expenses" class="text-gray-500 hover:text-blue-600 transition-colors">
            <i class="fas fa-arrow-left text-xl"></i>
        </a>
        <h1 class="text-3xl font-bold text-gray-800 dark:text-white">Registrar Gasto</h1>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-100 dark:border-gray-700 p-6">
        <form action="<?= BASE_URL ?>expenses/create" method="POST" class="space-y-6">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Fecha -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Fecha</label>
                    <input type="date" name="expense_date" required value="<?= date('Y-m-d') ?>"
                           class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-gray-900 dark:text-white transition-colors">
                </div>

                <!-- Categoría -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Categoría</label>
                    <select name="category" required 
                            class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-gray-900 dark:text-white transition-colors">
                        <option value="Servicios">Servicios (Agua, Luz, Internet)</option>
                        <option value="Nómina">Nómina / Salarios</option>
                        <option value="Alquiler">Alquiler / Arrendamiento</option>
                        <option value="Otro">Otro</option>
                    </select>
                </div>
            </div>

            <!-- Monto -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Monto ($)</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <span class="text-gray-500 sm:text-sm">$</span>
                    </div>
                    <input type="number" step="0.01" min="0.01" name="amount" required
                           class="pl-7 w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-gray-900 dark:text-white transition-colors">
                </div>
            </div>

            <!-- Descripción -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Descripción</label>
                <textarea name="description" rows="3" required
                          class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-gray-900 dark:text-white transition-colors placeholder-gray-400"
                          placeholder="Detalle del gasto..."></textarea>
            </div>

            <!-- Subir -->
            <div class="pt-4 flex justify-end">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-6 rounded-lg transition-colors flex items-center shadow-lg shadow-blue-500/30">
                    <i class="fas fa-save mr-2"></i> Registrar Gasto
                </button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>
