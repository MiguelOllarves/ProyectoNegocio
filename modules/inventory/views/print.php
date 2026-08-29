<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Inventario</title>
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
            border-bottom: 2px solid #10b981;
            padding-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            color: #065f46;
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
        .product-img {
            max-width: 40px;
            max-height: 40px;
            border-radius: 4px;
            object-fit: contain;
            display: block;
            margin: 0 auto;
        }
        .badge-iva {
            display: inline-block;
            padding: 2px 4px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: bold;
        }
        .iva-yes { background: #e0f2fe; color: #0284c7; }
        .iva-no { background: #fef08a; color: #854d0e; }
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
    $printTitle = "Reporte de Inventario";
    include __DIR__ . '/../../../includes/print_header.php'; 
    ?>

    <table>
        <thead>
            <tr>
                <th style="width: 50px;">Foto</th>
                <th>Producto</th>
                <th>Cód. Barra</th>
                <th>Stock</th>
                <th>Precio Unit.</th>
                <th>Precio Paquete</th>
                <th>Aplica IVA</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($products)): foreach ($products as $p): 
                $foto = '';
                if(!empty($p['image'])){
                    if ($p['image'] === 'base64') {
                        $foto = BASE_URL . 'inventory/image?id=' . $p['id'];
                    } else {
                        $foto = (strpos($p['image'], 'data:image') === 0 || strpos($p['image'], 'http') === 0) ? $p['image'] : BASE_URL . '../' . htmlspecialchars($p['image']);
                    }
                }
                // Precio del Paquete (bulk_cost * (1 + margin/100))
                $bulkCost = $p['bulk_cost'] > 0 ? $p['bulk_cost'] : 0;
                $margin = $p['profit_margin'] > 0 ? $p['profit_margin'] : 0;
                $bulkPrice = $bulkCost * (1 + ($margin / 100));
            ?>
            <tr>
                <td style="text-align: center;">
                    <?php if ($foto): ?>
                    <img src="<?= $foto ?>" class="product-img" alt="Foto" onerror="this.style.display='none'">
                    <?php else: ?>
                    <div style="font-size: 10px; color: #ccc;">Sin foto</div>
                    <?php endif; ?>
                </td>
                <td style="font-weight: bold;">
                    <?= htmlspecialchars($p['name']) ?><br>
                    <span style="font-size:10px; color:#666; font-weight:normal;"><?= htmlspecialchars($p['category_name'] ?? '') ?></span>
                </td>
                <td class="text-center font-mono">
                    <?= !empty($p['barcode']) ? htmlspecialchars($p['barcode']) : 'N/A' ?>
                </td>
                <td class="text-center font-bold">
                    <?= $p['stock'] ?> <span style="font-size:9px; color:#555;"><?= htmlspecialchars($p['unit_of_measure'] ?? 'Unidades') ?></span>
                </td>
                <td class="text-right font-bold" style="color: #059669;">
                    $<?= number_format($p['price'], 2) ?>
                </td>
                <td class="text-right">
                    <?= $bulkPrice > 0 ? '$' . number_format($bulkPrice, 2) : '<span style="color:#aaa;">N/A</span>' ?>
                    <br><span style="font-size:8px; color:#888;">(Bulto de <?= $p['units_per_bulk'] > 0 ? $p['units_per_bulk'] : 1 ?>)</span>
                </td>
                <td class="text-center">
                    <?php if (empty($p['is_tax_exempt'])): ?>
                        <span class="badge-iva iva-no">NO</span>
                    <?php else: ?>
                        <span class="badge-iva iva-yes">SÍ (16%)</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; else: ?>
            <tr>
                <td colspan="7" class="text-center" style="padding: 20px;">No hay productos registrados en el inventario.</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="footer">
        Desarrollado en TuInventario &copy; <?= date('Y') ?>
    </div>

</body>
</html>
