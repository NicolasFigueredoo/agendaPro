# Módulo 2: local, profesionales y servicios

## Instalar (CMD, parado en la carpeta `turnos`)

1. Descomprimí `turnos-modulo-2.zip` encima del proyecto y aceptá reemplazar.
2. Corré:

```cmd
php artisan storage:link
php artisan migrate:fresh --seed
php artisan test
```

> `migrate:fresh` borra la base y la vuelve a crear con los datos de ejemplo. Todavía no hay datos reales, así que no hay problema.

## Usuarios de prueba (contraseña: `password`)

| Rol         | Email                  |
|-------------|------------------------|
| Admin       | admin@centro.test      |
| Recepción   | recepcion@centro.test  |
| Profesional | camila@centro.test     |
| Profesional | agustina@centro.test   |
| Profesional | lucia@centro.test      |

## Pantallas nuevas (menú Configuración, solo admin)

- **Servicios** (pestañas Servicios / Categorías / Carga de precios)
- **Profesionales** (listado + alta/edición con servicios y horario semanal)
- **Local** (datos, logo y horario de atención)

## Archivos

Nuevos:
- `app/Models/`: Location, LocationHour, ServiceCategory, Service, Professional, ProfessionalSchedule, ProfessionalBreak
- `app/Support/`: Time, Weekdays, ScheduleValidator
- `app/Livewire/Location/Edit.php`
- `app/Livewire/Services/`: Index, Categories, Prices
- `app/Livewire/Professionals/`: Index, Form
- `database/migrations/`: 000200 locations, 000300 servicios, 000400 profesionales
- `database/factories/`: Location, ServiceCategory, Service, Professional
- `database/seeders/`: LocationSeeder, ServiceSeeder, ProfessionalSeeder
- `resources/views/components/`: brand-mark, services-tabs
- Vistas en `resources/views/livewire/location`, `services` y `professionals`
- `tests/Feature/`: ServicesTest, ProfessionalsTest, LocationTest

Reemplazados:
- `app/Models/User.php` (se agrega la relación `professional()`)
- `routes/web.php`
- `database/seeders/DatabaseSeeder.php`
- `resources/views/components/toast.blade.php` (ahora también muestra avisos después de un redirect)
- `resources/views/layouts/guest.blade.php` y `resources/views/livewire/layout/navigation.blade.php` (usan el nombre y el logo del local)
