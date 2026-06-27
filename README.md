<div align="center">
  <img src="https://img.icons8.com/color/120/000000/box.png" alt="Logo">
  <h1>TuInventario ERP v2.0</h1>
  <p><b>Sistema Integral de Ventas, Inventario y Gestión Fiscal</b></p>
</div>

---

## 🚀 Descripción del Proyecto

**TuInventario** es un sistema ERP (Enterprise Resource Planning) minimalista, extremadamente rápido y autogestionable, diseñado específicamente para controlar inventarios, puntos de venta (POS) y métricas contables. Cuenta con un fuerte enfoque práctico para comercios, soportando un entorno de **múltiples divisas (USD, VES, EUR, COP)**, adaptabilidad fiscal (Cálculos de IVA e IGTF automatizados), y lectura de tasas en tiempo real. 

Todo el sistema está construido bajo una potente identidad visual empleando paradigmas *Glassmorphism* (Diseño de cristal) e integrando **Dark/Light Mode** nativo.

## 🎯 Objetivos del Sistema
* ✅ Proveer una plataforma con **Cero Fricción de inicio** para pequeños y medianos comercios.
* ✅ Centralizar la contabilidad operativa: Cálculo de Inversión y **Ganancia Operativa Neta** en cada producto.
* ✅ Lograr la mayor agilidad en Punto de Venta integrando **HTMX y Alpine.js** sin tener que adoptar la complejidad que requieren React/Vue.js.
* ✅ Mantener trazabilidad de inventario detallada utilizando **Módulo de Kardex** en vivo.

## ✨ Características Principales (Features)

* 💳 **Punto de Venta Híbrido:** Gestión rápida con múltiples métodos de pago integrados dinámicamente y cálculo automático de impuestos.
* 📦 **Calculadora de Costos & Configuración:** Productos soportes para Venta por Bulto, Costo Unitario, márgenes de ganancia precalculables (Fórmula Inversa de Margen) y atributos personalizables al vuelo.
* 🤖 **Kardex y Log de Auditoría:** Historial estricto de entradas, reajustes y salidas del inventario, atada a logs del sistema para rastreo de actividad de un usuario.
* 📊 **Dashboard & Reportes:** Métricas al instante. Conoce cuánto cuesta (inversión) tu almacén actual vs cuánto ganarás al venderlo.
* ☁️ **Auto-Migración Segura:** Estructuras SQL robustas. Si agregas columnas, el sistema reacciona dinámicamente al iniciarse creando los recursos sin crashear.

## 🛠️ Stack Tecnológico

![PHP](https://img.shields.io/badge/PHP_8.1+-777BB4?style=for-the-badge&logo=php&logoColor=white) 
![TailwindCSS](https://img.shields.io/badge/Tailwind_CSS-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white)
![Alpine.js](https://img.shields.io/badge/Alpine.js-8BC0D0?style=for-the-badge&logo=alpine.js&logoColor=white)
![PostgreSQL](https://img.shields.io/badge/PostgreSQL_/_SQLite-316192?style=for-the-badge&logo=postgresql&logoColor=white)

---

## ☁️ Guía de Despliegue (Supabase + Vercel)

El proyecto soporta de forma nativa Base de Datos en **SQLite** localmente. Sin embargo, para despliegues Serverless o la nube (como Vercel, el que resetea los archivos dinámicos locales), la conexión ideal a usar será **Supabase (PostgreSQL)**.

### Paso 1: Configurar la Base de Datos con Supabase
1. Ingresa a [Supabase](https://supabase.com) y crea tu proyecto. 
2. Una vez creado, busca tu identificador de Base de Datos (en *Settings > Database > Connection String*), te entregará un formato como `postgresql://postgres:password_tuya@db.aws...`
3. Ve a la consola **SQL Editor** dentro de Supabase.
4. Abre e importa todo el contenido del archivo `database/schema.sql` que provee este repositorio. 
*(Nota Técnica: Asegurate de cambiar en el script la palabra `AUTOINCREMENT` propia de SQLite por la palabra `SERIAL` que es el equivalente nativo en Postgres).*

### Paso 2: Despliegue en Vercel
Para tu facilidad, con un solo clic Vercel clonará el repositorio y levantará la plataforma utilizando una instancia PHP de comunidad (Vercel-PHP). Solo necesitas darle el string de base de datos que te dio Supabase.

[![Deploy with Vercel](https://vercel.com/button)](https://vercel.com/new/clone?repository-url=https%3A%2F%2Fgithub.com%2FMiguelOllarves%2FProyectoNegocio&env=DATABASE_URL)

> **Proceso Manual:** Si lo prefieres, solo vinvula este Github dentro del panel de Vercel y añade la variable de entorno **`DATABASE_URL`** indicando tu cadena de conexión URI. El archivo `vercel.json` ya se encarga automáticamente de levantar el webserver PHP por ti.

### Paso 3: Ajuste de Conexión PHP (En Código)
Si tu `DATABASE_URL` no la lee automáticamente el sistema, asegúrate de actualizar tu documento de persistencia en 📁 `config/Database.php` y utilizar el formato de Postgres (Drive PDO "pgsql").
```php
$dsn = "pgsql:host=TU_HOST;port=5432;dbname=postgres";
$this->conn = new PDO($dsn, "postgres", "TU_CONTRASEÑA");
```

---

## 👨‍💻 Acerca del Autor y Soporte (Feedback)
El sistema ha sido estructurado para ser auto-desplegado. El login provee un usuario de demostración default:  
**Usuario:** `admin` | **Contraseña:** `admin123` *(O lo que hayas guardado como Seed)*.

> Requieres adaptaciones específicas, tienes una lluvia de requerimientos para llevar el sistema más lejos ("más fino"), o buscas un mantenimiento a la medida:

📩 **WhatsApp de Contacto Directo:** [0414-5176772](https://wa.me/584145176772)

<p align="center">Hecho para el Comercio Moderno 💚💙</p>
