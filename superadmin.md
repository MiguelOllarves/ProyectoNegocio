# SUPERADMIN — AUDITORÍA, REDISEÑO Y MEJORA INTEGRAL

Estoy desarrollando una plataforma SaaS de inventario/POS llamada **ProyectoNegocio / TuInventario**.

Repositorio:

https://github.com/MiguelOllarves/ProyectoNegocio

Quiero que trabajes sobre el proyecto existente y hagas una **auditoría completa y una mejora integral del módulo SuperAdmin**.

Actualmente el SuperAdmin está muy limitado.

Tengo problemas como:

- No puedo eliminar usuarios.
- No puedo editar usuarios.
- No puedo administrar correctamente los negocios.
- No puedo controlar completamente las cuentas.
- Hay funcionalidades del SuperAdmin que están incompletas.
- Quiero eliminar el módulo/sección de "Pagos" del área SuperAdmin.
- Quiero agregar todas las herramientas razonables que debería tener un SuperAdmin de una plataforma SaaS.
- Quiero que se revise seguridad, permisos, navegación, backend, frontend y base de datos.
- Quiero que se aproveche al máximo la arquitectura existente sin destruir funcionalidades que ya funcionan.

---

# IMPORTANTE: NO EMPIECES MODIFICANDO CÓDIGO

Primero debes analizar completamente el repositorio.

Quiero que inspecciones:

- estructura completa del proyecto
- autenticación
- autorización
- roles
- usuarios
- negocios
- sesiones
- base de datos
- migraciones
- controladores
- modelos
- servicios
- middleware
- vistas
- rutas
- JavaScript
- AJAX/fetch
- dashboard
- módulos administrativos
- inventario
- ventas
- compras
- clientes
- proveedores
- créditos
- gastos
- arqueo de caja
- reportes
- configuración
- cualquier módulo relacionado con usuarios, negocios o pagos.

Busca específicamente:

```text
admin
superadmin
role
roles
permissions
users
businesses
payments
subscription
plans
billing
auth
session
login
```

y cualquier otro concepto relacionado.

Antes de cambiar código, explícame:

1. cómo funciona actualmente el SuperAdmin
2. qué puede hacer actualmente
3. qué no puede hacer
4. qué tablas utiliza
5. qué permisos existen
6. cómo se protegen las rutas
7. qué problemas de seguridad existen
8. qué partes están incompletas
9. qué funcionalidades conviene agregar
10. qué funcionalidades de "Pagos" existen actualmente
11. cuáles se pueden eliminar sin afectar ventas/POS
12. qué cambios de base de datos serían necesarios.

---

# OBJETIVO PRINCIPAL

Quiero convertir el SuperAdmin en un verdadero **panel de administración de la plataforma**, no simplemente en otro usuario con algunos botones adicionales.

La arquitectura debe quedar conceptualmente así:

```text
                    SUPERADMIN
                        │
        ┌───────────────┼────────────────┐
        │               │                │
        ▼               ▼                ▼
     USUARIOS         NEGOCIOS         SISTEMA
        │               │                │
        ▼               ▼                ▼
    Gestionar       Gestionar        Configuración
    cuentas         negocios         global
        │               │                │
        └───────────────┼────────────────┘
                        ▼
                     AUDITORÍA
```

El SuperAdmin administra la plataforma completa.

Un administrador de negocio solamente administra SU negocio.

Un usuario normal solamente tiene los permisos que le correspondan dentro de su negocio.

---

# 1. SUPERADMIN DEBE TENER CONTROL REAL DE USUARIOS

Actualmente no puedo eliminar ni editar usuarios correctamente.

Esto debe solucionarse.

Crear un módulo:

```text
SuperAdmin
└── Usuarios
```

Debe permitir:

- listar usuarios
- buscar usuarios
- filtrar usuarios
- ver detalle
- editar usuario
- activar usuario
- desactivar usuario
- suspender usuario
- eliminar usuario
- restaurar usuario si se implementa papelera
- cambiar rol
- cambiar negocio
- ver negocios asociados
- ver fecha de registro
- ver último acceso
- ver estado
- ver email
- ver nombre
- ver teléfono si existe
- ver información relevante disponible.

---

# 2. EDITAR USUARIO

Debe existir una pantalla/modal completa:

```text
Editar Usuario
```

Campos según lo que soporte actualmente el proyecto:

```text
Nombre
Apellido
Email
Teléfono
Estado
Rol
Negocio
```

El SuperAdmin debe poder modificar estos datos.

Validar:

- email válido
- email único
- campos obligatorios
- roles válidos
- negocio válido
- permisos.

No permitir modificar datos de forma insegura desde frontend solamente.

Toda modificación debe validarse nuevamente en backend.

---

# 3. ELIMINAR USUARIO

Debe existir la opción:

```text
Eliminar usuario
```

Pero antes debes analizar si existen relaciones con:

- ventas
- compras
- gastos
- clientes
- proveedores
- movimientos
- auditoría
- sesiones
- negocios
- créditos
- otros registros.

No quiero que una eliminación rompa foreign keys ni destruya información histórica.

Por eso implementa una estrategia adecuada.

Preferentemente:

```text
Desactivar
```

para usuarios que tienen historial.

Y:

```text
Eliminar permanentemente
```

solamente cuando sea seguro.

Si el sistema necesita soft delete:

```text
deleted_at
```

o una solución equivalente, implementarla correctamente.

Antes de eliminar:

```text
¿Seguro que deseas eliminar este usuario?
```

Mostrar información del usuario.

---

# 4. PROTEGER AL SUPERADMIN

MUY IMPORTANTE:

El SuperAdmin no debe poder ser eliminado accidentalmente.

Debe existir una protección para:

- el último SuperAdmin
- el propio SuperAdmin
- cuentas críticas
- usuarios del sistema.

Ejemplo:

```text
No puedes eliminar tu propia cuenta de SuperAdmin.
```

Y:

```text
No puedes eliminar al último SuperAdmin de la plataforma.
```

También debe impedirse que un administrador normal pueda escalarse a SuperAdmin.

El cambio de rol:

```text
user → superadmin
```

debe estar protegido exclusivamente por SuperAdmin.

---

# 5. GESTIÓN DE ROLES

Audita el sistema actual de roles.

Determina si existen:

```text
superadmin
admin
employee
user
```

u otros.

No inventes roles si no son necesarios.

El SuperAdmin debe poder consultar claramente:

```text
Rol
Permisos
Usuarios asociados
```

Si el proyecto ya tiene permisos, reutilizarlos.

Si no existe un sistema de permisos suficiente, implementar uno razonable.

La regla fundamental:

```text
SUPERADMIN
    ↓
Plataforma completa

ADMIN DEL NEGOCIO
    ↓
Solo su negocio

EMPLEADO
    ↓
Solo módulos/permisos autorizados
```

---

# 6. GESTIÓN DE NEGOCIOS

Crear/mejorar:

```text
SuperAdmin
└── Negocios
```

El SuperAdmin debe poder:

- listar negocios
- buscar negocios
- filtrar negocios
- ver negocio
- editar negocio
- activar negocio
- desactivar negocio
- suspender negocio
- eliminar negocio cuando sea seguro
- restaurar negocio si se utiliza soft delete
- ver propietario
- ver cantidad de usuarios
- ver cantidad de productos
- ver ventas
- ver compras
- ver fecha de creación
- ver última actividad
- ver estado.

---

# 7. DETALLE DEL NEGOCIO

Al abrir un negocio:

```text
Detalle del negocio
```

Mostrar un resumen:

```text
Nombre
Rubro
Propietario
Email
Teléfono
Fecha de registro
Estado
Usuarios
Productos
Clientes
Proveedores
Ventas
Compras
```

Si los datos están disponibles, mostrar estadísticas.

Por ejemplo:

```text
Usuarios: 4
Productos: 185
Clientes: 76
Proveedores: 12
Ventas: 1.245
Compras: 84
```

No hacer consultas extremadamente pesadas sin necesidad.

Utilizar consultas optimizadas.

---

# 8. EDITAR NEGOCIO

El SuperAdmin debe poder editar información administrativa del negocio.

Por ejemplo:

```text
Nombre
Rubro
Estado
Datos de contacto
Configuraciones globales permitidas
```

No modificar información financiera o histórica arbitrariamente.

Separar:

```text
Configuración administrativa
```

de:

```text
Datos históricos
```

---

# 9. SUSPENDER NEGOCIO

Agregar estados claros.

Por ejemplo:

```text
Activo
Suspendido
Inactivo
```

Cuando un negocio esté suspendido:

- sus usuarios no deberían poder utilizar el sistema normalmente
- mostrar mensaje apropiado
- no eliminar sus datos
- conservar información
- permitir al SuperAdmin reactivarlo.

El middleware/backend debe verificar el estado.

No basta con ocultar botones en frontend.

---

# 10. PANEL PRINCIPAL DEL SUPERADMIN

Crear un dashboard administrativo real.

Mostrar tarjetas como:

```text
Usuarios totales
Usuarios activos
Usuarios suspendidos

Negocios totales
Negocios activos
Negocios suspendidos

Productos registrados
Ventas
Compras

Usuarios registrados recientemente
Negocios registrados recientemente
```

Agregar gráficos solamente si los datos realmente están disponibles y tiene sentido.

No llenar el dashboard de estadísticas inútiles.

---

# 11. ACTIVIDAD RECIENTE

Crear una sección:

```text
Actividad reciente
```

Mostrar eventos importantes:

```text
Nuevo usuario registrado
Nuevo negocio creado
Usuario suspendido
Usuario activado
Usuario editado
Negocio suspendido
Negocio activado
Usuario eliminado
Negocio eliminado
Cambio de rol
Cambio de contraseña administrativa
```

Esto debe venir de un sistema de auditoría.

---

# 12. AUDITORÍA / LOGS

Quiero que el SuperAdmin pueda saber:

```text
Quién
Qué hizo
Sobre qué
Cuándo
Desde dónde
Resultado
```

Ejemplo:

```text
Usuario:
admin@ejemplo.com

Acción:
Suspendió usuario

Objetivo:
Juan Pérez

Fecha:
27/08/2026 10:15

IP:
...

Resultado:
Exitoso
```

Crear o mejorar una tabla de auditoría si es necesario.

Registrar como mínimo:

- login
- logout
- creación de usuario
- edición de usuario
- eliminación de usuario
- activación
- suspensión
- cambio de rol
- creación de negocio
- edición de negocio
- suspensión de negocio
- eliminación de negocio
- cambios administrativos importantes.

No guardar contraseñas ni información sensible innecesaria.

---

# 13. FILTROS Y BÚSQUEDA

Las tablas del SuperAdmin deben tener:

```text
Buscar
```

y filtros.

Usuarios:

```text
Todos
Activos
Inactivos
Suspendidos
SuperAdmin
Admin
Empleado
```

Negocios:

```text
Todos
Activos
Suspendidos
Inactivos
Por rubro
```

También permitir ordenar por:

```text
Fecha
Nombre
Estado
Último acceso
```

Si hay muchos registros, utilizar paginación real desde backend.

No cargar miles de registros de golpe.

---

# 14. EXPORTACIÓN

Si encaja con la arquitectura existente, agregar:

```text
Exportar usuarios
Exportar negocios
Exportar auditoría
```

Preferentemente:

```text
CSV
Excel
```

pero no agregar dependencias innecesarias.

La exportación debe respetar permisos.

---

# 15. ACCIONES MASIVAS

Si es razonable, agregar selección múltiple:

```text
☑ Usuario 1
☑ Usuario 2
☑ Usuario 3
```

Acciones:

```text
Activar seleccionados
Suspender seleccionados
```

No permitir eliminación masiva peligrosa sin confirmación.

Nunca permitir que una acción masiva elimine SuperAdmins accidentalmente.

---

# 16. RESETEO DE CONTRASEÑA

El SuperAdmin debe poder ayudar a un usuario que perdió acceso.

No quiero que el SuperAdmin vea la contraseña.

Nunca mostrar contraseñas.

Implementar:

```text
Restablecer contraseña
```

mediante un mecanismo seguro.

Si el sistema actual no tiene recuperación adecuada:

- crear token temporal
- expirar token
- invalidar después de uso
- no almacenar contraseña en texto plano.

Si es necesario permitir que el SuperAdmin fuerce un cambio de contraseña al próximo login:

```text
Debe cambiar su contraseña al iniciar sesión.
```

---

# 17. CERRAR SESIONES

Si la arquitectura lo permite, agregar:

```text
Cerrar sesiones del usuario
```

y:

```text
Cerrar todas las sesiones
```

Esto es útil cuando:

- se roba una cuenta
- se cambia una contraseña
- se suspende un usuario
- existe actividad sospechosa.

Debe hacerse desde backend.

---

# 18. IMPERSONACIÓN / "INGRESAR COMO USUARIO"

Analiza si sería útil agregar:

```text
Ingresar como este usuario
```

para soporte técnico.

Si lo implementas, debe ser extremadamente seguro.

Requisitos:

- solo SuperAdmin
- registrar auditoría
- indicar claramente que se está actuando como otro usuario
- botón "Volver al SuperAdmin"
- nunca conocer la contraseña del usuario
- nunca permitir que el usuario impersonado escale permisos
- registrar inicio y final de la impersonación.

Si consideras que la arquitectura actual no es suficientemente segura para implementarlo, no lo agregues todavía y explica por qué.

---

# 19. ELIMINAR "PAGOS"

Quiero eliminar la sección/módulo de:

```text
Pagos
```

del área SuperAdmin.

PERO MUY IMPORTANTE:

Primero analiza qué significa "Pagos" en este proyecto.

Si "Pagos" se refiere al sistema de pagos/suscripciones de la plataforma, eliminarlo.

Si existe otra funcionalidad de pagos relacionada con:

- ventas
- POS
- cuentas por cobrar
- créditos
- compras

NO eliminarla.

La eliminación debe afectar solamente el módulo administrativo de plataforma que corresponda.

Si el módulo de pagos de plataforma tiene:

- rutas
- controladores
- vistas
- JavaScript
- tablas
- menús
- permisos
- endpoints
- consultas

debes eliminarlos o desactivarlos correctamente.

No dejar enlaces rotos.

No dejar menús apuntando a páginas inexistentes.

No dejar rutas administrativas accesibles.

Si existen tablas exclusivamente utilizadas por ese módulo y ya no son necesarias, evalúa eliminarlas mediante migración.

Antes de eliminarlas, comprobar dependencias.

---

# 20. MENÚ DEL SUPERADMIN

Rediseñar el menú administrativo para que tenga sentido.

Una estructura sugerida:

```text
SUPERADMIN

Dashboard

Usuarios
    Todos
    Activos
    Suspendidos

Negocios
    Todos
    Activos
    Suspendidos

Auditoría
    Actividad
    Logs

Sistema
    Configuración
    Unidades
    Categorías globales
    Otros parámetros globales

Herramientas
    Mantenimiento
    Exportaciones

Mi cuenta
    Perfil
    Seguridad
```

No agregues secciones que el proyecto realmente no pueda soportar.

---

# 21. CONFIGURACIÓN GLOBAL

Analiza qué configuraciones deberían pertenecer al SuperAdmin.

Por ejemplo:

```text
Nombre de la plataforma
Logo
Configuraciones globales
Estado del registro
Modo mantenimiento
```

Si implementas:

```text
Modo mantenimiento
```

debe existir una protección para que:

```text
SuperAdmin
```

pueda seguir entrando.

---

# 22. MODO MANTENIMIENTO

Sería útil que el SuperAdmin pueda activar:

```text
Modo mantenimiento
```

y mostrar un mensaje al resto de usuarios.

Debe quedar registrado:

```text
quién lo activó
cuándo
```

Y permitir desactivarlo.

---

# 23. ESTADÍSTICAS DE PLATAFORMA

Agregar estadísticas útiles si los datos existen.

Por ejemplo:

```text
Negocios creados este mes
Usuarios nuevos
Negocios activos
Usuarios activos
```

También:

```text
Nuevos negocios por mes
Nuevos usuarios por mes
```

Evitar estadísticas financieras si el módulo de pagos de plataforma será eliminado y no existen datos confiables.

---

# 24. ESTADO DE SALUD DEL SISTEMA

Si es compatible con la arquitectura, crear:

```text
Salud del sistema
```

Mostrando:

```text
Base de datos: OK
Sesiones: OK
Almacenamiento: OK
Configuración: OK
```

No exponer información sensible.

Nunca mostrar:

- contraseñas
- tokens
- claves secretas
- credenciales
- variables de entorno.

---

# 25. SEGURIDAD DEL SUPERADMIN

Este punto es obligatorio.

Audita todas las rutas del SuperAdmin.

No confíes en:

```text
ocultar botón
```

El backend debe validar:

```text
¿El usuario es realmente SuperAdmin?
```

antes de ejecutar cualquier operación.

Proteger:

- rutas
- controladores
- endpoints AJAX
- APIs
- acciones POST
- acciones DELETE
- cambios de roles
- cambios de estado
- eliminación.

Implementar correctamente:

- autorización
- CSRF si corresponde
- validación
- sanitización
- prepared statements
- protección contra IDOR
- protección contra escalamiento de privilegios
- protección contra SQL injection
- protección contra XSS
- protección contra CSRF
- control de sesión.

---

# 26. IDOR / ACCESO A OTROS NEGOCIOS

Este punto es especialmente importante.

Un administrador de negocio NO debe poder modificar:

```text
/business/123
```

cambiando el ID a:

```text
/business/124
```

para acceder a otro negocio.

Todas las consultas deben verificar ownership/tenant.

Ejemplo conceptual:

```text
WHERE business_id = usuario.business_id
```

para usuarios normales/admin del negocio.

El SuperAdmin sí puede acceder globalmente.

---

# 27. MULTITENANCY

Tu sistema es multi-negocio.

Por lo tanto, revisa todas las operaciones del SuperAdmin y del resto de usuarios para evitar fugas de información entre negocios.

Debe existir una separación clara:

```text
SUPERADMIN
    ↓
todos los negocios

BUSINESS ADMIN
    ↓
solo su negocio

EMPLOYEE
    ↓
solo su negocio y permisos
```

Audita especialmente:

- usuarios
- productos
- ventas
- compras
- clientes
- proveedores
- inventario
- reportes
- créditos
- gastos
- kardex
- recetas.

---

# 28. PERFIL DEL SUPERADMIN

Agregar:

```text
Mi perfil
```

con:

```text
Nombre
Email
Contraseña
Último acceso
```

Permitir:

```text
Cambiar contraseña
```

con validación segura.

---

# 29. CONFIRMACIONES

Acciones peligrosas deben tener confirmación.

Por ejemplo:

```text
Eliminar usuario
Suspender usuario
Eliminar negocio
Suspender negocio
Cambiar rol
Cerrar sesiones
Modo mantenimiento
```

La confirmación debe indicar claramente qué va a suceder.

Ejemplo:

```text
¿Suspender a Juan Pérez?

El usuario perderá acceso a la plataforma,
pero sus datos históricos se conservarán.
```

---

# 30. BORRADO SEGURO

No quiero:

```text
DELETE FROM users WHERE id = ...
```

sin analizar relaciones.

Antes de eliminar cualquier entidad:

1. comprobar dependencias
2. determinar si existe historial
3. utilizar soft delete cuando corresponda
4. conservar auditoría
5. evitar romper foreign keys
6. invalidar sesiones
7. actualizar estados relacionados si corresponde.

---

# 31. BASE DE DATOS

Analiza el esquema actual.

Si hacen falta cambios:

- crear migraciones
- no editar destructivamente tablas en producción
- conservar datos
- agregar índices
- agregar foreign keys
- agregar campos de estado
- agregar auditoría
- agregar soft delete si es necesario.

No crear tablas duplicadas si ya existe una estructura equivalente.

---

# 32. BACKEND

Toda funcionalidad del SuperAdmin debe existir correctamente en backend.

No quiero una interfaz que simule acciones.

Por ejemplo:

Si aparece:

```text
Eliminar
```

debe realmente existir el endpoint correspondiente y estar protegido.

Si aparece:

```text
Suspender
```

debe actualizar realmente el estado.

Si aparece:

```text
Editar
```

debe persistir correctamente los cambios.

---

# 33. FRONTEND

Mantén el lenguaje visual actual de TuInventario.

No quiero que parezca una aplicación diferente.

Conservar:

- colores
- tarjetas
- botones
- tipografía
- espaciado
- componentes
- estilo general.

Pero mejorar:

- jerarquía visual
- tablas
- filtros
- estados
- modales
- mensajes
- confirmaciones
- responsive.

---

# 34. ESTADOS VISUALES

Utilizar estados claros:

```text
Activo
Suspendido
Inactivo
Eliminado
```

No usar únicamente colores.

Mostrar texto.

Ejemplo:

```text
● Activo
● Suspendido
● Inactivo
```

---

# 35. NOTIFICACIONES

Después de acciones administrativas mostrar mensajes claros:

```text
Usuario actualizado correctamente.

Usuario suspendido correctamente.

Negocio activado correctamente.

Usuario eliminado correctamente.
```

Y errores:

```text
No se pudo eliminar el usuario porque tiene información histórica asociada.
```

Nunca mostrar errores SQL al usuario final.

---

# 36. MANEJO DE ERRORES

Implementar manejo consistente.

Errores esperados:

```text
404
403
422
500
```

Especialmente:

```text
403 Forbidden
```

cuando un usuario intenta acceder a una función de SuperAdmin.

No mostrar:

- stack traces
- SQL
- rutas internas
- credenciales
- información sensible.

---

# 37. REGISTRO DE AUDITORÍA

Toda acción crítica realizada por SuperAdmin debe registrarse.

Ejemplo:

```text
SUPERADMIN
    ↓
Suspendió usuario #25
    ↓
Fecha
    ↓
IP
    ↓
Resultado
```

Pero nunca guardar:

```text
password
token
secret
```

---

# 38. RENDIMIENTO

No hagas consultas como:

```text
SELECT *
```

cuando no sean necesarias.

Utiliza:

- paginación
- índices
- consultas agregadas
- filtros en SQL
- límites
- cargas bajo demanda.

El SuperAdmin debe seguir funcionando aunque existan:

```text
10.000 usuarios
1.000 negocios
100.000 productos
```

No es necesario optimizar prematuramente todo, pero no diseñar consultas obviamente costosas.

---

# 39. RESPONSIVE

El SuperAdmin debe funcionar correctamente en:

- desktop
- tablet
- móvil.

Las tablas pueden tener scroll horizontal cuando sea necesario.

No romper el dashboard actual.

---

# 40. NO CREAR FUNCIONALIDADES FALSAS

No quiero botones que solamente digan:

```text
Próximamente
```

Si agregas una funcionalidad, debe funcionar realmente.

Si alguna funcionalidad requiere infraestructura que no existe, documentarlo antes de implementarla.

---

# 41. COMPATIBILIDAD

No romper:

- login
- registro
- recuperación de contraseña
- inventario
- compras
- ventas
- POS
- clientes
- proveedores
- créditos
- gastos
- arqueo
- reportes
- recetas
- platos.

Especialmente:

**NO eliminar los pagos de las ventas/POS si "Pagos" se refiere a eso.**

La eliminación solicitada es del módulo administrativo de plataforma que actualmente aparece como "Pagos", después de determinar exactamente qué representa.

---

# 42. PRUEBAS OBLIGATORIAS

Después de implementar, probar como mínimo:

## Usuarios

```text
Crear usuario
Editar usuario
Activar usuario
Suspender usuario
Eliminar usuario
Restaurar usuario si existe soft delete
Cambiar rol
Buscar usuario
Filtrar usuario
```

## Negocios

```text
Ver negocio
Editar negocio
Activar negocio
Suspender negocio
Eliminar negocio cuando corresponda
Buscar negocio
Filtrar negocio
```

## Seguridad

Comprobar:

```text
Usuario normal → NO puede entrar a SuperAdmin

Admin negocio → NO puede entrar a SuperAdmin

Admin negocio → NO puede acceder a otro negocio

Usuario manipula IDs → NO obtiene información de otro negocio

Usuario intenta modificar rol → rechazado

Usuario intenta eliminar SuperAdmin → rechazado
```

## SuperAdmin

Comprobar:

```text
SuperAdmin → puede gestionar usuarios
SuperAdmin → puede gestionar negocios
SuperAdmin → puede consultar auditoría
SuperAdmin → puede suspender usuarios
SuperAdmin → puede activar usuarios
SuperAdmin → puede editar usuarios
SuperAdmin → puede administrar estados
```

---

# 43. PRUEBAS DE ELIMINACIÓN

Especialmente probar usuarios con historial.

Ejemplo:

```text
Usuario
  ↓
Tiene ventas
  ↓
Tiene movimientos
  ↓
Tiene registros históricos
```

Al eliminar/desactivar:

```text
NO romper historial
NO romper foreign keys
NO eliminar ventas históricas accidentalmente
NO eliminar auditoría
```

---

# 44. PRUEBAS DEL MÓDULO PAGOS

Antes de eliminar:

1. encontrar todas las referencias al módulo
2. identificar tablas
3. identificar rutas
4. identificar controladores
5. identificar vistas
6. identificar JavaScript
7. identificar permisos
8. identificar menú
9. identificar dependencias.

Después:

```text
No debe aparecer en el menú SuperAdmin.

No debe existir una ruta administrativa accesible.

No deben quedar enlaces rotos.

No deben quedar errores JavaScript.

No deben quedar referencias innecesarias.
```

Pero nuevamente:

**NO eliminar pagos relacionados con ventas/POS, créditos o cuentas por cobrar si son parte del funcionamiento normal del negocio.**

---

# 45. AUDITORÍA FINAL DEL PROYECTO

Una vez terminadas las modificaciones, revisa nuevamente todo el código y busca:

```text
TODO
FIXME
admin
superadmin
payment
payments
role
permission
delete
update
user
business
```

para detectar referencias antiguas.

También verifica:

- rutas huérfanas
- botones sin endpoint
- endpoints sin autorización
- tablas sin uso
- JavaScript sin uso
- imports sin uso
- enlaces rotos
- permisos inconsistentes.

---

# 46. DOCUMENTACIÓN

Al finalizar crea/actualiza documentación explicando:

## SuperAdmin

Qué puede hacer.

## Roles

Qué puede hacer cada rol.

## Usuarios

Cómo se administran.

## Negocios

Cómo se administran.

## Estados

Qué significa cada estado.

## Auditoría

Qué acciones se registran.

## Seguridad

Cómo se protegen las operaciones administrativas.

## Migraciones

Qué cambios se hicieron en base de datos.

---

# 47. NO QUIERO SOBREINGENIERÍA

Aunque quiero un SuperAdmin completo, no quiero que inventes una arquitectura gigantesca innecesariamente.

Primero reutiliza lo que ya existe.

Si ya existe:

```text
users
businesses
roles
permissions
```

úsalo.

Si ya existe un servicio:

```text
AuthService
```

reutilízalo.

Si existe un middleware:

```text
AdminMiddleware
```

mejóralo en vez de duplicarlo.

Si realmente hace falta una nueva tabla o servicio, justifica su necesidad.

---

# 48. PRIORIDADES

Implementa en este orden:

## PRIORIDAD 1 — CRÍTICO

- seguridad del SuperAdmin
- autorización backend
- editar usuarios
- eliminar/desactivar usuarios
- activar/suspender usuarios
- gestionar negocios
- editar negocios
- activar/suspender negocios
- proteger SuperAdmin
- eliminar correctamente el módulo Pagos de plataforma
- corregir rutas y permisos.

## PRIORIDAD 2 — IMPORTANTE

- búsqueda
- filtros
- paginación
- detalle de usuarios
- detalle de negocios
- auditoría
- actividad reciente
- cambio de contraseña
- cierre de sesiones.

## PRIORIDAD 3 — MEJORAS

- dashboard avanzado
- estadísticas
- exportaciones
- acciones masivas
- modo mantenimiento
- salud del sistema
- herramientas administrativas.

---

# 49. RESULTADO FINAL ESPERADO

Quiero entrar como SuperAdmin y encontrar algo parecido a:

```text
┌─────────────────────────────────────────────┐
│              SUPERADMIN                     │
├─────────────────────────────────────────────┤
│                                             │
│ Dashboard                                   │
│                                             │
│ Usuarios                                    │
│   ├── Todos                                 │
│   ├── Activos                               │
│   └── Suspendidos                           │
│                                             │
│ Negocios                                    │
│   ├── Todos                                 │
│   ├── Activos                               │
│   └── Suspendidos                           │
│                                             │
│ Auditoría                                   │
│                                             │
│ Sistema                                     │
│   ├── Configuración                         │
│   ├── Mantenimiento                         │
│   └── Salud del sistema                     │
│                                             │
│ Mi cuenta                                   │
│                                             │
└─────────────────────────────────────────────┘
```

Dashboard:

```text
┌────────────┐ ┌────────────┐ ┌────────────┐
│ Usuarios   │ │ Negocios   │ │ Activos    │
│   1.250    │ │    320     │ │    980     │
└────────────┘ └────────────┘ └────────────┘
```

Usuarios:

```text
Nombre
Email
Rol
Negocio
Estado
Último acceso
Acciones
```

Acciones:

```text
Ver
Editar
Activar
Suspender
Restablecer contraseña
Cerrar sesiones
Eliminar
```

Negocios:

```text
Negocio
Propietario
Rubro
Usuarios
Estado
Fecha de registro
Última actividad
Acciones
```

Acciones:

```text
Ver
Editar
Activar
Suspender
Eliminar
```

---

# 50. REGLA FINAL

El objetivo NO es simplemente agregar botones al SuperAdmin.

El objetivo es construir un **centro administrativo seguro y completo para la plataforma TuInventario**.

Debe quedar claramente separado:

```text
                    TUINVENTARIO
                         │
             ┌───────────┴───────────┐
             │                       │
        SUPERADMIN              NEGOCIOS
             │                       │
             │               ┌───────┴────────┐
             │               │                │
             │             ADMIN            EMPLEADOS
             │               │                │
             │               └───────┬────────┘
             │                       │
             │                  OPERACIÓN
             │                       │
             │        ┌──────────────┼──────────────┐
             │        │              │              │
             │    Inventario       Ventas        Compras
             │
             ├── Usuarios
             ├── Negocios
             ├── Auditoría
             ├── Seguridad
             ├── Sistema
             └── Mantenimiento
```

El SuperAdmin administra **la plataforma**.

El administrador administra **su negocio**.

Los empleados trabajan **dentro del negocio**.

No mezclar estos niveles.

---

# FORMA DE EJECUCIÓN

Trabaja en estas etapas:

### ETAPA 1
Audita el repositorio completo.

### ETAPA 2
Presenta un diagnóstico de la arquitectura actual.

### ETAPA 3
Presenta el diseño propuesto y las tablas/cambios necesarios.

### ETAPA 4
Implementa primero seguridad y permisos.

### ETAPA 5
Implementa gestión de usuarios.

### ETAPA 6
Implementa gestión de negocios.

### ETAPA 7
Implementa auditoría.

### ETAPA 8
Elimina correctamente el módulo Pagos de plataforma, sin tocar pagos del POS.

### ETAPA 9
Implementa mejoras adicionales del SuperAdmin.

### ETAPA 10
Ejecuta pruebas.

### ETAPA 11
Haz una auditoría final buscando rutas, botones, permisos y referencias rotas.

### ETAPA 12
Entrega un resumen de:

- archivos modificados
- tablas modificadas
- migraciones creadas
- funcionalidades agregadas
- funcionalidades eliminadas
- problemas de seguridad corregidos
- pruebas realizadas
- posibles mejoras futuras.

**NO hagas cambios destructivos sin explicarlos.**

**NO elimines datos existentes sin migración o estrategia de conservación.**

**NO elimines pagos del POS/ventas por confundirlos con el módulo "Pagos" del SuperAdmin.**

**NO empieces por el frontend. Primero entiende y corrige la autorización y la arquitectura del backend.**