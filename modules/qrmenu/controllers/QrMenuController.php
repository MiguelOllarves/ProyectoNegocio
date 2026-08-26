<?php

class QrMenuController extends Controller {

    private function getDb() {
        return Database::getInstance()->getConnection();
    }

    public function index() {
        $this->view('modules/qrmenu/views/index');
    }

    public function api_create() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
        $db = $this->getDb();

        $base64 = $_POST['menu_file_base64'] ?? '';
        if (empty($base64)) {
            $this->jsonResponse(['success' => false, 'message' => 'Ningún archivo subido.'], 400);
            return;
        }
        if (strlen($base64) > 8000000) {
            $this->jsonResponse(['success' => false, 'message' => 'El archivo supera el tamaño máximo de 5MB.'], 400);
            return;
        }

        $parts = explode(';', $base64);
        $mime = count($parts) > 1 ? str_replace('data:', '', $parts[0]) : 'application/pdf';

        // Generate unique SLUG
        $slug = substr(md5(uniqid(rand(), true)), 0, 8); // 8 chars slug
        // Generate random 4-digit code
        $code = str_pad((string)rand(0, 9999), 4, '0', STR_PAD_LEFT);

        try {
            $stmt = $db->prepare("INSERT INTO free_qr_menus (slug, edit_code, menu_base64, menu_type) VALUES (?, ?, ?, ?)");
            $stmt->execute([$slug, $code, $base64, $mime]);
            
            $this->jsonResponse([
                'success' => true, 
                'slug' => $slug, 
                'code' => $code,
                'qr_url' => rtrim(BASE_URL, '/') . '/qrmenu/show/' . $slug,
                'edit_url' => rtrim(BASE_URL, '/') . '/qrmenu/manage/' . $slug . '?code=' . $code
            ]);
        } catch(PDOException $e) {
            $this->jsonResponse(['success' => false, 'message' => 'Error de servidor: ' . $e->getMessage()], 500);
        }
    }

    public function api_update() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
        $db = $this->getDb();

        $slug = $_POST['slug'] ?? '';
        $code = $_POST['code'] ?? '';
        $base64 = $_POST['menu_file_base64'] ?? '';

        if (empty($slug) || empty($code) || empty($base64)) {
            $this->jsonResponse(['success' => false, 'message' => 'Datos incompletos.'], 400);
            return;
        }

        if (strlen($base64) > 8000000) {
            $this->jsonResponse(['success' => false, 'message' => 'El archivo supera el tamaño máximo de 5MB.'], 400);
            return;
        }
        
        $parts = explode(';', $base64);
        $mime = count($parts) > 1 ? str_replace('data:', '', $parts[0]) : 'application/pdf';

        try {
            // Verify code
            $stmt = $db->prepare("SELECT edit_code FROM free_qr_menus WHERE slug = ?");
            $stmt->execute([$slug]);
            $dbCode = $stmt->fetchColumn();

            if($dbCode !== $code) {
                $this->jsonResponse(['success' => false, 'message' => 'Código de edición incorrecto.'], 403);
                return;
            }

            // Update
            $update = $db->prepare("UPDATE free_qr_menus SET menu_base64 = ?, menu_type = ? WHERE slug = ?");
            $update->execute([$base64, $mime, $slug]);
            
            $this->jsonResponse(['success' => true, 'message' => 'Menú actualizado correctamente.']);
        } catch(PDOException $e) {
            $this->jsonResponse(['success' => false, 'message' => 'Error de servidor.'], 500);
        }
    }

    public function manage($slug = null) {
        if(!$slug) {
            header('Location: ' . BASE_URL . 'qrmenu');
            exit;
        }
        $code = $_GET['code'] ?? '';
        
        $db = $this->getDb();
        $stmt = $db->prepare("SELECT slug, edit_code FROM free_qr_menus WHERE slug = ?");
        $stmt->execute([$slug]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if(!$row) {
            die("Menú no encontrado.");
        }

        // Si entran sin el código, mostrar formulario de PIN
        if(empty($code) || $row['edit_code'] !== $code) {
            $this->view('modules/qrmenu/views/pin', ['slug' => $slug]);
            return;
        }

        $this->view('modules/qrmenu/views/manage', [
            'slug' => $slug,
            'code' => $code,
            'qr_url' => rtrim(BASE_URL, '/') . '/qrmenu/show/' . $slug
        ]);
    }

    public function show($slug = null) {
        if(!$slug) {
            http_response_code(404);
            die("QR no válido");
        }

        $db = $this->getDb();
        $stmt = $db->prepare("SELECT menu_base64, menu_type FROM free_qr_menus WHERE slug = ?");
        $stmt->execute([$slug]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row && !empty($row['menu_base64'])) {
            $base64 = $row['menu_base64'];
            $parts = explode(';', $base64);
            if(count($parts) > 1) {
                $base64 = explode(',', $parts[1])[1] ?? '';
            }
            $fileData = base64_decode($base64);
            $mime = $row['menu_type'] ?: 'application/pdf';

            // Very important: don't cache, we want them to get the newest file when reopening!
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            header("Content-Type: $mime");
            echo $fileData;
            exit;
        }

        // Not found
        header('Content-Type: text/html; charset=utf-8');
        echo "<div style='font-family:sans-serif; text-align:center; padding:50px; background:#f4f4f5; height:100vh; display:flex; align-items:center; justify-content:center;'><div><h2 style='color:#3f3f46; margin-bottom:10px;'>Menú no encontrado</h2><p style='color:#71717a;'>Este menú no existe o el identificador es incorrecto.</p></div></div>";
        exit;
    }
}
