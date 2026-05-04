# ARSITE CMS

Panel administrativo del ecosistema ARSITE. Esta aplicación SPA permite administrar el contenido que vive en el backend `arsite`.

## Objetivo

Centralizar la operación de contenido y administración para:

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

## Stack

- Vue `3`
- Vite `7`
- Vue Router `4`
- Pinia
- Axios
- Tailwind CSS `4`
- TipTap `3`
- Lowlight

## Funcionalidades principales

- autenticación contra backend con token
- panel administrativo por módulos
- formularios de alta y edición
- carga de imágenes
- respuestas a contactos desde CMS
- estadísticas y vistas administrativas
- validaciones visibles del lado cliente y validaciones backend

## Estructura principal

```text
arsite-cms/
├─ src/
│  ├─ components/
│  ├─ layouts/
│  ├─ router/
│  ├─ services/
│  ├─ stores/
│  ├─ utils/
│  └─ views/
├─ public/
└─ package.json
```

## Vistas principales

- `Dashboard`
- `UsersView`
- `BannersView`
- `SlidersView`
- `ProductsView`
- `ServicesView`
- `PartnersView`
- `ClientsView`
- `NewsView`
- `MilestonesView`
- `ContactView`
- `Profile`

## Requisitos

- Node.js `20.19+` o `22.12+`
- npm
- Backend `arsite` ejecutándose y accesible

## Instalación

```bash
npm install
```

## Variables de entorno

Crear un archivo `.env` con al menos:

```env
VITE_API_BASE_URL=http://127.0.0.1:8000/api
```

## Desarrollo local

```bash
npm run dev
```

## Build de producción

```bash
npm run build
```

## Scripts disponibles

```bash
npm run dev
npm run build
npm run preview
npm run lint
npm run format
```

## Flujo de integración con backend

1. El usuario inicia sesión desde el CMS.
2. El CMS envía credenciales al backend `arsite`.
3. El backend responde con token y contexto del usuario.
4. El CMS guarda la sesión y adjunta `Authorization: Bearer <token>` en peticiones privadas.
5. Cada módulo consume los endpoints protegidos del backend.

## Módulos conectados a backend

- Usuarios
- Contactos
- Banners
- Destacados
- Productos
- Servicios
- Partners
- Clientes
- Noticias
- Hitos

## Edición enriquecida

El módulo de noticias utiliza TipTap con:

- tablas
- imágenes
- color de texto
- resaltado
- alineación
- conteo de caracteres
- bloques de código

## Convenciones útiles

- `src/services/api.js`: integración Axios
- `src/stores/`: estado global con Pinia
- `src/utils/`: helpers compartidos, formateo y validación
- `src/views/`: pantallas principales por módulo

## Calidad y mantenimiento

Lint:

```bash
npm run lint
```

Formato:

```bash
npm run format
```

## Relación con otras apps

- Consume la API de `ProyectoBackend/arsite`
- Administra el contenido que después se publica en `ProyectoFront/web-publica`
