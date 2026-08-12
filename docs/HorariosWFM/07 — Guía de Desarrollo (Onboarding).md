---
tipo: guia-desarrollo
proyecto: "HorariosWFM"
fecha: 2026-08-12
tags: [desarrollo, setup, onboarding]
---

# 🛠 Guía de Desarrollo

## 1. Requisitos Previos
- **Lenguaje/Runtime**: PHP 8.3+ y Node.js (v18+)
- **Base de Datos**: PostgreSQL 16
- **Caché / Colas**: Redis
- **Framework**: Laravel 13
- **Herramientas**: Composer, NPM

## 2. Setup Inicial
```bash
# 1. Clonar el repositorio
git clone <url-del-repo> horarios-wfm
cd horarios-wfm

# 2. Instalar dependencias backend y frontend
composer install
npm install

# 3. Configurar entorno
cp .env.example .env
php artisan key:generate

# 4. Configurar base de datos en .env y luego ejecutar:
php artisan migrate --seed

# 5. Instalar dependencias de Flux UI (requiere credenciales)
composer config http-basic.composer.fluxui.dev "$FLUX_USERNAME" "$FLUX_LICENSE_KEY"
```

## 3. Variables de Entorno Principales
| Variable | Descripción | Valor por defecto |
| :--- | :--- | :--- |
| `DB_CONNECTION` | Conexión a la base de datos | `pgsql` |
| `CACHE_STORE` / `QUEUE_CONNECTION` | Usar Redis para mejor rendimiento | `redis` |
| `UCCX_HOST` / `UCCX_USER` / `UCCX_PASS` | Credenciales de Cisco Finesse / UCCX | - |
| `CUIC_HOST` / `CUIC_USER` / `CUIC_PASS` | Credenciales de reportes históricos CUIC | - |
| `WEBEX_BOT_TOKEN` / `WEBEX_ROOM_ID` | Integración de notificaciones de soporte | - |

## 4. Scripts y Comandos Disponibles
- **`composer dev`**: Comando concurrente que levanta todo el entorno local (Servidor PHP, Laravel Reverb/Horizon, Logs via Pail y Vite HMR `npm run dev`).
- **`composer dev:uploads`**: Igual que `composer dev` pero aumenta límites de subida de archivos (20MB) en artisan serve.
- **`composer test`**: Ejecuta la suite de pruebas completa (Pest PHP).
- **`composer lint:check`**: Verifica el formateo del código usando Pint.
- **`vendor/bin/pint --format agent`**: Corrige el formateo del código PHP.
- **`npm run build`**: Genera el bundle de producción para el frontend.

## 5. Estándares del Proyecto
- **Linter/Formatter**: Laravel Pint (obligatorio antes de hacer commit).
- **Git**: Convención de commits en español (ej: `feat(wfm): agregar validación...`). Obligatorio prefijar el alcance (scope) con el módulo afectado.
- **Branching**: Crear ramas desde `develop` con el formato `tipo/{modulo}-descripcion`. Nunca comitear a `main` o `develop` directamente.
- **Tipado estricto**: Todo archivo PHP debe declarar `declare(strict_types=1);`.

## 6. Comandos Útiles / Troubleshooting
- **Error de Vite (Manifest no encontrado)**: Ejecuta `npm run build` o mantén corriendo `composer dev`.
- **Limpiar aplicación (Cachés, vistas, rutas)**: `php artisan optimize:clear`
- **Recrear base de datos limpia con datos iniciales**: `php artisan migrate:fresh --seed`
- **Levantar Worker de sincronización Cisco (Manual)**: `php artisan cisco:sync --loop --interval=5`

---
**Relacionado**: [[03-Arquitectura]]
