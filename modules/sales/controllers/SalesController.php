<?php
require_once __DIR__ . '/../models/Sale.php';
require_once __DIR__ . '/../../inventory/models/Product.php';

class SalesController extends Controller {
    private $saleModel;
    private $productModel;

    public function __construct() {
        $this->saleModel = new Sale();
        $this->productModel = new Product();
    }

    public function index() {
        $products = $this->productModel->allWithCategoriesAndBrands();
        
        // Leer TODAS las configuraciones fiscales desde la BD (no hardcoded)
        $bcvRate    = (float) Settings::get('bcv_rate', 622.21);
        $eurRate    = (float) Settings::get('eur_rate', 670.50);
        $ivaRate    = (float) Settings::get('tax_iva', 16);     // Porcentaje: 16
        $igtfRate   = (float) Settings::get('tax_igtf', 3);     // Porcentaje: 3
        $ivaMethod  = Settings::get('iva_method', 'included');   // 'included' o 'add_later'

        // Leer métodos de pago activos desde la BD
        $db = Database::getInstance()->getConnection();
        $pmStmt = $db->query("SELECT * FROM payment_methods WHERE is_active = true ORDER BY id");
        $paymentMethods = $pmStmt->fetchAll(PDO::FETCH_ASSOC);

        $this->view('modules/sales/views/index', [
            'products'        => $products,
            'bcvRate'         => $bcvRate,
            'eurRate'         => $eurRate,
            'ivaRate'         => $ivaRate,
            'igtfRate'        => $igtfRate,
            'ivaMethod'       => $ivaMethod,
            'paymentMethods'  => $paymentMethods,
        ]);
    }

    public function process() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $data = json_decode(file_get_contents('php://input'), true);

                if (!empty($data['items']) && isset($data['totals'])) {
                    $userId = $_SESSION['user_id'] ?? 1; 
                    
                    $totals   = $data['totals'];
                    $payments = $data['payments'] ?? [];

                    // [SECURITY FIX] Recalcular totales desde Backend
                    $ivaRate    = (float) Settings::get('tax_iva', 16);
                    $ivaMethod  = Settings::get('iva_method', 'included');
                    $baseTotal = 0;
                    $calculatedIva = 0;

                    foreach ($data['items'] as &$item) {
                        $prod = $this->productModel->find($item['id']);
                        if ($prod) {
                            $item['price'] = (float)$prod['price']; // Override frontend price!
                            $qty = (float)($item['quantity'] ?? $item['qty'] ?? 1);
                            $lineTotal = $item['price'] * $qty;
                            
                            if ($ivaMethod === 'add_later' && empty($prod['is_tax_exempt'])) {
                                $baseTotal += $lineTotal;
                                $calculatedIva += $lineTotal * ($ivaRate / 100);
                            } else if ($ivaMethod === 'included' && empty($prod['is_tax_exempt'])) {
                                $bPrice = $item['price'] / (1 + ($ivaRate / 100));
                                $baseTotal += $bPrice * $qty;
                                $calculatedIva += ($item['price'] - $bPrice) * $qty;
                            } else {
                                $baseTotal += $lineTotal;
                            }
                        }
                    }

                    $totals['subtotalUsd'] = $baseTotal;
                    $totals['ivaUsd'] = $calculatedIva;
                    // IGTF logic depends on payments. Trust frontend IGTF but bounded to theoretical limit.
                    $maxIgtf = ($baseTotal + $calculatedIva) * 0.03;
                    $totals['igtfUsd'] = min((float)($totals['igtfUsd'] ?? 0), $maxIgtf);
                    $totals['totalUsd'] = $baseTotal + $calculatedIva + $totals['igtfUsd'];

                    $saleId = $this->saleModel->createSale(
                        $userId,
                        $totals['subtotalUsd'],
                        $totals['ivaUsd'],
                        $totals['igtfUsd'],
                        $totals['totalUsd'],
                        $totals['paidUsd'] ?? 0,
                        $totals['changeUsd'] ?? 0,
                        $data['items'],
                        $payments
                    );
                    
                    if ($saleId) {
                        // Enviar correo al administrador
                        try {
                            require_once __DIR__ . '/../../../config/Database.php';
                            require_once __DIR__ . '/../../../core/Mailer.php';
                            require_once __DIR__ . '/../../../core/EmailTemplates.php';
                            
                            $db = Database::getInstance()->getConnection();
                            $stmtAdmin = $db->prepare("SELECT email FROM businesses WHERE id = ?");
                            $stmtAdmin->execute([$_SESSION['business_id'] ?? 0]);
                            $adminData = $stmtAdmin->fetch();
                            
                            if ($adminData && !empty($adminData['email'])) {
                                $loginUrl = BASE_URL . "dashboard/ventas/historial";
                                $emailHtml = EmailTemplates::getSaleEmail($saleId, $totals['totalUsd'] ?? 0, $loginUrl);
                                Mailer::send($adminData['email'], "Nueva Venta Procesada - #" . $saleId, $emailHtml);
                            }
                        } catch (\Exception $e) {
                            error_log("Error enviando email de venta: " . $e->getMessage());
                        }

                        $this->jsonResponse(['success' => true, 'sale_id' => $saleId]);
                    } else {
                        // Let's get the last DB error if possible
                        $dbError = $this->saleModel->getDbError();
                        $this->jsonResponse(['success' => false, 'message' => 'Error en base de datos', 'db_error' => $dbError], 500);
                    }
                } else {
                    $this->jsonResponse(['success' => false, 'message' => 'Datos inválidos o carrito vacío'], 400);
                }
            } catch (\Throwable $th) {
                error_log($th->getMessage() . "\n" . $th->getTraceAsString());
                $this->jsonResponse(['success' => false, 'message' => $th->getMessage(), 'file' => $th->getFile(), 'line' => $th->getLine()], 500);
            }
        }
    }

    public function receipt($id) {
        $this->view('modules/sales/views/receipt', ['sale_id' => $id]);
    }

    public function history() {
        $business_id = $_SESSION['business_id'] ?? 1;
        $sales = $this->saleModel->getDailySales($business_id);
        
        $this->view('modules/sales/views/history', [
            'sales' => $sales
        ]);
    }

    public function voidSale($id) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $userId = $_SESSION['user_id'] ?? 1;
                $this->saleModel->voidSale($id, $userId);
                $this->jsonResponse(['success' => true, 'message' => 'Venta anulada correctamente. El inventario ha sido restaurado.']);
            } catch (\Exception $e) {
                $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
            }
        }
    }
}
