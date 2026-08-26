<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Ventas</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; color: #333; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #2563eb; padding-bottom: 10px; }
        .header h1 { margin: 0; font-size: 24px; color: #1e3a8a; }
        .header p { margin: 5px 0 0 0; color: #666; font-size: 12px; }
        .summary-box { display: flex; justify-content: space-between; margin-bottom: 20px; background: #f8fafc; border: 1px solid #e2e8f0; padding: 10px; border-radius: 8px; }
        .summary-item { text-align: center; flex: 1; border-right: 1px solid #e2e8f0; }
        .summary-item:last-child { border-right: none; }
        .summary-item strong { display: block; font-size: 16px; color: #1e3a8a; margin-top: 5px; }
        table { width: 100%; border-collapse: collapse; font-size: 11px; page-break-inside: auto; }
        tr { page-break-inside: avoid; page-break-after: auto; }
        thead { display: table-header-group; }
        th, td { border: 1px solid #d1d5db; padding: 8px 6px; text-align: left; }
        th { background-color: #f3f4f6; font-weight: bold; color: #111; text-align: center; }
        td.text-center { text-align: center; }
        td.text-right { text-align: right; }
        .footer { margin-top: 20px; font-size: 10px; text-align: right; color: #999; }
        @media print { @page { size: letter; margin: 1cm; } body { margin: 0; padding: 0; } .header { padding-top: 0; } }
    </style>
</head>
<body onload="window.print()">

    <?php 
    $printTitle = "Reporte de Ventas (Periodo: " . date('d/m/Y', strtotime($start)) . " al " . date('d/m/Y', strtotime($end)) . ")";
    include __DIR__ . '/../../../includes/print_header.php'; 
    ?>

    <div class="summary-box">
        <div class="summary-item">Ingresos Brutos <strong>$<?= number_format((float)($summary['revenue'] ?? 0), 2) ?></strong></div>
        <div class="summary-item">Costos <strong>$<?= number_format((float)($summary['costs'] ?? 0), 2) ?></strong></div>
        <div class="summary-item">Margen / Ganancia <strong>$<?= number_format((float)($summary['profit'] ?? 0), 2) ?></strong></div>
        <div class="summary-item">Transacciones Totales <strong style="color:#059669;"><?= $summary['count'] ?? 0 ?></strong></div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Fecha</th>
                <th>ID Venta</th>
                <th>Métodos de Pago</th>
                <th>Costo ($)</th>
                <th>Ganancia ($)</th>
                <th>Total Facturado ($)</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($sales)): foreach ($sales as $s): ?>
            <tr>
                <td class="text-center font-mono"><?= date('d/m/Y h:i A', strtotime($s['created_at'] ?? 'now')) ?></td>
                <td class="text-center" style="font-weight: bold;">VEN-<?= str_pad($s['id'] ?? 0, 4, '0', STR_PAD_LEFT) ?></td>
                <td style="font-size: 10px;"><?= htmlspecialchars($s['payment_methods'] ?? 'No especificado') ?></td>
                <td class="text-right">$<?= number_format((float)($s['total_cost'] ?? 0), 2) ?></td>
                <td class="text-right" style="color: #65a30d;">$<?= number_format((float)($s['total_profit'] ?? 0), 2) ?></td>
                <td class="text-right font-bold" style="color: #059669;">$<?= number_format((float)($s['total_amount'] ?? 0), 2) ?></td>
            </tr>
            <?php endforeach; else: ?>
            <tr>
                <td colspan="6" class="text-center" style="padding: 20px;">No se encontraron ventas para este período.</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="footer">
        Desarrollado en TuInventario &copy; <?= date('Y') ?>
    </div>

</body>
</html>
