# Turnos — Módulo 1: base + auth + roles

## 1. Crear el proyecto

```bash
composer create-project laravel/laravel:^11.0 turnos
cd turnos

# Auth con Breeze (stack Livewire + Volt) y Pest
composer require laravel/breeze:^2.0 --dev
php artisan breeze:install livewire --pest --no-interaction

# Paquetes que se usan en los módulos siguientes (ya los dejamos instalados)
composer require maatwebsite/excel:^3.1 mercadopago/dx-php:^3.0

# Traducciones al español (validaciones, auth, paginación)
composer require laravel-lang/common --dev
php artisan lang:add es
php artisan lang:update

npm install
npm install chart.js
```

> maatwebsite/excel necesita las extensiones `gd` y `zip` de PHP.

## 2. Copiar los archivos de este módulo

Copiá todo el contenido del zip encima del proyecto (respetando las carpetas). Pisa archivos de Laravel/Breeze, está bien.

**Borrá estos archivos** (registro público y eliminación de cuenta deshabilitados):

```bash
rm resources/views/livewire/pages/auth/register.blade.php
rm resources/views/livewire/profile/delete-user-form.blade.php
rm tests/Feature/Auth/RegistrationTest.php
rm tests/Feature/ExampleTest.php
```

## 3. Configurar y migrar

```bash
cp .env.example .env
php artisan key:generate
# Crear la base "turnos" en MySQL (utf8mb4) y completar DB_* en .env
php artisan migrate --seed
```

Usuarios creados:

| Rol       | Email                  | Contraseña |
|-----------|------------------------|------------|
| Admin     | admin@centro.test      | password   |
| Recepción | recepcion@centro.test  | password   |

(El admin se puede cambiar con `ADMIN_EMAIL` / `ADMIN_PASSWORD` antes de correr el seeder.)

## 4. Levantar

```bash
php artisan serve
npm run dev
php artisan queue:work     # para mails (módulo notificaciones)
php artisan schedule:work  # en local; en el server va el cron de abajo
```

Cron en producción:

```
* * * * * cd /ruta/al/proyecto && php artisan schedule:run >> /dev/null 2>&1
```

## 5. Tests

```bash
php artisan test
```

Los tests corren en SQLite en memoria (`phpunit.xml` de Laravel 11 ya viene así).

## Notas de diseño

- **Fechas:** se guardan en hora de Buenos Aires (`APP_TIMEZONE`), sin conversión a UTC. Para la agenda simplifica todo.
- **Montos:** siempre enteros (pesos). `App\Support\Money::format()` o `@ars($monto)` en Blade.
- **Settings:** `App\Support\Settings::get('agenda.slot_minutes')` / `Settings::set(...)`. Las claves de MP se encriptan con `APP_KEY` (si cambiás la APP_KEY hay que volver a cargarlas).
- **Permisos:** middleware `role:admin,recepcion` en rutas y gates (`admin`, `manage-settings`, `view-dashboard`, `manage-agenda`, `manage-clients`, `manage-payments`) para la UI y los componentes.
- **Menú:** cada ítem aparece solo cuando existe su ruta, así que se va completando módulo a módulo sin tocar la navegación.
