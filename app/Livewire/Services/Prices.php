<?php

namespace App\Livewire\Services;

use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Carga masiva de precios')]
class Prices extends Component
{
    /** @var array<int, int|string> [service_id => precio] */
    public array $prices = [];

    public $percent = '';

    public string $rounding = '100';

    public string $scope = ''; // '' = todas las categorías, o id de categoría

    public function mount(): void
    {
        $this->loadPrices();
    }

    protected function loadPrices(): void
    {
        $this->prices = Service::pluck('price', 'id')->all();
    }

    public function applyIncrease(): void
    {
        $this->validate([
            'percent' => ['required', 'numeric', 'min:-90', 'max:500'],
            'rounding' => ['required', 'in:1,10,50,100,500,1000'],
        ], [], ['percent' => 'porcentaje']);

        $factor = 1 + ((float) $this->percent / 100);
        $step = (int) $this->rounding;

        $ids = Service::query()
            ->when($this->scope !== '', fn ($q) => $q->where('service_category_id', (int) $this->scope))
            ->pluck('id');

        foreach ($ids as $id) {
            $current = (int) ($this->prices[$id] ?? 0);
            $this->prices[$id] = (int) (round(($current * $factor) / $step) * $step);
        }

        $this->dispatch('toast', message: 'Aumento aplicado en pantalla. Revisá y tocá "Guardar precios".');
    }

    public function discard(): void
    {
        $this->loadPrices();
        $this->resetValidation();
    }

    public function save(): void
    {
        $this->validate([
            'prices' => ['array'],
            'prices.*' => ['required', 'integer', 'min:0', 'max:99999999'],
        ], [
            'prices.*.required' => 'Obligatorio.',
            'prices.*.integer' => 'Solo números enteros.',
            'prices.*.min' => 'No puede ser negativo.',
        ]);

        $current = Service::pluck('price', 'id');
        $changed = 0;

        DB::transaction(function () use ($current, &$changed) {
            foreach ($this->prices as $id => $price) {
                if (! $current->has($id) || (int) $current[$id] === (int) $price) {
                    continue;
                }

                Service::whereKey($id)->update(['price' => (int) $price]);
                $changed++;
            }
        });

        $this->percent = '';
        $this->loadPrices();

        $this->dispatch('toast', message: $changed ? "Se actualizaron {$changed} precios." : 'No hubo cambios.');
    }

    public function render()
    {
        $original = Service::pluck('price', 'id');

        return view('livewire.services.prices', [
            'categories' => ServiceCategory::ordered()->with('services')->get(),
            'original' => $original,
            'dirty' => collect($this->prices)->filter(fn ($p, $id) => (int) ($original[$id] ?? 0) !== (int) $p)->count(),
        ]);
    }
}
