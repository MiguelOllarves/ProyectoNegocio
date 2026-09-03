<?php
// Fetch business configuration if not fully complete in $business
require_once __DIR__ . '/../../../config/Database.php';
$db = Database::getInstance()->getConnection();
$stmtBiz = $db->prepare("SELECT business_name, (logo_base64 IS NOT NULL AND logo_base64 != '') as has_logo, ticket_header, ticket_footer FROM businesses WHERE id = ?");
$tenant_id = $_SESSION['business_id'] ?? 1;
$stmtBiz->execute([$tenant_id]);
$biz = $stmtBiz->fetch(PDO::FETCH_ASSOC);

// Determine width (default 80mm, can be set via GET ?w=58)
$width = $_GET['w'] ?? '80';
$widthClass = $width === '58' ? 'max-w-[58mm]' : 'max-w-[80mm]';
$fontSizeClass = $width === '58' ? 'text-[10px]' : 'text-xs';
$titleClass = $width === '58' ? 'text-sm' : 'text-xl';
$headerSize = $width === '58' ? 'text-[11px]' : 'text-sm';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket Compromiso Legal - <?= htmlspecialchars($client['name']) ?></title>
    <!-- Use Tailwind via CDN for easy styling -->
    <link rel="stylesheet" href="<?= BASE_URL ?? "" ?>css/tailwind.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Courier+Prime:ital,wght@0,400;0,700;1,400;1,700&display=swap');
        
        body { font-family: 'Courier Prime', monospace; background-color: #f3f4f6; }
        @page { margin: 0; }
        
        @media print {
            body { background-color: white; margin: 0; padding: 0; display: block; -webkit-print-color-adjust: exact; }
            .no-print { display: none !important; }
            .ticket-container {
                box-shadow: none !important; margin: 0 !important; padding: 0 !important;
                width: 100% !important; max-width: <?= $width ?>mm !important;
            }
        }
    </style>
</head>
<body class="flex justify-center items-start min-h-screen p-4 sm:p-10">

    <div class="ticket-container bg-white p-4 sm:p-5 w-full <?= $widthClass ?> shadow-2xl text-black <?= $fontSizeClass ?> flex flex-col mx-auto transition-all">
        <!-- Logo -->
        <?php if (!empty($biz['has_logo'])): ?>
        <div class="flex justify-center mb-2">
            <img src="<?= BASE_URL ?>?serve_logo=1&tenant=<?= $credit['tenant_id'] ?>&t=<?= time() ?>" alt="Logo" class="max-w-full <?= $width === '58' ? 'h-10' : 'h-16' ?> object-contain grayscale" style="filter: grayscale(100%) contrast(1.2);">
        </div>
        <?php endif; ?>

        <!-- Encabezado -->
        <div class="text-center mb-3">
            <h1 class="font-bold <?= $titleClass ?> uppercase leading-tight mb-1"><?= htmlspecialchars($biz['business_name'] ?: 'TU INVENTARIO') ?></h1>
            <?php if (!empty($biz['ticket_header'])): ?>
                <div class="opacity-90 whitespace-pre-line leading-tight mb-2 uppercase"><?= htmlspecialchars($biz['ticket_header']) ?></div>
            <?php endif; ?>
            <p><?= date('d/m/Y h:i A') ?></p>
        </div>

        <div class="text-center font-bold uppercase <?= $headerSize ?> border-t border-b border-black py-1 mb-2">
            PAGARÉ / COMPROMISO
        </div>

        <div class="text-justify mb-3 leading-tight opacity-90 <?= $width === '58' ? 'text-[9px]' : 'text-[11px]' ?>">
            Yo, <strong><?= htmlspecialchars($client['name']) ?></strong> (Cód/CI: <?= htmlspecialchars($client['document'] ?? '______') ?>), me comprometo a pagar incondicionalmente a la orden de <strong><?= htmlspecialchars($biz['business_name'] ?: 'El Establecimiento') ?></strong> el siguiente monto.
        </div>

        <div class="border-t border-dashed border-black py-2 mb-2 space-y-1">
            <div class="flex justify-between items-end">
                <span>CONCEPTO:</span>
                <span class="text-right font-bold w-1/2"><?= htmlspecialchars($credit['credit_type'] === 'producto' ? 'PRODUCTO' : 'EFECTIVO') ?></span>
            </div>
            
            <div class="flex justify-between">
                <span>BASE:</span>
                <span class="text-right">$<?= number_format($credit['base_amount'] ?? $credit['total_amount'], 2) ?></span>
            </div>

            <?php if ($credit['credit_type'] === 'dinero' && isset($credit['interest_rate']) && $credit['interest_rate'] > 0): ?>
            <div class="flex justify-between">
                <span>INT. (<?= floatval($credit['interest_rate']) ?>%):</span>
                <span class="text-right">$<?= number_format(($credit['base_amount'] ?? 0) * ($credit['interest_rate'] / 100), 2) ?></span>
            </div>
            <?php elseif ($credit['credit_type'] === 'producto' && isset($credit['down_payment']) && $credit['down_payment'] > 0): ?>
            <div class="flex justify-between">
                <span>INICIAL:</span>
                <span class="text-right">-$<?= number_format($credit['down_payment'], 2) ?></span>
            </div>
            <?php endif; ?>

            <div class="flex justify-between font-bold border-t border-black mt-1 pt-1 text-sm">
                <span>TOTAL:</span>
                <span class="text-right">$<?= number_format($credit['remaining_amount'], 2) ?></span>
            </div>

            <div class="flex justify-between mt-1">
                <span>LÍMITE:</span>
                <span class="text-right font-bold"><?= $credit['due_date'] ? date('d/m/Y', strtotime($credit['due_date'])) : 'A la vista' ?></span>
            </div>
        </div>

        <?php if(!empty($credit['notes'])): ?>
        <div class="mb-3 leading-tight opacity-90 <?= $width === '58' ? 'text-[9px]' : 'text-[11px]' ?>">
            NOTA: <?= htmlspecialchars($credit['notes']) ?>
        </div>
        <?php endif; ?>

        <div class="opacity-80 text-center mb-10 <?= $width === '58' ? 'text-[8px]' : 'text-[9px]' ?>">
            * El incumplimiento genera recargos.
        </div>

        <div class="text-center mt-4">
            <div class="border-t border-black w-4/5 mx-auto pt-1">
                <p>FIRMA Y HUELLA</p>
                <p><?= htmlspecialchars($client['document'] ?? '') ?></p>
            </div>
        </div>
        
    </div>

    <!-- Controles flotantes (Ocultos en impresión) -->
    <div class="fixed top-4 right-4 flex flex-col gap-2 no-print bg-white/90 backdrop-blur p-3 rounded-xl shadow-xl border border-gray-200">
        <h3 class="font-bold text-xs uppercase text-gray-500 mb-1">Motor POS</h3>
        <a href="?id=<?= $credit['id'] ?>&w=80" class="<?= $width === '80' ? 'bg-black text-white' : 'bg-gray-100 text-gray-800' ?> px-3 py-1.5 rounded-lg text-xs font-bold text-center transition-colors">Modo 80mm</a>
        <a href="?id=<?= $credit['id'] ?>&w=58" class="<?= $width === '58' ? 'bg-black text-white' : 'bg-gray-100 text-gray-800' ?> px-3 py-1.5 rounded-lg text-xs font-bold text-center transition-colors">Modo 58mm</a>
        <hr class="my-1 border-gray-200">
        <button onclick="window.print()" class="bg-blue-600 hover:bg-blue-500 text-white px-4 py-2.5 rounded-lg font-bold shadow-lg transition-colors flex items-center justify-center text-sm">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
            Imprimir
        </button>
        <a href="<?= BASE_URL ?>credits" class="bg-gray-100 border border-gray-300 hover:bg-gray-200 text-gray-800 px-4 py-2 rounded-lg font-bold text-sm text-center transition-colors">Volver</a>
    </div>

</body>
</html>
