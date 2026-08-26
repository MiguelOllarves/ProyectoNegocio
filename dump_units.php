<?php
require_once __DIR__ . '/core/Model.php';

$m = new Model();
$stmt = $m->db->query("SELECT id, name, base_type FROM units_of_measure");
$units = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($units);
