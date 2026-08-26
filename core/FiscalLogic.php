<?php
class FiscalLogic {
    /**
     * Calcula el precio final de un producto en función del costo, margen (decimal) y el método.
     * 
     * @param float $cost Costo base o de adquisición
     * @param float $marginPercentage Margen expresado en porcentaje (ej. 30 para 30%)
     * @param string $method Método de cálculo ('simple' o 'fiscal')
     * @return float Precio Calculado
     */
    public static function calculatePrice($cost, $marginPercentage, $method = 'fiscal') {
        $margin = $marginPercentage / 100;
        
        if ($method === 'simple') {
            // Método simple: Costo + Ganancia directa
            return $cost + ($cost * $margin);
        } else {
            // Método fiscal: Costo / (1 - Margen)
            if ($margin >= 1) { 
                return $cost; // Seguridad matemática para evitar infinito/negativos en errores
            }
            return $cost / (1 - $margin);
        }
    }

    /**
     * Calcula el costo real fraccionado cuando se compra por bulto.
     * Ejemplo: si el saco de 50Kg cuesta $50, el costo base por Kg es $1.
     * 
     * @param float $totalPurchaseCost El costo total del bulto/saco.
     * @param float $conversionFactor La cantidad de unidades mínimas en el bulto (ej. 50).
     * @return float El costo calculado por unidad base con 4 decimales para mayor precisión matemática.
     */
    public static function calculateFractionalCost($totalPurchaseCost, $conversionFactor) {
        if ($conversionFactor <= 0) return $totalPurchaseCost; // Prevenir división por 0
        
        // Usamos 4 decimales para cálculos intermedios para prevenir pérdida de valor
        return round($totalPurchaseCost / $conversionFactor, 4);
    }

    /**
     * Calcula precio final de venta por unidad base integrando el margen de ganancia.
     * 
     * @param float $totalPurchaseCost El costo total del bulto o saco
     * @param float $conversionFactor Multiplicador para llevar el bulto a unidades base
     * @param float $marginPercentage Margen de ganancia
     * @param string $method 'simple' o 'fiscal'
     * @return float Precio final a de venta base.
     */
    public static function calculateFractionalSalePrice($totalPurchaseCost, $conversionFactor, $marginPercentage, $method = 'fiscal') {
        // Encontrar costo exacto por porción base
        $baseCost = self::calculateFractionalCost($totalPurchaseCost, $conversionFactor);
        
        // Aplicar el margen para obtener precio de venta por unidad base
        $basePrice = self::calculatePrice($baseCost, $marginPercentage, $method);
        
        // Redondeo final a 2 decimales para la moneda
        return round($basePrice, 2);
    }
}
