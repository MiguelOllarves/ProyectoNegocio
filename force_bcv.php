<?php
require 'core/Settings.php';
$db = Database::getInstance()->getConnection();
$db->exec('UPDATE settings SET updated_at = \'2000-01-01 00:00:00\' WHERE key = \'bcv_rate\'');
$db->exec('UPDATE settings SET updated_at = \'2000-01-01 00:00:00\' WHERE key = \'bcv_last_attempt\'');
$db->exec('DELETE FROM settings WHERE key = \'bcv_last_attempt\'');

// Let's directly call fetchUrl
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL            => 'https://ve.dolarapi.com/v1/dolares/oficial',
    CURLOPT_RETURNTRANSFER => 1,
    CURLOPT_CONNECTTIMEOUT => 2,
    CURLOPT_TIMEOUT        => 4,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_FOLLOWLOCATION => true,
]);
$resp = curl_exec($ch);
$err = curl_error($ch);
echo "DIRECT CURL: $resp\nERR: $err\n";

echo "Settings get: " . Settings::getBcvRate() . "\n";
