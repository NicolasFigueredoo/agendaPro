<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\MercadoPagoWebhookController;
use App\Livewire\Admin\Users\Index as UsersIndex;
use App\Livewire\Booking\PublicBooking;
use App\Livewire\Booking\Status as BookingStatusPage;
use App\Livewire\Clients\Import as ClientsImport;
use App\Livewire\Clients\Index as ClientsIndex;
use App\Livewire\Clients\Show as ClientsShow;
use App\Livewire\Location\Edit as LocationEdit;
use App\Livewire\Professionals\Form as ProfessionalForm;
use App\Livewire\Professionals\Index as ProfessionalsIndex;
use App\Livewire\Services\Categories as ServiceCategories;
use App\Livewire\Services\Index as ServicesIndex;
use App\Livewire\Services\Prices as ServicePrices;
use App\Livewire\Settings\Index as SettingsIndex;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/inicio');

// Público (clientes)
Route::get('reservar', PublicBooking::class)->name('booking.public');
Route::get('reserva/{token}', BookingStatusPage::class)->name('booking.status');

// Mercado Pago (sin CSRF: ver bootstrap/app.php)
Route::post('webhooks/mercadopago', MercadoPagoWebhookController::class)->name('webhooks.mercadopago');

Route::middleware('auth')->group(function () {
    Route::get('inicio', HomeController::class)->name('home');
    Route::view('profile', 'profile')->name('profile');

    // Admin + Recepción
    Route::middleware('role:admin,recepcion')->group(function () {
        Route::view('dashboard', 'dashboard')->name('dashboard');

        Route::get('clientes', ClientsIndex::class)->name('clients.index');
        Route::get('clientes/importar', ClientsImport::class)->name('clients.import');
        Route::get('clientes/{client}', ClientsShow::class)->name('clients.show');
    });

    // Solo Admin
    Route::middleware('role:admin')->prefix('configuracion')->group(function () {
        Route::get('usuarios', UsersIndex::class)->name('admin.users');

        Route::get('local', LocationEdit::class)->name('location.edit');

        Route::get('servicios', ServicesIndex::class)->name('services.index');
        Route::get('servicios/categorias', ServiceCategories::class)->name('services.categories');
        Route::get('servicios/precios', ServicePrices::class)->name('services.prices');

        Route::get('profesionales', ProfessionalsIndex::class)->name('professionals.index');
        Route::get('profesionales/nuevo', ProfessionalForm::class)->name('professionals.create');
        Route::get('profesionales/{professional}/editar', ProfessionalForm::class)->name('professionals.edit');

        Route::get('ajustes', SettingsIndex::class)->name('settings.index');
    });
});

require __DIR__.'/auth.php';
