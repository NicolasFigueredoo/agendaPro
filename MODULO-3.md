# Módulo 3: clientes

## Instalar (CMD, parado en la carpeta `turnos`)

1. Descomprimí `turnos-modulo-3.zip` encima del proyecto y aceptá reemplazar.
2. Corré:

```cmd
php artisan migrate:fresh --seed
php artisan test
```

> En local el seeder crea 40 clientes de prueba. En producción no (ahí importás los reales).

## Pantallas nuevas (menú Operación → Clientes, admin y recepción)

- **Listado:** buscador, orden por columnas, filtros y botones Importar / Exportar Excel.
- **Ficha:** datos, total gastado, turnos, inasistencias, última visita e historial de turnos con pagos.
- **Importar:** subís el archivo, asignás las columnas, elegís qué hacer con los repetidos y ves el resultado.

## Para importar desde AgendaPro

1. En AgendaPro exportá los clientes a Excel.
2. En el sistema: Clientes → Importar → elegís el archivo.
3. Revisá el mapeo de columnas (el sistema adivina por el nombre de cada columna).
4. Si AgendaPro trae el nombre y el apellido en una sola columna, asignala a "Nombre completo".
5. Importá. Las filas con error se listan con su número de fila para corregirlas.

Los teléfonos se guardan como `+549` + código de área + número (formato de WhatsApp), aunque vengan con 0, 15, espacios o guiones.

## Archivos

Nuevos:
- `app/Enums/`: BookingStatus, PaymentStatus, PaymentMethod, Gender
- `app/Models/`: Client, Booking, BookingStatusLog, Payment
- `app/Support/`: Phone, Provinces
- `app/Queries/ClientFilters.php`
- `app/Services/ClientImporter.php`
- `app/Imports/RawSheetImport.php`
- `app/Exports/ClientsExport.php`
- `app/Livewire/Forms/ClientForm.php`
- `app/Livewire/Clients/`: Index, Show, Import
- `database/migrations/`: 000500 clients, 000600 bookings / booking_status_logs / payments
- `database/factories/`: Client, Booking, Payment
- `resources/views/components/sort-th.blade.php`
- `resources/views/livewire/clients/` (index, show, import, partials/form-fields)
- `tests/Feature/`: ClientsTest, ClientImportTest

Reemplazados:
- `routes/web.php`
- `database/seeders/DatabaseSeeder.php`

Las tablas `bookings`, `booking_status_logs` y `payments` se crean en este módulo porque la ficha y los filtros de clientes las necesitan. Las pantallas de agenda y cobros llegan en sus módulos.
