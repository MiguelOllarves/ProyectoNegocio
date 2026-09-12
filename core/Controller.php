<?php
class Controller {
    // Función para renderizar vistas y pasarles datos
    public function view(string $view, array $data = []): void {
        // Extrae las variables del array asociativo para que usen su clave como nombre
        extract($data);
        
        $viewPath = __DIR__ . '/../' . $view . '.php';
        if (file_exists($viewPath)) {
            require_once $viewPath;
        } else {
            die("La vista solicitada no existe: " . $view);
        }
    }

    // Función para respuestas de API (por ejemplo para AJAX fetch calls con JSON)
    public function jsonResponse(array $data, int $statusCode = 200): void {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit();
    }
}
