<?php

namespace App\Queries;

use App\Models\Client;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

/**
 * Filtros del listado de clientes. Lo usan el listado y la exportación a Excel.
 *
 * $filters = [
 *   'search' => string, 'professional' => id, 'service' => id, 'status' => BookingStatus value,
 *   'birthday_from' => 'Y-m-d', 'birthday_to' => 'Y-m-d',
 *   'created_from' => 'Y-m-d', 'created_to' => 'Y-m-d',
 *   'booked' => 'yes' | 'no' | '',
 *   'sort' => columna, 'direction' => 'asc' | 'desc',
 * ]
 */
class ClientFilters
{
    public const SORTABLE = ['client_number', 'last_name', 'first_name', 'created_at', 'bookings_count', 'last_visit'];

    public static function query(array $filters): Builder
    {
        $query = Client::query()
            ->withCount(['bookings' => fn ($q) => $q->active()])
            ->withMax(['bookings as last_visit' => fn ($q) => $q->active()->where('start_at', '<=', now())], 'start_at')
            ->search($filters['search'] ?? null);

        static::applyBookingFilters($query, $filters);
        static::applyBirthday($query, $filters['birthday_from'] ?? null, $filters['birthday_to'] ?? null);

        if (! empty($filters['created_from'])) {
            $query->where('created_at', '>=', Carbon::parse($filters['created_from'])->startOfDay());
        }

        if (! empty($filters['created_to'])) {
            $query->where('created_at', '<=', Carbon::parse($filters['created_to'])->endOfDay());
        }

        $sort = in_array($filters['sort'] ?? null, self::SORTABLE, true) ? $filters['sort'] : 'last_name';
        $direction = ($filters['direction'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

        $query->orderBy($sort, $direction);

        if ($sort === 'last_name') {
            $query->orderBy('first_name', $direction);
        }

        return $query->orderBy('id');
    }

    protected static function applyBookingFilters(Builder $query, array $filters): void
    {
        $professional = $filters['professional'] ?? null;
        $service = $filters['service'] ?? null;
        $status = $filters['status'] ?? null;

        if ($professional || $service || $status) {
            $query->whereHas('bookings', function (Builder $q) use ($professional, $service, $status) {
                $q->when($professional, fn ($w) => $w->where('professional_id', $professional))
                    ->when($service, fn ($w) => $w->where('service_id', $service))
                    ->when($status, fn ($w) => $w->where('status', $status));
            });
        }

        match ($filters['booked'] ?? '') {
            'yes' => $query->whereHas('bookings', fn ($q) => $q->active()),
            'no' => $query->whereDoesntHave('bookings', fn ($q) => $q->active()),
            default => null,
        };
    }

    /**
     * Cumpleaños entre dos fechas, ignorando el año. Soporta rangos que cruzan fin de año (20/12 → 10/01).
     */
    protected static function applyBirthday(Builder $query, ?string $from, ?string $to): void
    {
        if (! $from && ! $to) {
            return;
        }

        $from = $from ? Carbon::parse($from)->format('m-d') : '01-01';
        $to = $to ? Carbon::parse($to)->format('m-d') : '12-31';

        $expr = $query->getConnection()->getDriverName() === 'sqlite'
            ? "strftime('%m-%d', birth_date)"
            : "DATE_FORMAT(birth_date, '%m-%d')";

        $query->whereNotNull('birth_date');

        if ($from <= $to) {
            $query->whereRaw("{$expr} between ? and ?", [$from, $to]);
        } else {
            $query->where(fn ($q) => $q->whereRaw("{$expr} >= ?", [$from])->orWhereRaw("{$expr} <= ?", [$to]));
        }
    }
}
