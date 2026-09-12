<?php
/**
 * BusinessProfileService - Sistema de perfiles y capacidades por tipo de negocio.
 * 
 * Gestiona las capabilities (features) que cada tipo de negocio tiene habilitadas.
 * Reemplaza la lógica hardcodeada de business_category por un sistema data-driven.
 */
class BusinessProfileService {

    private static $profiles = [
        'gastronomia' => [
            'label' => 'Gastronomía / Restaurante',
            'icon' => 'fa-utensils',
            'features' => [
                'inventory', 'sales', 'purchases', 'recipes', 'dishes',
                'recipe_costing', 'menu', 'qr_menu', 'restaurant_options',
                'kitchen', 'preparation_time', 'delivery', 'storefront',
                'clients', 'credits', 'reports', 'cashbox', 'expenses',
            ],
            'categories' => ['Bebidas', 'Entradas', 'Platos Principales', 'Postres', 'Contornos'],
            'units' => [
                ['name' => 'Porción', 'symbol' => 'porc', 'type' => 'unidad'],
                ['name' => 'Litro', 'symbol' => 'L', 'type' => 'volumen'],
                ['name' => 'Gramo', 'symbol' => 'g', 'type' => 'peso'],
            ],
        ],
        'ferreteria' => [
            'label' => 'Ferretería',
            'icon' => 'fa-tools',
            'features' => [
                'inventory', 'sales', 'purchases', 'brands', 'suppliers',
                'barcode', 'presentations', 'clients', 'credits',
                'reports', 'cashbox', 'expenses', 'storefront',
            ],
            'categories' => ['Herramientas', 'Tornillería', 'Electricidad', 'Plomería', 'Pintura'],
            'units' => [],
        ],
        'viveres' => [
            'label' => 'Víveres / Bodega',
            'icon' => 'fa-shopping-basket',
            'features' => [
                'inventory', 'sales', 'purchases', 'barcode', 'suppliers',
                'brands', 'clients', 'credits', 'reports', 'cashbox',
                'expenses', 'storefront',
            ],
            'categories' => ['Harinas', 'Granos', 'Lácteos', 'Enlatados', 'Aseo Personal'],
            'units' => [],
        ],
        'repuestos' => [
            'label' => 'Repuestos / Automotriz',
            'icon' => 'fa-cogs',
            'features' => [
                'inventory', 'sales', 'purchases', 'brands', 'suppliers',
                'barcode', 'presentations', 'clients', 'credits',
                'reports', 'cashbox', 'expenses', 'storefront',
            ],
            'categories' => ['Frenos', 'Suspensión', 'Motor', 'Eléctricos', 'Lubricantes'],
            'units' => [],
        ],
        'tecnologia' => [
            'label' => 'Tecnología',
            'icon' => 'fa-laptop',
            'features' => [
                'inventory', 'sales', 'purchases', 'serial_numbers', 'warranties',
                'brands', 'suppliers', 'clients', 'reports', 'cashbox',
                'expenses', 'storefront',
            ],
            'categories' => ['Smartphones', 'Laptops', 'Accesorios', 'Servicio Técnico'],
            'units' => [],
        ],
        'vehiculos' => [
            'label' => 'Vehículos',
            'icon' => 'fa-car',
            'features' => [
                'inventory', 'sales', 'purchases', 'brands', 'suppliers',
                'clients', 'credits', 'reports', 'cashbox', 'storefront',
            ],
            'categories' => ['Motos', 'Carros Usados', 'Accesorios'],
            'units' => [],
        ],
        'bienes_raices' => [
            'label' => 'Bienes Raíces',
            'icon' => 'fa-building',
            'features' => [
                'inventory', 'sales', 'purchases', 'clients', 'credits',
                'reports', 'cashbox', 'storefront',
            ],
            'categories' => ['Alquiler', 'Venta', 'Trámites'],
            'units' => [],
        ],
        'general' => [
            'label' => 'General / Mercadería',
            'icon' => 'fa-boxes',
            'features' => [
                'inventory', 'sales', 'purchases', 'clients', 'suppliers',
                'credits', 'reports', 'cashbox', 'expenses', 'storefront',
            ],
            'categories' => ['General', 'Servicios'],
            'units' => [],
        ],
    ];

    /**
     * Obtiene el perfil completo de un tipo de negocio por su código.
     */
    public static function getProfile(string $code): ?array {
        return self::$profiles[$code] ?? null;
    }

    /**
     * Obtiene todas las features habilitadas para un tipo de negocio.
     */
    public static function getFeatures(string $code): array {
        $profile = self::getProfile($code);
        return $profile ? $profile['features'] : [];
    }

    /**
     * Verifica si un tipo de negocio tiene una feature específica habilitada.
     */
    public static function hasFeature(string $code, string $feature): bool {
        return in_array($feature, self::getFeatures($code));
    }

    /**
     * Obtiene las categorías iniciales para un tipo de negocio.
     */
    public static function getSeedCategories(string $code): array {
        $profile = self::getProfile($code);
        return $profile ? $profile['categories'] : [];
    }

    /**
     * Obtiene las unidades de medida iniciales para un tipo de negocio.
     */
    public static function getSeedUnits(string $code): array {
        $profile = self::getProfile($code);
        return $profile ? $profile['units'] : [];
    }

    /**
     * Obtiene el label legible de un tipo de negocio.
     */
    public static function getLabel(string $code): string {
        $profile = self::getProfile($code);
        return $profile ? $profile['label'] : ucfirst($code);
    }

    /**
     * Obtiene el icono de un tipo de negocio.
     */
    public static function getIcon(string $code): string {
        $profile = self::getProfile($code);
        return $profile ? $profile['icon'] : 'fa-store';
    }

    /**
     * Retorna todos los perfiles disponibles (para formularios de registro).
     */
    public static function getAllProfiles(): array {
        $result = [];
        foreach (self::$profiles as $code => $profile) {
            $result[$code] = [
                'code' => $code,
                'label' => $profile['label'],
                'icon' => $profile['icon'],
            ];
        }
        return $result;
    }

    /**
     * Verifica si una feature está habilitada para el negocio actual en sesión.
     * Retorna false si no hay sesión de negocio.
     */
    public static function currentHasFeature(string $feature): bool {
        $category = $_SESSION['business_category'] ?? null;
        if (!$category) return false;
        return self::hasFeature($category, $feature);
    }

    /**
     * Obtiene el perfil del negocio actual en sesión.
     */
    public static function getCurrentProfile(): ?array {
        $category = $_SESSION['business_category'] ?? null;
        return $category ? self::getProfile($category) : null;
    }
}
