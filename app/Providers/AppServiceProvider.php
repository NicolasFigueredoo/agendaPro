<?php

namespace App\Providers;

use App\Enums\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Carbon::setLocale(config('app.locale'));

        $this->registerGates();

        // @ars($monto) → "$ 12.500"
        Blade::directive('ars', fn (string $expression) => "<?php echo \\App\\Support\\Money::format({$expression}); ?>");
    }

    protected function registerGates(): void
    {
        $staff = fn (User $user) => $user->hasRole(Role::Admin, Role::Recepcion);

        Gate::define('admin', fn (User $user) => $user->isAdmin());
        Gate::define('manage-settings', fn (User $user) => $user->isAdmin());

        Gate::define('view-dashboard', $staff);
        Gate::define('manage-agenda', $staff);   // crear / editar / mover / eliminar turnos
        Gate::define('manage-clients', $staff);
        Gate::define('manage-payments', $staff); // cobros, links de MP, caja
    }
}
