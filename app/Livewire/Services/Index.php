<?php

namespace App\Livewire\Services;

use App\Models\Professional;
use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Servicios')]
class Index extends Component
{
    #[Url(except: '')]
    public string $search = '';

    #[Url(except: 'all')]
    public string $status = 'all'; // all | active | inactive

    public bool $showModal = false;

    public ?int $editingId = null;

    public string $name = '';

    public ?int $categoryId = null;

    public $price = '';

    public $duration = 45;

    public string $description = '';

    public bool $active = true;

    /** @var array<int, string> */
    public array $professionalIds = [];

    public function create(?int $categoryId = null): void
    {
        $this->resetForm();
        $this->categoryId = $categoryId ?? ServiceCategory::ordered()->value('id');
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $this->resetForm();

        $service = Service::with('professionals:id')->findOrFail($id);

        $this->editingId = $service->id;
        $this->name = $service->name;
        $this->categoryId = $service->service_category_id;
        $this->price = $service->price;
        $this->duration = $service->duration_minutes;
        $this->description = (string) $service->description;
        $this->active = $service->active;
        $this->professionalIds = $service->professionals->pluck('id')->map(fn ($id) => (string) $id)->all();

        $this->showModal = true;
    }

    public function toggleAllProfessionals(): void
    {
        $all = Professional::active()->pluck('id')->map(fn ($id) => (string) $id)->all();

        $this->professionalIds = count(array_diff($all, $this->professionalIds)) === 0 ? [] : $all;
    }

    public function save(): void
    {
        $data = $this->validate([
            'name' => ['required', 'string', 'max:120'],
            'categoryId' => ['required', Rule::exists('service_categories', 'id')],
            'price' => ['required', 'integer', 'min:0', 'max:99999999'],
            'duration' => ['required', 'integer', 'min:5', 'max:600'],
            'description' => ['nullable', 'string', 'max:1000'],
            'active' => ['boolean'],
            'professionalIds' => ['array'],
            'professionalIds.*' => [Rule::exists('professionals', 'id')],
        ], [], [
            'name' => 'nombre',
            'categoryId' => 'categoría',
            'price' => 'precio',
            'duration' => 'duración',
            'description' => 'descripción',
        ]);

        DB::transaction(function () use ($data) {
            $attributes = [
                'name' => $data['name'],
                'service_category_id' => $data['categoryId'],
                'price' => (int) $data['price'],
                'duration_minutes' => (int) $data['duration'],
                'description' => $data['description'] ?: null,
                'active' => $data['active'],
            ];

            if ($this->editingId) {
                $service = Service::findOrFail($this->editingId);

                if ($service->service_category_id !== (int) $data['categoryId']) {
                    $attributes['sort_order'] = $this->nextSortOrder((int) $data['categoryId']);
                }

                $service->update($attributes);
            } else {
                $service = Service::create($attributes + [
                    'sort_order' => $this->nextSortOrder((int) $data['categoryId']),
                ]);
            }

            $service->professionals()->sync(array_map('intval', $data['professionalIds']));
        });

        $this->showModal = false;
        $this->dispatch('toast', message: $this->editingId ? 'Servicio actualizado.' : 'Servicio creado.');
        $this->resetForm();
    }

    public function move(int $id, string $direction): void
    {
        $service = Service::findOrFail($id);

        $ids = Service::where('service_category_id', $service->service_category_id)
            ->ordered()
            ->pluck('id')
            ->all();

        $index = array_search($id, $ids, true);
        $target = $direction === 'up' ? $index - 1 : $index + 1;

        if ($index === false || ! isset($ids[$target])) {
            return;
        }

        [$ids[$index], $ids[$target]] = [$ids[$target], $ids[$index]];

        DB::transaction(function () use ($ids) {
            foreach ($ids as $order => $serviceId) {
                Service::whereKey($serviceId)->update(['sort_order' => $order + 1]);
            }
        });
    }

    public function toggleActive(int $id): void
    {
        $service = Service::findOrFail($id);
        $service->update(['active' => ! $service->active]);

        $this->dispatch('toast', message: $service->active ? 'Servicio activado.' : 'Servicio desactivado.');
    }

    public function delete(int $id): void
    {
        try {
            Service::findOrFail($id)->delete();
            $this->dispatch('toast', message: 'Servicio eliminado.');
        } catch (QueryException) {
            $this->dispatch('toast', message: 'El servicio tiene turnos asociados. Desactivalo en lugar de eliminarlo.', type: 'error');
        }
    }

    protected function nextSortOrder(int $categoryId): int
    {
        return (int) Service::where('service_category_id', $categoryId)->max('sort_order') + 1;
    }

    protected function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'categoryId', 'price', 'duration', 'description', 'active', 'professionalIds']);
        $this->resetValidation();
    }

    public function render()
    {
        $filter = function ($query) {
            $query->withCount('professionals')
                ->when($this->search, function ($q) {
                    $term = '%'.$this->search.'%';
                    $q->where(fn ($w) => $w->where('name', 'like', $term)->orWhere('description', 'like', $term));
                })
                ->when($this->status === 'active', fn ($q) => $q->where('active', true))
                ->when($this->status === 'inactive', fn ($q) => $q->where('active', false));
        };

        $categories = ServiceCategory::ordered()
            ->with(['services' => $filter])
            ->get();

        if ($this->search || $this->status !== 'all') {
            $categories = $categories->filter(fn ($c) => $c->services->isNotEmpty());
        }

        return view('livewire.services.index', [
            'categories' => $categories,
            'allCategories' => ServiceCategory::ordered()->get(['id', 'name']),
            'professionals' => Professional::active()->ordered()->get(['id', 'name', 'color']),
            'durations' => [15, 20, 30, 40, 45, 60, 75, 90, 105, 120],
        ]);
    }
}
