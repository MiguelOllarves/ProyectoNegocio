<?php
require_once __DIR__ . '/../../../core/Settings.php';
$bcvRate = (float)Settings::getBcvRate();
$fmtUsd = function($n) { return number_format((float)$n, 2, ',', '.'); };
$fmtBs  = function($n) use ($bcvRate) { return number_format((float)$n * $bcvRate, 2, ',', '.'); };

include __DIR__ . '/../../../includes/header.php';
?>

<div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h2 class="text-2xl font-bold text-gray-800 dark:text-white flex items-center">
            <i class="fas fa-utensils text-red-500 mr-3"></i> Mis Platos
        </h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Cada plato descuenta sus ingredientes del inventario cuando lo vendes.</p>
    </div>
    <?php if (!empty($dishes)): ?>
    <a href="<?= BASE_URL ?>restaurant/create_view" class="w-full sm:w-auto inline-flex items-center justify-center bg-gradient-to-r from-brand-600 to-brand-500 hover:from-brand-500 hover:to-brand-400 text-white font-bold py-3 px-6 rounded-xl shadow-lg shadow-brand-500/30 transition-all hover:-translate-y-0.5 text-sm">
        <i class="fas fa-plus mr-2"></i> Crear Plato
    </a>
    <?php endif; ?>
</div>

<!-- Explicación simple -->
<div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-800 rounded-2xl p-4 mb-6 flex items-start gap-3">
    <i class="fas fa-lightbulb text-blue-500 mt-0.5"></i>
    <p class="text-xs text-blue-800 dark:text-blue-200 leading-relaxed">
        <b>Cómo funciona:</b> 1️⃣ Registra tus ingredientes en <a href="<?= BASE_URL ?>inventory" class="font-bold underline">Inventario</a> (carne, arroz, pan...).
        2️⃣ Crea tu plato aquí y dile cuánto lleva de cada ingrediente.
        3️⃣ Vende el plato en el Punto de Venta y el sistema solo descontará los ingredientes gastados.
    </p>
</div>

<?php if (empty($dishes)): ?>
<div class="bg-white dark:bg-slate-800 border border-dashed border-gray-300 dark:border-gray-600 rounded-2xl p-12 text-center">
    <div class="w-16 h-16 mx-auto mb-4 bg-red-50 dark:bg-red-900/20 rounded-full flex items-center justify-center">
        <i class="fas fa-hamburger text-2xl text-red-400"></i>
    </div>
    <h3 class="text-lg font-bold text-gray-700 dark:text-gray-200 mb-2">Todavía no tienes platos</h3>
    <p class="text-sm text-gray-500 dark:text-gray-400 mb-6 max-w-md mx-auto">Un plato es una comida que preparas con tus ingredientes. Por ejemplo: una hamburguesa que lleva 150g de carne, 1 pan y 2 quesos.</p>
    <a href="<?= BASE_URL ?>restaurant/create_view" class="inline-flex items-center bg-brand-600 hover:bg-brand-700 text-white font-bold py-3 px-8 rounded-xl shadow-lg shadow-brand-500/30 transition-all text-sm">
        <i class="fas fa-plus mr-2"></i> Crear mi primer plato
    </a>
</div>
<?php else: ?>
<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 lg:gap-6">

    <?php foreach ($dishes as $dish):
        $profitClass = $dish['profit'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400';
        $servings = $dish['available_servings'];
        $servingsText = $servings === null ? 'Sin receta' : (($servings == 0 || $servings >= PHP_INT_MAX) ? (string)$servings : (string)$servings);
        $servingsColor = ($servings !== null && $servings <= 0) ? 'text-red-600 dark:text-red-400' : 'text-gray-600 dark:text-gray-300';
    ?>
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm hover:shadow-md transition-shadow border border-gray-100 dark:border-gray-700 p-5 flex flex-col">
        <!-- Encabezado del plato -->
        <div class="flex items-start justify-between mb-4">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-red-100 to-orange-100 dark:from-red-900/40 dark:to-orange-900/40 flex items-center justify-center text-xl shrink-0">
                    <?php if (!empty($dish['image'])): ?>
                        <img src="<?= htmlspecialchars($dish['image']) ?>" class="w-full h-full object-cover rounded-xl" alt="">
                    <?php else: ?>
                        🍽️
                    <?php endif; ?>
                </div>
                <div>
                    <h3 class="font-bold text-gray-800 dark:text-white leading-tight"><?= htmlspecialchars($dish['name']) ?></h3>
                    <p class="text-xs text-gray-400 mt-0.5">
                        <?php if (!empty($dish['category_name'])): ?><span class="inline-block bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 px-2 py-0.5 rounded-full mr-1"><?= htmlspecialchars($dish['category_name']) ?></span><?php endif; ?>
                        <?php if (!empty($dish['prep_time'])): ?><i class="fas fa-clock mr-1"></i><?= (int)$dish['prep_time'] ?> min · <?php endif; ?>
                        <?= $dish['ingredients_count'] ?> ingrediente<?= $dish['ingredients_count'] == 1 ? '' : 'es' ?>
                    </p>
                </div>
            </div>
            <span class="text-right">
                <span class="block text-lg font-black text-gray-800 dark:text-white leading-tight">$<?= $fmtUsd($dish['price']) ?></span>
                <span class="block text-[11px] font-semibold text-gray-400">Bs. <?= $fmtBs($dish['price']) ?></span>
            </span>
        </div>

        <!-- Métricas -->
        <div class="grid grid-cols-3 gap-2 mb-4 text-center">
            <div class="bg-gray-50 dark:bg-slate-700/40 rounded-xl py-2 px-1">
                <p class="text-[10px] uppercase font-bold text-gray-400 tracking-wide">Costo</p>
                <p class="text-sm font-black text-brand-600">$<?= $fmtUsd($dish['recipe_cost']) ?></p>
                <p class="text-[10px] font-semibold text-gray-400">Bs. <?= $fmtBs($dish['recipe_cost']) ?></p>
            </div>
            <div class="bg-gray-50 dark:bg-slate-700/40 rounded-xl py-2 px-1">
                <p class="text-[10px] uppercase font-bold text-gray-400 tracking-wide">Ganancia</p>
                <p class="text-sm font-black <?= $profitClass ?>">$<?= $fmtUsd($dish['profit']) ?></p>
                <p class="text-[10px] font-semibold text-gray-400">Bs. <?= $fmtBs($dish['profit']) ?></p>
            </div>
            <div class="bg-gray-50 dark:bg-slate-700/40 rounded-xl py-2 px-1">
                <p class="text-[10px] uppercase font-bold text-gray-400 tracking-wide">Puedo hacer</p>
                <p class="text-sm font-black <?= $servingsColor ?>"><?= $servings === null ? '—' : $servings ?></p>
            </div>
        </div>

        <?php if ($servings !== null && $servings <= 0): ?>
        <div class="mb-4 bg-red-50 dark:bg-red-900/20 border border-red-100 dark:border-red-800 rounded-xl px-3 py-2 text-[11px] text-red-700 dark:text-red-300 font-medium">
            <i class="fas fa-exclamation-triangle mr-1"></i> No tienes ingredientes suficientes para preparar este plato.
        </div>
        <?php elseif ($servings !== null && $servings <= 3): ?>
        <div class="mb-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-100 dark:border-yellow-800 rounded-xl px-3 py-2 text-[11px] text-yellow-700 dark:text-yellow-300 font-medium">
            <i class="fas fa-exclamation-circle mr-1"></i> ¡Quedan pocas porciones! Considera comprar más ingredientes.
        </div>
        <?php endif; ?>

        <!-- Acciones -->
        <div class="mt-auto flex gap-2 pt-2">
            <a href="<?= BASE_URL ?>restaurant/edit_dish_view/<?= $dish['id'] ?>" class="flex-1 inline-flex items-center justify-center gap-2 bg-brand-50 dark:bg-brand-900/30 border border-brand-100 dark:border-brand-800 text-brand-700 dark:text-brand-300 hover:bg-brand-100 dark:hover:bg-brand-900/50 font-bold py-2.5 rounded-xl transition-all text-xs">
                <i class="fas fa-edit"></i> Editar Plato
            </a>
            <button type="button" onclick="deleteDish(<?= $dish['id'] ?>, '<?= htmlspecialchars($dish['name'], ENT_QUOTES) ?>')" class="inline-flex items-center justify-center w-11 bg-red-50 dark:bg-red-900/30 border border-red-100 dark:border-red-800 text-red-600 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-900/50 rounded-xl transition-all" title="Eliminar Plato">
                <i class="fas fa-trash-alt"></i>
            </button>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<script>
function deleteDish(id, name) {
    Swal.fire({
        title: '¿Eliminar "' + name + '"?',
        text: "El plato desaparecerá del menú. Tus ingredientes NO se borran.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (!result.isConfirmed) return;
        fetch(`<?= BASE_URL ?>restaurant/delete/${id}`, {
            method: 'GET',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => {
            if (res.ok) {
                Swal.fire({ title: '¡Eliminado!', icon: 'success', timer: 1500, showConfirmButton: false })
                    .then(() => window.location.reload());
            } else {
                return res.text().then(t => Swal.fire('Error', t.includes('dependency') ? 'No se puede eliminar porque tiene ventas asociadas.' : 'No se pudo eliminar el plato.', 'error'));
            }
        })
        .catch(() => Swal.fire('Error', 'Error de red', 'error'));
    });
}
</script>

<?php if (isset($_GET['success'])): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    let msg = '<?= $_GET['success'] === 'created' ? 'El plato ha sido registrado correctamente.' : 'El plato ha sido actualizado correctamente.' ?>';
    Swal.fire({
        title: '¡Éxito!',
        text: msg,
        icon: 'success',
        timer: 2500,
        showConfirmButton: false,
        customClass: { popup: 'rounded-2xl' }
    });
    // Remove the ?success param from URL cleanly
    window.history.replaceState({}, document.title, "<?= BASE_URL ?>restaurant");
});
</script>
<?php endif; ?>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>
