<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte Kardex</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; color: #333; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #ea580c; padding-bottom: 10px; }
        .header h1 { margin: 0; font-size: 24px; color: #c2410c; }
        .header p { margin: 5px 0 0 0; color: #666; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; font-size: 11px; page-break-inside: auto; }
        tr { page-break-inside: avoid; page-break-after: auto; }
        thead { display: table-header-group; }
        th, td { border: 1px solid #d1d5db; padding: 8px 6px; text-align: left; }
        th { background-color: #f3f4f6; font-weight: bold; color: #111; text-align: center; text-transform: uppercase; }
        td.text-center { text-align: center; }
        .badge { display: inline-block; padding: 2px 6px; border-radius: 4px; font-weight: bold; font-size: 9px; }
        .badge.in { background: #dcfce7; color: #166534; }
        .badge.out { background: #fee2e2; color: #991b1b; }
        .badge.adj { background: #fef3c7; color: #92400e; }
        .badge.sale { background: #e0e7ff; color: #3730a3; }
        .footer { margin-top: 20px; font-size: 10px; text-align: right; color: #999; }
        @media print { @page { size: letter; margin: 1cm; } body { margin: 0; padding: 0; } .header { padding-top: 0; } }
    </style>
</head>
<body onload="window.print()">

    <?php 
    $printTitle = "Historial de Movimientos de Kardex" . (!empty($selectedProduct) ? " (Filtro: Producto Específico)" : "");
    include __DIR__ . '/../../../includes/print_header.php'; 
    ?>

    <table>
        <thead>
            <tr>
                <th>Fecha y Hora</th>
                <th>Producto</th>
                <th>Tipo Movimiento</th>
                <th>Cantidad</th>
                <th>Stock Final Resultante</th>
                <th>Referencia</th>
                <th>Despachado / Recibido por</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($kardex)): foreach ($kardex as $k): ?>
            <tr>
                <td class="text-center font-mono"><?= date('d/m/Y h:i A', strtotime($k['created_at'] ?? 'now')) ?></td>
                <td style="font-weight: bold;"><?= htmlspecialchars($k['product_name'] ?? 'Producto Desconocido') ?></td>
                <td class="text-center">
                    <?php 
                        $type = $k['movement_type'] ?? 'unknown';
                        if ($type === 'in_purchase') echo '<span class="badge in">COMPRA (ENTRADA)</span>';
                        elseif ($type === 'out_sale') echo '<span class="badge sale">VENTA (SALIDA)</span>';
                        elseif ($type === 'adj_add') echo '<span class="badge adj">AJUSTE (+)</span>';
                        elseif ($type === 'adj_sub') echo '<span class="badge out">AJUSTE (-)</span>';
                        else echo htmlspecialchars((string)$type);
                    ?>
                </td>
                <td class="text-center" style="font-weight: bold; font-size: 14px; <?= ($k['quantity'] ?? 0) > 0 ? 'color:green;' : 'color:red;' ?>">
                    <?= ($k['quantity'] ?? 0) > 0 ? '+' : '' ?><?= $k['quantity'] ?? 0 ?>
                </td>
                <td class="text-center" style="font-weight: bold; font-size: 14px; background: #f9fafb;">
                    <?= $k['resulting_stock'] ?? '-' ?>
                </td>
                <td style="font-size: 10px; max-width: 250px;"><?= htmlspecialchars($k['reference'] ?? '-') ?></td>
                <td class="text-center"><?= htmlspecialchars($k['user_name'] ?? '-') ?></td>
            </tr>
            <?php endforeach; else: ?>
            <tr>
                <td colspan="7" class="text-center" style="padding: 20px;">No existen movimientos de Kardex registrados.</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="footer">
        Desarrollado en TuInventario &copy; <?= date('Y') ?>
    </div>

</body>
</html>
