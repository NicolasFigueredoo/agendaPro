<?php

namespace App\Livewire\Professionals;

use App\Models\Professional;
use Illuminate\Database\QueryException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Profesionales')]
class Index extends Component
{
    public function toggleActive(int $id): void
    {
        $professional = Professional::findOrFail($id);
        $professional->update(['active' => ! $professional->active]);

        $this->dispatch('toast', message: $professional->active ? 'Profesional activado.' : 'Profesional desactivado.');
    }

    public function delete(int $id): void
    {
        try {
            Professional::findOrFail($id)->delete();
            $this->dispatch('toast', message: 'Profesional eliminado.');
        } catch (QueryException) {
            $this->dispatch('toast', message: 'Tiene turnos asociados. Desactivalo en lugar de eliminarlo.', type: 'error');
        }
    }

    public function render()
    {
        return view('livewire.professionals.index', [
            'professionals' => Professional::ordered()
                ->with(['user:id,email', 'schedules'])
                ->withCount('services')
                ->get(),
        ]);
    }
}
