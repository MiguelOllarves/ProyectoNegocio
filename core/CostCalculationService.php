<?php
require_once __DIR__ . '/UnitConversionService.php';

class CostCalculationService {
    
    /**
     * Calcula el costo por unidad base.
     * Ejemplo: Compra 40 kg a $200. Si la unidad base es 'g', entonces:
     * 40 kg -> 40000 g
     * Costo por g = 200 / 40000 = $0.005
     */
    public static function calculateCostPerBaseUnit($totalCost, $purchaseQuantity, $purchaseUnitId) {
        $totalCost = (float)$totalCost;
        if ($totalCost < 0) {
            throw new Exception("El costo total no puede ser negativo.");
        }
        if ($purchaseQuantity <= 0) {
            throw new Exception("La cantidad comprada debe ser mayor que cero.");
        }

        $quantityInBase = UnitConversionService::convertToBase($purchaseQuantity, $purchaseUnitId);
        if ($quantityInBase <= 0) {
            throw new Exception("La cantidad calculada en unidad base es inválida (zero o negativa).");
        }

        return $totalCost / $quantityInBase;
    }

    /**
     * Calcula el costo de un ingrediente en base a la receta.
     * 
     * @param float $recipeQty Cantidad en la receta (ej. 100)
     * @param int $recipeUnitId Unidad en la receta (ej. ID de g)
     * @param float $costPerBaseUnit Costo por unidad base del producto (ej. $0.005)
     * @return float|string Costo calculado, o "MISSING_COST" si el costo base es nulo o <= 0 (si se requiere)
     */
    public static function calculateIngredientCost($recipeQty, $recipeUnitId, $costPerBaseUnit) {
        if ($costPerBaseUnit === null || $costPerBaseUnit === '') {
            return "MISSING_COST";
        }
        
        $costPerBaseUnit = (float)$costPerBaseUnit;
        if ($costPerBaseUnit == 0) {
           return "MISSING_COST";
        }
        
        // Convertimos la cantidad de la receta a la unidad base
        $qtyInBase = UnitConversionService::convertToBase($recipeQty, $recipeUnitId);
        
        $ingredientCost = $qtyInBase * $costPerBaseUnit;
        return $ingredientCost;
    }

    /**
     * Calcula el costo por una unidad de venta (u otra unidad arbitraria)
     */
    public static function calculateCostPerSaleUnit($costPerBaseUnit, $saleUnitId) {
        $unit = UnitConversionService::getUnit($saleUnitId);
        if (!$unit) {
            throw new Exception("Unidad de venta no encontrada.");
        }
        $factor = (float)$unit['conversion_to_base'];
        return $costPerBaseUnit * $factor;
    }
}
