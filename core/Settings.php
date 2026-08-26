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
     * - Cache estático por request (1 sola consulta).
     * - Si la API falla, se marca el intento y no se vuelve a intentar por 15 minutos
     *   (evita bloquear cada página con curl lento).
     */
    private static $bcvCache = null;

    public static function getBcvRate() {
        if (self::$bcvCache !== null) return self::$bcvCache;

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT value, updated_at FROM settings WHERE key = 'bcv_rate'");
        $stmt->execute();
        $row = $stmt->fetch();

        $autoUpdate = self::get('bcv_auto_update', '1');
        
        $shouldUpdate = false;
        $currentRate = 36.5; // Tasa por defecto de respaldo

        if ($row) {
            $currentRate = (float) $row['value'];
            $lastUpdated = strtotime($row['updated_at']);
            // Si pasaron más de 2 horas y auto update está activo, o si la tasa está en 0
            if ($autoUpdate === '1' && ( (time() - $lastUpdated) > (2 * 3600) || $currentRate <= 0.1) ) {
                $shouldUpdate = true;
            }
        } else if ($autoUpdate === '1') {
            $shouldUpdate = true;
        }

        // Throttle: si un intento falló hace menos de 15 minutos, no reintentar
        // (evita que una API caída congele TODAS las páginas del sistema)
        if ($shouldUpdate) {
            $lastAttempt = strtotime(self::get('bcv_last_attempt', '2000-01-01 00:00:00'));
            if ((time() - $lastAttempt) < (15 * 60)) {
                $shouldUpdate = false;
            }
        }

        if ($shouldUpdate) {
            self::set('bcv_last_attempt', date('Y-m-d H:i:s'));
            try {
                // API 1: DolarApi.com (Extremadamente rápida y estable)
                $resp = self::fetchUrl('https://ve.dolarapi.com/v1/dolares/oficial');

                $updated = false;
                if ($resp) {
                    $data = json_decode($resp, true);
                    if (isset($data['promedio'])) {
                        $currentRate = (float) $data['promedio'];
                        $updated = true;
                    }
                } 
                
                if (!$updated) {
                    // Endpoint alternativo de PydolarVenezuela
                    $resp = self::fetchUrl('https://pydolarvenezuela-api.vercel.app/api/v1/dollar/page?page=bcv');
                    
                    if ($resp) {
                        $data = json_decode($resp, true);
                        if (isset($data['monitors']['usd']['price'])) {
                            $currentRate = (float) $data['monitors']['usd']['price'];
                            $updated = true;
                        }
                    }
                }

                if ($updated && $currentRate > 0) {
                    self::set('bcv_rate', $currentRate);
                    self::set('bcv_last_auto', date('Y-m-d H:i:s'));
                    // Intento exitoso: limpiar el marcador de fallo
                    self::set('bcv_last_attempt', '2000-01-01 00:00:00');
                }
            } catch (\Exception $e) {
                // Falla silenciosa, retorna la tasa guardada anterior.
            }
        }

        $currentRate = round($currentRate, 2);
        self::$bcvCache = $currentRate;
        return $currentRate;
    }

    /**
     * Curl rápido y no-bloqueante para consultar tasas.
     */
    private static function fetchUrl($url) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_CONNECTTIMEOUT => 2, // máximo 2s conectando
            CURLOPT_TIMEOUT        => 4, // máximo 4s totales
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_FOLLOWLOCATION => true,
        ]);
        $resp = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return ($httpCode == 200) ? $resp : false;
    }
}
