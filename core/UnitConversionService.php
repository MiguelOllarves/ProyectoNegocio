<?php
require_once __DIR__ . '/../config/Database.php';

class UnitConversionService {
    
    /**
     * Obtiene los detalles de una unidad desde la base de datos
     */
    public static function getUnit($unitId) {
        if (!$unitId) return null;
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM units_of_measure WHERE id = ?");
        $stmt->execute([$unitId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene todas las unidades (útil para dropdowns)
     */
    public static function getAllUnits() {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->query("SELECT * FROM units_of_measure ORDER BY base_type, conversion_to_base");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Convierte una cantidad dada desde una unidad origen a su unidad base.
     * Ejemplo: 5 kg (origen) -> 5000 g (base)
     * 
     * @param float $amount Cantidad introducida (ej. 5)
     * @param int $fromUnitId ID de la unidad (ej. ID de Kg)
     * @return float Cantidad en la unidad base (ej. 5000)
     */
    public static function convertToBase($amount, $fromUnitId) {
        $amount = (float)$amount;
        if ($amount === 0.0) return 0.0;
        
        $unit = self::getUnit($fromUnitId);
        if (!$unit) {
            throw new Exception("Unidad de medida (ID: $fromUnitId) no encontrada.");
        }
        
        // El conversion_to_base indica cuánto vale 1 de esta unidad respecto a la base.
        // Ej: 1 kg = 1000.0 (gramos)
        $factor = (float)$unit['conversion_to_base'];
        return $amount * $factor;
    }

    /**
     * Convierte una cantidad desde la unidad base hacia una unidad de destino.
     * Ejemplo: 5000 g (base) -> 5 kg (destino)
     * 
     * @param float $amountInBase Cantidad en unidad base (ej. 5000)
     * @param int $toUnitId ID de la unidad (ej. ID de Kg)
     * @return float Cantidad en la unidad destino (ej. 5)
     */
    public static function convertFromBase($amountInBase, $toUnitId) {
        $amountInBase = (float)$amountInBase;
        if ($amountInBase === 0.0) return 0.0;
        
        $unit = self::getUnit($toUnitId);
        if (!$unit) {
            throw new Exception("Unidad de destino (ID: $toUnitId) no encontrada.");
        }
        
        $factor = (float)$unit['conversion_to_base'];
        if ($factor == 0) throw new Exception("Factor de conversión inválido para la unidad ID $toUnitId.");
        
        return $amountInBase / $factor;
    }

    /**
     * Convierte directamente entre dos unidades (deben ser de la misma familia).
     */
    public static function convert($amount, $fromUnitId, $toUnitId) {
        if ($fromUnitId == $toUnitId) return (float)$amount;
        
        $fromUnit = self::getUnit($fromUnitId);
        $toUnit = self::getUnit($toUnitId);
        
        if ($fromUnit['base_type'] !== $toUnit['base_type']) {
            throw new Exception("Conversión incompatible: No se puede convertir de " . $fromUnit['base_type'] . " a " . $toUnit['base_type']);
        }
        
        // amount * from_factor = base_amount
        // target_amount = base_amount / to_factor
        $baseAmount = $amount * (float)$fromUnit['conversion_to_base'];
        return $baseAmount / (float)$toUnit['conversion_to_base'];
    }


}
