# ARSITE Backend API

Backend principal del ecosistema ARSITE. Esta aplicación concentra la lógica de negocio, la autenticación, la administración de archivos, los endpoints del CMS y los endpoints públicos que consume la web corporativa.

## Objetivo

Proveer una API centralizada para:

- autenticación y gestión de usuarios
- administración de contenido desde el CMS
- exposición de contenido público para la web
- recepción y seguimiento de contactos
- almacenamiento de imágenes y archivos
- exportaciones y reportes

## Stack

- PHP `8.2+`
- Laravel `12`
- Laravel Sanctum
- MySQL
- Laravel Excel
- DomPDF
- Vite
- Tailwind CSS 4

## Módulos del sistema

- Usuarios
- Banners
- Destacados
- Productos
- Servicios
- Partners
- Clientes
- Noticias
- Hitos
- Contactos

## Estructura principal

```text
arsite/
├─ app/
│  ├─ Http/Controllers/Api/
│  ├─ Models/
│  ├─ Policies/
│  ├─ Support/
│  └─ Traits/
├─ config/
├─ database/
│  ├─ migrations/
│  └─ seeders/
├─ resources/
│  └─ views/
├─ routes/
│  ├─ api.php
│  └─ web.php
└─ storage/
   └─ app/public/
```

## Requisitos

- PHP `8.2` o superior
- Composer
- Node.js `20+`
- npm
- MySQL

## Instalación

```bash
composer install
npm install
copy .env.example .env
php artisan key:generate
php artisan migrate
php artisan storage:link
```

## Variables de entorno clave

Ejemplo mínimo:

```env
APP_NAME=ARSITE
APP_ENV=local
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=arsite
DB_USERNAME=root
DB_PASSWORD=

MAIL_MAILER=smtp
MAIL_HOST=127.0.0.1
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_FROM_ADDRESS="no-reply@example.com"
MAIL_FROM_NAME="${APP_NAME}"

MAIL_ADMIN_ADDRESS=soporte@arsite.com.mx
```

## Desarrollo local

Servidor Laravel:

```bash
php artisan serve
```

Assets:

```bash
npm run dev
```

Flujo combinado disponible en Composer:

```bash
composer dev
```

## Endpoints públicos destacados

Estos endpoints alimentan la web pública:

- `GET /api/banners/public`
- `GET /api/destacados/public`
- `GET /api/clientes/public`
- `GET /api/partners/public`
- `GET /api/servicios/public`
- `POST /api/contactos`

## Endpoints protegidos

El CMS consume rutas autenticadas para:

- usuarios
- banners
- destacados
- productos
- servicios
- partners
- clientes
- noticias
- hitos
- contactos

La autenticación se realiza mediante `Bearer Token` con Sanctum.

## Archivos y storage

Las cargas públicas se almacenan en `storage/app/public` y se exponen mediante:

```bash
php artisan storage:link
```

Carpetas habituales:

- `storage/app/public/banners`
- `storage/app/public/destacados`
- `storage/app/public/clientes`
- `storage/app/public/partners`
- `storage/app/public/productos`
- `storage/app/public/servicios`
- `storage/app/public/noticias`
- `storage/app/public/avatars`

## Logs y auditoría

El backend incluye logging estructurado para:

- autenticación
- accesos públicos
- errores de storage
- acciones administrativas clave
- validaciones rechazadas en módulos importantes

Ubicación:

```text
storage/logs/laravel.log
```

## Testing

```bash
php artisan test
```

O desde Composer:

```bash
composer test
```

## Formato y calidad

```bash
./vendor/bin/pint
```

## Relación con otras apps

- `arsite-cms` consume esta API para administrar contenido.
- `web-publica` consume endpoints públicos de esta API.

## Observaciones

- La consistencia entre base de datos, migraciones y storage es importante para evitar errores de creación o carga de archivos.
- Para producción conviene revisar correo SMTP, permisos de storage, cachés y variables de entorno antes del despliegue.
