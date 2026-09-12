# WORK PROMPT — TuInventario.app
## Evolución integral: multi-vertical + restaurante/modificadores + offline-first + seguridad + rendimiento

> **Objetivo:** entregar una versión de producción de TuInventario.app sin romper lo existente, manteniendo PHP + PDO + PostgreSQL/SQLite + HTML + Tailwind + HTMX/Alpine/JavaScript, pero corrigiendo arquitectura, aislamiento multi-tenant, adaptación por tipo de negocio, restaurante, tienda online, inventario, offline-first, rendimiento y seguridad.
>
> **Repositorio:** `https://github.com/MiguelOllarves/ProyectoNegocio`
>
> **Regla principal:** NO empieces programando. Primero audita el proyecto completo, reconstruye su arquitectura real y luego implementa por fases verificables. No asumas que el README coincide con el código actual.

---

# 0. CONTEXTO REAL YA DETECTADO

El proyecto ya posee:

- MVC PHP propio.
- PDO.
- PostgreSQL como configuración principal y soporte histórico para SQLite.
- Multi-tenant mediante `businesses` + `tenant_id`.
- Inventario.
- Productos, categorías y marcas.
- Presentaciones.
- Unidades de medida y conversiones.
- Costos y márgenes.
- POS.
- Compras.
- Ventas.
- Kardex.
- Clientes/proveedores/créditos.
- Restaurante.
- Recetas.
- Costeo de platos.
- Disponibilidad de platos por ingredientes.
- Storefront/tienda.
- Pedidos online.
- WhatsApp.
- QR menu.
- PWA/service worker.
- HTMX/Alpine/JavaScript.
- Migraciones automáticas.
- Auditoría.
- Rate limiting y CSRF parcial/ya implementado en varias zonas.

El modelo actual de producto tiene, entre otros:

- `tenant_id`
- `category_id`
- `brand_id`
- `supplier_id`
- `name`
- `sku`
- `barcode`
- `unit_cost`
- `price`
- `stock`
- `min_stock`
- `image`
- `dynamic_attributes`
- `is_dish`
- `prep_time`
- unidades de compra/venta/base.

El restaurante ya posee `recipe_items` y `Recipe.php` con:

- `getForDish`
- `saveRecipe`
- `calculateCost`
- `getAvailableServings`
- `checkAvailability`
- `consumeIngredients`
- `restoreIngredients`.

El registro actualmente crea un `business` con un campo `category` y después ejecuta un `seedBusinessData()` con perfiles como:

- `gastronomia`
- `viveres`
- `repuestos`
- `vehiculos`
- `bienes_raices`
- `tecnologia`
- `general`.

Actualmente el problema es que `general/mercadería general` funciona como fallback demasiado amplio y la UI/módulos no están gobernados de manera verdaderamente vertical por el tipo de negocio.

---

# 1. HALLAZGOS CRÍTICOS A VALIDAR Y CORREGIR

Antes de modificar cualquier cosa, comprueba estos hallazgos contra el código actual:

## 1.1 Tienda online e inventario

El checkout del storefront actualmente crea pedidos pero debe revisarse porque no está integrado de manera equivalente al POS con:

- `Recipe::checkAvailability()`
- `Recipe::consumeIngredients()`
- Kardex.
- transacciones atómicas de inventario.

### Objetivo

POS y Storefront deben usar el MISMO motor de consumo.

No permitir:

```text
POS -> consume inventario
Tienda -> crea pedido pero no consume inventario
```

Debe quedar:

```text
POS ------------------\
                       -> Sales/Inventory domain service -> stock/receta/Kardex
Storefront ------------/
```

---

## 1.2 Recetas

Fortalecer aislamiento tenant.

Cada:

- `dish_id`
- `ingredient_id`
- `recipe_item`
- opción
- grupo de opciones

debe verificarse contra el tenant actual.

Nunca aceptar un ID de otro negocio aunque exista.

Evitar que `product_id=123` de otro tenant pueda entrar por POST.

---

## 1.3 Kardex

Revisar que consumir una receta registre trazabilidad real.

Una venta de:

```text
1 Milanesa
```

debe poder producir movimientos trazables de:

```text
Carne -250g
Pan rallado -50g
Aceite -20ml
```

Y una configuración:

```text
Milanesa
+ Arroz
+ Tajadas
```

debe consumir la receta de cada plato/acompañante o el stock correspondiente y dejar referencia a la venta.

La anulación debe restaurar exactamente lo consumido.

---

## 1.4 Disponibilidad

No usar simplemente:

```sql
product.stock > 0
```

para platos elaborados.

Para `is_dish=true`, calcular disponibilidad según receta.

Para platos configurables, calcular disponibilidad según:

- receta base;
- opciones obligatorias;
- opciones seleccionadas;
- disponibilidad de cada opción;
- cantidad máxima que puede venderse.

---

## 1.5 Carrito

El carrito actual debe revisarse porque un producto configurable no puede identificarse solamente por `product_id`.

Ejemplo:

```text
Milanesa + Arroz + Tajadas
```

y:

```text
Milanesa + Pasta + Ensalada
```

son dos líneas distintas.

Crear un `configuration_key` determinístico basado en el producto + opciones normalizadas.

---

# 2. OBJETIVO ARQUITECTÓNICO PRINCIPAL

Convertir el sistema en:

```text
                    TuInventario Core
                           |
          +----------------+----------------+
          |                |                |
       Comercio        Gastronomía      Otros verticales
          |                |                |
       Inventario       Recetas          atributos
       Ventas           Modificadores    especializados
       Compras          Menús            especializados
       Kardex           Producción       especializados
       POS              Delivery         ...
```

La regla es:

> **El CORE es común. Las capacidades y la interfaz dependen del perfil del negocio.**

No duplicar sistemas por vertical.

---

# 3. SEPARAR CONCEPTOS QUE HOY ESTÁN MEZCLADOS

Actualmente existe:

```text
businesses.category
```

y además existen:

```text
categories
```

de productos.

Esto NO debe confundirse.

## 3.1 Tipo/vertical del negocio

Ejemplos:

```text
gastronomia
ferreteria
viveres
repuestos
tecnologia
vehiculos
bienes_raices
general
```

Esto determina capacidades del sistema.

## 3.2 Categoría de producto

Ejemplos en una ferretería:

```text
Tornillería
Electricidad
Plomería
Pinturas
Herramientas
```

Ejemplos en restaurante:

```text
Entradas
Platos principales
Contornos
Bebidas
Postres
```

Nunca utilizar `categories` de productos para decidir qué módulos aparecen.

---

# 4. NUEVO SISTEMA DE PERFILES DE NEGOCIO

Diseñar un sistema extensible de perfiles.

Preferido:

```text
business_types
business_type_features
businesses.business_type_id
```

También puede existir un registro de capabilities si la auditoría demuestra que otra solución es mejor.

## 4.1 Ejemplo de perfiles

### Gastronomía

```text
code: gastronomia

features:
- inventory
- sales
- purchases
- recipes
- dishes
- recipe_costing
- menu
- qr_menu
- restaurant_options
- kitchen
- preparation_time
- delivery
- storefront
- clients
- credits
- reports
- cashbox
```

### Ferretería

```text
code: ferreteria

features:
- inventory
- sales
- purchases
- brands
- suppliers
- barcode
- presentations
- clients
- credits
- reports
- cashbox
- storefront
```

NO mostrar por defecto:

```text
Recetas
Platos
Contornos
Cocina
Menú QR gastronómico
```

### Víveres

```text
code: viveres

features:
- inventory
- sales
- purchases
- barcode
- expiration_dates (si se implementa correctamente)
- suppliers
- brands
- clients
- credits
- reports
- cashbox
- storefront
```

### Tecnología

```text
code: tecnologia

features:
- inventory
- sales
- purchases
- serial_numbers
- warranties
- brands
- suppliers
- clients
- reports
- cashbox
- storefront
```

### Repuestos

```text
code: repuestos

features:
- inventory
- sales
- purchases
- compatibility/vehicle-reference (si se implementa)
- brands
- suppliers
- barcode
- clients
- reports
- cashbox
- storefront
```

### Vehículos

Debe tratarse como un vertical distinto. No mezclarlo con inventario tradicional si el dominio requiere:

- vehículos;
- documentos;
- estados;
- precio;
- ficha;
- características;
- fotos;
- etc.

Implementar solo capacidades que realmente estén soportadas por el código. No inventar un CRM automotor completo si no existe.

### Bienes raíces

No forzar el modelo de `stock` como si una propiedad fuera una caja de tornillos.

Si el sistema aún no soporta propiedades como entidad de dominio, crear un perfil limpio que oculte funcionalidades incompatibles y dejar un módulo especializado preparado, pero NO fabricar datos falsos.

### General

`general` debe ser el perfil mínimo:

```text
inventory
sales
purchases
clients
suppliers
reports
cashbox
settings
storefront (si está habilitado)
```

No debe cargar funcionalidades de restaurante por accidente.

---

# 5. REGLA DE PROVISIONAMIENTO DEL WORKSPACE

Cuando alguien registra:

```text
Tipo de negocio = Gastronomía
```

el workspace debe nacer gastronómico.

Debe recibir:

- módulos gastronómicos visibles;
- categorías iniciales gastronómicas;
- configuración de menú;
- recetas/platos disponibles;
- opciones/modificadores si corresponde;
- unidades relevantes;
- dashboard contextual.

NO crear:

```text
Mercadería General
```

si el negocio es restaurante.

Cuando registra:

```text
Tipo = Ferretería
```

crear:

```text
workspace ferretería
```

con:

```text
Herramientas
Tornillería
Electricidad
Plomería
Pintura
```

y no:

```text
Platos Principales
Bebidas
Contornos
Recetas
```

Cuando registra:

```text
Tipo = Tecnología
```

no crear módulos gastronómicos.

---

# 6. PROVISIONAMIENTO DEBE SER DATA-DRIVEN

No hacer:

```php
if ($category === 'gastronomia') { ... }
else if ($category === 'ferreteria') { ... }
else ...
```

repetido por todo el proyecto.

Crear un `BusinessProfileRegistry` / `BusinessProfileService`.

Ejemplo conceptual:

```php
BusinessProfileService::getProfile($businessId);

BusinessProfileService::hasFeature($businessId, 'recipes');

BusinessProfileService::hasFeature($businessId, 'restaurant_options');

BusinessProfileService::hasFeature($businessId, 'serial_numbers');

BusinessProfileService::getSeed($businessType);
```

La aplicación debe consultar capabilities.

---

# 7. MENÚ/SIDEBAR CONTEXTUAL

Auditar `includes/sidebar.php`, header, dashboard y cualquier navegación.

La navegación debe ser contextual.

## Gastronomía

```text
Dashboard
Ventas / POS
Inventario
Compras
Recetas / Platos
Menú
QR Menú
Pedidos
Clientes
Proveedores
Créditos
Caja
Reportes
Configuración
```

## Ferretería

```text
Dashboard
Ventas / POS
Inventario
Compras
Clientes
Proveedores
Créditos
Caja
Reportes
Configuración
Tienda
```

## Tecnología

Mostrar lo que el perfil soporte.

La URL tampoco debe bastar para entrar.

Si alguien intenta:

```text
/restaurant
```

desde una ferretería:

```text
403 / redirección contextual
```

según la política elegida.

Esto debe aplicarse en backend, no solamente ocultando botones.

---

# 8. AUTORIZACIÓN POR CAPABILITY

Agregar un guard:

```php
Middleware::requireFeature('recipes');
```

o equivalente.

No depender solamente de:

```php
if ($_SESSION['business_category'] === 'gastronomia')
```

La capability debe obtenerse de una fuente confiable y tenant-scoped.

SuperAdmin puede administrar, pero no debe contaminar datos de tenants.

---

# 9. RESTAURANTE: MOTOR GENÉRICO DE OPCIONES

Implementar grupos de opciones.

Tablas recomendadas:

```sql
restaurant_option_groups
restaurant_options
```

## `restaurant_option_groups`

Campos mínimos:

```text
id
tenant_id
product_id
name
min_selections
max_selections
required
display_order
active
created_at
updated_at
```

## `restaurant_options`

Campos mínimos:

```text
id
tenant_id
group_id
product_id
price_delta
display_order
active
created_at
updated_at
```

La opción apunta a `products`.

Esto permite reutilizar:

- stock;
- recetas;
- costo;
- unidades;
- imágenes;
- precio;
- Kardex.

---

# 10. EJEMPLO RESTAURANTE

Producto:

```text
Milanesa a la plancha
$8
```

Grupo:

```text
Contornos
min=2
max=2
required=true
```

Opciones:

```text
Arroz
Pasta
Tajadas
Ensalada Mixta
Ensalada César
Caraotas
Yuca sancochada
Puré de papas
```

Grupo:

```text
Bebida
min=1
max=1
required=true
```

Opciones:

```text
Agua
Coca-Cola
Malta +0.50
Jugo Natural +1.00
Ice Tea +0.50
Del Valle +1.00
```

---

# 11. OPCIONES DEBEN SER GENÉRICAS

NO crear:

```text
tabla_contornos
tabla_bebidas_restaurante
```

Debe funcionar para:

```text
Contornos
Bebidas
Salsas
Extras
Tamaños
Aderezos
Toppings
Guarniciones
```

El nombre es simplemente un `option_group.name`.

---

# 12. CARRITO CONFIGURABLE

Modelo:

```js
{
  productId: 15,
  name: "Milanesa a la plancha",
  basePrice: 8,
  unitPrice: 9,
  quantity: 1,
  configurationKey: "...",
  options: [
    {
      groupId: 1,
      groupName: "Contornos",
      selections: [
        {
          productId: 301,
          name: "Arroz",
          priceDelta: 0
        },
        {
          productId: 305,
          name: "Tajadas",
          priceDelta: 0
        }
      ]
    },
    {
      groupId: 2,
      groupName: "Bebida",
      selections: [
        {
          productId: 410,
          name: "Jugo Natural",
          priceDelta: 1
        }
      ]
    }
  ]
}
```

`configurationKey` debe ser determinístico y normalizado.

No confiar en el precio enviado por JS.

---

# 13. PRECIO: EL BACKEND MANDA

El frontend puede calcular para UX.

El backend debe:

1. cargar producto;
2. cargar grupos;
3. validar selección;
4. validar min/max;
5. validar que las opciones pertenecen al tenant;
6. validar que las opciones pertenecen al grupo;
7. leer `price_delta` desde BD;
8. calcular precio final;
9. ignorar cualquier precio manipulado desde navegador.

---

# 14. INVENTARIO DE OPCIONES

Si una opción es:

```text
Agua 500ml
```

y es producto normal:

```text
stock -= 1
```

Si es:

```text
Arroz
```

y `is_dish=true`:

```text
consume receta de arroz
```

Así:

```text
Milanesa
 + Arroz
 + Tajadas
 + Jugo
```

consume:

```text
receta Milanesa
receta Arroz
receta Tajadas
stock Jugo
```

---

# 15. SERVICIO CENTRAL DE CONSUMO

Crear un servicio de dominio común, por ejemplo:

```text
core/InventoryConsumptionService.php
```

Responsabilidades:

```text
consumeProduct()
consumeDish()
consumeConfiguredProduct()
restoreProduct()
restoreDish()
restoreConfiguredProduct()
checkAvailability()
```

Debe recibir:

```text
tenantId
productId
quantity
referenceType
referenceId
userId
configuration
```

Todo debe ejecutarse dentro de la misma transacción que la venta/pedido.

---

# 16. TRANSACCIONES

Checkout:

```text
BEGIN

validar tenant
validar productos
validar opciones
validar precio
validar stock/recetas
crear order
crear order_items
crear order_item_options
consumir inventario
crear Kardex
registrar auditoría

COMMIT
```

Ante cualquier fallo:

```text
ROLLBACK
```

Nunca dejar:

```text
pedido creado + inventario sin descontar
```

ni:

```text
inventario descontado + pedido fallido
```

---

# 17. PEDIDOS ESTRUCTURADOS

Agregar:

```text
store_order_items
store_order_item_options
```

No depender únicamente de `items_json`.

## `store_order_items`

```text
id
order_id
product_id
quantity
unit_price
total_price
product_name_snapshot
created_at
```

## `store_order_item_options`

```text
id
order_item_id
group_id
option_product_id
group_name_snapshot
option_name_snapshot
price_delta
quantity
created_at
```

Guardar snapshots de nombres/precios para que los pedidos históricos no cambien si luego se edita el producto.

---

# 18. RESTAURANTE + POS

El configurador debe poder reutilizarse en:

```text
Storefront
POS
QR Menu
```

No construir tres motores.

Crear una estructura de dominio común:

```text
ProductConfigurationService
```

que:

```text
loadConfiguration()
validateConfiguration()
calculateConfigurationPrice()
calculateConfigurationAvailability()
```

---

# 19. DISPONIBILIDAD DE CONFIGURACIONES

Ejemplo:

```text
Milanesa:
15 disponibles

Arroz:
20 disponibles

Tajadas:
8 disponibles

Jugo:
30 disponibles
```

La combinación:

```text
Milanesa + Arroz + Tajadas + Jugo
```

solo permite:

```text
8
```

si Tajadas es el cuello de botella.

Si el cliente elige una opción agotada:

```text
Tajadas — Agotado
```

No permitir checkout.

Idealmente mostrar:

```text
✓ Arroz
✓ Pasta
✓ Ensalada
✕ Tajadas
✕ Yuca
```

---

# 20. RECETAS Y RECURSIÓN

Mantener el principio de evitar recetas circulares.

No convertir automáticamente:

```text
Milanesa -> Arroz -> Milanesa
```

en recetas recursivas.

Las opciones deben referenciar productos, pero el motor debe tener protección contra ciclos si el futuro modelo lo permite.

Validar:

```text
producto opción no puede generar ciclo de producción.
```

---

# 21. UNIDADES Y COSTOS

Preservar el motor actual:

```text
Unidad base
Peso -> gramo
Volumen -> ml
Unidad -> und
```

No romper:

- `UnitConversionService`
- `CostCalculationService`.

Toda opción elaborada debe calcular costo mediante su propia receta.

Ejemplo:

```text
Arroz:
100g arroz crudo
5ml aceite
2g sal
```

El costo del arroz seleccionado se calcula con la misma infraestructura existente.

---

# 22. BORRADO / DESACTIVACIÓN

No eliminar silenciosamente productos que estén usados como:

- ingrediente;
- opción;
- producto vendido;
- referencia histórica.

Preferir:

```text
active = false
```

cuando corresponda.

Si se intenta eliminar:

```text
Arroz
```

y está usado en:

```text
Milanesa
Pabellón
Chuleta
```

informar claramente.

Nunca romper históricos.

---

# 23. SEGURIDAD MULTI-TENANT

Auditar TODO el proyecto buscando consultas como:

```sql
SELECT * FROM products WHERE id = ?
```

cuando deberían incluir:

```sql
AND tenant_id = ?
```

Revisar:

- productos;
- categorías;
- marcas;
- recetas;
- opciones;
- pedidos;
- ventas;
- compras;
- clientes;
- proveedores;
- créditos;
- reportes;
- Kardex;
- configuraciones;
- archivos;
- logos;
- QR;
- storefront;
- endpoints AJAX;
- APIs.

Regla:

> **Un ID nunca es autorización.**

Siempre validar pertenencia al tenant.

---

# 24. SEGURIDAD DEL REGISTRO/LOGIN

Auditar y mejorar:

- CSRF.
- rate limit.
- session fixation.
- `session_regenerate_id(true)` al login.
- cookies `HttpOnly`, `Secure`, `SameSite`.
- expiración de sesión.
- logout seguro.
- password hashing.
- mensajes que no filtren existencia de cuentas cuando no sea necesario.
- límites de tamaño.
- validación de inputs.
- validación de email.
- sanitización de salida.

No reducir la seguridad existente.

---

# 25. PROBLEMA CRÍTICO: `.env`

El proyecto entregado contiene un `.env`.

NO imprimir sus secretos.

Verificar:

```text
.gitignore
git ls-files
historial Git
logs
debug endpoints
```

Si `.env` o secretos fueron versionados alguna vez:

1. eliminar del repositorio;
2. rotar credenciales;
3. invalidar credenciales expuestas;
4. documentar el proceso;
5. usar variables de entorno;
6. nunca devolver secretos en una página web.

---

# 26. ENDPOINTS DE DEBUG

Auditar y eliminar o proteger fuertemente:

```text
public/test_env.php
public/test_products.php
public/test_edit_load.php
public/test_buffer.php
public/debug.php
public/debug_inventory.php
public/reset_db.php
```

Especialmente peligroso:

```text
reset_db.php
```

porque ejecuta operaciones destructivas.

NO dejar herramientas destructivas accesibles desde HTTP en producción.

Si son útiles para desarrollo:

```text
dev-only
CLI-only
entorno protegido
```

o eliminarlas del build de producción.

---

# 27. CREDENCIALES HARDCODEADAS

Buscar:

```text
passwords
API keys
database URLs
SMTP passwords
VAPID keys
super admin credentials
tokens
private keys
```

No dejar credenciales reales en código.

Todo secreto:

```text
ENV
secret manager
```

Nunca:

```php
password_hash('clave_real', ...)
```

para cuentas de producción.

---

# 28. SQL DINÁMICO

Auditar `Model.php` y todos los modelos.

Prepared statements para valores.

Para:

```text
ORDER BY
column names
table names
```

usar whitelist.

Nunca concatenar entrada del usuario directamente.

---

# 29. SEGURIDAD DE ARCHIVOS

Auditar:

- uploads;
- logos;
- imágenes;
- base64;
- nombres de archivo;
- MIME;
- extensión;
- tamaño;
- path traversal;
- SVG potencialmente peligroso.

Mantener/fortalecer `ImageValidator`.

No ejecutar archivos subidos.

Configurar servidor para impedir ejecución de PHP dentro de directorios de upload.

---

# 30. HEADERS

Agregar/validar:

```text
Content-Security-Policy
X-Content-Type-Options: nosniff
Referrer-Policy
Permissions-Policy
Strict-Transport-Security (solo HTTPS)
frame-ancestors / X-Frame-Options
```

La CSP debe ser compatible con HTMX/Alpine y debe eliminar gradualmente dependencias inseguras como `unsafe-inline` cuando sea posible.

No romper la aplicación por colocar una CSP imposible.

---

# 31. ERRORES

Nunca mostrar al usuario:

```text
PDOException
SQL
stack trace
DATABASE_URL
paths internos
```

En producción:

```text
mensaje amigable
request/correlation id
log interno
```

En desarrollo puede existir más detalle, controlado por configuración.

---

# 32. LOGS

No registrar:

- contraseñas;
- tokens;
- cookies;
- Authorization headers;
- secretos;
- información financiera innecesaria.

Implementar logs estructurados cuando sea viable.

---

# 33. MULTI-TENANT: AUDITORÍA COMPLETA

Crear una matriz:

```text
Tabla
tenant_id
FK business
CREATE protegido
READ protegido
UPDATE protegido
DELETE protegido
```

Toda tabla de negocio debe tener estrategia clara.

Las tablas globales, como:

```text
units_of_measure
business_types
feature registry
```

deben ser explícitamente globales.

No añadir `tenant_id` indiscriminadamente a datos realmente globales.

---

# 34. MODELO DE DATOS POR VERTICAL

No convertir todos los campos especializados en columnas de `products`.

Usar:

```text
CORE
products
categories
brands
inventory
sales
purchases
clients
suppliers
```

y especializaciones:

```text
restaurant
recipes
restaurant_options
```

```text
technology
serials/warranties
```

etc.

`dynamic_attributes` puede permanecer para extensiones simples, pero no debe ser la base de dominios complejos que necesitan consultas, índices, integridad y reportes.

---

# 35. MIGRACIONES

Crear migraciones idempotentes.

No editar solamente `schema.sql`.

Actualizar:

```text
schema.sql
schema_postgres.sql
Migration.php
```

según la arquitectura actual.

Toda migración debe:

- poder ejecutarse más de una vez;
- no destruir datos;
- tener checks;
- crear índices;
- mantener compatibilidad;
- ser testeable.

---

# 36. POSTGRESQL + SQLITE

El proyecto declara soporte para ambos.

Auditar todas las consultas nuevas.

Evitar asumir exclusivamente:

```text
SERIAL
ILIKE
RETURNING
JSONB
ON CONFLICT
```

si el código debe continuar soportando SQLite.

Si una función es específica de PostgreSQL, crear una abstracción o una rama clara por driver.

No declarar soporte SQLite si realmente una funcionalidad crítica lo rompe.

---

# 37. OFFLINE-FIRST REAL

El proyecto YA posee:

```text
public/sw.js
public/offline.html
public/manifest.json
```

Pero esto no equivale a una aplicación realmente offline.

Convertir el PWA en una arquitectura offline-first gradual.

## 37.1 Objetivos offline

Cuando no haya internet:

### POS

Debe poder:

- abrir;
- consultar catálogo previamente sincronizado;
- buscar productos;
- vender;
- calcular totales;
- guardar ventas localmente;
- imprimir/mostrar comprobante local si es posible;
- poner operaciones en cola.

### Inventario

Debe poder:

- consultar snapshot;
- revisar stock local;
- registrar movimientos soportados offline;
- poner cambios en cola.

### Storefront

Debe poder:

- cargar catálogo cacheado;
- mostrar productos;
- mantener carrito.

Checkout online puede quedar:

```text
pendiente de sincronización
```

si la arquitectura de negocio no permite confirmar un pedido remoto sin conexión.

---

# 38. NO MENTIR SOBRE EL STOCK OFFLINE

Esto es crucial.

Un cliente offline no puede conocer el stock global real de otros dispositivos.

Mostrar:

```text
Stock sincronizado: 18
Última sincronización: 20:15
```

No:

```text
Stock real: 18
```

si está desconectado.

---

# 39. INDEXEDDB

Usar IndexedDB nativo, no introducir un framework pesado salvo justificación.

Stores sugeridos:

```text
catalog
products
categories
customers
restaurant_configurations
pending_operations
sync_metadata
settings_snapshot
```

---

# 40. COLA OFFLINE

Cada operación debe tener:

```text
operation_id UUID
tenant_id
device_id
user_id
type
payload
created_at
status
attempts
last_error
idempotency_key
```

Nunca sincronizar una operación dos veces.

El servidor debe aceptar:

```text
Idempotency-Key
```

o equivalente.

---

# 41. SINCRONIZACIÓN

Implementar:

```text
offline -> online
```

con:

1. detectar conexión;
2. autenticar sesión;
3. enviar operaciones pendientes;
4. servidor valida tenant;
5. servidor valida versión/reglas;
6. servidor procesa transacción;
7. servidor devuelve resultado;
8. cliente marca operación como sincronizada;
9. refresca snapshot.

---

# 42. CONFLICTOS

Definir política explícita.

Para stock:

```text
server authoritative
```

Si el stock cambió mientras el dispositivo estaba offline:

```text
venta offline -> servidor revalida
```

Si no hay inventario:

```text
conflict
```

No ocultarlo.

Registrar:

```text
SYNC_CONFLICT
```

y permitir resolución administrativa.

---

# 43. OFFLINE Y RECETAS

Si una venta offline contiene:

```text
Milanesa + Arroz + Tajadas
```

guardar toda la configuración local.

Al sincronizar:

```text
servidor vuelve a validar
```

Nunca confiar en el cálculo offline para autorización final.

---

# 44. PWA Y SERVICE WORKER

Revisar el `sw.js` actual.

Mejorar:

- versionado;
- cache busting;
- assets locales;
- fallback;
- actualización segura;
- evitar cachear respuestas sensibles;
- no cachear páginas de otro tenant;
- no cachear información privada de forma insegura;
- estrategia para catálogo;
- estrategia para API pública;
- estrategia para POS autenticado.

No hacer `cache-first` indiscriminado de respuestas que contengan datos privados.

---

# 45. OFFLINE SECURITY

Nunca guardar en IndexedDB:

```text
passwords
tokens de larga duración innecesarios
secretos
credenciales
```

Minimizar PII.

Aplicar expiración de snapshots.

Al cerrar sesión:

```text
limpiar datos sensibles del tenant
```

Si cambia de negocio:

```text
purga/aislamiento local obligatorio.
```

---

# 46. RENDIMIENTO: PRINCIPIO

El sistema debe sentirse instantáneo.

No agregar frameworks grandes solo por moda.

Mantener:

```text
PHP server-rendered
HTMX
Alpine
JS vanilla
```

cuando sea suficiente.

---

# 47. RENDIMIENTO BACKEND

Auditar:

- N+1 queries;
- SELECT * innecesarios;
- consultas repetidas;
- falta de índices;
- cálculos repetidos;
- consultas de tenant;
- paginación;
- joins;
- `COUNT(*)` costosos;
- reportes sin filtros.

Agregar índices según evidencia.

Especialmente:

```text
tenant_id
tenant_id + sku
tenant_id + barcode
tenant_id + category_id
tenant_id + created_at
order_id
product_id
dish_id
group_id
```

No crear índices sin evaluar uso.

---

# 48. RENDIMIENTO FRONTEND

Evitar:

```text
cargar 10 MB de JS
```

para abrir POS.

Preferir:

- assets locales;
- lazy loading;
- módulos pequeños;
- cache;
- HTML inicial útil;
- debounce;
- eventos delegados;
- DOM mínimo.

---

# 49. CDN

Auditar dependencias CDN.

Para offline real, las dependencias críticas deben estar disponibles localmente.

Especialmente:

```text
HTMX
Alpine
FontAwesome
fuentes
```

si son necesarias.

No depender de CDN para que POS funcione.

---

# 50. DATOS INICIALES DEL NEGOCIO

El seeder debe ser declarativo.

Ejemplo conceptual:

```php
return [
    'gastronomia' => [
        'features' => [...],
        'categories' => [...],
        'units' => [...],
        'settings' => [...],
    ],
    'ferreteria' => [...],
    ...
];
```

No crear productos de demostración innecesarios.

Los datos iniciales deben ayudar al usuario, no contaminar su inventario.

---

# 51. UX DE REGISTRO

Al registrar negocio:

```text
¿Qué tipo de negocio tienes?

🍔 Restaurante / Gastronomía
🔧 Ferretería
🛒 Víveres / Bodega
⚙️ Repuestos
📱 Tecnología
🚗 Vehículos
🏠 Bienes Raíces
📦 Otro
```

Si selecciona "Otro":

- pedir nombre del tipo;
- crear perfil `general` seguro;
- permitir activar capacidades después.

No mostrar un sistema gigantesco sin contexto.

---

# 52. CAMBIO DE TIPO DE NEGOCIO

Definir política.

Cambiar de:

```text
general -> gastronomia
```

NO debe borrar productos.

Debe:

- cambiar capacidades;
- ofrecer migración de categorías;
- no destruir históricos;
- no duplicar seeds;
- registrar auditoría.

Si un restaurante cambia a ferretería:

```text
recetas existentes se conservan
```

pero la UI puede ocultarlas según la política.

Nunca borrar datos automáticamente.

---

# 53. SUPERADMIN

El SuperAdmin debe poder:

- ver tenants;
- ver perfil;
- cambiar perfil con confirmación;
- ver capabilities;
- activar/desactivar feature flags;
- auditar;
- ver salud del sistema.

Pero no debe modificar datos del tenant accidentalmente por falta de scope.

---

# 54. TESTING OBLIGATORIO

Crear/actualizar tests para:

## Multi-tenant

- tenant A no puede leer producto de B;
- tenant A no puede editar producto de B;
- tenant A no puede usar receta de B;
- tenant A no puede usar opción de B.

## Restaurante

- grupo requerido;
- min selections;
- max selections;
- opción inválida;
- opción de otro grupo;
- precio correcto;
- configuración única en carrito;
- disponibilidad;
- consumo;
- restauración.

## Checkout

- stock insuficiente;
- receta insuficiente;
- rollback;
- Kardex;
- idempotencia.

## Verticales

Crear:

```text
restaurante
ferreteria
viveres
tecnologia
general
```

y comprobar que cada workspace tiene solo las capabilities esperadas.

## Offline

- crear operación offline;
- persistir;
- reconectar;
- sincronizar;
- repetir la misma operación;
- verificar idempotencia;
- conflicto de stock.

## Seguridad

- CSRF;
- autorización;
- rate limiting;
- acceso a endpoints;
- upload;
- headers;
- sesiones.

---

# 55. PRUEBAS DE REGRESIÓN

Antes de finalizar:

```text
Registro
Login
Logout
Dashboard
Inventario
Crear producto
Editar producto
Eliminar/desactivar
Compras
Ventas
POS
Kardex
Reportes
Clientes
Proveedores
Créditos
Caja
Storefront
Pedidos
WhatsApp
QR
Restaurante
Recetas
Migraciones
PWA
```

Nada existente debe romperse.

---

# 56. CHECKLIST DE CALIDAD DEL CÓDIGO

Buscar:

```text
TODO
FIXME
debug
print_r
var_dump
die(
exit(
password hardcoded
secret
token
DATABASE_URL
SELECT *
SQL concatenado
$_GET directo en SQL
$_POST directo en SQL
```

Clasificar cada hallazgo.

Eliminar código muerto.

Eliminar archivos `.bak` del producto final si no son necesarios.

No dejar herramientas de diagnóstico públicas.

---

# 57. ARCHIVOS DE INTERÉS A AUDITAR PRIMERO

Como mínimo:

```text
config/config.php
config/Database.php

core/Model.php
core/Controller.php
core/Middleware.php
core/Settings.php
core/UnitConversionService.php
core/CostCalculationService.php

database/schema.sql
database/schema_postgres.sql
database/Migration.php

public/index.php
public/sw.js
public/manifest.json
public/.htaccess

includes/header.php
includes/sidebar.php
includes/footer.php

modules/auth/controllers/AuthController.php

modules/inventory/controllers/InventoryController.php
modules/inventory/models/Product.php
modules/inventory/models/Category.php
modules/inventory/models/Brand.php

modules/restaurant/controllers/RestaurantController.php
modules/restaurant/models/Recipe.php
modules/restaurant/views/dish_form.php

modules/sales/controllers/SalesController.php
modules/sales/models/Sale.php

modules/storefront/controllers/StorefrontController.php
modules/storefront/views/landing.php
modules/storefront/views/orders.php

modules/qrmenu/controllers/QrMenuController.php

modules/dashboard/controllers/DashboardController.php
modules/reports/controllers/ReportsController.php

modules/superadmin/controllers/SuperadminController.php
```

Pero NO limitar la auditoría a estos archivos. Revisar el repositorio completo.

---

# 58. PLAN DE EJECUCIÓN OBLIGATORIO

## FASE 0 — Auditoría

No cambiar lógica todavía.

Generar:

```text
ARCHITECTURE_AUDIT.md
SECURITY_AUDIT.md
TENANT_AUDIT.md
PERFORMANCE_AUDIT.md
OFFLINE_AUDIT.md
```

con:

- problemas;
- severidad;
- archivo;
- línea;
- impacto;
- solución;
- dependencia.

---

## FASE 1 — Seguridad y aislamiento

Primero:

- secrets;
- debug;
- reset endpoints;
- tenant isolation;
- autorización;
- sesiones;
- CSRF;
- uploads;
- errores;
- headers.

No continuar si existe vulnerabilidad crítica de tenant.

---

## FASE 2 — Business Profiles

Implementar:

```text
business_types
feature registry
BusinessProfileService
requireFeature()
provisioning
```

Migrar el `businesses.category` actual sin romper datos.

---

## FASE 3 — Refactor del seeder

Convertir `seedBusinessData()` en provisioning por perfil.

Debe ser:

```text
idempotente
tenant-safe
sin duplicados
sin productos basura
```

---

## FASE 4 — Motor de inventario

Crear:

```text
InventoryConsumptionService
```

y hacer que POS y Storefront lo utilicen.

---

## FASE 5 — Restaurante

Implementar:

```text
restaurant_option_groups
restaurant_options
ProductConfigurationService
```

---

## FASE 6 — Pedidos

Implementar:

```text
store_order_items
store_order_item_options
```

manteniendo compatibilidad/migración con `items_json` si hay pedidos históricos.

---

## FASE 7 — Storefront/POS

Integrar configurador.

---

## FASE 8 — Disponibilidad

Integrar disponibilidad real de:

- productos;
- platos;
- opciones;
- configuraciones.

---

## FASE 9 — Offline-first

Implementar:

```text
IndexedDB
pending_operations
sync
idempotency
conflicts
service worker
```

primero POS/catalogo y luego ampliar.

---

## FASE 10 — Rendimiento

Medir antes/después.

Optimizar:

```text
queries
índices
assets
caching
DOM
payloads
```

---

## FASE 11 — Testing

Ejecutar todo.

No marcar terminado con tests fallando.

---

# 59. REGLAS DE IMPLEMENTACIÓN

1. No reescribir el proyecto desde cero.
2. No migrar a Laravel/React/Vue salvo que una auditoría técnica demuestre una necesidad real.
3. No romper PHP + JS existente.
4. No eliminar funcionalidad existente sin migración.
5. No cambiar esquema sin migración.
6. No usar IDs sin validar tenant.
7. No confiar en datos del frontend.
8. No guardar secretos.
9. No exponer debug.
10. No introducir dependencias grandes sin justificación.
11. No crear duplicación entre POS y Storefront.
12. No crear tablas específicas para cada tipo de contorno.
13. No usar categorías de producto como feature flags.
14. No ocultar seguridad solo con CSS/UI.
15. No afirmar "offline" si una operación necesita servidor y no existe cola/sincronización.
16. No hacer cambios destructivos.
17. Mantener compatibilidad con datos existentes.
18. Preferir `active=false` sobre borrar entidades usadas.
19. Toda operación crítica debe ser transaccional.
20. Toda operación sincronizable debe ser idempotente.

---

# 60. DEFINITION OF DONE

La implementación SOLO se considera terminada si:

### Arquitectura

- [ ] perfiles de negocio implementados;
- [ ] capabilities implementadas;
- [ ] provisioning contextual;
- [ ] no existe restaurante en workspace ferretería por defecto;
- [ ] no existe "mercadería general" innecesaria en restaurante.

### Restaurante

- [ ] grupos de opciones;
- [ ] min/max;
- [ ] required;
- [ ] extras con precio;
- [ ] carrito configurable;
- [ ] POS configurable;
- [ ] storefront configurable;
- [ ] disponibilidad;
- [ ] consumo;
- [ ] restauración.

### Inventario

- [ ] POS y web comparten motor;
- [ ] recetas consumen stock;
- [ ] opciones consumen stock/receta;
- [ ] Kardex;
- [ ] rollback.

### Seguridad

- [ ] tenant isolation auditada;
- [ ] endpoints debug eliminados/protegidos;
- [ ] reset destructivo no accesible;
- [ ] secretos fuera del código;
- [ ] sesiones seguras;
- [ ] CSRF;
- [ ] rate limiting;
- [ ] uploads seguros;
- [ ] headers;
- [ ] errores seguros.

### Offline

- [ ] PWA instalable;
- [ ] assets críticos locales;
- [ ] catálogo cacheado;
- [ ] POS usable sin conexión después de sincronizar;
- [ ] IndexedDB;
- [ ] cola;
- [ ] idempotencia;
- [ ] sincronización;
- [ ] conflictos;
- [ ] limpieza al logout/cambio de tenant.

### Performance

- [ ] no N+1 críticos;
- [ ] índices;
- [ ] payloads reducidos;
- [ ] assets críticos cacheados;
- [ ] medición antes/después;
- [ ] POS rápido.

### Testing

- [ ] tests multi-tenant;
- [ ] tests restaurante;
- [ ] tests checkout;
- [ ] tests offline;
- [ ] tests seguridad;
- [ ] regresión general.

---

# 61. ENTREGA FINAL QUE DEBE PRODUCIR LA IA

Al terminar, entregar:

```text
IMPLEMENTATION_REPORT.md
```

con:

1. arquitectura antes/después;
2. tablas nuevas;
3. migraciones;
4. archivos modificados;
5. archivos nuevos;
6. vulnerabilidades corregidas;
7. estrategia offline;
8. estrategia multi-tenant;
9. estrategia por vertical;
10. estrategia restaurante;
11. pruebas ejecutadas;
12. resultados;
13. problemas restantes;
14. instrucciones de despliegue;
15. variables de entorno;
16. rollback plan.

Además:

```text
SECURITY_CHECKLIST.md
OFFLINE_GUIDE.md
BUSINESS_PROFILES.md
RESTAURANT_OPTIONS_GUIDE.md
```

si la magnitud del cambio lo justifica.

---

# 62. COMPORTAMIENTO ESPERADO DE LA IA QUE EJECUTA ESTE WORK

Quiero una IA que actúe como:

```text
Senior PHP Architect
+
Database Architect
+
Security Engineer
+
PWA/Offline Engineer
+
Performance Engineer
+
Restaurant POS Engineer
+
QA Engineer
```

No como un generador de snippets.

Debe:

1. inspeccionar;
2. razonar;
3. planificar;
4. implementar;
5. probar;
6. revisar;
7. corregir;
8. volver a probar;
9. documentar.

Si encuentra una decisión ambigua:

- elegir la opción más segura;
- preservar compatibilidad;
- documentar la decisión;
- no bloquear el trabajo innecesariamente.

---

# 63. ORDEN DE PRIORIDAD

Cuando dos objetivos entren en conflicto:

```text
1. Seguridad
2. Integridad de datos
3. Aislamiento multi-tenant
4. Correctitud de inventario
5. Compatibilidad
6. Offline confiable
7. Rendimiento
8. UX
9. Elegancia del código
```

Nunca sacrificar seguridad o integridad por velocidad.

---

# 64. OBJETIVO FINAL DEL PRODUCTO

TuInventario.app debe sentirse así:

## Restaurante

```text
Abro mi workspace
↓
veo solo herramientas de restaurante
↓
creo plato
↓
defino receta
↓
defino contornos
↓
defino bebidas
↓
cliente arma su almuerzo
↓
POS/tienda validan configuración
↓
se calcula precio
↓
se valida disponibilidad
↓
se vende
↓
se consume inventario
↓
se registra Kardex
↓
se puede anular y restaurar
```

## Ferretería

```text
Abro mi workspace
↓
veo ferretería
↓
productos
marcas
proveedores
compras
ventas
barcode
stock
clientes
reportes
```

Sin:

```text
recetas
contornos
bebidas
cocina
```

## Tecnología

```text
productos
marcas
seriales
garantías
compras
ventas
clientes
inventario
reportes
```

## General

```text
CORE LIMPIO
```

---

# 65. PRINCIPIO FINAL

No construir "un restaurante dentro de un inventario genérico".

Construir:

> **un motor de gestión empresarial multi-vertical donde cada negocio recibe únicamente las capacidades que corresponden a su dominio, y donde gastronomía es una especialización poderosa del mismo núcleo.**

El resultado debe ser:

```text
RÁPIDO
SEGURO
MULTI-TENANT
OFFLINE-FIRST
MODULAR
ESCALABLE
MANTENIBLE
COMPATIBLE
```

Y, sobre todo:

> **Si mañana se agrega "farmacia", "salón de belleza", "panadería", "taller mecánico" o "tienda de ropa", no debe ser necesario duplicar el sistema: debe poder crearse un nuevo perfil y sus capacidades especializadas sobre el mismo CORE.**

---

# 66. COMANDO/PROCEDIMIENTO FINAL

Antes de modificar:

```bash
git status
git diff
find .
```

Excluir del análisis pesado:

```text
node_modules
.git
uploads generados
assets binarios grandes
```

pero revisar explícitamente `.git`, `.gitignore` y archivos sensibles cuando corresponda a seguridad.

Después de cada fase:

```text
lint/syntax
tests
migration check
tenant check
security check
```

Antes de entregar:

```text
git diff
git status
test suite
manual smoke test
```

Nunca sobrescribir silenciosamente cambios locales del usuario.

---

# FIN DEL WORK PROMPT
