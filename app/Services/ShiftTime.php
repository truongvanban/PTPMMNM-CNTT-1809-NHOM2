<?php

declare(strict_types=1);

namespace App\Services;

use DateTimeImmutable;

final class ShiftTime
{
    /**
     * @return array{0: int, 1: int}
     */
    public static function interval(array $shift): array
    {
        $start = new DateTimeImmutable($shift['date'] . ' ' . $shift['time_start']);
        $end = new DateTimeImmutable($shift['date'] . ' ' . $shift['time_end']);
        if ($end <= $start) {
            $end = $end->modify('+1 day');
        }

        return [$start->getTimestamp(), $end->getTimestamp()];
    }

    public static function overlaps(array $firstShift, array $secondShift): bool
    {
        [$firstStart, $firstEnd] = self::interval($firstShift);
        [$secondStart, $secondEnd] = self::interval($secondShift);

        return $firstStart < $secondEnd && $firstEnd > $secondStart;
    }
}
