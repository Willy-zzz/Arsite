<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

```
arsite
├─ .editorconfig
├─ app
│  ├─ Exports
│  │  └─ GenericExport.php
│  ├─ Http
│  │  ├─ Controllers
│  │  │  ├─ Api
│  │  │  │  ├─ AuthController.php
│  │  │  │  ├─ BannerController.php
│  │  │  │  ├─ BaseApiController.php
│  │  │  │  ├─ ClienteController.php
│  │  │  │  ├─ Codigo_controler_banner.txt
│  │  │  │  ├─ ContactoController.php
│  │  │  │  ├─ DestacadoController.php
│  │  │  │  ├─ NoticiaController.php
│  │  │  │  ├─ PartnerController.php
│  │  │  │  ├─ ProductoController.php
│  │  │  │  ├─ ServicioController.php
│  │  │  │  └─ UserController.php
│  │  │  └─ Controller.php
│  │  └─ Middleware
│  │     ├─ EncryptCookies.php
│  │     └─ VerifyCsrfToken.php
│  ├─ Models
│  │  ├─ Banner.php
│  │  ├─ Cliente.php
│  │  ├─ Contacto.php
│  │  ├─ Destacado.php
│  │  ├─ Noticia.php
│  │  ├─ Partner.php
│  │  ├─ Producto.php
│  │  ├─ Servicio.php
│  │  └─ User.php
│  ├─ Policies
│  │  ├─ BasePolicy.php
│  │  ├─ ContactoPolicy.php
│  │  └─ UserPolicy.php
│  ├─ Providers
│  │  ├─ AppServiceProvider.php
│  │  └─ AuthServiceProvider.php
│  └─ Traits
│     └─ Exportable.php
├─ arsite.sql
├─ artisan
├─ bootstrap
│  ├─ app.php
│  ├─ cache
│  │  ├─ packages.php
│  │  └─ services.php
│  └─ providers.php
├─ composer.json
├─ composer.lock
├─ config
│  ├─ app.php
│  ├─ auth.php
│  ├─ cache.php
│  ├─ cors.php
│  ├─ database.php
│  ├─ dompdf.php
│  ├─ excel.php
│  ├─ filesystems.php
│  ├─ logging.php
│  ├─ mail.php
│  ├─ queue.php
│  ├─ sanctum.php
│  ├─ services.php
│  └─ session.php
├─ database
│  ├─ factories
│  │  └─ UserFactory.php
│  ├─ migrations
│  │  ├─ 0001_01_01_000000_create_users_table.php
│  │  ├─ 0001_01_01_000001_create_cache_table.php
│  │  ├─ 0001_01_01_000002_create_jobs_table.php
│  │  ├─ 2025_08_07_191311_create_banners_table.php
│  │  ├─ 2025_08_09_001503_create_destacados_table.php
│  │  ├─ 2025_08_09_001723_create_productos_table.php
│  │  ├─ 2025_08_09_002036_create_servicios_table.php
│  │  ├─ 2025_08_09_002142_create_partners_table.php
│  │  ├─ 2025_08_09_002347_create_clientes_table.php
│  │  ├─ 2025_08_09_002802_create_noticias_table.php
│  │  ├─ 2025_08_17_222703_create_contactos_table.php
│  │  └─ 2025_08_18_213707_create_personal_access_tokens_table.php
│  └─ seeders
│     ├─ AdminUserSeeder.php
│     └─ DatabaseSeeder.php
├─ package-lock.json
├─ package.json
├─ phpunit.xml
├─ public
│  ├─ .htaccess
│  ├─ avatars
│  │  └─ presets
│  │     ├─ avatar-01.svg
│  │     ├─ avatar-02.svg
│  │     ├─ avatar-03.svg
│  │     ├─ avatar-04.svg
│  │     ├─ avatar-05.svg
│  │     ├─ avatar-06.svg
│  │     ├─ avatar-07.svg
│  │     ├─ avatar-08.svg
│  │     ├─ avatar-09.svg
│  │     └─ avatar-10.svg
│  ├─ favicon.ico
│  ├─ images
│  │  ├─ footer.png
│  │  ├─ logo.png
│  │  └─ watermark.png
│  ├─ index.php
│  ├─ phpinfo.php
│  └─ robots.txt
├─ README.md
├─ resources
│  ├─ css
│  │  └─ app.css
│  ├─ js
│  │  ├─ app.js
│  │  └─ bootstrap.js
│  └─ views
│     ├─ exports
│     │  └─ generic-pdf.blade.php
│     └─ welcome.blade.php
├─ routes
│  ├─ api.php
│  ├─ console.php
│  └─ web.php
├─ storage
│  ├─ app
│  │  ├─ private
│  │  └─ public
│  │     ├─ avatars
│  │     │  ├─ avatar_12_1767723105.png
│  │     │  ├─ avatar_13_1767731657.png
│  │     │  ├─ avatar_18_1767807591.png
│  │     │  └─ avatar_23_1768180163.png
│  │     ├─ banners
│  │     │  ├─ 20251005001815_1759623495_68e1b947b366b.jpg
│  │     │  ├─ 20251027102106_1761560466_68ff479268436.jpg
│  │     │  ├─ 20251027102112_1761560472_68ff4798399de.jpg
│  │     │  ├─ 20251119225753_691e4b71aad3b.jpg
│  │     │  ├─ 20251120194434_691f6fa2290a4.jpg
│  │     │  ├─ 20260119004529_696d7ea9d775b.png
│  │     │  ├─ 20260124210001_697532d1e3869.PNG
│  │     │  ├─ 20260124210334_697533a65443c.jpg
│  │     │  ├─ 20260125064255_6975bb6fa832c.jpeg
│  │     │  ├─ 20260125064433_6975bbd19ff89.jpeg
│  │     │  ├─ 20260125192554_69766e4279d3a.webp
│  │     │  └─ 20260125221309_697695751d937.jpeg
│  │     ├─ clientes
│  │     │  ├─ 20251012033134_1760239894_68eb2116ee9ad.png
│  │     │  ├─ 20251126195240_69275a88b3afe.png
│  │     │  ├─ 20251126210755_69276c2b3441f.png
│  │     │  └─ 20260205163228_6984c61c3aef2.png
│  │     ├─ destacados
│  │     │  ├─ 20251005004313_1759624993_68e1bf215b207.png
│  │     │  ├─ 20251005004351_1759625031_68e1bf47d42da.png
│  │     │  ├─ 20251005004924_1759625364_68e1c0943a361.png
│  │     │  ├─ 20251005005050_1759625450_68e1c0eaa0a2e.png
│  │     │  ├─ 20251005005413_1759625653_68e1c1b54c417.png
│  │     │  ├─ 20251005011008_1759626608_68e1c5709377e.png
│  │     │  ├─ 20260126224604_6977eeac48e95.jpg
│  │     │  └─ 20260127021333_69781f4d1c859.jpg
│  │     ├─ noticias
│  │     │  ├─ imagenes
│  │     │  │  ├─ 1760653690_imagen_68f1717a308ec.png
│  │     │  │  └─ 1760659186_imagen_68f186f2bba19.png
│  │     │  └─ portadas
│  │     │     ├─ 1760653689_portada_68f1717937dac.png
│  │     │     ├─ 1760659186_portada_68f186f2b7ae9.png
│  │     │     ├─ 1763279702_portada_691983566a76f.png
│  │     │     └─ 1763571924_portada_691df8d45a2bf.png
│  │     ├─ partners
│  │     │  ├─ 20251005191042_1759691442_68e2c2b2d4c74.jpg
│  │     │  └─ 20251005202420_1759695860_68e2d3f485b2b.jpg
│  │     ├─ productos
│  │     │  └─ 20251005051515_1759641315_68e1fee3957dd.png
│  │     └─ servicios
│  │        └─ 20251005132028_1759670428_68e2709c1112b.png
│  ├─ framework
│  │  ├─ cache
│  │  │  ├─ data
│  │  │  └─ laravel-excel
│  │  ├─ sessions
│  │  ├─ testing
│  │  └─ views
│  │     ├─ 029dc83c9d9b0d11cc24d61377a3af1b.php
│  │     ├─ 075a6b66258db45fc1ba7fe0a8e79c7e.php
│  │     ├─ 08a8d5edf442f4ea1452355c657978b6.php
│  │     ├─ 0eac73f078a79bbaead1a57afa099096.php
│  │     ├─ 2891339694bfd25018c8e3682460733c.php
│  │     ├─ 2b1c2adf8c10e8812dd19dd439f27ab4.php
│  │     ├─ 32cbca525d9489887d6a252a45f6185e.php
│  │     ├─ 42024f6f764fee0bbdd981d2a3ce7606.php
│  │     ├─ 45430508068a0028f835f981a7c5f192.php
│  │     ├─ 4e4f870532cbd25ede5778101b528f59.php
│  │     ├─ 5127c4cbf73803c312e9f06ed5a0ee93.php
│  │     ├─ 5611dfa59ab0bf442cac9c212d730a4c.php
│  │     ├─ 5daa699ee3f2a0059d093ed6b75efb1e.php
│  │     ├─ 5f23c276176a36d1ac1addcdf3c88d41.php
│  │     ├─ 63725da4b542cd8bdb588e6edfada271.php
│  │     ├─ 63d481df91ad9e06fead636d6a3e7ff7.php
│  │     ├─ 660f8d2f4ca607b8d72c9d28d2a3bf28.php
│  │     ├─ 67e1a74bdffdacd9319d13d4a864e81f.php
│  │     ├─ 696738dd8181fbb466e707226bc5d3c3.php
│  │     ├─ 6af796a869de84f20b42728c31b4149f.php
│  │     ├─ 6b39789dae7f02232886083a5045d5b9.php
│  │     ├─ 75ce1141d6b69698d14daa25c5d23741.php
│  │     ├─ 7655dfcb69ed4b6d352181612b636b6c.php
│  │     ├─ 78d9aa97b8a686b4ad8cf8a92631e62b.php
│  │     ├─ 7d6983ee6fde4cac9a205358b50916f9.php
│  │     ├─ 859b50903c16feb489aadbbc16b05d71.php
│  │     ├─ 867fd6856fce8c4f0853acbdcfb6eaa0.php
│  │     ├─ 9140c86c9c8c06e938411eb63b326f7a.php
│  │     ├─ 9186bc023f7b60a9908d360b799f9274.php
│  │     ├─ 93395b9ae7303f28f6cf5b58d878ba27.php
│  │     ├─ 9424ad80c3c2095396abcd07253a7458.php
│  │     ├─ 9adb1fd1e578d774547075dc1a84dc30.php
│  │     ├─ 9b5d09bbd9c7f752373dcfadb5b32d32.php
│  │     ├─ b71c88757fedc933ff75e064de735085.php
│  │     ├─ b96965c218408307093e51a4d593783c.php
│  │     ├─ b9a9a39bd3119d5dce2ab5d0dd8859c0.php
│  │     ├─ c5095b5c0903fe2d1e350b7566131c62.php
│  │     ├─ cc6b17f3e73be16d794c5d1fea02a3b6.php
│  │     ├─ d23ce3c27fa4f8e196ae0063cceb0882.php
│  │     ├─ dea0f297af1b83b6f68a7af9821fc565.php
│  │     ├─ dea35051617c8482a55230eb513d95f7.php
│  │     ├─ e03c1ce9ba170d9bae2426c02624a0b8.php
│  │     └─ ea8d5a5e630a178756ab8b02de54e6f0.php
│  └─ logs
├─ tests
│  ├─ Feature
│  │  └─ ExampleTest.php
│  ├─ Pest.php
│  ├─ TestCase.php
│  └─ Unit
│     └─ ExampleTest.php
└─ vite.config.js

```