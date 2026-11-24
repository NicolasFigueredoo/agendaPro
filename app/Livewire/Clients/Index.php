<?php

namespace App\Livewire\Clients;

use App\Enums\BookingStatus;
use App\Enums\Gender;
use App\Exports\ClientsExport;
use App\Livewire\Forms\ClientForm;
use App\Models\Professional;
use App\Models\Service;
use App\Queries\ClientFilters;
use App\Support\Provinces;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

#[Layout('layouts.app')]
#[Title('Clientes')]
class Index extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $professional = '';

    #[Url(except: '')]
    public string $service = '';

    #[Url(except: '')]
    public string $status = '';

    #[Url(except: '')]
    public string $birthday_from = '';

    #[Url(except: '')]
    public string $birthday_to = '';

    #[Url(except: '')]
    public string $created_from = '';

    #[Url(except: '')]
    public string $created_to = '';

    #[Url(except: '')]
    public string $booked = '';

    #[Url(except: 'last_name')]
    public string $sort = 'last_name';

    #[Url(except: 'asc')]
    public string $direction = 'asc';

    public bool $showModal = false;

    public ClientForm $form;

    public function updated(string $property): void
    {
        if (! str_starts_with($property, 'form.') && $property !== 'showModal') {
            $this->resetPage();
        }
    }

    public function sortBy(string $column): void
    {
        if (! in_array($column, ClientFilters::SORTABLE, true)) {
            return;
        }

        if ($this->sort === $column) {
            $this->direction = $this->direction === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sort = $column;
            $this->direction = in_array($column, ['created_at', 'bookings_count', 'last_visit'], true) ? 'desc' : 'asc';
        }

        $this->resetPage();
    }

    public function birthdayPreset(string $preset): void
    {
        [$from, $to] = match ($preset) {
            'today' => [today(), today()],
            'week' => [today(), today()->addDays(6)],
            'month' => [today()->startOfMonth(), today()->endOfMonth()],
            default => [null, null],
        };

        $this->birthday_from = $from?->toDateString() ?? '';
        $this->birthday_to = $to?->toDateString() ?? '';
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['professional', 'service', 'status', 'birthday_from', 'birthday_to', 'created_from', 'created_to', 'booked']);
        $this->resetPage();
    }

    public function create(): void
    {
        $this->form->clear();
        $this->showModal = true;
    }

    public function save(): void
    {
        $client = $this->form->save();

        $this->showModal = false;
        $this->form->clear();

        session()->flash('toast', 'Cliente creado.');

        $this->redirectRoute('clients.show', $client, navigate: true);
    }

    public function export()
    {
        return Excel::download(new ClientsExport($this->filters()), 'clientes-'.now()->format('Y-m-d').'.xlsx');
    }

    protected function filters(): array
    {
        return [
            'search' => $this->search,
            'professional' => $this->professional,
            'service' => $this->service,
            'status' => $this->status,
            'birthday_from' => $this->birthday_from,
            'birthday_to' => $this->birthday_to,
            'created_from' => $this->created_from,
            'created_to' => $this->created_to,
            'booked' => $this->booked,
            'sort' => $this->sort,
            'direction' => $this->direction,
        ];
    }

    public function activeFiltersCount(): int
    {
        return collect([
            $this->professional, $this->service, $this->status,
            $this->birthday_from || $this->birthday_to,
            $this->created_from || $this->created_to,
            $this->booked,
        ])->filter()->count();
    }

    public function render()
    {
        return view('livewire.clients.index', [
            'clients' => ClientFilters::query($this->filters())->paginate(25),
            'professionals' => Professional::ordered()->get(['id', 'name']),
            'services' => Service::ordered()->get(['id', 'name']),
            'statuses' => BookingStatus::options(),
            'genders' => Gender::options(),
            'provinces' => Provinces::ALL,
            'activeFilters' => $this->activeFiltersCount(),
        ]);
    }
}
