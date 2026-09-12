<?php
/**
 * ImageValidator - Servicio de validación y sanitización de imágenes
 * Blindaje contra imágenes maliciosas o infectadas
 */
class ImageValidator {

    // MIME types permitidos (reales, no declarados)
    private static $allowedMimes = [
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/gif',
    ];

    // MIME types permitidos para archivos (PDF)
    private static $allowedFileMimes = [
        'application/pdf',
    ];

    // Tamaño máximo para imágenes (3MB)
    const MAX_IMAGE_SIZE = 3000000;

    // Tamaño máximo para archivos (6MB)
    const MAX_FILE_SIZE = 6000000;

    // Tamaño máximo de un tag base64 en BD (protección contra payload gigante)
    const MAX_BASE64_DB_SIZE = 4500000;

    /**
     * Valida y sanitiza una imagen en base64 (logo, producto, etc)
     * Retorna ['valid' => true, 'clean_base64' => '...'] o ['valid' => false, 'error' => '...']
     */
    public static function validateImage(string $base64, string $context = 'image'): array {
        if (empty($base64)) {
            return ['valid' => false, 'error' => 'No se proporcionó imagen.'];
        }

        // 1. Verificar formato del data URI
        if (!preg_match('/^data:image\/(jpeg|png|webp|gif);base64,/', $base64, $matches)) {
            return ['valid' => false, 'error' => 'Formato de imagen no permitido. Solo se aceptan JPG, PNG, WEBP y GIF.'];
        }

        $declaredMime = 'image/' . $matches[1];

        // 2. Verificar tamaño del string base64
        if (strlen($base64) > self::MAX_BASE64_DB_SIZE) {
            return ['valid' => false, 'error' => 'La imagen es demasiado grande para almacenar. Máximo 3MB.'];
        }

        // 3. Extraer y decodificar los datos
        $parts = explode(';', $base64, 2);
        if (count($parts) !== 2) {
            return ['valid' => false, 'error' => 'Formato base64 inválido.'];
        }

        $dataParts = explode(',', $parts[1], 2);
        if (count($dataParts) !== 2) {
            return ['valid' => false, 'error' => 'Datos de imagen corruptos.'];
        }

        $rawData = base64_decode($dataParts[1], true);
        if ($rawData === false) {
            return ['valid' => false, 'error' => 'No se pudo decodificar la imagen.'];
        }

        // 4. Verificar tamaño real del archivo decodificado
        if (strlen($rawData) > self::MAX_IMAGE_SIZE) {
            return ['valid' => false, 'error' => 'La imagen excede el tamaño máximo de 3MB.'];
        }

        // 5. VALIDACIÓN CRÍTICA: Verificar MIME type real leyendo los bytes del archivo
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $realMime = $finfo->buffer($rawData);

        if (!in_array($realMime, self::$allowedMimes)) {
            // Detectar si es un archivo disfrazado (PHP, HTML, JS, etc)
            $dangerousTypes = ['text/html', 'text/php', 'application/x-httpd-php', 'text/javascript', 'application/javascript'];
            if (in_array($realMime, $dangerousTypes)) {
                return ['valid' => false, 'error' => 'ALERTA DE SEGURIDAD: El archivo contiene código peligroso y ha sido bloqueado.'];
            }
            return ['valid' => false, 'error' => 'El tipo real del archivo no coincide con una imagen válida. Detectado: ' . $realMime];
        }

        // 6. Verificar magic bytes (headers del archivo)
        $magicValid = self::validateMagicBytes($rawData, $realMime);
        if (!$magicValid) {
            return ['valid' => false, 'error' => 'Los datos del archivo están corruptos o son sospechosos.'];
        }

        // 7. Buscar patrones maliciosos embebidos
        if (self::containsMaliciousPatterns($rawData)) {
            return ['valid' => false, 'error' => 'Se detectó contenido malicioso en la imagen y ha sido bloqueada.'];
        }

        // 8. Re-construir el base64 limpio con el MIME real verificado
        $cleanBase64 = 'data:' . $realMime . ';base64,' . base64_encode($rawData);

        return [
            'valid' => true,
            'clean_base64' => $cleanBase64,
            'mime' => $realMime,
            'size' => strlen($rawData),
        ];
    }

    /**
     * Valida un archivo (PDF para menús)
     */
    public static function validateFile(string $base64, string $context = 'file'): array {
        if (empty($base64)) {
            return ['valid' => false, 'error' => 'No se proporcionó archivo.'];
        }

        // Verificar tamaño
        if (strlen($base64) > self::MAX_BASE64_DB_SIZE) {
            return ['valid' => false, 'error' => 'El archivo es demasiado grande. Máximo 6MB.'];
        }

        // Extraer datos
        $parts = explode(';', $base64, 2);
        $dataParts = explode(',', $parts[1] ?? '', 2);
        $rawData = base64_decode($dataParts[1] ?? '', true);

        if ($rawData === false || strlen($rawData) > self::MAX_FILE_SIZE) {
            return ['valid' => false, 'error' => 'Archivo demasiado grande o corrupto.'];
        }

        // Verificar MIME real
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $realMime = $finfo->buffer($rawData);

        if (!in_array($realMime, self::$allowedFileMimes)) {
            return ['valid' => false, 'error' => 'Solo se permiten archivos PDF. Detectado: ' . $realMime];
        }

        // Verificar magic bytes PDF: %PDF
        if (substr($rawData, 0, 4) !== '%PDF') {
            return ['valid' => false, 'error' => 'El archivo no es un PDF válido.'];
        }

        // Buscar patrones maliciosos en PDF
        if (self::containsMaliciousPatterns($rawData)) {
            return ['valid' => false, 'error' => 'Se detectó contenido sospechoso en el PDF.'];
        }

        return [
            'valid' => true,
            'clean_base64' => 'data:application/pdf;base64,' . base64_encode($rawData),
            'mime' => 'application/pdf',
            'size' => strlen($rawData),
        ];
    }

    /**
     * Verifica magic bytes según el MIME type
     */
    private static function validateMagicBytes(string $data, string $mime): bool {
        $header = substr($data, 0, 8);

        switch ($mime) {
            case 'image/jpeg':
                // JPEG: FF D8 FF
                return substr($header, 0, 3) === "\xFF\xD8\xFF";

            case 'image/png':
                // PNG: 89 50 4E 47 0D 0A 1A 0A
                return substr($header, 0, 8) === "\x89PNG\r\n\x1A\n";

            case 'image/gif':
                // GIF: 47 49 46 38 (GIF8)
                return substr($header, 0, 4) === 'GIF8';

            case 'image/webp':
                // WebP: RIFF....WEBP
                return substr($header, 0, 4) === 'RIFF' && substr($data, 8, 4) === 'WEBP';

            default:
                return false;
        }
    }

    /**
     * Busca patrones maliciosos conocidos en archivos binarios
     */
    private static function containsMaliciousPatterns(string $data): bool {
        // Patrones de código peligroso que podrían estar embebidos
        $patterns = [
            // PHP tags
            '/<\?php/i',
            '/<\?=/i',
            '/<\?[^x]/i',  // Short tags <?
            // Script injection
            '/<script/i',
            '/javascript:/i',
            '/vbscript:/i',
            // HTML injection
            '/<iframe/i',
            '/<object/i',
            '/<embed/i',
            '/<applet/i',
            '/on(load|error|click|mouse)/i',
            // Server-side includes
            '/<!--\s*#(exec|include|echo|config)/i'
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $data)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Limpia y re-s comprime una imagen existente para eliminar metadatos EXIF peligrosos
     * Útil para migrar imágenes existentes en la BD
     */
    public static function sanitizeExistingImage(string $base64): ?string {
        if (empty($base64) || !preg_match('/^data:image\/[^;]+;base64,/', $base64)) {
            return null;
        }

        $parts = explode(';', $base64, 2);
        $dataParts = explode(',', $parts[1] ?? '', 2);
        $rawData = base64_decode($dataParts[1] ?? '', true);

        if ($rawData === false) return null;

        // Verificar que sea imagen real
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $realMime = $finfo->buffer($rawData);

        if (!in_array($realMime, self::$allowedMimes)) return null;

        // Para JPG/PNG: re-construir con GD elimina EXIF y metadatos ocultos
        $image = null;
        switch ($realMime) {
            case 'image/jpeg':
                $image = @imagecreatefromstring($rawData);
                if ($image) {
                    ob_start();
                    imagejpeg($image, null, 85);
                    $cleanData = ob_get_clean();
                    imagedestroy($image);
                    return 'data:image/jpeg;base64,' . base64_encode($cleanData);
                }
                break;
            case 'image/png':
                $image = @imagecreatefromstring($rawData);
                if ($image) {
                    ob_start();
                    imagepng($image, null, 6);
                    $cleanData = ob_get_clean();
                    imagedestroy($image);
                    return 'data:image/png;base64,' . base64_encode($cleanData);
                }
                break;
            case 'image/webp':
                $image = @imagecreatefromstring($rawData);
                if ($image) {
                    ob_start();
                    imagewebp($image, null, 80);
                    $cleanData = ob_get_clean();
                    imagedestroy($image);
                    return 'data:image/webp;base64,' . base64_encode($cleanData);
                }
                break;
            case 'image/gif':
                // GIF no tiene EXIF peligroso, pero validamos que sea real
                if (substr($rawData, 0, 4) === 'GIF8') {
                    return 'data:image/gif;base64,' . base64_encode($rawData);
                }
                break;
        }

        return null;
    }
}
