# PROMPT MAESTRO — AUDITORÍA Y REFACTORIZACIÓN OBLIGATORIA DEL MOTOR DE INVENTARIO, UNIDADES Y COSTOS

## CONTEXTO

Voy a proporcionarte un proyecto completo en un archivo ZIP llamado:

`ProyectoNegocio.zip`

Este proyecto contiene un sistema de gestión de negocio/restaurante con inventario, compras, productos, unidades de medida, recetas/platos, ventas, kardex, costos y posiblemente otros módulos relacionados.

Necesito que hagas una **auditoría técnica completa y una refactorización real del código**, no una explicación superficial.

Hay un problema crítico en el sistema:

> Si registro, por ejemplo, 40 kg de queso y el costo máximo del queso es $5 por kg, cuando creo un plato y agrego 100 gramos de queso, el sistema puede mostrar un costo como $7.50.

Eso es matemáticamente imposible.

La matemática correcta es:

```text
1 kg = 1000 g

40 kg de queso
costo = $5/kg

100 g = 0.1 kg

0.1 × $5 = $0.50
```

Por lo tanto:

```text
100 g de queso = $0.50
```

NO:

```text
100 g = $7.50
```

Quiero que investigues y corrijas la causa real en TODO el proyecto.

---

# REGLA PRINCIPAL

## NO CONFÍES EN MIS SUPOSICIONES NI EN LAS TUYAS

Debes inspeccionar físicamente el ZIP completo.

No puedes responder basándote únicamente en nombres de archivos, patrones típicos, memoria o suposiciones.

Debes:

1. Descomprimir el ZIP.
2. Recorrer todos los directorios.
3. Identificar todos los archivos.
4. Leer todos los archivos relevantes.
5. Buscar todas las referencias relacionadas con:
   - inventario
   - stock
   - productos
   - compras
   - costos
   - `unit_cost`
   - `cost`
   - `price`
   - unidades
   - gramos
   - kilogramos
   - litros
   - mililitros
   - recetas
   - ingredientes
   - platos
   - ventas
   - kardex
   - conversiones
   - `conversion_factor`
   - `conversion_to_base`
   - `content_per_purchase`
   - `purchase_unit`
   - `sale_unit`
   - `base_unit`
   - `quantity`
   - `stock`
   - `cost_at_sale`
   - cualquier cálculo relacionado con costos.

NO hagas una búsqueda superficial.

Debes rastrear el flujo completo de datos:

```text
COMPRA
   ↓
INVENTARIO
   ↓
STOCK
   ↓
COSTO
   ↓
UNIDAD BASE
   ↓
RECETA
   ↓
INGREDIENTE
   ↓
COSTO DEL INGREDIENTE
   ↓
COSTO DEL PLATO
   ↓
VENTA
   ↓
KARDEX
   ↓
REPORTES
```

---

# REGLA ANTI-ALUCINACIÓN

Este proyecto debe modificarse sobre código real.

## Está PROHIBIDO:

- inventar archivos que no existen;
- inventar tablas que no existen sin antes justificar su creación;
- inventar columnas;
- asumir que una función existe;
- asumir que una clase existe;
- asumir que una API existe;
- decir "ya está corregido" sin haber modificado el código;
- crear soluciones ficticias;
- dar pseudocódigo como si fuera implementación;
- reemplazar código real por ejemplos;
- ocultar errores;
- usar conversiones arbitrarias;
- asumir que kg significa gramos;
- asumir que litros equivalen a unidades;
- asumir factores de conversión;
- usar `1` como factor de conversión cuando falta información;
- redondear para esconder errores;
- modificar solamente el frontend para ocultar un error del backend.

Si no sabes algo:

**DEBES INSPECCIONAR EL CÓDIGO Y CONFIRMARLO.**

Si todavía no puede confirmarse:

```text
NO CONFIRMADO
```

y debes explicar exactamente qué archivo/dato falta.

---

# OBJETIVO PRINCIPAL

Debes conseguir que TODO el sistema tenga una única lógica matemática para:

- unidades;
- conversiones;
- cantidades;
- stock;
- costos;
- compras;
- recetas;
- ingredientes;
- platos;
- ventas;
- kardex;
- reportes.

No quiero múltiples motores de conversión.

No quiero conversiones duplicadas en JavaScript y PHP.

No quiero que cada módulo haga sus propios cálculos.

Quiero:

```text
UN ÚNICO MOTOR DE UNIDADES
+
UN ÚNICO MOTOR DE COSTOS
```

---

# PROBLEMA ARQUITECTÓNICO CRÍTICO

Debes investigar especialmente si el campo:

```text
unit_cost
```

está siendo utilizado para representar conceptos diferentes.

Por ejemplo:

```text
unit_cost = costo total de la compra
```

en un módulo y:

```text
unit_cost = costo por kg
```

en otro módulo.

Eso es INCORRECTO.

Un mismo campo no puede tener dos significados económicos.

---

# CONCEPTO CORRECTO

Debemos distinguir obligatoriamente:

```text
1. costo total de compra
2. cantidad comprada
3. unidad de compra
4. cantidad normalizada
5. unidad base
6. costo por unidad base
7. unidad de venta
8. costo por unidad de venta
9. cantidad consumida
10. costo del consumo
```

---

# EJEMPLO CANÓNICO QUE DEBES IMPLEMENTAR

Supongamos:

```text
Producto:
Queso

Cantidad comprada:
40 kg

Costo total:
$200

Unidad de compra:
kg

Unidad base:
g
```

El sistema debe calcular:

```text
40 kg = 40,000 g

$200 / 40 kg = $5/kg

$200 / 40,000 g = $0.005/g
```

Por tanto:

```text
1 g = $0.005
100 g = $0.50
500 g = $2.50
1000 g = $5.00
1 kg = $5.00
```

Todos estos resultados deben ser matemáticamente equivalentes.

---

# REGLA DE ORO DEL COSTO

El sistema debe tener una única fuente de verdad para el costo.

Recomendación:

```text
cost_per_base_unit
```

Ejemplo:

```text
$0.005/g
```

A partir de eso se puede obtener:

```text
cost_per_sale_unit
```

Por ejemplo:

```text
$5/kg
```

Pero el costo de una receta debe calcularse preferentemente desde la unidad base.

---

# FÓRMULA OBLIGATORIA

Para cualquier ingrediente:

```text
cantidad_base =
cantidad_receta × factor_de_conversion
```

Después:

```text
costo_ingrediente =
cantidad_base × costo_por_unidad_base
```

Ejemplo:

```text
100 g
×
$0.005/g
=
$0.50
```

---

# EJEMPLO CON UNIDAD DIFERENTE

Si la receta utiliza:

```text
0.1 kg
```

y la unidad base es:

```text
g
```

entonces:

```text
0.1 kg = 100 g
```

y:

```text
100 × $0.005 = $0.50
```

El resultado debe ser idéntico al caso de:

```text
100 g
```

---

# REGLA DE EQUIVALENCIA

Estos tres cálculos deben producir exactamente el mismo costo:

```text
100 g
```

```text
0.1 kg
```

```text
100 × $0.005/g
```

Todos deben producir:

```text
$0.50
```

No debe existir ninguna diferencia.

---

# CASO DE $7.50

Si el sistema muestra:

```text
100 g = $7.50
```

debes detectar el origen.

Si:

```text
$5/kg
```

entonces:

```text
$7.50 / $5 = 1.5 kg
```

Por tanto:

```text
$7.50
```

corresponde a:

```text
1.5 kg
```

y NO:

```text
100 g
```

Debes rastrear por qué el sistema podría estar interpretando 100 g como 1.5 kg, o por qué está utilizando un costo unitario incorrecto.

---

# UNIDADES

Debes auditar completamente el sistema de unidades.

Debes encontrar todos los sistemas existentes.

Especialmente busca:

```text
units_of_measure
conversion_to_base
base_unit_id
sale_unit_id
purchase_unit_id
contained_unit_id
content_per_purchase
conversion_factor
units_per_bulk
bulk_cost
unit_of_measure
```

Si existen dos sistemas diferentes para conversiones:

```text
SISTEMA NUEVO
```

y:

```text
SISTEMA ANTIGUO
```

debes determinar cuál debe quedar como fuente de verdad.

No permitas que ambos calculen independientemente.

---

# MOTOR DE UNIDADES

Debe existir conceptualmente una sola función central:

```text
convertQuantity(
    quantity,
    fromUnit,
    toUnit
)
```

Debe utilizar una única fuente de datos.

Por ejemplo:

```text
1 kg = 1000 g
1 L = 1000 ml
```

pero NUNCA:

```text
1 kg = 1 unidad
```

a menos que el producto explícitamente defina que una unidad representa 1 kg.

---

# PROHIBICIÓN DE CONVERSIONES MÁGICAS

Está prohibido hacer cosas como:

```javascript
quantity * 1000
```

porque "probablemente es kg".

También está prohibido:

```javascript
if (unit === 'kg') ...
```

si eso duplica la lógica del motor de unidades.

Toda conversión debe pasar por el motor central.

---

# PROHIBICIÓN DE SUPOSICIONES

Nunca hacer:

```text
"asumimos Kg -> g"
```

Nunca:

```text
"si no existe unidad, usamos 1"
```

Nunca:

```text
"si no conocemos el factor, usamos 1"
```

Nunca:

```text
"si falla la conversión, usamos el factor antiguo"
```

Eso debe convertirse en error explícito.

---

# TIPOS DE UNIDADES

Debes distinguir por dimensión física.

Por ejemplo:

```text
MASS
g
kg
mg
lb
oz

VOLUME
ml
L
gal

COUNT
unidad
pieza
docena

LENGTH
cm
m

AREA
m2
cm2
```

No debe ser posible convertir:

```text
kg → litros
```

sin una densidad/product-specific conversion explícita.

Tampoco:

```text
kg → unidades
```

sin una relación explícitamente definida.

---

# INVENTARIO

Audita completamente el módulo de inventario.

Debes determinar:

1. qué unidad utiliza `stock`;
2. si el stock está almacenado en unidad base;
3. cómo entra una compra;
4. cómo se normaliza;
5. cómo se registra el costo;
6. cómo se actualiza el stock;
7. cómo se revierte;
8. cómo se ajusta;
9. cómo se consume;
10. cómo se muestra.

---

# REGLA DE STOCK

Recomendación obligatoria:

```text
STOCK SIEMPRE EN UNIDAD BASE
```

Ejemplo:

```text
40 kg
```

si la base es gramos:

```text
40000 g
```

Nunca almacenar:

```text
stock = 40
```

y luego intentar recordar que eran kg.

El número de stock debe tener una unidad semánticamente definida.

---

# COMPRAS

Una compra debe distinguir:

```text
quantity
purchase_unit
total_cost
```

Ejemplo:

```text
quantity = 40
purchase_unit = kg
total_cost = 200
```

El sistema calcula:

```text
cost_per_purchase_unit = 200 / 40 = 5
```

y después:

```text
cost_per_base_unit
```

---

# NO CONFUNDIR ESTOS CAMPOS

No confundir:

```text
$200
```

con:

```text
$5/kg
```

Son cosas completamente diferentes.

Debe existir una representación clara.

---

# VALIDACIÓN DE COMPRAS

Antes de guardar una compra, validar:

```text
quantity > 0
total_cost >= 0
unit válida
conversión válida
```

Después calcular:

```text
normalized_quantity
cost_per_base_unit
```

---

# EDICIÓN DE COMPRAS

Audita especialmente la edición.

Cuando se modifica:

```text
cantidad
unidad
costo
```

debe recalcularse correctamente el stock y el costo.

No se debe duplicar stock.

No se debe restar usando una unidad diferente.

---

# ANULACIÓN / REVERSIÓN DE COMPRAS

Esto es extremadamente importante.

Si se compraron:

```text
40 kg
```

y el stock se almacena como:

```text
40000 g
```

al revertir debe quitar:

```text
40000 g
```

NO:

```text
40
```

Debes auditar todos los caminos de:

```text
DELETE
VOID
CANCEL
REVERSE
ROLLBACK
```

relacionados con compras.

---

# KARDEX

Audita el Kardex completo.

Cada movimiento debe tener conceptualmente:

```text
quantity
unit
quantity_in_base
base_unit
cost_per_base_unit
total_cost
movement_type
reference
```

Los movimientos deben ser auditables.

---

# RECETAS

La receta debe almacenar:

```text
ingredient_id
quantity
unit_id
```

No debe almacenar un costo permanente si el costo debe ser dinámico.

El costo debe obtenerse mediante el motor.

---

# COSTO DEL INGREDIENTE

Debe funcionar así:

```text
recipe quantity
       ↓
recipe unit
       ↓
convert to base
       ↓
cost per base unit
       ↓
ingredient cost
```

Ejemplo:

```text
100 g
→ 100 g
→ $0.005/g
→ $0.50
```

---

# COSTO DEL PLATO

Si un plato tiene:

```text
100 g queso = $0.50
200 g carne = $X
50 ml aceite = $Y
```

entonces:

```text
plate_cost =
0.50 + X + Y
```

Debe sumar los costos reales de los ingredientes.

---

# PROHIBIDO REDONDEAR ANTES DE TIEMPO

No hagas:

```text
$0.005 → $0.01
```

antes del cálculo.

Mantén suficiente precisión internamente.

Redondea solamente al mostrar.

Por ejemplo:

```text
internal:
0.005

display:
$0.50
```

---

# PRECISIÓN MONETARIA

Audita si el proyecto utiliza:

```text
FLOAT
DOUBLE
DECIMAL
NUMERIC
```

Para dinero debe preferirse una representación decimal exacta, por ejemplo:

```text
NUMERIC(18,6)
```

o una precisión adecuada a la arquitectura existente.

No uses cálculos flotantes sin control para dinero si pueden producir errores acumulativos.

---

# COSTOS NEGATIVOS

Debes validar que:

```text
quantity > 0
```

y:

```text
cost >= 0
```

salvo que exista un caso contable explícitamente justificado.

---

# COSTO CERO

Determina cuándo:

```text
cost = 0
```

es válido y cuándo significa:

```text
COSTO FALTANTE
```

No permitas que un `0` silencioso esconda un dato faltante.

---

# PRODUCTOS SIN COSTO

Si un ingrediente no tiene costo:

NO inventar:

```text
$1
```

NO usar:

```text
unit_cost = 1
```

NO asumir precio.

Debe generar un estado explícito:

```text
MISSING_COST
```

---

# IA VS MATEMÁTICA

Esta separación es OBLIGATORIA.

La IA puede:

- interpretar lenguaje natural;
- identificar producto;
- identificar cantidad;
- identificar unidad;
- detectar posibles errores;
- pedir aclaración;
- sugerir una corrección.

La IA NO debe ser responsable del cálculo financiero final.

---

# EJEMPLO

Usuario:

> Compré 40 kilos de queso por 200 dólares.

La IA puede interpretar:

```json
{
  "quantity": 40,
  "unit": "kg",
  "total_cost": 200
}
```

Pero el motor matemático debe calcular:

```text
40 kg
$200

$5/kg
```

La IA no debe inventar el resultado.

---

# FUNCIONES MATEMÁTICAS CENTRALIZADAS

Crea o refactoriza el sistema para tener funciones centralizadas equivalentes a:

```text
convertQuantity()
normalizeQuantity()
calculateCostPerBaseUnit()
calculateCostPerSaleUnit()
calculateIngredientCost()
calculateRecipeCost()
calculatePurchaseCost()
calculateStockImpact()
validateUnitCompatibility()
validateCostConsistency()
```

Adapta los nombres al lenguaje/framework real del proyecto.

NO inventes una arquitectura incompatible con el proyecto.

---

# FRONTEND

El frontend NO debe volver a implementar la lógica matemática.

Si actualmente JavaScript hace:

```javascript
quantity * 1000
```

o:

```javascript
totalCost / content
```

debes determinar si esa lógica debe eliminarse o reemplazarse por datos proporcionados por el backend.

El frontend puede mostrar:

```text
$5/kg
```

pero el backend debe ser la fuente de verdad.

---

# BACKEND

El backend debe ser la autoridad.

Toda operación importante debe pasar por:

```text
UnitConversionService
CostCalculationService
```

o la arquitectura equivalente que determine el proyecto.

---

# BASE DE DATOS

Audita:

- tablas;
- columnas;
- tipos;
- índices;
- foreign keys;
- constraints;
- migraciones.

Busca específicamente:

```text
unit_cost
cost
price
conversion_factor
conversion_to_base
content_per_purchase
units_per_bulk
bulk_cost
base_unit_id
purchase_unit_id
sale_unit_id
```

Determina cuáles son:

```text
válidos
duplicados
obsoletos
ambiguos
peligrosos
```

---

# MIGRACIONES

Si necesitas cambiar la base de datos:

1. crea una migración;
2. no destruyas datos existentes;
3. convierte los datos antiguos;
4. valida los datos migrados;
5. documenta cómo se hizo la conversión;
6. crea rollback cuando sea posible.

---

# DATOS EXISTENTES

NO supongas que todos los valores existentes son correctos.

Debes crear un diagnóstico para detectar productos potencialmente corruptos.

Por ejemplo:

```text
unit_cost > costo_total
```

cuando no debería serlo.

O:

```text
cost_per_base_unit
```

incompatible con:

```text
purchase_total_cost
purchase_quantity
```

---

# DETECTOR DE ANOMALÍAS

Crea validaciones para detectar casos como:

```text
100 g de un producto a $5/kg = $7.50
```

Debe marcarse.

Otro ejemplo:

```text
1 kg a $5/kg = $500
```

Debe marcarse.

Otro:

```text
1000 g a $5/kg = $0.005
```

Debe marcarse si el sistema muestra ese resultado como costo total.

---

# PRUEBAS OBLIGATORIAS

Debes crear pruebas automatizadas.

No basta con decir:

> "lo probé manualmente".

---

# TEST 1 — QUESO

Entrada:

```text
40 kg
$200 total
```

Resultado:

```text
$5/kg
$0.005/g
```

Después:

```text
100 g
```

debe producir:

```text
$0.50
```

---

# TEST 2

```text
500 g
```

debe producir:

```text
$2.50
```

---

# TEST 3

```text
1 kg
```

debe producir:

```text
$5.00
```

---

# TEST 4

```text
1.5 kg
```

debe producir:

```text
$7.50
```

Esto demuestra que $7.50 es correcto para 1.5 kg, pero no para 100 g.

---

# TEST 5 — EQUIVALENCIA

Comparar:

```text
100 g
```

contra:

```text
0.1 kg
```

Los costos deben ser idénticos.

---

# TEST 6

Comparar:

```text
1000 g
```

contra:

```text
1 kg
```

Deben ser idénticos.

---

# TEST 7 — STOCK

Compra:

```text
40 kg
```

Stock base:

```text
40000 g
```

---

# TEST 8 — REVERSIÓN

Compra:

```text
40 kg
```

Luego cancelar.

Stock debe volver exactamente al valor anterior.

---

# TEST 9 — RECETA

Receta:

```text
100 g queso
```

Costo:

```text
$0.50
```

---

# TEST 10 — RECETA EQUIVALENTE

Receta:

```text
0.1 kg queso
```

Costo:

```text
$0.50
```

---

# TEST 11 — PLATO

Si:

```text
queso = $0.50
carne = $2.00
aceite = $0.20
```

el plato debe costar:

```text
$2.70
```

---

# TEST 12 — UNIDADES INCOMPATIBLES

Intentar:

```text
kg → ml
```

sin conversión explícita.

Debe fallar.

NO asumir densidad.

---

# TEST 13 — COSTO FALTANTE

Ingrediente sin costo.

Debe producir:

```text
MISSING_COST
```

No:

```text
$0
```

silenciosamente.

---

# TEST 14 — CANTIDAD CERO

Debe rechazarse si no tiene sentido.

---

# TEST 15 — COSTO NEGATIVO

Debe rechazarse salvo que el negocio tenga un caso explícito para ello.

---

# TEST 16 — PRECISIÓN

Comprobar que:

```text
$0.005/g × 100 g
```

no se transforme prematuramente en:

```text
$0.01 × 100
```

---

# TEST 17 — COMPRA POR BULTO

Si existe:

```text
1 caja
10 kg
$50
```

entonces:

```text
$5/kg
```

Y:

```text
100 g = $0.50
```

---

# TEST 18 — CONTENIDO

Si existe:

```text
1 bulto
40 kg
$200
```

debe producir:

```text
$5/kg
```

NO:

```text
$200/kg
```

---

# TEST 19 — CAMBIO DE UNIDAD DE COMPRA

Si se compra:

```text
5000 g
```

y la base es:

```text
g
```

debe equivaler a:

```text
5 kg
```

---

# TEST 20 — CONSISTENCIA

Debe cumplirse:

```text
total_cost
=
normalized_quantity × cost_per_base_unit
```

dentro de la tolerancia decimal definida.

---

# AUDITORÍA DE TODOS LOS ARCHIVOS

Después de implementar la solución debes realizar una segunda búsqueda global.

Busca nuevamente:

```text
unit_cost
conversion_factor
conversion_to_base
quantity * 1000
quantity / 1000
* 1000
/ 1000
bulk_cost
units_per_bulk
cost / quantity
quantity * cost
```

No quiero que quede una segunda implementación antigua escondida.

---

# REQUISITO MUY IMPORTANTE

Si encuentras algo como:

```javascript
unit_cost * quantity
```

no lo corrijas automáticamente.

Primero determina:

```text
¿Qué unidad tiene quantity?
¿Qué significa unit_cost?
```

Después decide la corrección.

---

# NO ROMPER FUNCIONALIDAD

La refactorización debe mantener:

- inventario;
- compras;
- ventas;
- recetas;
- platos;
- kardex;
- reportes;
- stock;
- precios;
- autenticación;
- usuarios;
- permisos;
- demás funcionalidades existentes.

No elimines funcionalidades para resolver el problema.

---

# COMPATIBILIDAD

Si el sistema tiene datos históricos, debes preservar su integridad.

Si hay registros antiguos ambiguos:

```text
NO LOS CONVIERTAS SILENCIOSAMENTE
```

Debes generar un reporte:

```text
REGISTROS QUE REQUIEREN REVISIÓN
```

con:

```text
id
producto
valor actual
interpretación posible
razón de ambigüedad
acción recomendada
```

---

# LOGS

Cuando ocurra un error matemático importante, registra información suficiente para diagnosticarlo.

Por ejemplo:

```text
ingredient_id
recipe_id
quantity
recipe_unit
base_unit
normalized_quantity
cost_per_base_unit
calculated_cost
```

---

# DEBUG DEL CASO DEL QUESO

Debes implementar o preparar una forma de inspeccionar exactamente este caso:

```text
Producto: queso
Compra: 40 kg
Costo total: $200
Receta: 100 g
```

Y el sistema debe poder mostrar:

```text
Cantidad receta: 100
Unidad receta: g

Unidad base: g
Cantidad base: 100

Costo por unidad base: $0.005

Costo calculado: $0.50
```

Si aparece:

```text
$7.50
```

debes identificar exactamente qué variable está provocando el error.

No quiero una explicación genérica.

Quiero:

```text
archivo
línea
variable
valor recibido
valor esperado
fórmula incorrecta
fórmula correcta
```

---

# FORMATO DE ENTREGA OBLIGATORIO

Cuando termines, NO respondas simplemente:

> "Listo."

Debes entregar un informe estructurado.

## 1. RESUMEN EJECUTIVO

Explica:

- cuál era el problema;
- cuál era la causa;
- qué se modificó;
- cuál es la arquitectura final.

## 2. ARCHIVOS INSPECCIONADOS

Lista los archivos relevantes inspeccionados.

## 3. ARCHIVOS MODIFICADOS

Para cada archivo:

```text
archivo:
problema:
cambio:
razón:
```

## 4. BASE DE DATOS

Indica:

```text
tablas modificadas
columnas modificadas
migraciones creadas
datos migrados
```

## 5. MOTOR DE UNIDADES

Explica:

```text
unidad base
unidad compra
unidad venta
conversiones
validaciones
```

## 6. MOTOR DE COSTOS

Explica:

```text
costo total
costo por unidad base
costo por unidad de venta
costo de ingrediente
costo de receta
```

## 7. FLUJO COMPLETO

Explica:

```text
Compra
↓
Normalización
↓
Inventario
↓
Costo
↓
Receta
↓
Plato
↓
Venta
↓
Kardex
```

## 8. TESTS

Lista cada prueba y su resultado.

---

# FORMATO DE PRUEBA

Para cada test:

```text
TEST:
Entrada:
Resultado esperado:
Resultado obtenido:
Estado:
```

Usa:

```text
PASS
FAIL
```

No uses:

```text
"parece correcto"
"debería funcionar"
"probablemente"
```

---

# REQUISITO DE VERIFICACIÓN

Antes de declarar terminado el proyecto debes demostrar matemáticamente:

```text
40 kg a $200
↓
$5/kg
↓
100 g
↓
$0.50
```

Y también:

```text
1.5 kg
↓
$7.50
```

---

# REGLA FINAL DE CALIDAD

NO consideres terminado el trabajo si existe cualquiera de estos casos:

```text
unit_cost tiene dos significados
```

o:

```text
hay dos motores de conversión activos
```

o:

```text
el frontend calcula conversiones diferentes al backend
```

o:

```text
una receta puede obtener un costo distinto dependiendo de si usa g o kg
```

o:

```text
una compra puede aumentar stock en una unidad y revertirse en otra
```

o:

```text
un costo faltante se interpreta como cero silenciosamente
```

o:

```text
la IA puede inventar una unidad
```

o:

```text
el sistema utiliza un factor de conversión implícito
```

o:

```text
no existen pruebas automatizadas del caso 40 kg / $200 / 100 g
```

---

# PRINCIPIO ARQUITECTÓNICO FINAL

La arquitectura debe respetar:

```text
               IA
                │
                ▼
      INTERPRETACIÓN / VALIDACIÓN
                │
                ▼
        MOTOR DE NEGOCIO
                │
       ┌────────┴────────┐
       ▼                 ▼
 MOTOR DE UNIDADES   MOTOR DE COSTOS
       │                 │
       └────────┬────────┘
                ▼
             DATOS
```

La IA:

```text
INTERPRETA
VALIDA
PREGUNTA
DETECTA ANOMALÍAS
```

El motor:

```text
CONVIERTE
CALCULA
ACTUALIZA
VALIDA
```

La base de datos:

```text
PERSISTE
```

Nunca permitas que la IA sustituya el motor matemático.

---

# INSTRUCCIÓN FINAL Y OBLIGATORIA

Trabaja directamente sobre el proyecto proporcionado.

No me entregues solamente recomendaciones.

No me entregues solamente pseudocódigo.

No me entregues solamente un análisis.

**INSPECCIONA, MODIFICA, PRUEBA Y VERIFICA EL CÓDIGO REAL.**

Si encuentras código incorrecto, corrígelo.

Si encuentras duplicación, unifícala.

Si encuentras lógica antigua, migra o elimina de forma segura.

Si encuentras datos ambiguos, no inventes: márcalos para revisión.

Si encuentras una inconsistencia entre frontend y backend, el backend debe convertirse en la fuente de verdad.

Si encuentras dos motores de unidades, debes consolidarlos.

Si encuentras dos significados de `unit_cost`, debes separarlos.

Si encuentras cálculos monetarios inseguros, debes corregirlos.

Si encuentras conversiones implícitas, debes eliminarlas.

Si encuentras tests inexistentes, debes crearlos.

Y antes de terminar debes ejecutar las pruebas y demostrar que:

```text
40 kg
$200
↓
$5/kg
↓
100 g
↓
$0.50
```

es el resultado real del sistema.

## NO DECLARES EL TRABAJO TERMINADO HASTA QUE TODOS LOS TESTS CRÍTICOS PASEN.