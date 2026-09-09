<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/Database.php';
$db = Database::getInstance()->getConnection();
$rows = $db->query("SELECT pid, state, LEFT(query, 100) as query, now()-query_start AS duration FROM pg_stat_activity WHERE datname = current_database() AND state != 'idle'")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $q) {
    echo $q['state'] . " | " . $q['query'] . " | " . $q['duration'] . "\n";
}
if (!$rows) echo "Sin consultas activas (idle)\n";