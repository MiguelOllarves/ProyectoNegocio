# Tu Inventario

Aplicacion web PHP para administrar operaciones de comercios y restaurantes. Incluye ventas, inventario, compras, caja, clientes, proveedores, gastos, creditos, reportes, recetas, menu QR y tienda publica.

## Funcionalidades

- Punto de venta y registro de ventas, pagos, impuestos y anulaciones.
- Inventario por negocio, presentaciones, conversion de unidades, costo de productos y Kardex.
- Compras, proveedores, clientes, cuentas por cobrar, caja y gastos.
- Recetas y productos preparados; el consumo de recetas descuenta ingredientes del inventario.
- Dashboard, reportes, configuracion por tipo de negocio, menu QR y storefront publica.
- Interfaz web con PHP, JavaScript, Tailwind CSS y soporte PWA/offline.

## Arquitectura

- `public/index.php` es el punto de entrada, aplica encabezados de seguridad y enruta las solicitudes a los controladores de `modules/`.
- `modules/` contiene controladores, modelos y vistas agrupados por funcionalidad.
- `core/` contiene servicios compartidos, incluyendo conversion de unidades, calculo de costos, consumo de inventario y logica fiscal.
- `config/Database.php` conecta mediante PDO a PostgreSQL. `database/schema_postgres.sql` define el esquema; las migraciones pueden completar tablas y columnas.
- `api/index.php` adapta el punto de entrada para funciones PHP de Vercel. `scripts/build-vercel.js` prepara el paquete de despliegue.
- `public/` contiene los recursos del sitio y la PWA.

El esquema y la configuracion actuales usan PostgreSQL. `database/schema.sql` no es el esquema recomendado para desplegar esta version.

## Requisitos

- PHP 8.0 o posterior con `pdo_pgsql` habilitado. `fileinfo` tambien es necesario para validar imagenes.
- PostgreSQL 12 o posterior, o un servicio compatible como Supabase.
- Composer 2.
- Node.js 22.x y npm para compilar CSS y preparar el despliegue de Vercel.

## Desarrollo local

1. Instala las dependencias:

   ```sh
   composer install
   npm install
   npm run build:css
   ```

2. Crea una base de datos PostgreSQL. Configura la variable `DATABASE_URL` en el entorno del proceso o en un archivo `.env` local, ignorado por Git:

   ```dotenv
   DATABASE_URL=<URI_POSTGRESQL_PRIVADA>
   APP_URL=http://127.0.0.1:8000
   ```

   Usa valores propios; no copies credenciales reales a este README, al codigo ni a archivos versionados. Si la contrasena contiene caracteres reservados de URL, codificalos.

3. Las migraciones se ejecutan al conectar en entornos que no sean Vercel. Tambien puedes inicializar el esquema PostgreSQL desde `database/schema_postgres.sql`. El archivo `database/RESET_COMPLETO.sql` es destructivo: no lo ejecutes sobre una base de datos con datos que debas conservar.

4. Inicia el servidor de desarrollo:

   ```sh
   php -S 127.0.0.1:8000 -t public public/index.php
   ```

   Abre `http://127.0.0.1:8000`.

## Despliegue en Vercel

1. Crea una base PostgreSQL y aplica `database/schema_postgres.sql` desde el mecanismo administrativo del proveedor antes de desplegar. En Vercel las migraciones estan desactivadas por defecto para evitar operaciones largas durante una solicitud serverless.
2. Importa el repositorio en Vercel. `vercel.json` ejecuta `node scripts/build-vercel.js` y genera la funcion PHP y los recursos estaticos.
3. En **Project Settings > Environment Variables**, configura `DATABASE_URL` con la cadena de conexion PostgreSQL y `APP_URL` con la URL canonica HTTPS de la aplicacion. No guardes estas variables en Git.
4. Despliega, registra el primer negocio y comprueba el inicio de sesion, la conexion con la base y los recursos estaticos. El nombre de usuario inicial del administrador es el documento de identidad usado durante el registro.

Variables opcionales:

| Variable | Uso |
| --- | --- |
| `APP_URL` | URL canonica de la aplicacion; `NEXT_PUBLIC_APP_URL` se usa como alternativa. |
| `DB_HOST`, `DB_PORT`, `DB_USER`, `DB_PASS`, `DB_NAME` | Alternativa a `DATABASE_URL`, principalmente para desarrollo. |
| `SMTP_HOST`, `SMTP_USER`, `SMTP_PASS`, `SMTP_PORT`, `SMTP_SECURE`, `SMTP_FROM_EMAIL`, `SMTP_FROM_NAME` | Envio de correo. |
| `VAPID_PUBLIC_KEY`, `VAPID_PRIVATE_KEY` | Notificaciones Web Push. La clave privada es secreta; la publica puede compartirse con el cliente. |
| `SUBSCRIPTION_PRICE_USD` | Precio configurado para suscripciones. |
| `GTM_ID` | Identificador publico de Google Tag Manager. |
| `TRUSTED_PROXY_IPS` | Direcciones IP exactas de proxies inversos autorizados, separadas por comas. Solo se aceptan cabeceras de IP reenviadas cuando la solicitud llega desde una de estas direcciones. |
| `RUN_MIGRATIONS=1` | Habilita migraciones en Vercel; usalo solo si aceptas el tiempo y los cambios de esquema que implique. |

No uses prefijos publicos como `NEXT_PUBLIC_` para contrasenas, claves privadas o cadenas de conexion.

## Cuentas y seguridad

- El esquema ya no crea cuentas de demostracion ni usuarios privilegiados con contrasenas/hash predefinidos.
- El registro de la aplicacion crea un administrador asociado al negocio registrado. Las cuentas `super_admin` deben provisionarse de forma controlada por el operador, con una contrasena unica y un hash generado con `password_hash`; no habilites una cuenta predeterminada en produccion.
- Mantén `.env` y cualquier archivo de credenciales fuera de Git. Si una credencial se subio alguna vez, eliminar el archivo de la rama actual no basta: rota la credencial y limpia el historial afectado antes de publicar nuevos cambios.
- Configura `TRUSTED_PROXY_IPS` solo con las IP exactas de proxies que controles; sin esta lista se ignoran `X-Forwarded-For` y `CF-Connecting-IP` para rate limits y bloqueos.
- No subas copias de sesiones, volcados de base de datos, claves privadas ni respaldos que contengan datos personales o credenciales.

## Pruebas

Las pruebas existentes son scripts PHP, no una suite automatizada integrada. Por ejemplo:

```sh
php tests/test_product_configuration.php
```

Algunas pruebas requieren una base PostgreSQL local configurada mediante `DATABASE_URL`.
