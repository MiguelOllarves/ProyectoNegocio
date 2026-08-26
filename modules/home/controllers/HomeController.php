<?php
class HomeController extends Controller {
    public function index() {
        // Redirigir al dashboard si ya inició sesión
        if (isset($_SESSION['user_id'])) {
            header('Location: ' . BASE_URL . 'dashboard');
            exit;
        }

        $this->view('modules/home/views/index');
    }
}
