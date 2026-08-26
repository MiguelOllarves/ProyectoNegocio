<?php include __DIR__ . '/../../../includes/header.php'; ?>

<div class="mb-6">
    <h2 class="text-2xl font-extrabold text-gray-800 dark:text-white tracking-tight">Configurar Mi Tienda</h2>
    <p class="text-gray-500 dark:text-gray-400 text-sm font-medium mt-1">Personaliza el aspecto de tu catálogo digital público</p>
</div>

<?php if (isset($_GET['saved']) && !isset($_GET['slug_error'])): ?>
    <div class="bg-green-50 text-green-700 p-4 rounded-lg mb-6 text-sm font-medium border border-green-200 flex items-center">
        <i class="fas fa-check-circle mr-2"></i>¡Configuración guardada exitosamente!
    </div>
<?php endif; ?>

<?php if (isset($_GET['slug_error'])): ?>
    <div class="bg-yellow-50 text-yellow-800 p-4 rounded-lg mb-6 text-sm font-medium border border-yellow-200">
        <i class="fas fa-exclamation-triangle mr-2"></i>La configuración se guardó, pero el <b>Enlace Personalizado</b> que elegiste ya está en uso por otra empresa. Por favor, intenta con otro distinto.
    </div>
<?php endif; ?>

<div class="bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 p-4 rounded-xl mb-6 text-sm border border-indigo-100 dark:border-indigo-800 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
    <div class="w-full md:w-auto overflow-hidden">
        <span class="font-bold block mb-1">Enlace Público de tu Tienda:</span>
        <code class="bg-white dark:bg-slate-800 px-2 py-1 rounded text-xs block truncate w-full" title="<?= BASE_URL ?>tienda/<?= $_SESSION['business_slug'] ?? $_SESSION['business_id'] ?>"><?= BASE_URL ?>tienda/<?= $_SESSION['business_slug'] ?? $_SESSION['business_id'] ?></code>
    </div>
    <a href="<?= BASE_URL ?>tienda/<?= $_SESSION['business_slug'] ?? $_SESSION['business_id'] ?>" target="_blank" class="shrink-0 bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-2 rounded-lg font-medium text-xs transition-colors shadow-sm w-full md:w-auto flex items-center justify-center">
        Abrir Tienda
    </a>
</div>

<form id="configForm" action="<?= BASE_URL ?>storefront/save" method="POST" enctype="multipart/form-data" class="pb-32 sm:pb-6 w-full">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Identidad Visual -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 flex flex-col h-full">
        <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-4 border-b border-gray-100 dark:border-gray-700 pb-2">
            <i class="fas fa-palette text-purple-500 mr-2"></i>Identidad Visual
        </h3>
        <div class="flex flex-col gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nombre de la Tienda</label>
                <input type="text" name="store_name" value="<?= htmlspecialchars($config['store_name'] ?? $business['business_name'] ?? '') ?>" class="w-full p-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-700 dark:text-white focus:ring-2 focus:ring-purple-500 focus:outline-none" placeholder="Mi Tienda Oficial">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Título Principal (Hero)</label>
                <input type="text" name="hero_title" value="<?= htmlspecialchars($config['hero_title'] ?? $business['business_name'] ?? '') ?>" class="w-full p-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-700 dark:text-white focus:ring-2 focus:ring-purple-500 focus:outline-none" placeholder="Bienvenido a nuestra tienda">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Subtítulo / Frase</label>
                <input type="text" name="hero_subtitle" value="<?= htmlspecialchars($config['hero_subtitle'] ?? '') ?>" class="w-full p-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-700 dark:text-white focus:ring-2 focus:ring-purple-500 focus:outline-none" placeholder="Los mejores productos al mejor precio">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Color Principal</label>
                <div class="flex items-center gap-3">
                    <input type="color" name="primary_color" value="<?= htmlspecialchars($config['primary_color'] ?? '#10b981') ?>" class="w-12 h-10 rounded cursor-pointer border-0">
                    <span class="text-xs text-gray-500 dark:text-gray-400"><?= htmlspecialchars($config['primary_color'] ?? '#10b981') ?></span>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Enlace Personalizado (Slug)</label>
                <div class="flex flex-col xl:flex-row xl:items-stretch shadow-sm rounded-lg overflow-hidden border border-gray-300 dark:border-gray-600 focus-within:ring-2 focus-within:ring-purple-500">
                    <span class="flex items-center px-3 py-2 xl:py-0 bg-gray-50 dark:bg-slate-800 text-gray-500 dark:text-gray-400 text-sm border-b xl:border-b-0 xl:border-r border-gray-300 dark:border-gray-600">
                        <?= BASE_URL ?>tienda/
                    </span>
                    <input type="text" name="slug" value="<?= htmlspecialchars($business['slug'] ?? $_SESSION['business_slug'] ?? '') ?>" class="flex-1 p-2.5 bg-white dark:bg-slate-700 dark:text-white focus:outline-none w-full min-w-0" placeholder="mi-empresa-genial">
                </div>
                <p class="text-xs text-gray-500 mt-1">Este será el enlace público oficial que compartirás con tus clientes. Usa minúsculas y sin espacios.</p>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Subir Logo (Opcional)</label>
                <input type="file" name="logo_image" accept="image/*" class="w-full p-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-700 dark:text-white focus:ring-2 focus:ring-purple-500 focus:outline-none text-sm">
                <input type="hidden" name="logo_url" value="<?= htmlspecialchars($config['logo_url'] ?? '') ?>">
                <?php if (!empty($config['logo_url'])): ?>
                    <p class="text-xs text-green-500 mt-1">Logo actual cargado.</p>
                <?php endif; ?>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Subir Imagen de Fondo (Opcional)</label>
                <input type="file" name="background_image" accept="image/*" class="w-full p-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-700 dark:text-white focus:ring-2 focus:ring-purple-500 focus:outline-none text-sm">
                <input type="hidden" name="background_image_current" value="<?= htmlspecialchars($config['background_image'] ?? '') ?>">
                <?php if (!empty($config['background_image'])): ?>
                    <p class="text-xs text-green-500 mt-1">Fondo actual cargado.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Redes y Contacto -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 flex flex-col h-full">
        <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-4 border-b border-gray-100 dark:border-gray-700 pb-2">
            <i class="fas fa-share-nodes text-blue-500 mr-2"></i>Redes y Contacto
        </h3>
        <div class="flex flex-col gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><i class="fab fa-whatsapp text-green-500 mr-1"></i>WhatsApp</label>
                <input type="text" name="whatsapp" value="<?= htmlspecialchars($config['whatsapp'] ?? '') ?>" class="w-full p-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-700 dark:text-white focus:ring-2 focus:ring-purple-500 focus:outline-none" placeholder="+58 414 1234567">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><i class="fab fa-instagram text-pink-500 mr-1"></i>Instagram</label>
                <input type="text" name="instagram" value="<?= htmlspecialchars($config['instagram'] ?? '') ?>" class="w-full p-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-700 dark:text-white focus:ring-2 focus:ring-purple-500 focus:outline-none" placeholder="@tu_negocio">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><i class="fab fa-facebook text-blue-600 mr-1"></i>Facebook</label>
                <input type="url" name="facebook" value="<?= htmlspecialchars($config['facebook'] ?? '') ?>" class="w-full p-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-700 dark:text-white focus:ring-2 focus:ring-purple-500 focus:outline-none" placeholder="https://facebook.com/tu_negocio">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><i class="fab fa-tiktok text-black dark:text-white mr-1"></i>TikTok</label>
                <input type="text" name="tiktok" value="<?= htmlspecialchars($config['tiktok'] ?? '') ?>" class="w-full p-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-700 dark:text-white focus:ring-2 focus:ring-purple-500 focus:outline-none" placeholder="@tu_negocio">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><i class="fab fa-twitter text-blue-400 mr-1"></i>Twitter / X</label>
                <input type="text" name="twitter" value="<?= htmlspecialchars($config['twitter'] ?? '') ?>" class="w-full p-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-700 dark:text-white focus:ring-2 focus:ring-purple-500 focus:outline-none" placeholder="@tu_negocio">
            </div>
        </div>
    </div>

    <!-- Información de Operación (NUEVO) -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 flex flex-col h-full">
        <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-4 border-b border-gray-100 dark:border-gray-700 pb-2">
            <i class="fas fa-store text-emerald-500 mr-2"></i>Información de Operación
        </h3>
        <div class="space-y-4 flex-1">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Horario de Atención</label>
                <input type="text" name="business_hours" value="<?= htmlspecialchars($config['business_hours'] ?? '') ?>" class="w-full p-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-700 dark:text-white focus:ring-2 focus:ring-purple-500 focus:outline-none" placeholder="Ej: Lunes a Sábado de 9am a 6pm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Dirección de Retiro / Tienda</label>
                <textarea name="business_address" rows="2" class="w-full p-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-700 dark:text-white focus:ring-2 focus:ring-purple-500 focus:outline-none" placeholder="Av. Principal, Edificio Central..."><?= htmlspecialchars($config['business_address'] ?? '') ?></textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Correo de Contacto</label>
                <input type="email" name="contact_email" value="<?= htmlspecialchars($config['contact_email'] ?? '') ?>" class="w-full p-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-700 dark:text-white focus:ring-2 focus:ring-purple-500 focus:outline-none" placeholder="contacto@mitienda.com">
            </div>
        </div>
    </div>

    <!-- Opciones -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 flex flex-col h-full">
        <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-4 border-b border-gray-100 dark:border-gray-700 pb-2">
            <i class="fas fa-sliders text-orange-500 mr-2"></i>Opciones
        </h3>
        <div class="space-y-3">
            <label class="flex items-center gap-3 cursor-pointer">
                <input type="checkbox" name="show_prices" value="1" <?= ($config['show_prices'] ?? 1) ? 'checked' : '' ?> class="w-5 h-5 text-purple-600 rounded focus:ring-purple-500">
                <span class="text-sm text-gray-700 dark:text-gray-300 font-medium">Mostrar precios en el catálogo</span>
            </label>
            <label class="flex items-center gap-3 cursor-pointer">
                <input type="checkbox" name="is_published" value="1" <?= ($config['is_published'] ?? 1) ? 'checked' : '' ?> class="w-5 h-5 text-purple-600 rounded focus:ring-purple-500">
                <span class="text-sm text-gray-700 dark:text-gray-300 font-medium">Tienda publicada (visible al público)</span>
            </label>
        </div>
    </div>

    </div> <!-- End grid -->

    <!-- Action Bar -->
    <div class="mt-8 flex flex-col sm:flex-row justify-end gap-3 sm:gap-4 sm:border-t sm:border-slate-200 sm:pt-6">
        <a href="<?= BASE_URL ?>tienda/<?= $_SESSION['business_slug'] ?? $_SESSION['business_id'] ?>" id="preview-link" target="_blank" class="w-full sm:w-auto text-center text-purple-600 hover:bg-purple-50 bg-purple-50 sm:bg-transparent rounded-lg py-4 sm:py-3 font-medium text-sm transition-colors border border-purple-200 sm:border-transparent hover:border-purple-200">
            <i class="fas fa-external-link-alt mr-1"></i>Previsualizar tienda
        </a>
        <button type="submit" id="saveBtn" class="w-full sm:w-auto bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 text-white font-bold py-4 sm:py-3 px-8 rounded-lg shadow-md transition-all flex items-center justify-center">
            <i class="fas fa-save mr-2"></i>Guardar Configuración
        </button>
    </div>
</form>

<script>
document.getElementById('configForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const btn = document.getElementById('saveBtn');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Guardando...';
    btn.disabled = true;
    btn.classList.add('opacity-75', 'cursor-not-allowed');

    try {
        const formData = new FormData(this);
        const response = await fetch(this.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        const result = await response.json();
        
        if (result.success) {
            Swal.fire({
                icon: 'success',
                title: '¡Guardado!',
                text: result.message,
                timer: 2000,
                showConfirmButton: false
            });
            
            // Actualizar los enlaces en la interfaz sin recargar
            if (result.slug) {
                const newUrl = '<?= BASE_URL ?>tienda/' + result.slug;
                document.getElementById('preview-link').href = newUrl;
                
                const publicLinkCode = document.querySelector('code');
                if (publicLinkCode) publicLinkCode.textContent = newUrl;
                
                const openBtns = document.querySelectorAll('a[href^="<?= BASE_URL ?>tienda/"]');
                openBtns.forEach(btn => btn.href = newUrl);
            }
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: result.message || 'Ocurrió un problema al guardar.'
            });
        }
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Error de red',
            text: 'No se pudo conectar con el servidor.'
        });
    } finally {
        btn.innerHTML = originalText;
        btn.disabled = false;
        btn.classList.remove('opacity-75', 'cursor-not-allowed');
    }
});
</script>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>
