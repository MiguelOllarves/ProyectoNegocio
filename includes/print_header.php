<?php
require_once __DIR__ . '/../config/Database.php'; 
$db = Database::getInstance()->getConnection();
$tenantId = $_SESSION['business_id'] ?? $_SESSION['tenant_id'] ?? 1;

// Obtener datos del negocio desde la tabla businesses
$stmt = $db->prepare("SELECT owner_name, business_name, rif, business_phone, logo_base64 FROM businesses WHERE id = ?");
$stmt->execute([$tenantId]);
$biz = $stmt->fetch(PDO::FETCH_ASSOC);

// Obtener configuración pública de la tienda, por si el usuario personalizó el nombre o logo allí
$stmtCfg = $db->prepare("SELECT store_name, logo_url, whatsapp FROM store_config WHERE business_id = ?");
$stmtCfg->execute([$tenantId]);
$storeCfg = $stmtCfg->fetch(PDO::FETCH_ASSOC) ?: [];

// Lógica de fallback: usar nombre de tienda si existe, sino el business_name global
$storeName = !empty($storeCfg['store_name']) ? trim($storeCfg['store_name']) : '';
$baseBizName = !empty($biz['business_name']) ? trim($biz['business_name']) : 'Empresa';
$bName = (!empty($storeName) && stripos($storeName, 'TuInventario') === false) ? $storeName : $baseBizName;

$bRif = $biz['rif'] ?? '';
$bPhone = !empty($storeCfg['whatsapp']) ? $storeCfg['whatsapp'] : ($biz['business_phone'] ?? '');

$bLogo = '';
if (!empty($storeCfg['logo_url'])) {
    $bLogo = BASE_URL . $storeCfg['logo_url']; // Ej. uploads/logo.png
} elseif (!empty($biz['logo_base64'])) {
    $bLogo = $biz['logo_base64'];
}

$reportTitle = $printTitle ?? 'Documento';
?>
<div class="header" style="text-align: center; margin-bottom: 20px; border-bottom: 2px solid #10b981; padding-bottom: 10px;">
    <table style="width: 100%; border: none;">
        <tr style="border: none;">
            <td style="width: 120px; text-align: left; border: none; vertical-align: middle;">
                <?php if (!empty($bLogo)): ?>
                    <img src="<?= htmlspecialchars($bLogo) ?>" style="max-width: 100px; max-height: 80px; object-fit: contain;">
                <?php endif; ?>
            </td>
            <td style="text-align: center; border: none; vertical-align: middle;">
                <h1 style="margin: 0; font-size: 24px; color: #065f46; font-weight: 900; line-height: 1.2;"><?= htmlspecialchars($bName) ?></h1>
                
                <?php if(!empty($bRif) || !empty($bPhone)): ?>
                <div style="font-size: 11px; margin-top: 4px; color: #4b5563;">
                    <?php if(!empty($bRif)): ?><strong>RIF:</strong> <?= htmlspecialchars($bRif) ?> &nbsp;&nbsp;&nbsp;<?php endif; ?>
                    <?php if(!empty($bPhone)): ?><strong>Tel:</strong> <?= htmlspecialchars($bPhone) ?><?php endif; ?>
                </div>
                <?php endif; ?>

                <h2 style="margin: 12px 0 0 0; font-size: 15px; color: #374151; font-weight: bold;"><?= htmlspecialchars($reportTitle) ?></h2>
                <div style="margin: 4px 0 0 0; color: #6b7280; font-size: 10px;">
                    Generado el <?= date('d/m/Y h:i A') ?> | Usuario: <?= htmlspecialchars($_SESSION['username'] ?? 'Sistema') ?>
                </div>
            </td>
            <td style="width: 120px; border: none;"><!-- Espaciador para centrar texto real --></td>
        </tr>
    </table>
</div>
