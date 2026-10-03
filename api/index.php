<?php
// Wrapper para Vercel Serverless
// Vercel requiere que las funciones serverless (PHP) estén bajo el directorio /api.
// Este wrapper enruta todo hacia el index principal del sistema sin alterar local.

$_SERVER['SCRIPT_NAME'] = '/index.php'; 

if (isset($_GET['url']) && $_GET['url'] === 'ping') {
    http_response_code(200);
    echo 'pong';
    exit;
}

require __DIR__ . '/../public/index.php';
