<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket #<?= htmlspecialchars($sale_id) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Estilos optimizados para impresión térmica de 80mm */
        @media print {
            body { width: 80mm; margin: 0; padding: 0; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body class="bg-gray-100 flex justify-center items-start min-h-screen p-4">

    <!-- Mockup of the receipt -->
    <div class="bg-white p-6 max-w-[80mm] w-full shadow-lg text-sm text-gray-800 font-mono">
        <div class="text-center mb-4">
            <h1 class="font-bold text-xl uppercase">Tu Inventario</h1>
            <p class="text-xs mt-1">Ticket #<?= str_pad($sale_id, 6, '0', STR_PAD_LEFT) ?></p>
            <p class="text-xs"><?= date('d/m/Y H:i') ?></p>
        </div>
        
        <div class="border-t border-b border-dashed border-gray-400 py-3 mb-3">
            <div class="flex justify-between font-bold mb-2">
                <span>CANT. DESC.</span>
                <span>IMPORTE</span>
            </div>
            
            <?php
            // Fetch sale from database
            require_once __DIR__ . '/../../../config/Database.php';
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT si.quantity, si.price_at_sale, p.name FROM sale_items si JOIN products p ON si.product_id = p.id WHERE si.sale_id = ?");
            $stmt->execute([$sale_id]);
            $items = $stmt->fetchAll();
            
            $stmtSale = $db->prepare("SELECT total, cash_received, change_given FROM sales WHERE id = ?");
            $stmtSale->execute([$sale_id]);
            $sale = $stmtSale->fetch();
            ?>
            
            <?php foreach ($items as $item): ?>
                <div class="flex justify-between text-xs mb-1">
                    <span class="w-3/4"><?= $item['quantity'] ?>x <?= substr($item['name'], 0, 18) ?></span>
                    <span class="w-1/4 text-right">$<?= number_format($item['quantity'] * $item['price_at_sale'], 2) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="flex justify-between font-bold mb-1">
            <span>TOTAL</span>
            <span class="text-lg">$<?= number_format($sale['total'], 2) ?></span>
        </div>
        <div class="flex justify-between text-xs">
            <span>EFECTIVO</span>
            <span>$<?= number_format($sale['cash_received'], 2) ?></span>
        </div>
        <div class="flex justify-between text-xs">
            <span>CAMBIO</span>
            <span>$<?= number_format($sale['change_given'], 2) ?></span>
        </div>
        
        <div class="text-center text-xs mt-6 border-t border-dashed border-gray-400 pt-3">
            <p>¡Gracias por su compra!</p>
            <p class="mt-1">Vuelva pronto</p>
        </div>
    </div>
    
    <div class="fixed top-4 right-4 flex flex-col gap-2 no-print">
        <button onclick="window.print()" class="bg-brand-600 hover:bg-brand-700 text-white px-4 py-2 rounded-lg font-bold shadow transition-colors flex items-center justify-center" style="background-color: #0ea5e9;">
            Imprimir Ticket
        </button>
        <a href="<?= BASE_URL ?>sales" class="bg-gray-100 hover:bg-gray-200 text-gray-800 border border-gray-300 px-4 py-2 rounded-lg font-bold shadow-sm transition-colors text-center inline-block">
            Volver
        </a>
    </div>

</body>
</html>
