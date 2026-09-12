# WORK PROMPT V2 --- TuInventario.app

## Basado en auditoría del repositorio real + ZIP actual

**Repositorio oficial:**
https://github.com/MiguelOllarves/ProyectoNegocio

**Regla:** el ZIP proporcionado por el usuario y su working tree son la
fuente de verdad para la implementación. No sobrescribir cambios locales
sin autorización.

------------------------------------------------------------------------

# 1. MISIÓN

Actúa como un equipo senior formado por:

-   Arquitecto PHP/MVC.
-   Arquitecto de base de datos PostgreSQL/SQLite.
-   Ingeniero de seguridad AppSec.
-   Ingeniero de sistemas multi-tenant/SaaS.
-   Ingeniero PWA/offline-first.
-   Ingeniero de POS/restaurante.
-   Ingeniero de performance.
-   QA/Automation Engineer.

Tu trabajo no es agregar solamente "contornos".

Debes evolucionar TuInventario.app hacia un **ERP/POS multi-vertical,
modular, rápido, seguro y offline-first**, manteniendo PHP + PDO +
HTML + Tailwind + Alpine/HTMX/JavaScript y reutilizando el código
existente.

NO reescribas el proyecto desde cero.

------------------------------------------------------------------------

# 2. REGLA ABSOLUTA: AUDITAR ANTES DE EDITAR

Primero inspecciona:

``` text
git status
git diff
git log
git ls-files
composer.json
package.json
config/
core/
database/
includes/
modules/
public/
api/
cron/
tests/
docs/
```

También inspecciona el working tree, no solamente `origin/main`.

En la copia actual existe un working tree con numerosos archivos
modificados respecto al commit `333cc94`; esos cambios deben
considerarse trabajo local y NO deben destruirse.

El repositorio GitHub actualmente contiene el sistema de perfiles,
opciones de restaurante, consumo centralizado y configuración de
productos, pero están parcialmente implementados y tienen
inconsistencias.

------------------------------------------------------------------------

# 3. HALLAZGOS REALES DEL CÓDIGO ACTUAL

## 3.1 Ya existe `BusinessProfileService`

Archivo:

``` text
core/BusinessProfileService.php
```

Ya contiene perfiles:

``` text
gastronomia
ferreteria
viveres
repuestos
tecnologia
vehiculos
bienes_raices
general
```

y features.

## PROBLEMA

El servicio es estático y utiliza:

``` php
$_SESSION['business_category']
```

mientras `Migration.php` también crea:

``` text
business_types
business_type_features
```

Esto genera dos fuentes de verdad.

### CORREGIR

Elegir una arquitectura coherente:

``` text
DB = fuente de verdad de tipos/capabilities configurables
Código = registry/schema/provisioning seguro
```

Puede existir cache/registry de definiciones, pero no mantener dos
sistemas que puedan divergir.

------------------------------------------------------------------------

# 4. PROBLEMA REAL DE WORKSPACE POR VERTICAL

El registro actualmente acepta:

``` text
category
```

desde POST y lo inserta directamente.

Debe validarse contra una lista/tabla válida.

No permitir:

``` text
category = cualquier_string
```

El registro debe crear un workspace con un perfil coherente.

------------------------------------------------------------------------

# 5. PROVISIONAMIENTO ACTUAL

`AuthController::seedBusinessData()` todavía contiene lógica específica:

``` php
$seedProducts = [
    'gastronomia' => ...,
    'viveres' => ...,
    ...
];
```

Debe convertirse en provisioning data-driven.

### Objetivo

``` text
business type
   ↓
profile
   ↓
capabilities
   ↓
initial categories
   ↓
initial settings
   ↓
optional starter data
```

No llenar automáticamente un restaurante con "mercadería general".

No llenar una ferretería con platos.

No llenar tecnología con recetas.

------------------------------------------------------------------------

# 6. MUY IMPORTANTE: NO CONFUNDIR DOS COSAS

## Business Type

Ejemplo:

``` text
gastronomia
ferreteria
tecnologia
```

Define capacidades.

## Product Category

Ejemplo:

Ferretería:

``` text
Herramientas
Tornillería
Electricidad
Plomería
Pintura
```

Restaurante:

``` text
Entradas
Platos Principales
Contornos
Bebidas
Postres
```

Nunca usar `categories` para decidir qué módulos existen.

------------------------------------------------------------------------

# 7. CAMBIO DE TIPO DE NEGOCIO

Si un negocio cambia:

``` text
general → gastronomia
```

NO borrar productos.

NO borrar históricos.

NO duplicar seeds.

Cambiar capacidades y ofrecer migración/configuración cuando sea
necesario.

Si:

``` text
gastronomia → ferreteria
```

las recetas históricas se conservan, pero el menú de navegación puede
ocultarlas según las capabilities.

Registrar el cambio en auditoría.

------------------------------------------------------------------------

# 8. CAPABILITIES REALES

Crear/normalizar capacidades como:

``` text
inventory
sales
purchases
clients
suppliers
brands
barcode
presentations
credits
expenses
cashbox
reports
storefront

recipes
dishes
recipe_costing
restaurant_options
menu
qr_menu
kitchen
preparation_time
delivery

serial_numbers
warranties
```

El router, sidebar, dashboard y controllers deben utilizar capabilities.

------------------------------------------------------------------------

# 9. EL ROUTER ACTUAL NO ES SUFICIENTE

`public/index.php` actualmente protege por feature explícitamente el
módulo:

``` text
restaurant → recipes
```

Pero no existe una política universal para:

``` text
recipes
restaurant_options
serial_numbers
warranties
```

etc.

### Implementar

``` php
Middleware::requireFeature('recipes');
Middleware::requireFeature('restaurant_options');
```

o equivalente.

La protección debe ser backend.

Ocultar el menú NO es seguridad.

------------------------------------------------------------------------

# 10. RESTAURANTE: LO QUE YA EXISTE

Ya existen:

``` text
core/ProductConfigurationService.php
core/InventoryConsumptionService.php
modules/restaurant/models/RestaurantOption.php
restaurant_option_groups
restaurant_options
store_order_items
store_order_item_options
sale_item_options
```

No vuelvas a crearlos si ya existen.

Debes auditarlos y corregirlos.

------------------------------------------------------------------------

# 11. BUG CRÍTICO: IDENTIFICADOR DE OPCIÓN

`restaurant_options` tiene:

``` text
id
product_id
```

El `id` identifica la fila de la opción.

El `product_id` identifica el producto consumible.

Actualmente el frontend y backend mezclan estos conceptos.

Ejemplo:

-   POS usa `opt.id`.
-   Storefront intenta usar `opt.option_id`.
-   `ProductConfigurationService::calculateConfigurationAvailability()`
    interpreta `option_id` como si fuera `product_id`.

Esto es incorrecto.

## MODELO CORRECTO

Frontend:

``` json
{
  "group_id": 1,
  "option_id": 42
}
```

donde:

``` text
42 = restaurant_options.id
```

Backend:

``` text
restaurant_options.id = 42
        ↓
product_id = 301
        ↓
producto Arroz
```

El backend nunca debe asumir que `option_id == product_id`.

------------------------------------------------------------------------

# 12. BUG CRÍTICO: STOREFRONT NO PUEDE CARGAR OPCIONES CORRECTAMENTE

`landing.php` hace:

``` javascript
fetch(BASE_URL + 'restaurant/get_option_groups/' + product.id)
```

Pero `restaurant` es un módulo autenticado.

Un cliente público de la tienda no tiene sesión.

Por lo tanto esa petición puede terminar en login/403 y el código hace:

``` javascript
.catch(() => this._doAddToCart(product, event));
```

Resultado:

> El cliente puede terminar agregando el plato sin sus opciones.

## SOLUCIÓN PREFERIDA

NO hacer una llamada autenticada desde el storefront.

Para máxima velocidad y offline:

``` text
StorefrontController::show()
    ↓
carga productos
    ↓
carga configuración pública de opciones
    ↓
inyecta opciones en el catálogo
```

Ejemplo:

``` json
product.restaurant_options = [...]
```

Así el catálogo público tiene todo lo necesario en una sola respuesta.

No enviar información privada.

------------------------------------------------------------------------

# 13. OPCIONES DEBEN SER GENÉRICAS

No crear:

``` text
tabla_contornos
tabla_bebidas
tabla_salsas
```

Usar:

``` text
restaurant_option_groups
restaurant_options
```

aunque el nombre técnico pueda posteriormente generalizarse a:

``` text
product_option_groups
product_options
```

si la auditoría considera que las opciones ya no son exclusivamente
gastronómicas.

El mismo motor debe servir para:

``` text
Contornos
Bebidas
Salsas
Extras
Tamaños
Toppings
Aderezos
```

------------------------------------------------------------------------

# 14. CONFIGURACIÓN DEL EJEMPLO REAL

Para:

``` text
Milanesa a la plancha
```

configurar:

``` text
Grupo: Contornos
min = 2
max = 2
required = true
```

Opciones:

``` text
Arroz
Pasta
Tajadas
Ensalada Mixta
Ensalada César
Caraotas
Yuca sancochada
Puré de papas
```

Y:

``` text
Grupo: Bebida
min = 1
max = 1
required = true
```

Opciones:

``` text
Agua
Coca-Cola
Malta
Jugo Natural
Ice Tea
Del Valle
```

Los precios adicionales se almacenan en:

``` text
price_delta
```

------------------------------------------------------------------------

# 15. BUG/MEJORA: ADMIN SOLO LISTA PRODUCTOS NO-PLATO

`RestaurantController::options_view()` actualmente carga:

``` sql
WHERE tenant_id = ? AND is_dish = FALSE
```

Esto impide usar como opción un plato elaborado.

Debe permitirse seleccionar:

``` text
producto simple
```

o:

``` text
plato elaborado
```

si el negocio necesita:

``` text
Arroz
Tajadas
Puré
```

como productos con receta.

Pero proteger contra ciclos.

------------------------------------------------------------------------

# 16. VALIDACIÓN DE GRUPOS

Al crear:

``` text
min_selections
max_selections
```

validar:

``` text
min >= 0
max >= 1
min <= max
```

Además:

``` text
max <= número de opciones activas
```

cuando se publica el grupo.

No permitir configuraciones imposibles.

------------------------------------------------------------------------

# 17. VALIDACIÓN DE OPCIONES

Al agregar una opción:

1.  verificar que el grupo pertenece al tenant;
2.  verificar que el producto pertenece al tenant;
3.  verificar que el producto existe;
4.  impedir que el plato padre sea su propia opción;
5.  impedir duplicados;
6.  validar `price_delta`;
7.  validar que la opción es compatible con el tipo de configuración.

Actualmente `RestaurantOption::createGroup()` y `addOption()` reciben
IDs y escriben sin comprobar completamente la pertenencia del objeto al
tenant.

CORREGIR.

------------------------------------------------------------------------

# 18. NO BORRAR HISTÓRICOS

Para productos usados como:

``` text
ingrediente
opción
producto vendido
```

preferir:

``` text
active = false
```

sobre DELETE.

Los pedidos históricos deben conservar snapshots.

------------------------------------------------------------------------

# 19. CARRITO

El carrito no puede usar únicamente:

``` text
product.id
```

como identidad.

Debe existir:

``` text
configurationKey
```

Ejemplo:

``` text
Milanesa + Arroz + Tajadas
```

es diferente de:

``` text
Milanesa + Pasta + Ensalada
```

La clave debe generarse a partir de:

``` text
product_id
grupo/opción normalizados
```

y no depender del orden de selección.

------------------------------------------------------------------------

# 20. NO CONFIAR EN EL PRECIO DEL FRONTEND

Frontend puede mostrar:

``` text
$9.00
```

Pero el backend debe:

1.  cargar precio base;
2.  cargar opciones;
3.  validar grupos;
4.  validar opciones;
5.  cargar `price_delta` desde BD;
6.  calcular total.

Nunca confiar en:

``` text
item.price
```

enviado desde JavaScript.

------------------------------------------------------------------------

# 21. BUG ACTUAL: STOREFRONT VALIDA PERO NO RECHAZA CONFIGURACIÓN INVÁLIDA

El checkout actual hace algo equivalente a:

``` php
$configPrice = validateConfiguration(...)
$price = base + price_delta
```

pero no debe continuar si:

``` text
valid = false
```

Debe devolver:

``` text
422
```

o un error estructurado:

``` json
{
  "success": false,
  "code": "INVALID_CONFIGURATION"
}
```

No crear el pedido.

------------------------------------------------------------------------

# 22. INVENTARIO: MOTOR CENTRAL

Ya existe:

``` text
core/InventoryConsumptionService.php
```

Debe convertirse en la única ruta para consumo.

POS:

``` text
Sale
 → InventoryConsumptionService
```

Storefront:

``` text
Order
 → InventoryConsumptionService
```

No mantener implementaciones paralelas.

------------------------------------------------------------------------

# 23. INVENTARIO DE UNA CONFIGURACIÓN

Ejemplo:

``` text
Milanesa
+ Arroz
+ Tajadas
+ Jugo
```

Debe consumir:

``` text
receta Milanesa
receta Arroz
receta Tajadas
stock Jugo
```

Todo dentro de una transacción.

------------------------------------------------------------------------

# 24. BUG CRÍTICO: CONSUMO DE STOCK SIN PROTECCIÓN DE CARRERA

No basta:

``` sql
SELECT stock
UPDATE stock = stock - ?
```

Dos clientes simultáneos pueden vender más stock del disponible.

Implementar una estrategia segura.

Opciones:

``` sql
UPDATE products
SET stock = stock - :qty
WHERE id = :id
AND tenant_id = :tenant
AND stock >= :qty
```

y comprobar:

``` text
rowCount() === 1
```

Para recetas:

-   bloquear filas relevantes con `FOR UPDATE` cuando PostgreSQL esté
    disponible;
-   o utilizar actualizaciones condicionales y transacción consistente;
-   garantizar rollback completo.

No permitir stock negativo salvo una configuración explícita del
negocio.

------------------------------------------------------------------------

# 25. RECETAS

Mantener:

``` text
UnitConversionService
CostCalculationService
Recipe
```

pero corregir:

-   tenant isolation;
-   cantidades;
-   unidades incompatibles;
-   stock;
-   concurrencia;
-   rollback;
-   Kardex.

------------------------------------------------------------------------

# 26. BUG/MEJORA: DISPONIBILIDAD

Para un plato:

``` text
available = mínimo de ingredientes
```

Para una configuración:

``` text
mínimo de:
- plato base
- cada opción seleccionada
```

Pero primero convertir:

``` text
option_id
→ restaurant_options.product_id
```

No confundir IDs.

------------------------------------------------------------------------

# 27. STOCK DE PLATOS

No usar:

``` text
product.stock
```

como stock real de un plato si su inventario se deriva de recetas.

Usar:

``` text
available_servings
```

calculado desde receta.

Para un producto simple:

``` text
stock
```

Para un plato:

``` text
recipe availability
```

------------------------------------------------------------------------

# 28. KARDEx

Toda salida debe ser trazable.

Ejemplo:

``` text
Venta #100
Milanesa
 ├── Carne -250g
 ├── Pan -50g
 └── Aceite -20ml

Opción:
Arroz
 ├── Arroz crudo -100g
 └── Aceite -5ml
```

Las entradas por anulación deben apuntar a la operación original.

No generar movimientos ambiguos.

------------------------------------------------------------------------

# 29. PEDIDOS ESTRUCTURADOS

Ya existen tablas creadas por migración:

``` text
store_order_items
store_order_item_options
sale_item_options
```

Deben incorporarse correctamente al esquema/migraciones oficiales.

No depender únicamente de `items_json`.

`items_json` puede mantenerse para compatibilidad histórica, pero debe
ser secundario.

------------------------------------------------------------------------

# 30. BUG DE ESQUEMA/MIGRACIÓN ACTUAL

`Migration.php` contiene referencias que deben revisarse porque parecen
apuntar a objetos que no corresponden con las tablas creadas, por
ejemplo:

``` text
restaurant_option_groups(dish_id)
restaurant_option_dishes
```

mientras el modelo actual usa:

``` text
restaurant_option_groups.product_id
restaurant_options.product_id
```

Eliminar referencias obsoletas.

Una migración nunca debe intentar crear índices sobre columnas/tablas
inexistentes y simplemente ocultar el error.

Los errores de migración deben clasificarse:

``` text
expected compatibility
vs
critical migration failure
```

------------------------------------------------------------------------

# 31. MIGRACIONES: NO SILENCIAR TODO

Actualmente hay muchos:

``` php
catch (\Exception $e) {}
```

Esto puede ocultar errores reales.

Implementar:

``` text
MigrationResult
```

o logs estructurados.

Si una migración crítica falla:

``` text
la aplicación debe saberlo
```

No continuar fingiendo que todo está bien.

------------------------------------------------------------------------

# 32. POSTGRESQL / SQLITE

El proyecto declara soporte para ambos.

Pero `Migration.php` usa cosas como:

``` text
SERIAL
DATETIME
```

de forma mezclada.

Diseñar migraciones compatibles con ambos drivers.

No afirmar compatibilidad SQLite si una migración crítica solo funciona
en PostgreSQL.

------------------------------------------------------------------------

# 33. SCHEMA OFICIAL

Las nuevas tablas no deben existir solamente por auto-migración.

Actualizar:

``` text
database/schema.sql
database/schema_postgres.sql
```

y la estrategia de migraciones.

El esquema oficial debe representar el estado esperado.

------------------------------------------------------------------------

# 34. PROBLEMA REAL: REGISTRO NO ES ATÓMICO

Actualmente el negocio y usuario se crean y luego:

``` text
commit
↓
seedBusinessData()
```

Eso significa que el negocio puede existir aunque falle el provisioning.

Corregir a:

``` text
BEGIN
  create business
  create owner
  provision profile
  create default config
COMMIT
```

El envío de correo/push ocurre DESPUÉS del commit.

Si el correo falla:

``` text
no rollback del negocio
```

------------------------------------------------------------------------

# 35. REGISTRO: VALIDACIÓN DE BUSINESS TYPE

Nunca aceptar arbitrariamente:

``` text
$_POST['category']
```

Validar:

``` text
exists
active
allowed
```

y guardar la relación normalizada:

``` text
business_type_id
```

manteniendo `category` temporalmente si hace falta compatibilidad.

------------------------------------------------------------------------

# 36. BUSINESS TYPES EN BD

`Migration.php` ya crea:

``` text
business_types
business_type_features
```

pero `BusinessProfileService` sigue usando arrays PHP.

Unificar.

Objetivo:

``` text
businesses.business_type_id
```

con FK.

Las capabilities pueden estar en:

``` text
business_type_features
```

y, si se necesita personalización por tenant:

``` text
business_features
```

------------------------------------------------------------------------

# 37. CAPABILITIES POR TENANT

Diseñar posibilidad futura:

``` text
business type defaults
+
tenant overrides
```

Ejemplo:

``` text
Ferretería
```

normalmente no tiene:

``` text
recipes
```

pero un administrador podría activar una capability experimental si el
producto la soporta.

Nunca activar capabilities incompatibles por accidente.

------------------------------------------------------------------------

# 38. SEGURIDAD MULTI-TENANT

Hacer auditoría global de:

``` text
SELECT
UPDATE
DELETE
INSERT
JOIN
```

sobre:

``` text
products
categories
brands
suppliers
clients
recipe_items
restaurant_option_groups
restaurant_options
sales
sale_items
purchases
purchase_items
kardex
credits
expenses
store_orders
settings
notifications
```

Regla:

> Un ID no es autorización.

------------------------------------------------------------------------

# 39. HALLAZGOS REALES DE CONSULTAS

Hay consultas sin tenant directo que dependen indirectamente de:

``` text
users.business_id
```

por ejemplo en créditos, compras y algunas vistas.

Eso debe auditarse.

Para entidades transaccionales críticas, considerar agregar:

``` text
tenant_id
```

directamente.

Especialmente:

``` text
sales
purchases
expenses
cashbox
```

si la arquitectura lo permite sin romper históricos.

------------------------------------------------------------------------

# 40. MODELO DE TENANT

Establecer claramente:

``` text
business = tenant
user.business_id = tenant
```

Toda operación autenticada debe resolver tenant desde sesión confiable.

No aceptar:

``` text
tenant_id
business_id
```

del navegador para acciones administrativas.

------------------------------------------------------------------------

# 41. STOREFRONT PÚBLICO

Aquí sí existe un `business_id`/slug público.

Eso es correcto conceptualmente, pero debe validarse:

``` text
business existe
store publicado
```

y el storefront solo debe acceder a datos públicos del tenant indicado
por slug.

Nunca permitir que un `business_id` enviado por el cliente cambie el
tenant de una sesión autenticada.

------------------------------------------------------------------------

# 42. BUG DE SEGURIDAD IMPORTANTE EN STOREFRONT CHECKOUT

Actualmente el checkout hace:

``` php
if (!isset($_SESSION['business_id'])) {
    $_SESSION['business_id'] = $data['business_id'];
}
```

Esto debe tratarse con extremo cuidado.

Una petición pública no debe convertir un `business_id` arbitrario en
tenant de una sesión autenticada existente.

Usar contexto de tienda explícito:

``` text
StoreContext
```

separado de:

``` text
AuthenticatedTenantContext
```

No contaminar la sesión del usuario.

------------------------------------------------------------------------

# 43. CHECKOUT PÚBLICO

El checkout debe:

``` text
resolver tienda por slug/contexto firmado o servidor
↓
obtener tenant
↓
validar pedido
```

Preferir:

``` text
POST /tienda/{slug}/checkout
```

o un token/contexto de tienda validado.

No confiar solamente en:

``` text
business_id
```

en JSON.

------------------------------------------------------------------------

# 44. IDEMPOTENCIA

Crear soporte para:

``` text
idempotency_key
```

en:

``` text
checkout
sales
offline sync
```

Una petición repetida no debe duplicar:

``` text
pedido
venta
consumo
Kardex
crédito
```

------------------------------------------------------------------------

# 45. CRÉDITOS

Revisar especialmente:

``` text
Storefront → Credit
```

El pedido online a crédito debe tener una referencia clara.

Evitar crear crédito duplicado si se reintenta checkout.

Ideal:

``` text
credit.source_type = store_order
credit.source_id = order_id
```

o equivalente.

------------------------------------------------------------------------

# 46. OFFLINE-FIRST

El proyecto ya tiene:

``` text
public/sw.js
public/offline.html
public/manifest.json
```

Eso NO significa que sea offline-first.

Implementar por capas.

------------------------------------------------------------------------

# 47. FASE OFFLINE 1

Prioridad:

``` text
POS
Catálogo
Carrito
```

Debe funcionar sin conexión después de una sincronización inicial.

------------------------------------------------------------------------

# 48. INDEXEDDB

Crear stores:

``` text
catalog
products
categories
customers
restaurant_options
settings_snapshot
pending_operations
sync_metadata
```

No almacenar secretos.

------------------------------------------------------------------------

# 49. OPERACIONES OFFLINE

Cada operación:

``` text
operation_id
idempotency_key
tenant_id
device_id
user_id
type
payload
created_at
attempts
status
last_error
```

El servidor debe validar nuevamente todo al sincronizar.

------------------------------------------------------------------------

# 50. STOCK OFFLINE

El stock offline es un snapshot.

Mostrar:

``` text
Stock sincronizado: 18
Última sincronización: 07:40
```

No afirmar:

``` text
Stock real: 18
```

cuando existen otros dispositivos.

------------------------------------------------------------------------

# 51. CONFLICTO OFFLINE

Servidor autoritativo.

Si dos dispositivos venden la última unidad:

``` text
dispositivo A sincroniza → éxito
dispositivo B sincroniza → conflicto
```

Nunca crear stock negativo silenciosamente.

Registrar:

``` text
SYNC_CONFLICT
```

------------------------------------------------------------------------

# 52. SERVICE WORKER

Revisar completamente `public/sw.js`.

No hacer cache indiscriminado de:

``` text
páginas privadas
respuestas con datos sensibles
sesiones
```

El catálogo público sí puede cachearse de forma segura.

Los assets críticos deben existir localmente para no depender de CDN.

------------------------------------------------------------------------

# 53. CDN Y VELOCIDAD

Actualmente hay dependencias externas como:

``` text
Font Awesome CDN
Google Fonts
Alpine CDN
```

Para modo offline real:

-   empaquetar localmente dependencias críticas;
-   evitar que POS dependa de Internet para arrancar;
-   mantener payload pequeño.

------------------------------------------------------------------------

# 54. PERFORMANCE

No migrar a React/Vue.

Mantener:

``` text
PHP server-side
HTMX
Alpine
Vanilla JS
```

cuando sean suficientes.

------------------------------------------------------------------------

# 55. PERFORMANCE BACKEND

Auditar N+1.

Ejemplo actual de restaurante:

``` text
por cada plato:
  calculateCost()
  getAvailableServings()
  getForDish()
```

Esto puede multiplicarse por cientos de platos.

Crear consultas/batches/caches apropiados.

No hacer:

``` text
1 plato = 10 queries
100 platos = 1000 queries
```

------------------------------------------------------------------------

# 56. STOREFRONT RÁPIDO

En lugar de:

``` text
cargar producto
↓
hacer fetch de opciones
↓
esperar
```

usar:

``` text
catálogo
+
configuración pública
```

en la primera respuesta.

Esto también mejora offline.

------------------------------------------------------------------------

# 57. IMÁGENES

El proyecto usa base64 en varios lugares.

Auditar el costo.

Base64 en HTML/JSON puede aumentar muchísimo el payload.

Para productos grandes:

preferir archivos/URLs optimizados cuando el entorno lo permita.

Si se mantiene base64:

-   limitar dimensiones;
-   comprimir;
-   lazy load;
-   no enviar imagen completa donde no haga falta.

------------------------------------------------------------------------

# 58. PRODUCT MODEL

No convertir `products` en una tabla monstruosa.

Core:

``` text
products
categories
brands
inventory
```

Especializaciones:

``` text
restaurant
technology
automotive
etc.
```

`dynamic_attributes` sirve para atributos simples, no para reemplazar
dominios complejos.

------------------------------------------------------------------------

# 59. RESTAURANTE Y PRODUCTOS

Un plato sigue siendo:

``` text
products.is_dish = true
```

y su receta:

``` text
recipe_items
```

Las opciones apuntan a productos.

Esto permite reutilizar:

``` text
stock
recipe
cost
price
image
```

------------------------------------------------------------------------

# 60. CICLOS DE RECETAS

No permitir:

``` text
A → B
B → A
```

ni ciclos indirectos.

Validar antes de guardar configuraciones/recetas.

------------------------------------------------------------------------

# 61. ADMIN RESTAURANTE

La pantalla de opciones debe permitir:

``` text
Crear grupo
Editar grupo
Desactivar grupo
Eliminar grupo solo si no rompe históricos
Agregar opción
Editar opción
Desactivar opción
Ordenar
```

Debe mostrar:

``` text
mínimo
máximo
obligatorio
disponibilidad
precio adicional
```

------------------------------------------------------------------------

# 62. PRODUCT CONFIGURATION SERVICE

Convertirlo en dominio central.

Debe exponer:

``` text
loadConfiguration()
normalizeConfiguration()
validateConfiguration()
calculatePrice()
calculateAvailability()
buildSnapshot()
buildConfigurationKey()
```

El mismo servicio:

``` text
Storefront
POS
QR Menu
```

------------------------------------------------------------------------

# 63. INVENTORY CONSUMPTION SERVICE

Debe exponer:

``` text
checkAvailability()
consume()
restore()
consumeConfiguredProduct()
restoreConfiguredProduct()
calculateCost()
```

No duplicar consumo en:

``` text
Sale.php
StorefrontController.php
```

------------------------------------------------------------------------

# 64. SALE.PHP

Actualmente `Sale::createSale()` tiene lógica directa para:

``` text
stock
recipe
options
Kardex
```

Refactorizar para delegar al servicio.

`Sale` debe orquestar la transacción, no duplicar toda la lógica de
inventario.

------------------------------------------------------------------------

# 65. STORE FRONT CHECKOUT

Debe ser una operación transaccional:

``` text
BEGIN

resolve tenant/store
validate request
validate idempotency
load products
normalize items
validate configurations
calculate server prices
validate inventory
create order
create order items
create options
consume inventory
create Kardex
create credit if applicable
audit

COMMIT
```

Notificaciones/correo:

``` text
after commit
```

------------------------------------------------------------------------

# 66. ORDER STATUS

Definir estados claros:

``` text
pendiente
confirmado
preparando
listo
despachado
cancelado
```

Solo estados permitidos.

Las transiciones deben estar controladas.

------------------------------------------------------------------------

# 67. CANCELACIÓN

Al cancelar un pedido confirmado que ya consumió inventario:

``` text
restoreConfiguredProduct()
```

una sola vez.

Evitar doble restauración.

Usar:

``` text
inventory_consumption_records
```

o un mecanismo equivalente de idempotencia/trazabilidad si hace falta.

------------------------------------------------------------------------

# 68. SEGURIDAD: CSRF

Todas las acciones autenticadas mutables deben validar CSRF.

No confiar solamente en:

``` text
X-CSRF-Token
```

si el backend no lo comprueba.

Auditar:

``` text
save_option
delete_option
delete_group
restaurant create/edit
storefront config
sales
settings
users
credits
```

------------------------------------------------------------------------

# 69. SEGURIDAD: SESIONES

Implementar/revisar:

``` text
session_regenerate_id(true)
HttpOnly
Secure
SameSite
timeout
logout
```

No mezclar contexto público de tienda con contexto de tenant
autenticado.

------------------------------------------------------------------------

# 70. RATE LIMITING

El rate limit actual usa:

``` text
HTTP_CF_CONNECTING_IP
HTTP_X_FORWARDED_FOR
REMOTE_ADDR
```

No confiar ciegamente en headers de proxy.

Configurar proxies confiables.

Para acciones críticas usar:

``` text
IP + usuario + acción
```

cuando corresponda.

------------------------------------------------------------------------

# 71. DEBUG

Auditar:

``` text
public/test_env.php
public/test_products.php
public/test_edit_load.php
public/test_buffer.php
public/debug.php
public/debug_inventory.php
```

Si existen en el working tree aunque no estén en Git, impedir que
lleguen a producción.

Revisar especialmente:

``` text
public/reset_database.sql
```

y cualquier endpoint destructivo.

------------------------------------------------------------------------

# 72. `.env`

El ZIP contiene `.env`, aunque `.gitignore` lo excluye.

No imprimir sus valores.

Verificar:

``` text
git ls-files
git log --all -- .env
```

Si algún secreto estuvo versionado:

``` text
rotar
invalidar
reemplazar
```

No basta con borrar el archivo actual.

------------------------------------------------------------------------

# 73. SECRETS

Buscar:

``` text
password
token
API_KEY
SECRET
VAPID
DATABASE_URL
SMTP
```

No dejar credenciales de producción.

El README actual contiene credenciales/demo mencionadas como:

``` text
admin / admin123
```

No usar credenciales débiles en producción.

Cambiar documentación para no fomentar cuentas inseguras.

------------------------------------------------------------------------

# 74. SQL INJECTION

`Model::paginate()` concatena `orderBy`.

Aunque normalmente provenga de código interno, crear whitelist.

Nunca permitir:

``` text
$_GET['sort']
```

directamente.

------------------------------------------------------------------------

# 75. UPLOADS

Auditar:

``` text
logo
product image
menu
```

Validar:

``` text
MIME real
tamaño
dimensiones
extensión
contenido
```

No permitir ejecución de archivos subidos.

------------------------------------------------------------------------

# 76. HEADERS

Implementar:

``` text
Content-Security-Policy
X-Content-Type-Options: nosniff
Referrer-Policy
Permissions-Policy
HSTS si HTTPS
frame-ancestors
```

La CSP debe ser compatible con Alpine/HTMX y mejorarse gradualmente.

------------------------------------------------------------------------

# 77. ERRORES

No mostrar:

``` text
SQL
PDOException
stack trace
filesystem path
secret
```

En producción:

``` text
mensaje amigable
correlation/request id
log interno
```

------------------------------------------------------------------------

# 78. AUDIT LOG

Registrar cambios sensibles:

``` text
login
logout
business_type_changed
feature_changed
product_created
product_updated
product_deactivated
recipe_changed
option_changed
sale_created
sale_voided
order_created
order_cancelled
sync_conflict
```

Nunca registrar contraseñas/tokens.

------------------------------------------------------------------------

# 79. BUSINESS TYPE: PERFIL RECOMENDADO

## Gastronomía

``` text
inventory
sales
purchases
recipes
dishes
recipe_costing
menu
qr_menu
restaurant_options
kitchen
preparation_time
delivery
storefront
clients
credits
reports
cashbox
expenses
```

## Ferretería

``` text
inventory
sales
purchases
brands
suppliers
barcode
presentations
clients
credits
reports
cashbox
expenses
storefront
```

Sin:

``` text
recipes
dishes
restaurant_options
kitchen
```

## Víveres

``` text
inventory
sales
purchases
barcode
brands
suppliers
clients
credits
reports
cashbox
expenses
storefront
```

## Tecnología

``` text
inventory
sales
purchases
serial_numbers
warranties
brands
suppliers
clients
reports
cashbox
expenses
storefront
```

## Repuestos

``` text
inventory
sales
purchases
brands
suppliers
barcode
presentations
clients
credits
reports
cashbox
expenses
storefront
```

## General

Solo CORE.

------------------------------------------------------------------------

# 80. BIENES RAÍCES Y VEHÍCULOS

No forzar estos perfiles a comportarse como inventario tradicional si el
dominio no está implementado.

Ocultar capacidades incompatibles.

Preparar extensiones especializadas sin inventar datos.

------------------------------------------------------------------------

# 81. TEST MATRIX

Crear pruebas para:

## Tenant

``` text
A no lee B
A no modifica B
A no elimina B
A no usa receta B
A no usa opción B
A no usa pedido B
```

## Restaurant

``` text
min/max
required
duplicados
opción inválida
opción de otro tenant
precio
disponibilidad
consumo
rollback
cancelación
```

## Storefront

``` text
catálogo público
configuración
checkout
precio manipulado
business_id manipulado
idempotencia
stock
crédito
```

## POS

``` text
producto simple
plato
plato configurado
opciones
stock
receta
Kardex
anulación
```

## Offline

``` text
catálogo offline
venta offline
cola
sync
reintento
idempotencia
conflicto
logout
cambio de tenant
```

------------------------------------------------------------------------

# 82. TEST DE VERTICAL

Crear fixtures:

``` text
Restaurante Demo
Ferretería Demo
Víveres Demo
Tecnología Demo
General Demo
```

Comprobar:

### Restaurante

Tiene:

``` text
Recetas
Platos
Opciones
Menú
```

### Ferretería

NO tiene:

``` text
Recetas
Contornos
Bebidas
Cocina
```

### Tecnología

Tiene:

``` text
Seriales
Garantías
```

si están implementados.

------------------------------------------------------------------------

# 83. PERFORMANCE TEST

Medir:

``` text
página POS inicial
búsqueda producto
agregar producto
abrir configurador
checkout
dashboard
storefront
```

Comparar:

``` text
antes
después
```

Evitar optimizaciones basadas únicamente en percepción.

------------------------------------------------------------------------

# 84. MIGRACIÓN DE DATOS EXISTENTES

No romper:

``` text
businesses.category
```

durante migración.

Crear:

``` text
business_type_id
```

y hacer backfill:

``` text
category → business_type
```

Después de verificar todos los registros:

``` text
category
```

puede quedar como compatibilidad o retirarse en una migración posterior.

------------------------------------------------------------------------

# 85. DATA INTEGRITY

Agregar constraints donde sea seguro:

``` text
FK
UNIQUE
CHECK
NOT NULL
```

Especialmente:

``` text
restaurant_option_groups
restaurant_options
```

Ejemplo:

``` text
UNIQUE(group_id, product_id)
```

para evitar la misma opción duplicada.

------------------------------------------------------------------------

# 86. MONETARY PRECISION

Revisar uso de:

``` text
REAL
float
```

para dinero.

Preferir:

``` text
NUMERIC(15,2)
```

en PostgreSQL.

Si SQLite requiere compatibilidad, centralizar el tratamiento.

No depender de aritmética binaria de floats para totales fiscales.

------------------------------------------------------------------------

# 87. FISCALIDAD

No alterar:

``` text
IVA
IGTF
tasas
```

sin tests.

El precio base + opciones debe integrarse correctamente con la capa
fiscal existente.

------------------------------------------------------------------------

# 88. DOCUMENTACIÓN

Actualizar:

``` text
README.md
```

para reflejar:

``` text
multi-vertical
restaurant options
offline-first
security
```

Crear:

``` text
docs/business-profiles.md
docs/restaurant-options.md
docs/offline-first.md
docs/security.md
docs/architecture.md
```

------------------------------------------------------------------------

# 89. ORDEN DE IMPLEMENTACIÓN

## FASE 0

Auditoría.

Entregar:

``` text
docs/AUDIT.md
docs/SECURITY_AUDIT.md
docs/TENANT_AUDIT.md
docs/PERFORMANCE_AUDIT.md
docs/OFFLINE_AUDIT.md
```

## FASE 1

Seguridad + tenant.

## FASE 2

Business Profiles.

## FASE 3

Provisioning.

## FASE 4

Inventory domain service.

## FASE 5

Restaurant options.

## FASE 6

POS/storefront.

## FASE 7

Structured orders.

## FASE 8

Offline.

## FASE 9

Performance.

## FASE 10

Tests.

## FASE 11

Documentación + release.

------------------------------------------------------------------------

# 90. CADA FASE DEBE HACER

``` text
inspeccionar
↓
planificar
↓
implementar
↓
lint
↓
tests
↓
revisar diff
↓
buscar regresiones
↓
corregir
```

No saltar directamente a la siguiente fase con tests fallando.

------------------------------------------------------------------------

# 91. NO HACER

No:

``` text
reescribir todo
migrar a React
migrar a Laravel sin necesidad
crear duplicados de productos
crear tablas contornos/bebidas
confiar en precios JS
confiar en tenant_id enviado por cliente
ocultar módulos solo con CSS
usar stock offline como verdad global
silenciar errores críticos
borrar históricos
hacer checkout no idempotente
```

------------------------------------------------------------------------

# 92. DEFINITION OF DONE

La tarea está terminada solo cuando:

``` text
[ ] restaurante funciona con opciones
[ ] opciones funcionan en POS
[ ] opciones funcionan en storefront
[ ] opciones funcionan en QR/menu donde corresponda
[ ] inventario se consume correctamente
[ ] recetas se consumen correctamente
[ ] Kardex registra todo
[ ] cancelación restaura correctamente
[ ] checkout es transaccional
[ ] checkout es idempotente
[ ] multi-tenant está aislado
[ ] business profiles funcionan
[ ] workspace se adapta al vertical
[ ] no se crean módulos incompatibles
[ ] offline funciona para POS/catalogo
[ ] sincronización funciona
[ ] conflictos se detectan
[ ] seguridad auditada
[ ] debug protegido/eliminado
[ ] secretos fuera del código
[ ] migraciones coherentes
[ ] PostgreSQL validado
[ ] SQLite validado o su soporte corregido/documentado
[ ] tests pasan
[ ] performance medida
[ ] documentación actualizada
```

------------------------------------------------------------------------

# 93. REPORTE FINAL

Entregar:

``` text
IMPLEMENTATION_REPORT.md
```

Debe incluir:

1.  problemas encontrados;
2.  arquitectura final;
3.  migraciones;
4.  tablas;
5.  servicios;
6.  capabilities;
7.  perfiles;
8.  restaurante;
9.  inventario;
10. offline;
11. seguridad;
12. performance;
13. tests;
14. compatibilidad;
15. riesgos pendientes;
16. instrucciones de deploy;
17. rollback.

------------------------------------------------------------------------

# 94. RESULTADO QUE QUIERO

TuInventario.app debe poder hacer:

``` text
REGISTRO
↓
"Soy restaurante"
↓
workspace gastronómico limpio
↓
creo Milanesa
↓
creo receta
↓
creo Contornos
↓
creo Bebidas
↓
publico catálogo
↓
cliente selecciona:
  Milanesa
  + Arroz
  + Tajadas
  + Jugo
↓
servidor recalcula precio
↓
servidor valida inventario
↓
crea pedido
↓
consume:
  receta Milanesa
  receta Arroz
  receta Tajadas
  stock Jugo
↓
Kardex
↓
POS ve el mismo pedido
↓
cancelación restaura todo
```

Y:

``` text
REGISTRO
↓
"Soy ferretería"
↓
workspace ferretería
↓
Herramientas
Tornillería
Electricidad
Plomería
Pintura
↓
SIN recetas
SIN contornos
SIN cocina
```

Y:

``` text
REGISTRO
↓
"Soy tecnología"
↓
inventario
seriales
garantías
marcas
ventas
compras
```

------------------------------------------------------------------------

# 95. PRINCIPIO ARQUITECTÓNICO DEFINITIVO

No construir:

> "un inventario que también tiene restaurante".

Construir:

> **un CORE empresarial multi-vertical con módulos y capacidades
> especializadas por dominio.**

Gastronomía es una especialización.

Ferretería es otra.

Tecnología es otra.

El CORE debe permanecer pequeño, rápido y estable.

------------------------------------------------------------------------

# 96. CRITERIO DE EXCELENCIA

Si una solución funciona pero:

-   duplica lógica,
-   rompe offline,
-   mezcla tenants,
-   depende del frontend,
-   genera N+1,
-   destruye históricos,
-   agrega frameworks innecesarios,
-   oculta errores,
-   o deja datos inconsistentes,

NO está terminada.

La implementación debe priorizar:

``` text
SEGURIDAD
↓
INTEGRIDAD
↓
MULTI-TENANT
↓
CORRECTITUD
↓
OFFLINE
↓
PERFORMANCE
↓
UX
```

**Empieza por la auditoría real del repositorio y continúa hasta dejar
el sistema probado y documentado.**
