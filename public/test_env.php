<?php
require_once __DIR__ . '/config/config.php';

echo "DATABASE_URL: " . (getenv('DATABASE_URL') ?: 'NO SET') . "\n";
echo "DB_HOST: " . (defined('DB_HOST') ? DB_HOST : 'NO DEFINIDO') . "\n";
echo "DB_USER: " . (defined('DB_USER') ? DB_USER : 'NO DEFINIDO') . "\n";
echo "DB_PASS: " . (defined('DB_PASS') ? DB_PASS : 'NO DEFINIDO') . "\n";
echo "DB_NAME: " . (defined('DB_NAME') ? DB_NAME : 'NO DEFINIDO') . "\n";

$url = getenv('DATABASE_URL');
if ($url) {
    $opts = parse_url($url);
    echo "\nparse_url result:\n";
    print_r($opts);
}
