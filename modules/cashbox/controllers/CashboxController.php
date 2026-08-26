<?php
require_once __DIR__ . '/../models/Cashbox.php';

class CashboxController extends Controller {
    private $model;

    public function __construct() {
        $this->model = new Cashbox();
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . BASE_URL . 'auth/login');
            exit;
        }
    }

    /**
     * Vista principal del módulo cashbox
     */
    public function index() {
        $userId = $_SESSION['user_id'];
        $openSession = $this->model->getOpenSession($userId);

        $ventasUsd = 0;
        $ventasBs  = 0;

        if ($openSession) {
            // Sumar ventas desde la apertura
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT COALESCE(SUM(total), 0) as total_usd FROM sales WHERE created_at >= :apertura");
            $stmt->execute(['apertura' => $openSession['fecha_apertura']]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $ventasUsd = $row['total_usd'] ?? 0;

            // Sumar pagos en bolívares desde ventas_pagos
            $stmt2 = $db->prepare("
                SELECT COALESCE(SUM(vp.monto_bs), 0) as total_bs
                FROM ventas_pagos vp
                JOIN sales s ON vp.venta_id = s.id
                WHERE s.created_at >= :apertura
            ");
            $stmt2->execute(['apertura' => $openSession['fecha_apertura']]);
            $row2 = $stmt2->fetch(PDO::FETCH_ASSOC);
            $ventasBs = $row2['total_bs'] ?? 0;
        }

        $esperadoUsd = ($openSession['monto_inicial_usd'] ?? 0) + $ventasUsd;
        $esperadoBs  = ($openSession['monto_inicial_bs'] ?? 0) + $ventasBs;

        $sessions = $this->model->allSessionsWithUsers();

        $this->view('modules/cashbox/views/index', [
            'openSession'  => $openSession,
            'ventasUsd'    => $ventasUsd,
            'ventasBs'     => $ventasBs,
            'esperadoUsd'  => $esperadoUsd,
            'esperadoBs'   => $esperadoBs,
            'sessions'     => $sessions,
        ]);
    }

    /**
     * Abrir caja
     */
    public function open() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $userId    = $_SESSION['user_id'];
            $montoUsd  = floatval($_POST['monto_inicial_usd'] ?? 0);
            $montoBs   = floatval($_POST['monto_inicial_bs'] ?? 0);
            $this->model->openSession($userId, $montoUsd, $montoBs);
        }
        header('Location: ' . BASE_URL . 'cashbox');
        exit;
    }

    /**
     * Cerrar caja
     */
    public function close() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $sessionId    = intval($_POST['session_id']);
            $ventasUsd    = floatval($_POST['ventas_usd'] ?? 0);
            $ventasBs     = floatval($_POST['ventas_bs'] ?? 0);
            $declaradoUsd = floatval($_POST['declarado_usd'] ?? 0);
            $declaradoBs  = floatval($_POST['declarado_bs'] ?? 0);
            $notes        = trim($_POST['notes'] ?? '');

            $this->model->closeSession($sessionId, $ventasUsd, $ventasBs, $declaradoUsd, $declaradoBs, $notes);
        }
        header('Location: ' . BASE_URL . 'cashbox');
        exit;
    }
}
