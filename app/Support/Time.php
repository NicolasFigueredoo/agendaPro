<?php

namespace App\Support;

/**
 * Helpers para horas "HH:MM" (los campos TIME de MySQL vuelven como "HH:MM:SS")
 * e intervalos en minutos desde las 00:00.
 */
class Time
{
    /** "09:30:00" → "09:30" */
    public static function hm(?string $time): ?string
    {
        return $time ? substr($time, 0, 5) : null;
    }

    /** "09:30" → 570 */
    public static function toMinutes(string $time): int
    {
        [$h, $m] = array_map('intval', explode(':', $time) + [0, 0]);

        return $h * 60 + $m;
    }

    /** 570 → "09:30" */
    public static function fromMinutes(int $minutes): string
    {
        return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
    }

    public static function isValid(mixed $time): bool
    {
        return is_string($time) && preg_match('/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/', $time) === 1;
    }

    /** 90 → "1 h 30 min" */
    public static function durationLabel(int $minutes): string
    {
        $h = intdiv($minutes, 60);
        $m = $minutes % 60;

        return match (true) {
            $h === 0 => "{$m} min",
            $m === 0 => "{$h} h",
            default => "{$h} h {$m} min",
        };
    }

    /**
     * Resta [start, end) a una lista de intervalos.
     * subtract([[540, 1080]], 780, 840) → [[540, 780], [840, 1080]]
     *
     * @param  array<int, array{0:int, 1:int}>  $intervals
     * @return array<int, array{0:int, 1:int}>
     */
    public static function subtract(array $intervals, int $start, int $end): array
    {
        $result = [];

        foreach ($intervals as [$s, $e]) {
            if ($end <= $s || $start >= $e) {
                $result[] = [$s, $e];

                continue;
            }

            if ($start > $s) {
                $result[] = [$s, $start];
            }

            if ($end < $e) {
                $result[] = [$end, $e];
            }
        }

        return $result;
    }
}
