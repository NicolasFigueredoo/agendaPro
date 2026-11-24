<?php

namespace App\Models;

use App\Support\Time;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Professional extends Model
{
    use HasFactory;

    public const COLORS = [
        '#e11d48', '#db2777', '#c026d3', '#7c3aed', '#4f46e5', '#2563eb',
        '#0891b2', '#059669', '#65a30d', '#d97706', '#ea580c', '#475569',
    ];

    protected $fillable = [
        'location_id',
        'user_id',
        'name',
        'color',
        'active',
        'accepts_online',
        'commission_percent',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'accepts_online' => 'boolean',
            'commission_percent' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(ProfessionalSchedule::class)->orderBy('weekday');
    }

    public function breaks(): HasMany
    {
        return $this->hasMany(ProfessionalBreak::class)->orderBy('weekday')->orderBy('start_time');
    }

    public function scheduleBlocks(): HasMany
    {
        return $this->hasMany(ScheduleBlock::class)->orderBy('start_at');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    /** Activos y con reservas online habilitadas. */
    public function scopeOnline(Builder $query): Builder
    {
        return $query->where('active', true)->where('accepts_online', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function performs(Service|int $service): bool
    {
        $id = $service instanceof Service ? $service->id : $service;

        return $this->services->contains('id', $id);
    }

    /**
     * Intervalos de trabajo de un día de la semana, en minutos desde 00:00, descontando descansos.
     * Ej: 09-18 con descanso 13-14 → [[540, 780], [840, 1080]]
     * (No incluye bloqueos puntuales: eso lo resuelve App\Services\Availability.)
     *
     * @return array<int, array{0:int, 1:int}>
     */
    public function workingIntervals(int $weekday): array
    {
        $schedule = $this->schedules->firstWhere('weekday', $weekday);

        if (! $schedule || ! $schedule->active) {
            return [];
        }

        $intervals = [[Time::toMinutes($schedule->start_time), Time::toMinutes($schedule->end_time)]];

        foreach ($this->breaks->where('weekday', $weekday) as $break) {
            $intervals = Time::subtract($intervals, Time::toMinutes($break->start_time), Time::toMinutes($break->end_time));
        }

        return $intervals;
    }

    /** Minutos disponibles en un día de la semana (para el cálculo de ocupación). */
    public function workingMinutes(int $weekday): int
    {
        return collect($this->workingIntervals($weekday))->sum(fn ($i) => $i[1] - $i[0]);
    }
}
