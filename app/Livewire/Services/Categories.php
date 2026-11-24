<?php

namespace App\Livewire\Services;

use App\Models\ServiceCategory;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Categorías de servicios')]
class Categories extends Component
{
    public bool $showModal = false;

    public ?int $editingId = null;

    public string $name = '';

    public bool $active = true;

    public function create(): void
    {
        $this->reset(['editingId', 'name', 'active']);
        $this->resetValidation();
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $this->resetValidation();

        $category = ServiceCategory::findOrFail($id);

        $this->editingId = $category->id;
        $this->name = $category->name;
        $this->active = $category->active;
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:80'],
            'active' => ['boolean'],
        ], [], ['name' => 'nombre']);

        if ($this->editingId) {
            ServiceCategory::findOrFail($this->editingId)->update([
                'name' => $this->name,
                'active' => $this->active,
            ]);
        } else {
            ServiceCategory::create([
                'name' => $this->name,
                'active' => $this->active,
                'sort_order' => (int) ServiceCategory::max('sort_order') + 1,
            ]);
        }

        $this->showModal = false;
        $this->dispatch('toast', message: 'Categoría guardada.');
    }

    public function move(int $id, string $direction): void
    {
        $ids = ServiceCategory::ordered()->pluck('id')->all();
        $index = array_search($id, $ids, true);
        $target = $direction === 'up' ? $index - 1 : $index + 1;

        if ($index === false || ! isset($ids[$target])) {
            return;
        }

        [$ids[$index], $ids[$target]] = [$ids[$target], $ids[$index]];

        DB::transaction(function () use ($ids) {
            foreach ($ids as $order => $categoryId) {
                ServiceCategory::whereKey($categoryId)->update(['sort_order' => $order + 1]);
            }
        });
    }

    public function delete(int $id): void
    {
        $category = ServiceCategory::withCount('services')->findOrFail($id);

        if ($category->services_count > 0) {
            $this->dispatch('toast', message: 'La categoría tiene servicios. Movelos o desactivala.', type: 'error');

            return;
        }

        $category->delete();

        $this->dispatch('toast', message: 'Categoría eliminada.');
    }

    public function render()
    {
        return view('livewire.services.categories', [
            'categories' => ServiceCategory::ordered()->withCount('services')->get(),
        ]);
    }
}
