<?php

namespace App\Livewire\Professionals;

use App\Enums\Role;
use App\Models\Location;
use App\Models\Professional;
use App\Models\ServiceCategory;
use App\Models\User;
use App\Support\ScheduleValidator;
use App\Support\Time;
use App\Support\Weekdays;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Form extends Component
{
    public ?int $professionalId = null;

    public string $name = '';

    public string $color = '#e11d48';

    public bool $active = true;

    public bool $acceptsOnline = false;

    public $userId = '';

    public $commission = '';

    /** @var array<int, string> */
    public array $serviceIds = [];

    /** @var array<int, array{active: bool, start: string, end: string, breaks: array<int, array{start: string, end: string}>}> */
    public array $schedule = [];

    public function mount(?Professional $professional = null): void
    {
        if ($professional?->exists) {
            $professional->load(['services:id', 'schedules', 'breaks']);

            $this->professionalId = $professional->id;
            $this->name = $professional->name;
            $this->color = $professional->color;
            $this->active = $professional->active;
            $this->acceptsOnline = $professional->accepts_online;
            $this->userId = (string) ($professional->user_id ?? '');
            $this->commission = $professional->commission_percent ?? '';
            $this->serviceIds = $professional->services->pluck('id')->map(fn ($id) => (string) $id)->all();

            foreach (array_keys(Weekdays::NAMES) as $day) {
                $row = $professional->schedules->firstWhere('weekday', $day);

                $this->schedule[$day] = [
                    'active' => (bool) $row?->active,
                    'start' => Time::hm($row?->start_time) ?? '09:00',
                    'end' => Time::hm($row?->end_time) ?? '18:00',
                    'breaks' => $professional->breaks
                        ->where('weekday', $day)
                        ->map(fn ($b) => ['start' => Time::hm($b->start_time), 'end' => Time::hm($b->end_time)])
                        ->values()
                        ->all(),
                ];
            }

            return;
        }

        $used = Professional::pluck('color')->all();
        $this->color = collect(Professional::COLORS)->first(fn ($c) => ! in_array($c, $used, true)) ?? Professional::COLORS[0];

        foreach (array_keys(Weekdays::NAMES) as $day) {
            $this->schedule[$day] = [
                'active' => $day <= 6,
                'start' => '09:00',
                'end' => $day === 6 ? '14:00' : '18:00',
                'breaks' => [],
            ];
        }
    }

    public function addBreak(int $day): void
    {
        $this->schedule[$day]['breaks'][] = ['start' => '13:00', 'end' => '14:00'];
    }

    public function removeBreak(int $day, int $index): void
    {
        unset($this->schedule[$day]['breaks'][$index]);
        $this->schedule[$day]['breaks'] = array_values($this->schedule[$day]['breaks']);
        $this->resetValidation();
    }

    public function copyToAll(int $day): void
    {
        foreach (array_keys($this->schedule) as $d) {
            if ($d !== $day) {
                $this->schedule[$d] = $this->schedule[$day];
            }
        }

        $this->resetValidation();
        $this->dispatch('toast', message: 'Horario de '.Weekdays::NAMES[$day].' copiado a todos los días.');
    }

    public function toggleCategory(int $categoryId): void
    {
        $ids = ServiceCategory::findOrFail($categoryId)
            ->services()
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->all();

        $allSelected = count(array_diff($ids, $this->serviceIds)) === 0;

        $this->serviceIds = $allSelected
            ? array_values(array_diff($this->serviceIds, $ids))
            : array_values(array_unique([...$this->serviceIds, ...$ids]));
    }

    public function toggleAllServices(): void
    {
        $ids = \App\Models\Service::pluck('id')->map(fn ($id) => (string) $id)->all();

        $this->serviceIds = count(array_diff($ids, $this->serviceIds)) === 0 ? [] : $ids;
    }

    public function save(): void
    {
        $data = $this->validate([
            'name' => ['required', 'string', 'max:80'],
            'color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'active' => ['boolean'],
            'acceptsOnline' => ['boolean'],
            'userId' => [
                'nullable',
                Rule::exists('users', 'id')->where('role', Role::Profesional->value),
                Rule::unique('professionals', 'user_id')->ignore($this->professionalId),
            ],
            'commission' => ['nullable', 'integer', 'min:0', 'max:100'],
            'serviceIds' => ['array'],
            'serviceIds.*' => [Rule::exists('services', 'id')],
        ], [
            'userId.unique' => 'Ese usuario ya está vinculado a otro profesional.',
            'userId.exists' => 'El usuario tiene que tener rol Profesional.',
        ], [
            'name' => 'nombre',
            'commission' => 'comisión',
        ]);

        $errors = ScheduleValidator::errors($this->schedule);

        if ($errors) {
            foreach ($errors as $key => $message) {
                $this->addError($key, $message);
            }

            $this->dispatch('toast', message: 'Revisá el horario semanal.', type: 'error');

            return;
        }

        DB::transaction(function () use ($data) {
            $attributes = [
                'name' => $data['name'],
                'color' => strtolower($data['color']),
                'active' => $data['active'],
                'accepts_online' => $data['acceptsOnline'],
                'user_id' => $data['userId'] ?: null,
                'commission_percent' => $data['commission'] === '' || $data['commission'] === null ? null : (int) $data['commission'],
            ];

            if ($this->professionalId) {
                $professional = Professional::findOrFail($this->professionalId);
                $professional->update($attributes);
            } else {
                $professional = Professional::create($attributes + [
                    'location_id' => Location::current()->id,
                    'sort_order' => (int) Professional::max('sort_order') + 1,
                ]);
            }

            $professional->services()->sync(array_map('intval', $data['serviceIds']));

            $professional->schedules()->delete();
            $professional->breaks()->delete();

            foreach ($this->schedule as $day => $row) {
                $professional->schedules()->create([
                    'weekday' => $day,
                    'active' => (bool) $row['active'],
                    'start_time' => $row['start'],
                    'end_time' => $row['end'],
                ]);

                if (! $row['active']) {
                    continue;
                }

                foreach ($row['breaks'] as $break) {
                    $professional->breaks()->create([
                        'weekday' => $day,
                        'start_time' => $break['start'],
                        'end_time' => $break['end'],
                    ]);
                }
            }
        });

        session()->flash('toast', $this->professionalId ? 'Profesional actualizado.' : 'Profesional creado.');

        $this->redirectRoute('professionals.index', navigate: true);
    }

    public function render()
    {
        $users = User::query()
            ->where('role', Role::Profesional->value)
            ->where(fn ($q) => $q->whereDoesntHave('professional')
                ->when($this->professionalId, fn ($w) => $w->orWhereHas('professional', fn ($p) => $p->whereKey($this->professionalId))))
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return view('livewire.professionals.form', [
            'categories' => ServiceCategory::ordered()->with('services')->get(),
            'users' => $users,
            'weekdays' => Weekdays::NAMES,
            'colors' => Professional::COLORS,
        ])->title($this->professionalId ? 'Editar profesional' : 'Nuevo profesional');
    }
}
