<?php include __DIR__ . '/../../../includes/header.php'; ?>

<div class="mb-6 flex justify-between items-center">
    <div>
        <h2 class="text-2xl font-extrabold text-gray-800 dark:text-white tracking-tight"><i class="fas fa-database text-amber-500 mr-2"></i> Bóveda de Respaldo</h2>
        <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">Descarga y resguarda la información total de la plataforma.</p>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-8 text-center">
        <div class="w-20 h-20 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-500 mx-auto rounded-full flex items-center justify-center text-3xl mb-4">
            <i class="fas fa-cloud-download-alt"></i>
        </div>
        <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-2">Base de Datos Completa</h3>
        <p class="text-gray-500 dark:text-gray-400 text-sm mb-6">Al presionar este botón, el sistema compactará la base de datos completa con todos los inquilinos, pagos e inventarios, y te la enviará como un archivo a tu computadora.</p>
        
        <a href="<?= BASE_URL ?>superadmin/backup_db" class="inline-flex items-center justify-center bg-gray-900 hover:bg-black text-white px-6 py-3 rounded-xl font-bold transition-all shadow-md w-full md:w-auto">
            <i class="fas fa-download mr-2 text-emerald-400"></i> Descargar Respaldo (SQLite)
        </a>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-8 text-center" style="opacity: 0.6; filter: grayscale(1);">
        <div class="w-20 h-20 bg-blue-100 dark:bg-blue-900/30 text-blue-500 mx-auto rounded-full flex items-center justify-center text-3xl mb-4">
            <i class="fas fa-folder-open"></i>
        </div>
        <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-2">Imágenes y Captures (Próximamente)</h3>
        <p class="text-gray-500 dark:text-gray-400 text-sm mb-6">La opción para empaquetar todas las referencias de pagos, logotipos de clientes y capturas en un archivo ZIP estará disponible pronto.</p>
        
        <button disabled class="inline-flex items-center justify-center bg-gray-200 text-gray-500 dark:bg-gray-700 dark:text-gray-400 px-6 py-3 rounded-xl font-bold transition-all w-full md:w-auto cursor-not-allowed">
            <i class="fas fa-lock mr-2"></i> Descargar Medios (ZIP)
        </button>
    </div>
</div>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>
