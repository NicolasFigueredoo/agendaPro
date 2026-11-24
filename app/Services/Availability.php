<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Professional;
use App\Models\ScheduleBlock;
use App\Support\Time;
use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * Disponibilidad de los profesionales.
 *
 * Disponible = jornada del día − descansos − bloqueos − turnos que ocupan horario
 * (no cancelados y, si son reservas online sin seña, solo mientras no venció la retención).
 *
 * Todo se maneja en minutos desde las 00:00 del día.
 */
class Availability
{
    /**
     * Jornada del día menos descansos y bloqueos.
     *
     * @return array<int, array{0:int, 1:int}>
     */
    public function workingIntervals(Professional $professional, CarbonInterface $date): array
    {
        $intervals = $professional->workingIntervals($date->dayOfWeekIso);

        if ($intervals === []) {
            return [];
        }

        $blocks = ScheduleBlock::query()
            ->where('professional_id', $professional->id)
            ->where('start_at', '<', $date->copy()->endOfDay())
            ->where('end_at', '>', $date->copy()->startOfDay())
            ->get();

        foreach ($blocks as $block) {
            [$s, $e] = $this->clipToDay($block->start_at, $block->end_at, $date);
            $intervals = Time::subtract($intervals, $s, $e);
        }

        return $intervals;
    }

    /**
     * Turnos que ocupan horario ese día.
     *
     * @return array<int, array{0:int, 1:int}>
     */
    public function busyIntervals(Professional $professional, CarbonInterface $date, ?int $exceptBookingId = null): array
    {
        return Booking::query()
            ->where('professional_id', $professional->id)
            ->occupying()
            ->overlapping($date->copy()->startOfDay(), $date->copy()->endOfDay())
            ->when($exceptBookingId, fn ($q) => $q->whereKeyNot($exceptBookingId))
            ->get(['start_at', 'end_at'])
            ->map(fn (Booking $b) => $this->clipToDay($b->start_at, $b->end_at, $date))
            ->all();
    }

    /**
     * Huecos libres del día.
     *
     * @return array<int, array{0:int, 1:int}>
     */
    public function freeIntervals(Professional $professional, CarbonInterface $date, ?int $exceptBookingId = null): array
    {
        $free = $this->workingIntervals($professional, $date);

        foreach ($this->busyIntervals($professional, $date, $exceptBookingId) as [$s, $e]) {
            $free = Time::subtract($free, $s, $e);
        }

        return $free;
    }

    /**
     * Horarios de inicio posibles para un servicio de $duration minutos.
     * Arranca al inicio de cada hueco y después sigue la grilla de $step minutos.
     *
     * @return array<int, string> ["09:00", "09:30", ...]
     */
    public function slots(Professional $professional, CarbonInterface $date, int $duration, int $step = 30, ?CarbonInterface $notBefore = null): array
    {
        $slots = [];
        $minStart = null;

        if ($notBefore) {
            if ($notBefore->copy()->startOfDay()->gt($date->copy()->startOfDay())) {
                return [];
            }

            if ($notBefore->isSameDay($date)) {
                $minStart = $notBefore->hour * 60 + $notBefore->minute;
            }
        }

        foreach ($this->freeIntervals($professional, $date) as [$s, $e]) {
            $t = $s;

            while ($t + $duration <= $e) {
                if ($minStart === null || $t >= $minStart) {
                    $slots[] = Time::fromMinutes($t);
                }

                $t = (intdiv($t, $step) + 1) * $step;
            }
        }

        return array_values(array_unique($slots));
    }

    /** ¿El rango completo cae dentro de la jornada (sin descansos ni bloqueos)? */
    public function isWithinSchedule(Professional $professional, CarbonInterface $start, CarbonInterface $end): bool
    {
        if (! $start->isSameDay($end) && ! $end->eq($start->copy()->addDay()->startOfDay())) {
            return false;
        }

        $s = $start->hour * 60 + $start->minute;
        $e = $end->isSameDay($start) ? $end->hour * 60 + $end->minute : 1440;

        foreach ($this->workingIntervals($professional, $start) as [$ws, $we]) {
            if ($s >= $ws && $e <= $we) {
                return true;
            }
        }

        return false;
    }

    public function professionalIsBusy(int $professionalId, CarbonInterface $start, CarbonInterface $end, ?int $exceptBookingId = null): bool
    {
        return Booking::query()
            ->where('professional_id', $professionalId)
            ->occupying()
            ->overlapping($start, $end)
            ->when($exceptBookingId, fn ($q) => $q->whereKeyNot($exceptBookingId))
            ->exists();
    }

    public function clientIsBusy(int $clientId, CarbonInterface $start, CarbonInterface $end, ?int $exceptBookingId = null): bool
    {
        return Booking::query()
            ->where('client_id', $clientId)
            ->occupying()
            ->overlapping($start, $end)
            ->when($exceptBookingId, fn ($q) => $q->whereKeyNot($exceptBookingId))
            ->exists();
    }

    /** ¿El profesional puede tomar ese turno? (jornada + sin superposición) */
    public function isAvailable(Professional $professional, CarbonInterface $start, CarbonInterface $end, ?int $exceptBookingId = null): bool
    {
        return $this->isWithinSchedule($professional, $start, $end)
            && ! $this->professionalIsBusy($professional->id, $start, $end, $exceptBookingId);
    }

    /** @return array{0:int, 1:int} */
    protected function clipToDay(CarbonInterface $start, CarbonInterface $end, CarbonInterface $date): array
    {
        $dayStart = Carbon::parse($date)->startOfDay();
        $dayEnd = $dayStart->copy()->addDay();

        $s = $start->lt($dayStart) ? 0 : (int) $dayStart->diffInMinutes($start);
        $e = $end->gt($dayEnd) ? 1440 : (int) $dayStart->diffInMinutes($end);

        return [$s, $e];
    }
}
