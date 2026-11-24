<?php

namespace App\Livewire\Clients;

use App\Enums\BookingStatus;
use App\Enums\Gender;
use App\Livewire\Forms\ClientForm;
use App\Models\Client;
use App\Support\Provinces;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Show extends Component
{
    public Client $client;

    public bool $showModal = false;

    public ClientForm $form;

    public function mount(Client $client): void
    {
        $this->client = $client;
    }

    public function edit(): void
    {
        $this->form->clear();
        $this->form->setClient($this->client);
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->client = $this->form->save();
        $this->showModal = false;

        $this->dispatch('toast', message: 'Datos del cliente actualizados.');
    }

    public function delete(): void
    {
        $this->client->delete(); // soft delete: los turnos y pagos quedan

        session()->flash('toast', 'Cliente eliminado.');

        $this->redirectRoute('clients.index', navigate: true);
    }

    public function render()
    {
        $bookings = $this->client->bookings()
            ->with(['service:id,name', 'professional:id,name,color', 'payments'])
            ->orderByDesc('start_at')
            ->get();

        $active = $bookings->where('status', '!=', BookingStatus::Cancelado);

        $stats = [
            'total' => $active->count(),
            'attended' => $bookings->where('status', BookingStatus::Asistio)->count(),
            'no_shows' => $bookings->where('status', BookingStatus::NoAsistio)->count(),
            'cancelled' => $bookings->where('status', BookingStatus::Cancelado)->count(),
            'spent' => (int) $bookings->sum(fn ($b) => $b->payments->sum('amount')),
            'last_visit' => $active->where('start_at', '<=', now())->first()?->start_at,
            'next' => $active->where('start_at', '>', now())->sortBy('start_at')->first(),
        ];

        return view('livewire.clients.show', [
            'bookings' => $bookings,
            'stats' => $stats,
            'genders' => Gender::options(),
            'provinces' => Provinces::ALL,
        ])->title($this->client->fullName());
    }
}
