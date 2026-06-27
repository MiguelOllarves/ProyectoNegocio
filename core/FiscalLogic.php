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
}
