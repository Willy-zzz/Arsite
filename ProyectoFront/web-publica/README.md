# ARSITE Web Pública

Sitio corporativo público de ARSITE. Esta aplicación entrega la experiencia visible para clientes y visitantes, y consume contenido administrado desde el backend principal.

## Objetivo

Presentar la información institucional y comercial de la empresa mediante una SPA basada en Vue, servida desde Laravel.

## Stack

- PHP `8.2+`
- Laravel `12`
- Vue `3`
- Vue Router `4`
- Axios
- Vite `7`
- Tailwind CSS `4`
- `vue3-recaptcha2`

## Secciones principales

- `Home`
- `About`
- `Products`
- `CategoriaProductos`
- `Services`
- `Partners`
- `Clients`
- `Contact`
- `Terminos`
- flujo de soporte en `resources/js/pages/soporte`

## Integraciones con backend

La web consume endpoints públicos del backend `arsite`, por ejemplo:

- banners del carrusel
- destacados del home
- clientes
- partners
- servicios
- formulario de contacto

## Estructura principal

```text
web-publica/
├─ app/
├─ public/
├─ resources/
│  ├─ css/
│  └─ js/
│     ├─ components/
│     ├─ pages/
│     ├─ router/
│     └─ utils/
├─ routes/
│  └─ web.php
└─ package.json
```

## Requisitos

- PHP `8.2` o superior
- Composer
- Node.js `20+`
- npm

## Instalación

```bash
composer install
npm install
copy .env.example .env
php artisan key:generate
php artisan migrate
```

## Variables de entorno

Ejemplo recomendado:

```env
APP_URL=http://127.0.0.1:8001
VITE_API_BASE_URL=http://127.0.0.1:8000/api
```

Si se usa reCAPTCHA en producción:

```env
VITE_RECAPTCHA_SITE_KEY=tu_site_key
```

## Desarrollo local

Servidor Laravel:

```bash
php artisan serve --port=8001
```

Assets:

```bash
npm run dev
```

## Build

```bash
npm run build
```

## Scripts disponibles

```bash
npm run dev
npm run build
```

## Flujo de contenido

1. El backend administra banners, destacados, clientes, partners y servicios.
2. La web pública consulta los endpoints públicos correspondientes.
3. El visitante ve contenido actualizado sin entrar al CMS.
4. Formularios como `Contáctanos` envían información al backend para seguimiento administrativo.

## Notas

- Laravel funciona como contenedor de la aplicación y punto de entrada de la SPA.
- Vue Router maneja la navegación del lado cliente.
- La consistencia visual depende de mantener alineadas las rutas públicas del backend con el contenido publicado en CMS.
