<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Auditoría</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; color: #333; font-size: 11px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #374151; padding-bottom: 10px; }
        .header h1 { margin: 0; font-size: 24px; color: #111827; }
        .header p { margin: 5px 0 0 0; color: #666; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; page-break-inside: auto; }
        tr { page-break-inside: avoid; page-break-after: auto; }
        thead { display: table-header-group; }
        th, td { border: 1px solid #d1d5db; padding: 8px 6px; text-align: left; }
        th { background-color: #f3f4f6; font-weight: bold; color: #111; text-align: center; text-transform: uppercase; }
        td.text-center { text-align: center; }
        .badge { display: inline-block; padding: 2px 6px; border-radius: 4px; font-weight: bold; font-size: 9px; }
        .badge.create { background: #dcfce7; color: #166534; }
        .badge.update { background: #dbeafe; color: #1e40af; }
        .badge.delete { background: #fee2e2; color: #991b1b; }
        .footer { margin-top: 20px; text-align: right; color: #999; }
        @media print { @page { size: letter; margin: 1cm; } body { margin: 0; padding: 0; } .header { padding-top: 0; } }
    </style>
</head>
<body onload="window.print()">

    <?php 
    $printTitle = "Registro General de Auditoría del Sistema";
    include __DIR__ . '/../../../includes/print_header.php'; 
    ?>

    <table>
        <thead>
            <tr>
                <th style="width: 140px;">Fecha y Hora (UTC)</th>
                <th>Usuario Responsable</th>
                <th>Acción</th>
                <th>Módulo Afectado</th>
                <th>ID Referencia</th>
                <th style="min-width: 250px;">Detalles Crudos (JSON)</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($logs)): foreach ($logs as $log): 
                $action = strtoupper($log['action']);
                $badgeClass = 'badge ';
                if ($action === 'CREATE') $badgeClass .= 'create';
                elseif ($action === 'UPDATE') $badgeClass .= 'update';
                elseif ($action === 'DELETE') $badgeClass .= 'delete';
            ?>
            <tr>
                <td class="text-center font-mono"><?= date('d/m/Y h:i:s A', strtotime($log['created_at'])) ?></td>
                <td style="font-weight: bold;"><?= htmlspecialchars($log['user_name'] ?? 'SISTEMA BASE') ?></td>
                <td class="text-center">
                    <span class="<?= $badgeClass ?>"><?= $action ?></span>
                </td>
                <td class="text-center" style="font-family: monospace; font-size: 10px;"><?= htmlspecialchars($log['table_name']) ?></td>
                <td class="text-center" style="font-weight: bold;">#<?= $log['record_id'] ?></td>
                <td style="font-family: monospace; font-size: 9px; color: #555; word-wrap: break-word; word-break: break-all;">
                    <?= htmlspecialchars(strlen($log['details']) > 150 ? substr($log['details'], 0, 150) . '...' : $log['details']) ?>
                </td>
            </tr>
            <?php endforeach; else: ?>
            <tr>
                <td colspan="6" class="text-center" style="padding: 20px;">No existen logs de auditoría.</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="footer">
        Firma de Auditoría - TuInventario &copy; <?= date('Y') ?>
    </div>

</body>
</html>
