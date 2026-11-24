<?php

namespace App\Livewire\Location;

use App\Models\Location;
use App\Support\ScheduleValidator;
use App\Support\Time;
use App\Support\Weekdays;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
#[Title('Local')]
class Edit extends Component
{
    use WithFileUploads;

    public int $locationId;

    public string $name = '';

    public string $address = '';

    public string $phone = '';

    public string $email = '';

    public $logo = null;

    public ?string $currentLogoUrl = null;

    /** @var array<int, array{active: bool, start: string, end: string}> */
    public array $hours = [];

    public function mount(): void
    {
        $location = Location::current()->load('hours');

        $this->locationId = $location->id;
        $this->name = $location->name;
        $this->address = (string) $location->address;
        $this->phone = (string) $location->phone;
        $this->email = (string) $location->email;
        $this->currentLogoUrl = $location->logoUrl();

        foreach (array_keys(Weekdays::NAMES) as $day) {
            $row = $location->hours->firstWhere('weekday', $day);

            $this->hours[$day] = [
                'active' => $row ? $row->active : $day <= 6,
                'start' => Time::hm($row?->start_time) ?? '09:00',
                'end' => Time::hm($row?->end_time) ?? '21:00',
            ];
        }
    }

    public function copyToAll(int $day): void
    {
        foreach (array_keys($this->hours) as $d) {
            if ($d !== $day) {
                $this->hours[$d] = $this->hours[$day];
            }
        }

        $this->dispatch('toast', message: 'Horario de '.Weekdays::NAMES[$day].' copiado a todos los días.');
    }

    public function removeLogo(): void
    {
        $location = Location::findOrFail($this->locationId);

        if ($location->logo_path) {
            Storage::disk('public')->delete($location->logo_path);
            $location->update(['logo_path' => null]);
        }

        $this->currentLogoUrl = null;
        $this->logo = null;

        $this->dispatch('toast', message: 'Logo eliminado.');
    }

    public function save(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'logo' => ['nullable', 'image', 'max:2048'],
        ], [], [
            'name' => 'nombre',
            'address' => 'dirección',
            'phone' => 'teléfono',
            'logo' => 'logo',
        ]);

        $errors = ScheduleValidator::errors($this->hours, 'hours');

        if ($errors) {
            foreach ($errors as $key => $message) {
                $this->addError($key, $message);
            }

            return;
        }

        $location = Location::findOrFail($this->locationId);

        DB::transaction(function () use ($location) {
            $data = [
                'name' => $this->name,
                'address' => $this->address ?: null,
                'phone' => $this->phone ?: null,
                'email' => $this->email ?: null,
            ];

            if ($this->logo) {
                if ($location->logo_path) {
                    Storage::disk('public')->delete($location->logo_path);
                }

                $data['logo_path'] = $this->logo->store('logos', 'public');
            }

            $location->update($data);

            foreach ($this->hours as $day => $row) {
                $location->hours()->updateOrCreate(
                    ['weekday' => $day],
                    [
                        'active' => (bool) $row['active'],
                        'start_time' => $row['active'] ? $row['start'] : null,
                        'end_time' => $row['active'] ? $row['end'] : null,
                    ]
                );
            }
        });

        $this->logo = null;
        $this->currentLogoUrl = $location->fresh()->logoUrl();

        $this->dispatch('toast', message: 'Datos del local guardados.');
    }

    public function render()
    {
        return view('livewire.location.edit', [
            'weekdays' => Weekdays::NAMES,
        ]);
    }
}
