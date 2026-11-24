# Módulo: reservas online + seña con Mercado Pago

El cliente entra a `/reservar`, elige servicio → profesional (o "cualquiera") → día y hora → deja sus datos → paga la seña en Mercado Pago. El horario queda retenido mientras paga; si no paga a tiempo se libera solo. Cuando el pago se aprueba, el turno pasa a **Confirmado**, se registra la seña y le llega un mail.

## 1. Instalar (CMD, en la carpeta `turnos`)

1. Descomprimí `turnos-modulo-reservas-online.zip` encima del proyecto y aceptá reemplazar.
2. Corré:

```cmd
php artisan migrate
php artisan db:seed --class=ProfessionalSeeder
php artisan test
```

(`migrate` agrega las tablas y columnas nuevas sin borrar nada. El seeder marca a Cami con "acepta reservas online", como en AgendaPro.)

3. Dejá corriendo, además de `php artisan serve` y `npm run dev`:

```cmd
php artisan schedule:work
php artisan queue:work
```

- `schedule:work` libera cada minuto las reservas que no pagaron la seña.
- `queue:work` manda los mails. En local, con `MAIL_MAILER=log` en el `.env`, los mails quedan en `storage/logs/laravel.log`.

En el servidor van el cron (`* * * * * php artisan schedule:run`) y un worker de colas (Supervisor).

## 2. Configurar Mercado Pago en modo prueba (sandbox)

1. Entrá a https://www.mercadopago.com.ar/developers/panel/app con la cuenta de MP del local → **Crear aplicación** → tipo de pago online, Checkout Pro.
2. En la aplicación → **Credenciales de prueba**: copiá el **Access Token** y la **Public Key**.
3. En el sistema: **Configuración → Reglas y ajustes → Mercado Pago**: pegalas, dejá tildado **Modo prueba**, guardá y tocá **Probar conexión**.
4. En el panel de MP → **Cuentas de prueba**: creá un **comprador** de prueba. Para pagar en sandbox, iniciá sesión en Mercado Pago con ese comprador (en una ventana de incógnito) o pagá como invitado con una tarjeta de prueba:

| Tarjeta | Número | CVV | Vto. |
|---|---|---|---|
| Mastercard | 5031 7557 3453 0604 | 123 | 11/30 |
| Visa | 4509 9535 6623 3704 | 123 | 11/30 |

En **Nombre del titular** poné `APRO` (pago aprobado) u `OTHE` (rechazado). DNI: `12345678`.

## 3. Recibir el webhook en tu compu (ngrok)

Mercado Pago necesita una URL **pública y https** para avisar los pagos.

1. Instalá ngrok (https://ngrok.com/download) y corré en otra ventana: `ngrok http 8000`.
2. Copiá la URL que te da (ej. `https://abcd-1234.ngrok-free.app`) y ponela en el `.env`:

```
APP_URL=https://abcd-1234.ngrok-free.app
MERCADOPAGO_WEBHOOK_BASE_URL=https://abcd-1234.ngrok-free.app
```

3. `php artisan config:clear`.
4. En el panel de MP → tu aplicación → **Webhooks** → Modo de prueba: URL `https://abcd-1234.ngrok-free.app/webhooks/mercadopago`, evento **Pagos**. Guardá y copiá la **Clave secreta** a Ajustes → Mercado Pago.
5. Probá entrando a `https://abcd-1234.ngrok-free.app/reservar` (desde el celular también).

> Aunque el webhook no llegue, la página de retorno (`/reserva/...`) consulta el pago a MP apenas el cliente vuelve, así que el turno se confirma igual.

## 4. Pasar a producción

1. En MP → **Credenciales de producción** (hay que completar los datos del negocio).
2. Cargá el Access Token, la Public Key y la clave secreta de producción en Ajustes y **destildá Modo prueba**.
3. En Webhooks → Modo productivo: `https://tudominio.com.ar/webhooks/mercadopago`.
4. Compartí el link `https://tudominio.com.ar/reservar` (Instagram, WhatsApp, Google).

## Qué hay de nuevo

- **Página pública** `/reservar` (pensada para el celular) y página de estado `/reserva/{token}` (confirmado / esperando pago / reintentar / vencida).
- **Configuración → Reglas y ajustes**: reservas online (anticipación mínima, días hacia adelante, minutos para pagar, condiciones de la seña) y Mercado Pago (credenciales encriptadas, modo prueba, seña % o fija, probar conexión, URL del webhook).
- **Profesionales**: casilla "Acepta reservas online".
- **Dashboard**: próximos turnos de 7 días, con marca "Online" y "Esperando seña" (hasta que esté la agenda).
- **Webhook** `/webhooks/mercadopago`: valida firma, consulta el pago a la API, registra la seña con su payment_id, pasa el turno a Confirmado y es idempotente.
- **Disponibilidad** (`App\Services\Availability`): jornada − descansos − bloqueos − turnos ocupados. La va a usar también la agenda.

## Archivos

Nuevos:
- `app/Services/`: Availability, MercadoPagoService, BookingPaymentService, OnlineBookingService
- `app/Models/`: ScheduleBlock, MpPaymentLink
- `app/Support/Deposit.php`, `app/Exceptions/SlotUnavailableException.php`
- `app/Http/Controllers/MercadoPagoWebhookController.php`
- `app/Livewire/Booking/`: PublicBooking, Status · `app/Livewire/Settings/Index.php`
- `app/Mail/BookingConfirmed.php` + `resources/views/mail/booking-confirmed.blade.php`
- `app/Console/Commands/ReleaseExpiredBookings.php`
- `database/migrations/`: 000700 campos online, 000800 schedule_blocks, 000900 mp_payment_links
- `resources/views/layouts/public.blade.php`, `resources/views/livewire/booking/*`, `resources/views/livewire/settings/index.blade.php`
- `tests/Feature/`: AvailabilityTest, OnlineBookingTest, MercadoPagoWebhookTest

Reemplazados:
- `app/Models/`: Booking, Professional · `app/Support/`: Settings, Time
- `app/Livewire/Professionals/Form.php` + su vista
- `config/services.php`, `routes/web.php`, `routes/console.php`
- `resources/views/dashboard.blade.php`
- `database/seeders/ProfessionalSeeder.php`, `database/factories/ProfessionalFactory.php`
- `tests/Pest.php` (los tests ya no necesitan `npm run dev`)
