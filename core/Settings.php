<?php
require_once __DIR__ . '/../config/Database.php';

class Settings {
    public static function get($key, $default = null) {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT value FROM settings WHERE key = ?");
        $stmt->execute([$key]);
        $val = $stmt->fetchColumn();
        return $val !== false ? $val : $default;
    }

    public static function set($key, $value) {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("INSERT INTO settings (key, value) VALUES (?, ?) ON CONFLICT(key) DO UPDATE SET value = excluded.value, updated_at = CURRENT_TIMESTAMP");
        // Nota: ON CONFLICT solo funciona en SQLite desde 3.24.0. Usaremos un fallback en caso de error.
        try {
            $stmt->execute([$key, $value]);
        } catch (\PDOException $e) {
            // Fallback para SQLite viejo o PDO distinto si no soporta upsert
            $stmt = $db->prepare("SELECT 1 FROM settings WHERE key = ?");
            $stmt->execute([$key]);
            if ($stmt->fetchColumn()) {
                $db->prepare("UPDATE settings SET value = ?, updated_at = CURRENT_TIMESTAMP WHERE key = ?")->execute([$value, $key]);
            } else {
                $db->prepare("INSERT INTO settings (key, value) VALUES (?, ?)")->execute([$key, $value]);
            }
        }
    }

    /**
     * Obtiene la tasa BCV actual. Si han pasado más de 12 horas, intenta actualizarla desde la API.
     */
    public static function getBcvRate() {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT value, updated_at FROM settings WHERE key = 'bcv_rate'");
        $stmt->execute();
        $row = $stmt->fetch();

        $shouldUpdate = true;
        $currentRate = 36.5; // Tasa por defecto de respaldo

        if ($row) {
            $currentRate = (float) $row['value'];
            $lastUpdated = strtotime($row['updated_at']);
            // Si pasaron menos de 12 horas, no actualizamos
            if ((time() - $lastUpdated) < (12 * 3600)) {
                $shouldUpdate = false;
            }
        }

        if ($shouldUpdate) {
            // Intentar actualizar la tasa desde una API pública (ejemplo: api.exchangedir.com o una genérica)
            try {
                // En producción deberías usar curl con timeout para que no trabe el sistema
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, 'https://ve.dolarapi.com/v1/dolares/bcv');
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_TIMEOUT, 5);
                $resp = curl_exec($ch);
                curl_close($ch);

                if ($resp) {
                    $data = json_decode($resp, true);
                    if (isset($data['promedio'])) {
                        $currentRate = (float) $data['promedio'];
                        self::set('bcv_rate', $currentRate);
                    }
                }
            } catch (\Exception $e) {
                // Falla silenciosa, retorna la tasa guardada anterior
            }
        }

        return $currentRate;
    }
}
