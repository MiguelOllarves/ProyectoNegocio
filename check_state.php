<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/Database.php';
$db = Database::getInstance()->getConnection();

echo "Negocios: " . $db->query("SELECT COUNT(*) FROM businesses")->fetchColumn() . "\n";
echo "Usuarios: " . $db->query("SELECT COUNT(*) FROM users")->fetchColumn() . "\n\n";

echo "=== NEGOCIO id=1 ===\n";
$row = $db->query("SELECT id, business_name, slug, email, created_at FROM businesses WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
print_r($row);

echo "=== USUARIOS con username 00000000 ===\n";
$rows = $db->query("SELECT u.id, u.username, u.full_name, u.role, u.status, u.created_at, u.business_id FROM users u WHERE u.username = '00000000'")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) print_r($r);

echo "\n=== NEGOCIOS DEMO ===\n";
$rows = $db->query("SELECT id, business_name, slug, created_at FROM businesses WHERE LOWER(business_name) LIKE '%demo%' OR LOWER(slug) LIKE '%demo%'")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) echo "  id={$r['id']} | {$r['business_name']} | /{$r['slug']} | {$r['created_at']}\n";