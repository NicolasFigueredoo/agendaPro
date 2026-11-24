<?php

namespace App\Support;

/**
 * Valida un horario semanal con la forma:
 * [weekday => ['active' => bool, 'start' => 'HH:MM', 'end' => 'HH:MM', 'breaks' => [['start' => .., 'end' => ..], ...]]]
 *
 * Devuelve [clave_livewire => mensaje] para usar con $this->addError().
 */
class ScheduleValidator
{
    public static function errors(array $days, string $prefix = 'schedule'): array
    {
        $errors = [];

        foreach ($days as $day => $data) {
            if (empty($data['active'])) {
                continue;
            }

            $start = $data['start'] ?? null;
            $end = $data['end'] ?? null;

            if (! Time::isValid($start)) {
                $errors["{$prefix}.{$day}.start"] = 'Hora de inicio inválida.';

                continue;
            }

            if (! Time::isValid($end)) {
                $errors["{$prefix}.{$day}.end"] = 'Hora de fin inválida.';

                continue;
            }

            $startMin = Time::toMinutes($start);
            $endMin = Time::toMinutes($end);

            if ($endMin <= $startMin) {
                $errors["{$prefix}.{$day}.end"] = 'La hora de fin tiene que ser posterior al inicio.';

                continue;
            }

            $ranges = [];

            foreach ($data['breaks'] ?? [] as $i => $break) {
                $key = "{$prefix}.{$day}.breaks.{$i}";

                if (! Time::isValid($break['start'] ?? null) || ! Time::isValid($break['end'] ?? null)) {
                    $errors["{$key}.start"] = 'Completá el horario del descanso.';

                    continue;
                }

                $bs = Time::toMinutes($break['start']);
                $be = Time::toMinutes($break['end']);

                if ($be <= $bs) {
                    $errors["{$key}.start"] = 'El descanso tiene que terminar después de empezar.';

                    continue;
                }

                if ($bs < $startMin || $be > $endMin) {
                    $errors["{$key}.start"] = 'El descanso tiene que estar dentro del horario del día.';

                    continue;
                }

                foreach ($ranges as [$os, $oe]) {
                    if ($bs < $oe && $be > $os) {
                        $errors["{$key}.start"] = 'Se superpone con otro descanso.';

                        continue 2;
                    }
                }

                $ranges[] = [$bs, $be];
            }
        }

        return $errors;
    }
}
