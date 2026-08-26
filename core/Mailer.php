<?php
/**
 * Clase Mailer para el envío de correos electrónicos.
 * Utiliza PHPMailer para entrega SMTP confiable.
 */
class Mailer {
    
    /**
     * Envía un correo electrónico.
     *
     * @param string $to Email del destinatario
     * @param string $subject Asunto del correo
     * @param string $body Cuerpo del mensaje
     * @return bool True si se envió (o simuló correctamente)
     */
    public static function send($to, $subject, $body) {
        $autoload = __DIR__ . '/../vendor/autoload.php';
        if (file_exists($autoload)) {
            require_once $autoload;
        }

        // Si no hay configuración SMTP, hacer simulación en log
        if (!defined('SMTP_HOST') || empty(SMTP_HOST)) {
            return self::simulate($to, $subject, $body, "Falta configuración SMTP (SMTP_HOST vacío).");
        }

        if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            return self::simulate($to, $subject, $body, "Librería PHPMailer no encontrada.");
        }

        $mail = new PHPMailer\PHPMailer\PHPMailer(true);

        try {
            // Configuración del Servidor
            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = SMTP_USER;
            $mail->Password   = SMTP_PASS;
            $mail->SMTPSecure = SMTP_SECURE;
            $mail->Port       = SMTP_PORT;

            // Remitente y Destinatario
            $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
            $mail->addAddress($to);

            // Contenido
            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
            $mail->Subject = $subject;
            $mail->Body    = $body;
            $mail->AltBody = strip_tags($body);

            $mail->send();
            return true;

        } catch (Exception $e) {
            // Falla el envío real, hacer fallback a log
            $errorMsg = "Error SMTP: {$mail->ErrorInfo}";
            return self::simulate($to, $subject, $body, $errorMsg);
        }
    }

    /**
     * Guarda el correo en un log de texto (Fallback).
     */
    private static function simulate($to, $subject, $body, $reason) {
        $logMsg = "[" . date('Y-m-d H:i:s') . "] [Email Simulation - $reason]\n";
        $logMsg .= "To: $to | Subject: $subject\n";
        $logMsg .= "$body\n";
        $logMsg .= "---------------------------------------------------\n";
        
        file_put_contents(__DIR__ . '/../error_log_sales.txt', $logMsg, FILE_APPEND);
        return true; // Retornamos true simulando éxito para no romper el flujo
    }
}
