<?php
require 'config/config.php';
require 'config/Database.php';

$start = microtime(true);
try {
    $db = Database::getInstance()->getConnection();
    echo 'Connected in ' . (microtime(true) - $start) . ' seconds';
} catch (Exception $e) {
    echo 'Failed in ' . (microtime(true) - $start) . ' seconds: ' . $e->getMessage();
}
