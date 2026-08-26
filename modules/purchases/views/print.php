<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Compras</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            color: #333;
            font-size: 12px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #8b5cf6;
            padding-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            color: #5b21b6;
        }
        .header p {
            margin: 5px 0 0 0;
            color: #666;
            font-size: 12px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
            page-break-inside: auto;
        }
        tr { page-break-inside: avoid; page-break-after: auto; }
        thead { display: table-header-group; }
        tfoot { display: table-footer-group; }
        th, td {
            border: 1px solid #d1d5db;
            padding: 8px 6px;
            text-align: left;
            vertical-align: middle;
        }
        th {
            background-color: #f3f4f6;
            font-weight: bold;
            color: #111;
            text-align: center;
            text-transform: uppercase;
        }
        td.text-center { text-align: center; }
        td.text-right { text-align: right; }
        .footer {
            margin-top: 20px;
            font-size: 10px;
            text-align: right;
            color: #999;
        }
        @media print {
            @page { size: letter; margin: 1cm; }
            body { margin: 0; padding: 0; }
            .header { padding-top: 0; }
        }
    </style>
</head>
<body onload="window.print()">

    <?php 
    $printTitle = "Master de Compras y Recepciones";
    include __DIR__ . '/../../../includes/print_header.php'; 
    ?>

    <table>
        <thead>
            <tr>
                <th style="width: 80px;">ID Compra</th>
                <th>Fecha / Hora</th>
                <th>Proveedor</th>
                <th>Total Dólares</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $grandTotal = 0;
            if (!empty($purchases)): foreach ($purchases as $p): 
                $grandTotal += $p['total'];
            ?>
            <tr>
                <td class="text-center font-bold font-mono">CMP-<?= str_pad($p['id'], 4, '0', STR_PAD_LEFT) ?></td>
                <td class="text-center text-gray-700"><?= date('d/m/Y h:i A', strtotime($p['created_at'])) ?></td>
                <td style="font-weight: bold;"><?= htmlspecialchars($p['supplier_name'] ?? 'Sin proveedor') ?></td>
                <td class="text-right font-bold text-green-700">$<?= number_format($p['total'], 2) ?></td>
            </tr>
            <?php endforeach; else: ?>
            <tr>
                <td colspan="4" class="text-center" style="padding: 20px;">No hay compras registradas.</td>
            </tr>
            <?php endif; ?>
        </tbody>
        <?php if (!empty($purchases)): ?>
        <tfoot>
            <tr>
                <td colspan="3" class="text-right" style="font-weight: bold; padding-right: 15px;">TOTAL COMPRAS GENERAL:</td>
                <td class="text-right" style="font-weight: bold; font-size: 14px; background-color: #f3f4f6;">$<?= number_format($grandTotal, 2) ?></td>
            </tr>
        </tfoot>
        <?php endif; ?>
    </table>

    <div class="footer">
        Desarrollado en TuInventario &copy; <?= date('Y') ?>
    </div>

</body>
</html>
