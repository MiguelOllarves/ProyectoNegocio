<?php
/**
 * ImageValidator - Servicio de validación de imágenes
 * Solo acepta archivos JPG y PNG
 */
class ImageValidator {

    private static $allowedMimes = [
        'image/jpeg',
        'image/png',
    ];

    private static $allowedFileMimes = [
        'application/pdf',
    ];

    const MAX_IMAGE_SIZE = 3000000;
    const MAX_FILE_SIZE = 6000000;
    const MAX_BASE64_DB_SIZE = 4500000;

    public static function validateImage(string $base64, string $context = 'image'): array {
        if (empty($base64)) {
            return ['valid' => false, 'error' => 'No se proporcionó imagen.'];
        }

        if (!preg_match('/^data:image\/(jpeg|png);base64,/', $base64, $matches)) {
            return ['valid' => false, 'error' => 'Formato de imagen no permitido. Solo se aceptan JPG y PNG.'];
        }

        if (strlen($base64) > self::MAX_BASE64_DB_SIZE) {
            return ['valid' => false, 'error' => 'La imagen es demasiado grande para almacenar. Máximo 3MB.'];
        }

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

        if (strlen($rawData) > self::MAX_IMAGE_SIZE) {
            return ['valid' => false, 'error' => 'La imagen excede el tamaño máximo de 3MB.'];
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $realMime = $finfo->buffer($rawData);

        if (!in_array($realMime, self::$allowedMimes)) {
            return ['valid' => false, 'error' => 'Tipo de imagen no permitido. Solo se aceptan JPG y PNG.'];
        }

        $cleanBase64 = 'data:' . $realMime . ';base64,' . base64_encode($rawData);

        return [
            'valid' => true,
            'clean_base64' => $cleanBase64,
            'mime' => $realMime,
            'size' => strlen($rawData),
        ];
    }

    public static function validateFile(string $base64, string $context = 'file'): array {
        if (empty($base64)) {
            return ['valid' => false, 'error' => 'No se proporcionó archivo.'];
        }

        if (strlen($base64) > self::MAX_BASE64_DB_SIZE) {
            return ['valid' => false, 'error' => 'El archivo es demasiado grande. Máximo 6MB.'];
        }

        $parts = explode(';', $base64, 2);
        $dataParts = explode(',', $parts[1] ?? '', 2);
        $rawData = base64_decode($dataParts[1] ?? '', true);

        if ($rawData === false || strlen($rawData) > self::MAX_FILE_SIZE) {
            return ['valid' => false, 'error' => 'Archivo demasiado grande o corrupto.'];
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $realMime = $finfo->buffer($rawData);

        if (!in_array($realMime, self::$allowedFileMimes)) {
            return ['valid' => false, 'error' => 'Solo se permiten archivos PDF. Detectado: ' . $realMime];
        }

        if (substr($rawData, 0, 4) !== '%PDF') {
            return ['valid' => false, 'error' => 'El archivo no es un PDF válido.'];
        }

        return [
            'valid' => true,
            'clean_base64' => 'data:application/pdf;base64,' . base64_encode($rawData),
            'mime' => 'application/pdf',
            'size' => strlen($rawData),
        ];
    }

    public static function sanitizeExistingImage(string $base64): ?string {
        if (empty($base64) || !preg_match('/^data:image\/[^;]+;base64,/', $base64)) {
            return null;
        }

        $parts = explode(';', $base64, 2);
        $dataParts = explode(',', $parts[1] ?? '', 2);
        $rawData = base64_decode($dataParts[1] ?? '', true);

        if ($rawData === false) return null;

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $realMime = $finfo->buffer($rawData);

        if (!in_array($realMime, self::$allowedMimes)) return null;

        $image = @imagecreatefromstring($rawData);
        if ($image) {
            ob_start();
            if ($realMime === 'image/jpeg') {
                imagejpeg($image, null, 85);
            } else {
                imagepng($image, null, 6);
            }
            $cleanData = ob_get_clean();
            imagedestroy($image);
            return 'data:' . $realMime . ';base64,' . base64_encode($cleanData);
        }

        return null;
    }
}
