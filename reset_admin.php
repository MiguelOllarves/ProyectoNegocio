<?php
require __DIR__ . '/config/Database.php';
$db = Database::getInstance()->getConnection();
$hash = password_hash('182247576', PASSWORD_DEFAULT);
$db->query("UPDATE users SET password = '$hash' WHERE role = 'super_admin'");
echo "Contraseña reseteada para super_admin a 182247576.";
