<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Comprobante de Compra #<?= str_pad($purchase['id'], 4, '0', STR_PAD_LEFT) ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; color: #333; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #8b5cf6; padding-bottom: 10px; }
        .header h1 { margin: 0; font-size: 24px; color: #5b21b6; }
        .header p { margin: 5px 0 0 0; color: #666; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; font-size: 11px; margin-top: 20px; margin-bottom: 20px; }
        th, td { border: 1px solid #d1d5db; padding: 8px 6px; text-align: left; vertical-align: middle; }
        th { background-color: #f3f4f6; font-weight: bold; color: #111; text-align: center; text-transform: uppercase; }
        td.text-center { text-align: center; }
        td.text-right { text-align: right; }
        .info-grid { display: flex; justify-content: space-between; margin-bottom: 15px; }
        .info-box { width: 48%; padding: 10px; border: 1px solid #e5e7eb; border-radius: 8px; background: #fafafa; }
        .info-box strong { display: block; margin-bottom: 5px; color: #4b5563; text-transform: uppercase; font-size: 10px; }
        .qr-container { text-align: center; margin-top: 30px; }
        .qr-container img { width: 120px; height: 120px; border: 1px solid #e5e7eb; padding: 5px; border-radius: 8px; }
        .qr-container p { font-size: 10px; color: #666; margin-top: 5px; }
        .footer { margin-top: 20px; font-size: 10px; text-align: center; color: #999; border-top: 1px solid #eee; padding-top: 10px; }
        @media print { @page { size: letter; margin: 1cm; } body { margin: 0; padding: 0; } }
    </style>
</head>
<body onload="window.print()">

    <?php 
    $printTitle = "Comprobante de Ingreso / Compra";
    include __DIR__ . '/../../../includes/print_header.php'; 
    $qrData = "COMPRA:" . str_pad($purchase['id'], 4, '0', STR_PAD_LEFT) . "|PROV:" . ($purchase['supplier_name'] ?? 'N/A') . "|TOTAL:$" . number_format($purchase['total'], 2);
    $qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode($qrData);
    ?>

    <div class="info-grid">
        <div class="info-box">
            <strong>Datos del Documento</strong>
            Nº de Control: <span style="font-family: monospace; font-weight: bold; color: #8b5cf6;">CMP-<?= str_pad($purchase['id'], 4, '0', STR_PAD_LEFT) ?></span><br>
            Fecha: <?= date('d/m/Y h:i A', strtotime($purchase['created_at'])) ?><br>
            Registrado por: <?= htmlspecialchars($purchase['user_name'] ?? 'Usuario del Sistema') ?>
        </div>
        <div class="info-box">
            <strong>Datos del Proveedor</strong>
            Razón Social: <?= htmlspecialchars($purchase['supplier_name'] ?? 'Sin Proveedor Asignado') ?><br>
            Contacto: <?= htmlspecialchars($purchase['supplier_contact'] ?? 'N/A') ?><br>
            Notas: <?= htmlspecialchars($purchase['notes'] ?? 'Ninguna') ?>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 40px;">Item</th>
                <th>Código / SKU</th>
                <th>Descripción del Producto</th>
                <th>Formato</th>
                <th style="width: 80px;">Cantidad</th>
                <th style="width: 100px;">Costo Unit.</th>
                <th style="width: 100px;">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $i = 1;
            if (!empty($purchase['items'])): foreach ($purchase['items'] as $item): 
            ?>
            <tr>
                <td class="text-center"><?= $i++ ?></td>
                <td class="text-center" style="font-family: monospace;"><?= htmlspecialchars($item['sku'] ?? 'S/C') ?></td>
                <td style="font-weight: bold;"><?= htmlspecialchars($item['name']) ?></td>
                <td class="text-center" style="text-transform: capitalize;"><?= htmlspecialchars($item['unit_type'] ?? 'unidad') ?></td>
                <td class="text-center"><?= $item['quantity'] ?></td>
                <td class="text-right">$<?= number_format($item['cost_per_unit'], 2) ?></td>
                <td class="text-right font-bold text-green-700">$<?= number_format($item['quantity'] * $item['cost_per_unit'], 2) ?></td>
            </tr>
            <?php endforeach; else: ?>
            <tr>
                <td colspan="7" class="text-center" style="padding: 20px;">Esta compra no tiene items registrados.</td>
            </tr>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="6" class="text-right" style="font-weight: bold; padding-right: 15px; text-transform: uppercase;">Monto Total de Compra:</td>
                <td class="text-right" style="font-weight: bold; font-size: 14px; background-color: #f3f4f6; border-top: 2px solid #111;">$<?= number_format($purchase['total'], 2) ?></td>
            </tr>
        </tfoot>
    </table>

    <div class="qr-container">
        <img src="<?= $qrUrl ?>" alt="QR Code">
        <p>Escanear para validar información en sistema</p>
    </div>

    <div class="footer">
        Firma del Receptor: _________________________________<br><br>
        Documento generado por TuInventario APP &copy; <?= date('Y') ?>
    </div>

</body>
</html>
