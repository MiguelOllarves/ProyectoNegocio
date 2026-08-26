<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Clientes</title>
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
            border-bottom: 2px solid #2563eb;
            padding-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            color: #1e3a8a;
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
    $printTitle = "Directorio de Clientes";
    include __DIR__ . '/../../../includes/print_header.php'; 
    ?>

    <table>
        <thead>
            <tr>
                <th style="width: 30px;">#</th>
                <th>Nombre del Cliente</th>
                <th>Cédula / RIF</th>
                <th>Teléfono</th>
                <th>Correo Electrónico</th>
                <th>Dirección</th>
                <th>Lugar de Trabajo</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($clients)): $i = 1; foreach ($clients as $c): ?>
            <tr>
                <td class="text-center font-bold text-gray-500"><?= $i++ ?></td>
                <td style="font-weight: bold;">
                    <?= htmlspecialchars($c['name']) ?>
                </td>
                <td class="text-center font-mono">
                    <?= !empty($c['document']) ? htmlspecialchars($c['document']) : 'N/A' ?>
                </td>
                <td class="text-center">
                    <?= !empty($c['phone']) ? htmlspecialchars($c['phone']) : 'N/A' ?>
                </td>
                <td class="text-center">
                    <?= !empty($c['email']) ? htmlspecialchars($c['email']) : 'N/A' ?>
                </td>
                <td>
                    <?= !empty($c['address']) ? htmlspecialchars((strlen($c['address']) > 40 ? substr($c['address'],0,40).'...' : $c['address'])) : 'N/A' ?>
                </td>
                <td style="font-size:10px; color:#555;">
                    <?= !empty($c['workplace']) ? htmlspecialchars($c['workplace']) : 'Independiente' ?>
                </td>
            </tr>
            <?php endforeach; else: ?>
            <tr>
                <td colspan="7" class="text-center" style="padding: 20px;">No hay clientes registrados.</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="footer">
        Desarrollado en TuInventario &copy; <?= date('Y') ?>
    </div>

</body>
</html>
