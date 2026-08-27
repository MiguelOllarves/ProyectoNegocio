# Objetivo

Estoy desarrollando un sistema de gestión de negocios llamado **ProyectoNegocio / TuInventario**.

Repositorio:

https://github.com/MiguelOllarves/ProyectoNegocio

Quiero que trabajes directamente sobre este proyecto existente.

**MUY IMPORTANTE:** no quiero que conviertas el sistema de inventario en un sistema exclusivo para restaurantes.

El sistema está diseñado para ser **multirubro**. Cuando un negocio se registra, puede pertenecer a diferentes rubros y el inventario debe poder manejar productos de cualquier tipo:

- restaurantes
- tiendas
- bodegas
- supermercados
- ferreterías
- panaderías
- carnicerías
- comercios generales
- etc.

Actualmente quiero mejorar la lógica de **productos, unidades de medida, compras, inventario, ventas y recetas**, especialmente para que un producto pueda ser comprado de una manera, vendido de otra y consumido en una receta de otra manera, sin romper la arquitectura general.

---

# 1. PRIMERO ANALIZA EL PROYECTO

Antes de modificar código:

1. Analiza completamente el repositorio.
2. Identifica:
   - estructura de carpetas
   - módulos
   - controladores
   - modelos
   - servicios
   - vistas
   - JavaScript
   - base de datos
   - relaciones entre tablas
   - sistema de inventario
   - compras
   - ventas
   - kardex
   - productos
   - unidades de medida
   - recetas/platos
3. Identifica específicamente cómo funcionan actualmente:
   - `products`
   - `units_of_measure`
   - `purchases`
   - `purchase_items`
   - `kardex`
   - `recipe_items`
   - `UnitConversionService`
   - `CostCalculationService`
   - formulario de productos
   - formulario de platos
4. Busca cualquier lógica relacionada con:
   - `unit_cost`
   - `bulk_cost`
   - `units_per_bulk`
   - `purchase_unit_id`
   - `sale_unit_id`
   - `contained_unit_id`
   - `content_per_purchase`
   - `conversion_factor`
   - `stock`
   - conversiones de unidades
5. Antes de cambiar nada, explica brevemente qué arquitectura tiene actualmente el proyecto y qué partes pueden reutilizarse.

**No quiero una reconstrucción completa del proyecto.**

Quiero evolucionar la arquitectura existente.

---

# 2. PROBLEMA ACTUAL

Actualmente el formulario de producto mezcla varios conceptos.

Por ejemplo, tengo:

**Producto:**

> Arroz Princesa

Y actualmente puedo indicar:

- Tipo de medición
- cómo se vende
- cómo se compra
- cuántas unidades trae el bulto
- cuánto cuesta el bulto
- etc.

El problema es que estos conceptos no son exactamente lo mismo.

Por ejemplo:

## Producto

Arroz Princesa.

## Compra

Lo compro:

> 1 bulto

Ese bulto contiene:

> 20 paquetes

Cada paquete contiene:

> 1 kg

Costo del bulto:

> $25

Entonces:

```text
1 bulto
= 20 paquetes
= 20 kg
= 20.000 gramos
```

Pero después puedo vender el arroz:

```text
1 paquete
```

Y en un restaurante puedo utilizar:

```text
200 gramos
```

del mismo producto para preparar un plato.

El sistema debe entender que **todo sigue siendo el mismo producto**.

No quiero crear:

```text
Arroz Princesa Bulto
Arroz Princesa Unidad
Arroz Princesa Gramos
```

Debe existir un único producto:

```text
Arroz Princesa
```

con diferentes presentaciones, unidades y conversiones.

---

# 3. PRINCIPIO FUNDAMENTAL

La arquitectura debe separar estos conceptos:

```text
PRODUCTO
PRESENTACIÓN
UNIDAD DE CONTROL
COMPRA
VENTA
CONSUMO / RECETA
```

No deben mezclarse.

---

# 4. PRODUCTO

El producto representa el artículo real.

Ejemplo:

```text
Nombre: Arroz Princesa
Categoría: Secos y Abarrotes
Marca: Princesa
```

El producto debe tener una **unidad de control/base de inventario**.

Ejemplos:

```text
Arroz → kg
Carne → kg
Harina → g o kg
Aceite → litro
Refresco → unidad
Coca-Cola → unidad
Tornillo → unidad
Cable → metro
Tela → metro
```

La unidad base depende del producto.

NO quiero que todo el inventario sea convertido obligatoriamente a gramos.

---

# 5. FAMILIAS DE UNIDADES

El sistema ya tiene el concepto de familias de unidades.

Debe mantener y utilizar correctamente familias como:

```text
PESO
VOLUMEN
UNIDAD
LONGITUD
```

o las que ya existan en el proyecto.

Ejemplos:

### Peso

```text
g
kg
mg
```

### Volumen

```text
ml
l
```

### Unidad

```text
unidad
pieza
docena
```

### Longitud

```text
cm
m
```

Las conversiones solamente deben hacerse cuando tengan sentido.

Ejemplos válidos:

```text
kg → g
g → kg

l → ml
ml → l

m → cm
cm → m
```

No debe hacerse automáticamente:

```text
unidad → gramos
unidad → litros
```

a menos que exista explícitamente una equivalencia definida.

---

# 6. UNIDAD BASE DEL INVENTARIO

Cada producto debe tener una unidad base/control.

Ejemplo:

```text
Arroz:
unidad base = kg
```

Entonces el inventario internamente puede guardar:

```text
20 kg
```

No importa que el proveedor lo haya vendido como:

```text
1 bulto
```

El bulto es una presentación de compra.

---

# 7. PRESENTACIONES

Necesito que el sistema soporte presentaciones.

Una presentación describe cómo viene o cómo se comercializa un producto.

Por ejemplo:

```text
Producto: Arroz Princesa

Presentación:
Bulto

Contenido:
20 kg
```

Otra presentación:

```text
Producto: Arroz Princesa

Presentación:
Paquete

Contenido:
1 kg
```

Ambas pertenecen al mismo producto.

Otro ejemplo:

```text
Producto: Coca-Cola

Presentación:
Caja

Contenido:
24 unidades
```

Y:

```text
Producto: Coca-Cola

Presentación:
Unidad

Contenido:
1 unidad
```

---

# 8. COMPRAS

La compra debe utilizar una presentación.

Ejemplo:

```text
Producto:
Arroz Princesa

Presentación de compra:
Bulto

Cantidad comprada:
1

Contenido:
20 kg

Costo total:
$25
```

El sistema debe convertir automáticamente:

```text
1 bulto × 20 kg
=
20 kg
```

Y registrar en inventario:

```text
+20 kg
```

El costo unitario resultante es:

```text
$25 / 20 kg
=
$1,25 por kg
```

También debe poder calcular:

```text
$0,125 por 100 g
```

y, si corresponde:

```text
$0,00125 por g
```

---

# 9. MUY IMPORTANTE: EL COSTO DEBE PODER CAMBIAR POR COMPRA

No quiero que el sistema sobrescriba incorrectamente los costos históricos.

Ejemplo:

Primera compra:

```text
Bulto:
20 kg
Costo:
$25
```

Costo:

```text
$1,25/kg
```

Segunda compra:

```text
Bulto:
20 kg
Costo:
$30
```

Costo:

```text
$1,50/kg
```

El sistema debe conservar la información de las compras y movimientos.

No debe destruir el histórico.

Debe respetar la estrategia de costos que ya tenga implementada el proyecto o permitir una estrategia coherente como:

- costo promedio
- último costo
- FIFO

Pero no inventar datos.

---

# 10. CASO ESPECIAL: DIFERENTES CONTENIDOS

También debe soportar casos como:

Compra 1:

```text
20 paquetes × 1 kg
Costo = $25
```

Compra 2:

```text
20 paquetes × 900 g
Costo = $25
```

No son equivalentes.

La primera compra contiene:

```text
20.000 g
```

La segunda:

```text
18.000 g
```

Por lo tanto, el costo por kg es diferente.

El sistema debe calcularlo correctamente y conservar el dato de cada compra/lote.

---

# 11. INVENTARIO

El inventario debe representar la existencia en la unidad base/control del producto.

Ejemplo:

```text
Arroz Princesa
Unidad base: kg

Existencia:
20 kg
```

Aunque se haya comprado:

```text
1 bulto
```

El inventario debe saber que ese bulto representa:

```text
20 kg
```

Cuando se vende:

```text
1 paquete = 1 kg
```

debe descontar:

```text
-1 kg
```

Queda:

```text
19 kg
```

Cuando un restaurante utiliza:

```text
200 g
```

debe descontar:

```text
0,2 kg
```

Queda:

```text
19,8 kg
```

---

# 12. VENTAS

La venta debe poder utilizar una presentación diferente a la compra.

Ejemplo:

Compré:

```text
1 bulto
= 20 kg
```

Pero vendo:

```text
1 paquete
= 1 kg
```

Entonces:

```text
Venta:
1 paquete

Conversión:
1 paquete = 1 kg

Inventario:
20 kg → 19 kg
```

El precio de venta puede ser independiente del costo de compra.

---

# 13. RECETAS / PLATOS

El módulo de restaurante es solamente un módulo adicional.

NO debe cambiar la arquitectura general del inventario.

Una receta puede consumir productos del inventario.

Ejemplo:

```text
Plato:
Arroz con carne

Ingredientes:

Arroz Princesa:
200 g

Carne:
150 g

Aceite:
30 ml

Cebolla:
50 g
```

Cada ingrediente debe indicar su cantidad y unidad de consumo.

---

# 14. CÁLCULO DEL COSTO DE RECETA

Cuando agrego:

```text
Arroz Princesa
Cantidad:
200 g
```

el sistema debe consultar la unidad base del producto.

Si el producto tiene:

```text
Unidad base:
kg
```

debe convertir:

```text
200 g
=
0,2 kg
```

Si el costo actual es:

```text
$1,25/kg
```

entonces:

```text
0,2 × $1,25
=
$0,25
```

El ingrediente debe mostrar:

```text
Arroz Princesa
200 g
Costo: $0,25
```

---

# 15. COSTO DE 100 GRAMOS

Para productos de peso, el sistema debe poder mostrar equivalencias útiles.

Ejemplo:

```text
Costo:
$1,25/kg
```

Debe poder mostrar:

```text
100 g = $0,125
```

Esto es solamente una representación/cálculo.

No significa que el inventario tenga que almacenarse obligatoriamente en gramos.

---

# 16. EJEMPLO COMPLETO

Implementa y prueba este caso:

Producto:

```text
Arroz Princesa
Unidad base:
kg
```

Compra:

```text
1 bulto
20 unidades
Cada unidad:
1 kg

Costo del bulto:
$25
```

Resultado:

```text
Contenido:
20 kg

Costo:
$1,25/kg
```

Venta:

```text
1 paquete
```

Conversión:

```text
1 paquete = 1 kg
```

Inventario:

```text
20 kg → 19 kg
```

Receta:

```text
200 g
```

Conversión:

```text
200 g = 0,2 kg
```

Costo:

```text
0,2 × $1,25
=
$0,25
```

Si el plato tiene además:

```text
Carne:
150 g

Aceite:
30 ml

Cebolla:
50 g
```

el sistema debe calcular automáticamente:

```text
Costo arroz
+
Costo carne
+
Costo aceite
+
Costo cebolla
=
Costo total del plato
```

---

# 17. NO ROMPER EL INVENTARIO MULTIRUBRO

Debes probar también ejemplos que NO sean restaurantes.

## Ejemplo 1: Ferretería

Producto:

```text
Tornillo 3"
Unidad base:
unidad
```

Compra:

```text
1 caja
100 unidades
Costo:
$10
```

Inventario:

```text
100 unidades
```

Venta:

```text
5 unidades
```

Inventario:

```text
95 unidades
```

---

## Ejemplo 2: Tienda

Producto:

```text
Coca-Cola
Unidad base:
unidad
```

Compra:

```text
1 caja
24 unidades
Costo:
$20
```

Inventario:

```text
24 unidades
```

Venta:

```text
1 unidad
```

Inventario:

```text
23 unidades
```

---

## Ejemplo 3: Carnicería

Producto:

```text
Carne de res
Unidad base:
kg
```

Compra:

```text
10 kg
Costo:
$50
```

Costo:

```text
$5/kg
```

Venta:

```text
0,5 kg
```

Inventario:

```text
9,5 kg
```

---

## Ejemplo 4: Restaurante

Producto:

```text
Arroz
Unidad base:
kg
```

Compra:

```text
1 bulto
20 kg
$25
```

Receta:

```text
200 g
```

Consumo:

```text
0,2 kg
```

---

# 18. REGLA IMPORTANTE SOBRE LAS CONVERSIONES

No quiero una lógica tipo:

> "Si no conozco la conversión, asumir que es Kg o litros".

Eso es peligroso.

Las conversiones deben ser explícitas.

Si el sistema no puede convertir:

```text
unidad A → unidad base
```

debe mostrar un error claro:

> "No existe una conversión válida entre estas unidades."

No debe inventar una equivalencia.

---

# 19. FORMULARIO DE PRODUCTO

Rediseña el formulario actual de producto sin perder su estilo visual.

Actualmente tengo una sección similar a:

```text
¿Cómo se mide y se compra?
```

Quiero que la experiencia sea más clara.

La lógica debería ser aproximadamente:

## Producto

```text
Nombre
Categoría
Marca
Imagen
```

## Unidad de control

```text
¿Cómo se controla este producto?

[ Unidad ]
[ Peso ]
[ Volumen ]
[ Longitud ]
```

Dependiendo de la selección, mostrar las unidades compatibles.

Ejemplo:

```text
Unidad de control:
[ Kilogramo (kg) ]
```

---

## Presentación de compra

Permitir definir cómo se compra:

```text
Presentación:
[ Bulto ]

Contenido:
[ 20 ]

Unidad:
[ Kilogramo (kg) ]
```

O, si el negocio compra paquetes:

```text
Presentación:
[ Caja ]

Cantidad:
[ 24 ]

Unidad:
[ Unidad ]
```

La interfaz debe adaptarse dinámicamente.

---

# 20. NO QUIERO PERDER LA FUNCIONALIDAD ACTUAL

Antes de modificar:

- revisa la base de datos
- revisa migraciones
- revisa servicios
- revisa formularios
- revisa controladores
- revisa AJAX/JavaScript
- revisa consultas SQL

Mantén compatibilidad siempre que sea posible.

No elimines campos o tablas simplemente porque parezcan innecesarios.

Si realmente necesitas modificar la estructura:

1. explica por qué
2. crea una migración
3. migra datos existentes
4. conserva compatibilidad
5. evita perder información

---

# 21. INVENTARIO COMO NÚCLEO

La arquitectura final debe seguir esta idea:

```text
                         PRODUCTO
                            │
              ┌─────────────┼─────────────┐
              │             │             │
              ▼             ▼             ▼
          COMPRAS         VENTAS       CONSUMOS
              │             │             │
              │             │             ▼
              │             │          RECETAS
              │             │             │
              └─────────────┼─────────────┘
                            ▼
                       INVENTARIO
                            │
                            ▼
                         KARDEX
```

El inventario es el núcleo.

Restaurante/recetas es solamente un consumidor especializado del inventario.

---

# 22. NO CREAR DUPLICADOS DE PRODUCTOS

No quiero que para manejar diferentes presentaciones se creen productos duplicados.

Incorrecto:

```text
Arroz 1kg
Arroz bulto
Arroz 200g
```

Correcto:

```text
ARROZ PRINCESA
│
├── Presentación: Bulto → 20 kg
├── Presentación: Paquete → 1 kg
└── Consumo de receta → 200 g
```

---

# 23. COSTO

Define claramente qué significa `unit_cost`.

Debe representar:

> costo de una unidad de la unidad base del producto.

Ejemplo:

```text
Producto:
Arroz

Unidad base:
kg

unit_cost:
1.25
```

Entonces:

```text
1 kg = $1,25
0,5 kg = $0,625
200 g = $0,25
100 g = $0,125
```

Para un producto por unidad:

```text
Producto:
Coca-Cola

Unidad base:
unidad

unit_cost:
$0,83
```

Para un producto por litro:

```text
Producto:
Aceite

Unidad base:
litro

unit_cost:
$3
```

---

# 24. PRECIO DE VENTA

El precio de venta no debe confundirse con el costo.

Debe seguir funcionando la lógica actual de:

```text
Costo
+
Margen/ganancia
=
Precio sugerido
```

Pero también debe permitirse introducir un precio manual cuando corresponda.

Si el proyecto actualmente soporta:

```text
% Ganancia
Comercial
Costo / %
```

mantener esa funcionalidad.

---

# 25. UI DE RECETAS

Cuando agrego un ingrediente al plato:

```text
Arroz Princesa
Cantidad:
[ 200 ]

Unidad:
[ gramos ]
```

debe mostrar inmediatamente:

```text
Costo:
$0,25
```

También quiero mostrar información útil:

```text
Disponible:
19,8 kg

Costo:
$1,25/kg

Costo de 100 g:
$0,125

Costo de esta cantidad:
$0,25
```

Al cambiar:

```text
200 g → 250 g
```

el costo debe actualizarse inmediatamente.

---

# 26. STOCK Y RECETAS

Es importante diferenciar:

### Guardar receta

No debe descontar inventario.

### Vender/preparar plato

Sí debe descontar los ingredientes correspondientes.

Por ejemplo:

Receta:

```text
Arroz:
200 g
```

Guardar receta:

```text
NO descontar inventario.
```

Vender 1 plato:

```text
-200 g de arroz
```

Vender 10 platos:

```text
-2 kg de arroz
```

---

# 27. VALIDACIONES

Implementa validaciones para evitar:

- unidades incompatibles
- cantidades negativas
- costos negativos
- conversiones inexistentes
- stock negativo si la configuración del negocio no lo permite
- presentaciones sin contenido
- productos sin unidad base cuando esta sea necesaria
- recetas con ingredientes incompatibles

Los errores deben ser claros para el usuario.

---

# 28. DECIMALES

El sistema debe soportar cantidades decimales cuando la unidad lo permita.

Ejemplos:

```text
0,2 kg
0,5 kg
250,5 g
1,5 litros
0,25 litros
```

Pero no debe permitir:

```text
0,5 tornillos
```

si el producto se controla por unidades y no permite fracciones.

Utiliza la configuración que ya tenga el proyecto para determinar cuándo se permiten decimales.

---

# 29. BACKEND

No hagas únicamente cambios visuales.

La lógica debe estar correctamente implementada en backend.

Toda conversión importante debe validarse también en servidor.

No confiar solamente en JavaScript.

Centraliza la lógica de conversión y costos en servicios reutilizables.

Aprovecha y refactoriza:

```text
UnitConversionService
CostCalculationService
```

si es necesario.

No dupliques fórmulas en múltiples controladores.

---

# 30. BASE DE DATOS

Primero determina si las tablas actuales pueden soportar correctamente este modelo.

Si es posible reutilizar:

```text
products
units_of_measure
purchase_items
kardex
recipe_items
```

hazlo.

Si hace falta una entidad nueva como:

```text
product_presentations
```

o:

```text
product_units
```

puedes crearla, pero solamente si realmente mejora el modelo.

No agregues tablas innecesarias.

Si modificas la base de datos:

- crea migración
- documenta la migración
- conserva datos existentes
- establece foreign keys correctamente
- agrega índices donde corresponda

---

# 31. MIGRACIÓN DE DATOS EXISTENTES

Este punto es obligatorio.

El proyecto ya puede tener productos registrados con la estructura antigua.

No quiero perderlos.

Analiza los campos actuales y crea una estrategia para migrarlos.

Por ejemplo, si actualmente existe:

```text
unit_cost
purchase_unit_id
sale_unit_id
contained_unit_id
content_per_purchase
conversion_factor
```

determina cómo mapear esos valores al nuevo modelo.

No borres datos existentes sin una estrategia.

---

# 32. PRUEBAS

Después de implementar, prueba como mínimo:

### Caso A

```text
Producto por unidad
Compra por caja
Venta por unidad
```

### Caso B

```text
Producto por peso
Compra por bulto
Venta por kg
```

### Caso C

```text
Producto por peso
Compra por bulto
Receta consume gramos
```

### Caso D

```text
Producto por volumen
Compra por caja
Receta consume ml
```

### Caso E

```text
Producto por unidad
Receta consume unidades
```

### Caso F

```text
Compra 1:
20 kg por $25

Compra 2:
20 kg por $30
```

Verificar que el histórico no se pierda.

### Caso G

```text
20 kg disponibles

Receta:
200 g

Vender 10 platos

Resultado:
18 kg
```

---

# 33. EJEMPLO QUE DEBE FUNCIONAR AL FINAL

Quiero poder hacer exactamente esto:

## Crear producto

```text
Arroz Princesa

Unidad de control:
Kg
```

## Comprar

```text
1 Bulto

Contenido:
20 Kg

Costo:
$25
```

Resultado:

```text
Inventario:
20 Kg

Costo:
$1,25/Kg

Costo 100g:
$0,125
```

## Crear plato

```text
Plato:
Arroz con pollo
```

Ingredientes:

```text
Arroz Princesa:
200 g
```

El sistema muestra:

```text
Costo del arroz:
$0,25
```

Agregar:

```text
Pollo:
150 g
```

Agregar:

```text
Aceite:
30 ml
```

El sistema calcula:

```text
Costo total de ingredientes:
$X
```

y luego:

```text
Margen:
30%

Precio sugerido:
$X
```

## Vender el plato

Si vendo:

```text
1 Arroz con pollo
```

el inventario debe descontar automáticamente:

```text
Arroz:
-200 g

Pollo:
-150 g

Aceite:
-30 ml
```

y registrar correctamente los movimientos en el kardex.

---

# 34. PRINCIPIO MÁS IMPORTANTE

No soluciones solamente el caso del arroz.

La solución debe ser **genérica y reutilizable**.

El objetivo no es:

> "hacer que arroz funcione".

El objetivo es:

> **crear una arquitectura de unidades, presentaciones, compras, ventas, costos y consumos que permita que cualquier producto del sistema pueda tener una forma de compra, una forma de venta y una forma de consumo diferentes, manteniendo un único producto y un inventario consistente.**

---

# 35. FORMA DE TRABAJO

Quiero que trabajes en este orden:

### Fase 1 — Análisis

Analiza el repositorio completo y explica:

- qué existe actualmente
- qué está bien
- qué está mal
- qué debe modificarse
- qué tablas se pueden reutilizar
- qué tablas necesitan cambios

### Fase 2 — Diseño

Propón la estructura final antes de implementarla.

Incluye:

- entidades
- relaciones
- flujo de compra
- flujo de inventario
- flujo de venta
- flujo de receta
- cálculo de costos
- conversiones

### Fase 3 — Base de datos

Implementa migraciones necesarias.

### Fase 4 — Backend

Implementa:

- unidades
- conversiones
- presentaciones
- costos
- inventario
- compras
- ventas
- recetas

### Fase 5 — Frontend

Actualiza los formularios manteniendo el diseño visual actual.

No quiero una interfaz completamente diferente.

### Fase 6 — Integración

Comprueba que:

```text
Compra
↓
Inventario
↓
Venta
```

funciona.

Y que:

```text
Compra
↓
Inventario
↓
Receta
↓
Venta de plato
↓
Descuento de ingredientes
```

también funciona.

### Fase 7 — Pruebas

Ejecuta las pruebas necesarias y corrige cualquier inconsistencia.

---

# 36. RESTRICCIONES

NO:

- conviertas el sistema en restaurante
- dupliques productos por presentación
- asumas conversiones automáticamente
- hardcodees Kg/litros
- rompas el sistema multirubro
- elimines datos existentes
- hagas únicamente cambios visuales
- dupliques lógica de conversión
- calcules costos diferentes en frontend y backend
- descuentes inventario al guardar una receta

SÍ:

- reutiliza la arquitectura existente
- centraliza conversiones
- centraliza costos
- conserva histórico
- utiliza unidad base por producto
- permite presentaciones
- separa compra, venta y consumo
- mantén compatibilidad
- usa migraciones
- valida en backend
- mantén el diseño actual
- documenta cambios importantes

---

# RESULTADO ESPERADO

Al finalizar quiero tener un sistema donde:

```text
PRODUCTO
   ↓
Unidad base
   ↓
Presentaciones
   ↓
Compra
   ↓
Inventario
   ├── Venta
   │
   └── Consumo / Receta
```

Y que un producto como:

```text
Arroz Princesa
```

pueda ser:

```text
COMPRADO
1 bulto = 20 kg

ALMACENADO
20 kg

VENDIDO
1 paquete = 1 kg

CONSUMIDO EN RECETA
200 g = 0,2 kg

COSTO
$1,25/kg
$0,125/100g
$0,25/200g
```

sin crear productos duplicados y sin hacer que el inventario dependa del módulo de restaurante.

**Antes de comenzar a modificar código, analiza el repositorio y explícame la arquitectura actual y el plan de implementación. Después procede con los cambios.**