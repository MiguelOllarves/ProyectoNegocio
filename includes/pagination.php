<?php
// Variables esperadas: $page, $totalPages, $totalRecords, $limit, $baseUrl, $hxTarget, $colspan
$page = (int)($page ?? 1);
$totalPages = (int)($totalPages ?? 1);
$totalRecords = (int)($totalRecords ?? 0);
$limit = (int)($limit ?? 5);
$hxTarget = $hxTarget ?? '';
$colspan = $colspan ?? 100;

if ($totalRecords == 0 || $totalPages <= 1) {
    return;
}

$start = ($page - 1) * $limit + 1;
$end = min($page * $limit, $totalRecords);

// Si no se define un $baseUrl explicitamente en el controlador (para casos simples donde se reusa la misma URL de la vista principal)
if (!isset($baseUrl)) {
    $baseUrl = $_SERVER['REQUEST_URI'];
    // Remove query string if any to base URL
    $baseUrl = strtok($baseUrl, '?');
}

// Preserve existing query params (limit, search, filters) except 'page'
$queryParams = $_GET;
unset($queryParams['page']);
$queryString = http_build_query($queryParams);
$separator = strpos($baseUrl, '?') !== false ? '&' : '?';
if (!empty($queryString)) {
    $baseUrl .= $separator . $queryString;
    $separator = '&';
}
?>
<tr class="bg-gray-50/50 dark:bg-slate-800/30 border-t border-gray-100 dark:border-gray-800">
    <td colspan="<?= $colspan ?>" class="px-6 py-4">
        <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="text-sm text-gray-500 dark:text-gray-400 font-medium">
                Mostrando del <span class="font-bold text-gray-900 dark:text-white"><?= $start ?></span> 
                al <span class="font-bold text-gray-900 dark:text-white"><?= $end ?></span> 
                de <span class="font-bold text-gray-900 dark:text-white"><?= $totalRecords ?></span> registros
            </div>
            <div class="flex gap-2">
<?php
// Resolve tailwind CSS conflicts for VS Code by defining class strings separately
$baseBtnClass = "px-4 py-2 border border-gray-200 dark:border-gray-700 rounded-lg text-sm font-bold shadow-sm transition-all";
$disabledBtnClass = "bg-gray-100 dark:bg-slate-800 text-gray-400 cursor-not-allowed";
$enabledBtnClass = "bg-white dark:bg-slate-900 text-gray-700 dark:text-gray-200 hover:bg-brand-50 dark:hover:bg-brand-900/30 hover:border-brand-200 hover:text-brand-600";
?>
                <button <?= $page <= 1 ? 'disabled' : '' ?> 
                    class="<?= $baseBtnClass ?> <?= $page <= 1 ? $disabledBtnClass : $enabledBtnClass ?>"
                    hx-get="<?= $baseUrl . $separator . 'page=' . ($page - 1) ?>" 
                    hx-target="<?= $hxTarget ?>"
                    hx-select="<?= $hxTarget ?>">
                    <i class="fas fa-chevron-left mr-1 text-[10px]"></i> Anterior
                </button>
                <button <?= $page >= $totalPages ? 'disabled' : '' ?> 
                    class="<?= $baseBtnClass ?> <?= $page >= $totalPages ? $disabledBtnClass : $enabledBtnClass ?>"
                    hx-get="<?= $baseUrl . $separator . 'page=' . ($page + 1) ?>" 
                    hx-target="<?= $hxTarget ?>"
                    hx-select="<?= $hxTarget ?>">
                    Siguiente <i class="fas fa-chevron-right ml-1 text-[10px]"></i>
                </button>
            </div>
        </div>
    </td>
</tr>
