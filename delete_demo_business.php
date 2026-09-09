<?php
// ELIMINACIÓN DEFINITIVA del negocio demo "Negocio Demo Luis" + usuario #2
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/Database.php';

$db = Database::getInstance()->getConnection();
$tenantId = 1;

try {
    $db->beginTransaction();

    // Buscar todos los usuarios del negocio
    $usersStmt = $db->prepare("SELECT id FROM users WHERE business_id = ?");
    $usersStmt->execute([$tenantId]);
    $userIds = $usersStmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Usuarios del negocio: " . implode(', ', $userIds) . "\n";

    // 1. Sesiones de esos usuarios
    foreach ($userIds as $uid) {
        $db->prepare("DELETE FROM sessions WHERE id IN (SELECT active_session_id FROM users WHERE id = ?)")->execute([$uid]);
        $db->prepare("DELETE FROM login_sessions WHERE user_id = ?")->execute([$uid]);
        try { $db->prepare("DELETE FROM push_subscriptions WHERE user_id = ?")->execute([$uid]); } catch (Exception $e) {}
        try { $db->prepare("DELETE FROM audit_logs WHERE user_id = ?")->execute([$uid]); } catch (Exception $e) {}
        echo "  - Sesiones del usuario $uid limpias\n";
    }

    // 2. Créditos y pagos de crédito
    $db->prepare("DELETE FROM credit_payments WHERE credit_id IN (SELECT id FROM credits WHERE tenant_id = ?)")->execute([$tenantId]);
    $db->prepare("DELETE FROM credits WHERE tenant_id = ?")->execute([$tenantId]);

    // 3. Arqueos
    foreach ($userIds as $uid) {
        $db->prepare("DELETE FROM arqueo_caja WHERE user_id = ?")->execute([$uid]);
    }

    // 4. Kardex por productos
    $db->prepare("DELETE FROM kardex WHERE product_id IN (SELECT id FROM products WHERE tenant_id = ?)")->execute([$tenantId]);
    // Kardex por usuario
    foreach ($userIds as $uid) {
        $db->prepare("DELETE FROM kardex WHERE user_id = ?")->execute([$uid]);
    }

    // 5. Detalles de compras y ventas
    $db->prepare("DELETE FROM purchase_items WHERE purchase_id IN (SELECT id FROM purchases WHERE user_id IN (SELECT id FROM users WHERE business_id = ?))")->execute([$tenantId]);
    $db->prepare("DELETE FROM purchases WHERE user_id IN (SELECT id FROM users WHERE business_id = ?)")->execute([$tenantId]);
    $db->prepare("DELETE FROM sale_items WHERE sale_id IN (SELECT id FROM sales WHERE user_id IN (SELECT id FROM users WHERE business_id = ?))")->execute([$tenantId]);
    $db->prepare("DELETE FROM sales WHERE user_id IN (SELECT id FROM users WHERE business_id = ?)")->execute([$tenantId]);

    // 6. Gastos
    foreach ($userIds as $uid) {
        $db->prepare("DELETE FROM expenses WHERE user_id = ?")->execute([$uid]);
    }

    // 7. Pedidos de tienda y notificaciones
    $db->prepare("DELETE FROM store_orders WHERE tenant_id = ?")->execute([$tenantId]);
    $db->prepare("DELETE FROM notifications WHERE tenant_id = ?")->execute([$tenantId]);

    // 8. Pagos
    try { $db->prepare("DELETE FROM payments WHERE tenant_id = ?")->execute([$tenantId]); } catch (Exception $e) {}

    // 9. Shopping de recetas
    try { $db->prepare("DELETE FROM recipe_items WHERE tenant_id = ?")->execute([$tenantId]); } catch (Exception $e) {}

    // 10. Categorías, marcas, productos
    $db->prepare("DELETE FROM products WHERE tenant_id = ?")->execute([$tenantId]);
    $db->prepare("DELETE FROM categories WHERE tenant_id = ?")->execute([$tenantId]);
    $db->prepare("DELETE FROM brands WHERE tenant_id = ?")->execute([$tenantId]);
    $db->prepare("DELETE FROM suppliers WHERE tenant_id = ?")->execute([$tenantId]);
    $db->prepare("DELETE FROM clients WHERE tenant_id = ?")->execute([$tenantId]);

    // 11. Configuración de tienda
    try { $db->prepare("DELETE FROM store_config WHERE business_id = ?")->execute([$tenantId]); } catch (Exception $e) {}

    // 12. Product presentations (vía FK cascade usualmente)
    try {
        $db->prepare("DELETE FROM product_presentations WHERE product_id NOT IN (SELECT id FROM products)")->execute();
    } catch (Exception $e) {}

    // 13. Los usuarios
    foreach ($userIds as $uid) {
        $db->prepare("DELETE FROM users WHERE id = ?")->execute([$uid]);
        echo "  - Usuario $uid eliminado ✓\n";
    }

    // 14. El negocio
    $db->prepare("DELETE FROM businesses WHERE id = ?")->execute([$tenantId]);
    echo "Negocio 'Negocio Demo Luis' (id 1) ELIMINADO ✓\n";

    $db->commit();
    echo "\n=== NEGOCIO DEMO ELIMINADO COMPLETAMENTE ===\n";

} catch (Exception $e) {
    $db->rollBack();
    echo "ERROR: " . $e->getMessage() . "\n";
}