<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/Database.php';

function logMsg($m) { echo $m . "\n"; flush(); }

$db = Database::getInstance()->getConnection();
$tenantId = 1;

try {
    $db->beginTransaction();

    $usersStmt = $db->prepare("SELECT id FROM users WHERE business_id = ?");
    $usersStmt->execute([$tenantId]);
    $userIds = $usersStmt->fetchAll(PDO::FETCH_COLUMN);
    logMsg("Usuarios del negocio: " . implode(',', $userIds));

    foreach ($userIds as $uid) {
        $s = $db->prepare("DELETE FROM sessions WHERE id IN (SELECT active_session_id FROM users WHERE id = ?)");
        $s->execute([$uid]);
        logMsg("  user $uid: sessions OK");

        $s = $db->prepare("DELETE FROM login_sessions WHERE user_id = ?");
        $s->execute([$uid]);
        logMsg("  user $uid: login_sessions OK");

        try { $db->prepare("DELETE FROM push_subscriptions WHERE user_id = ?")->execute([$uid]); logMsg("  user $uid: push_subscriptions OK"); } catch (Exception $e) { logMsg("  user $uid: push_subscriptions SKIP"); }
        try { $db->prepare("DELETE FROM audit_logs WHERE user_id = ?")->execute([$uid]); logMsg("  user $uid: audit_logs OK"); } catch (Exception $e) { logMsg("  user $uid: audit_logs SKIP"); }
        try { $db->prepare("DELETE FROM expenses WHERE user_id = ?")->execute([$uid]); logMsg("  user $uid: expenses OK"); } catch (Exception $e) { logMsg("  user $uid: expenses SKIP"); }
        try { $db->prepare("DELETE FROM arqueo_caja WHERE user_id = ?")->execute([$uid]); logMsg("  user $uid: arqueo_caja OK"); } catch (Exception $e) { logMsg("  user $uid: arqueo_caja SKIP"); }
        try { $db->prepare("DELETE FROM kardex WHERE user_id = ?")->execute([$uid]); logMsg("  user $uid: kardex OK"); } catch (Exception $e) { logMsg("  user $uid: kardex SKIP"); }
        try { $db->prepare("DELETE FROM sale_items WHERE sale_id IN (SELECT id FROM sales WHERE user_id = ?)")->execute([$uid]); logMsg("  user $uid: sale_items OK"); } catch (Exception $e) { logMsg("  user $uid: sale_items SKIP"); }
        try { $db->prepare("DELETE FROM sales WHERE user_id = ?")->execute([$uid]); logMsg("  user $uid: sales OK"); } catch (Exception $e) { logMsg("  user $uid: sales SKIP"); }
        try { $db->prepare("DELETE FROM purchase_items WHERE purchase_id IN (SELECT id FROM purchases WHERE user_id = ?)")->execute([$uid]); logMsg("  user $uid: purchase_items OK"); } catch (Exception $e) { logMsg("  user $uid: purchase_items SKIP"); }
        try { $db->prepare("DELETE FROM purchases WHERE user_id = ?")->execute([$uid]); logMsg("  user $uid: purchases OK"); } catch (Exception $e) { logMsg("  user $uid: purchases SKIP"); }

        $s = $db->prepare("DELETE FROM users WHERE id = ?");
        $s->execute([$uid]);
        logMsg("  user $uid: USER DELETED");
    }

    // Negocio
    try { $db->prepare("DELETE FROM credit_payments WHERE credit_id IN (SELECT id FROM credits WHERE tenant_id = ?)")->execute([$tenantId]); } catch (Exception $e) {}
    try { $db->prepare("DELETE FROM credits WHERE tenant_id = ?")->execute([$tenantId]); } catch (Exception $e) {}
    try { $db->prepare("DELETE FROM store_orders WHERE tenant_id = ?")->execute([$tenantId]); } catch (Exception $e) {}
    try { $db->prepare("DELETE FROM notifications WHERE tenant_id = ?")->execute([$tenantId]); } catch (Exception $e) {}
    try { $db->prepare("DELETE FROM payments WHERE tenant_id = ?")->execute([$tenantId]); } catch (Exception $e) {}
    try { $db->prepare("DELETE FROM recipe_items WHERE tenant_id = ?")->execute([$tenantId]); } catch (Exception $e) {}
    try { $db->prepare("DELETE FROM kardex WHERE product_id IN (SELECT id FROM products WHERE tenant_id = ?)")->execute([$tenantId]); } catch (Exception $e) {}
    try { $db->prepare("DELETE FROM products WHERE tenant_id = ?")->execute([$tenantId]); } catch (Exception $e) {}
    try { $db->prepare("DELETE FROM categories WHERE tenant_id = ?")->execute([$tenantId]); } catch (Exception $e) {}
    try { $db->prepare("DELETE FROM brands WHERE tenant_id = ?")->execute([$tenantId]); } catch (Exception $e) {}
    try { $db->prepare("DELETE FROM suppliers WHERE tenant_id = ?")->execute([$tenantId]); } catch (Exception $e) {}
    try { $db->prepare("DELETE FROM clients WHERE tenant_id = ?")->execute([$tenantId]); } catch (Exception $e) {}
    try { $db->prepare("DELETE FROM store_config WHERE business_id = ?")->execute([$tenantId]); } catch (Exception $e) {}

    $s = $db->prepare("DELETE FROM businesses WHERE id = ?");
    $s->execute([$tenantId]);
    logMsg("Business demo deleted: " . $s->rowCount());

    $db->commit();
    logMsg("COMMIT OK");
} catch (Exception $e) {
    $db->rollBack();
    logMsg("ERROR: " . $e->getMessage());
}