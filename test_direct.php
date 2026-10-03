<?php
$url = 'postgresql://postgres:Maom.18224757..@db.ovhqivrgpjevvmyeznyz.supabase.co:5432/postgres';
$dbOpts = parse_url($url);

$start = microtime(true);
try {
    $dsn = "pgsql:host=" . $dbOpts['host'] . ";port=" . $dbOpts['port'] . ";dbname=" . ltrim($dbOpts['path'], '/');
    $pdo = new PDO($dsn, $dbOpts['user'], $dbOpts['pass']);
    echo 'Connected to direct URL in ' . (microtime(true) - $start) . ' seconds';
} catch (Exception $e) {
    echo 'Failed to connect direct URL in ' . (microtime(true) - $start) . ' seconds: ' . $e->getMessage();
}
