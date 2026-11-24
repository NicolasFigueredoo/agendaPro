<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/**
 * Pantalla de inicio según rol:
 * - Profesional → su agenda (o perfil hasta que exista el módulo agenda)
 * - Admin / Recepción → dashboard
 */
class HomeController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        if ($request->user()->isProfesional()) {
            return redirect()->route(Route::has('agenda') ? 'agenda' : 'profile');
        }

        return redirect()->route('dashboard');
    }
}
