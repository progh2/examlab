<?php

namespace App\Support;

use Carbon\CarbonImmutable;

class SpacedRepetition
{
    /** @var array<int, int> */
    private const STAGE_MINUTES = [
        0 => 10,
        1 => 60 * 24,
        2 => 60 * 24 * 3,
        3 => 60 * 24 * 7,
        4 => 60 * 24 * 14,
        5 => 60 * 24 * 30,
        6 => 60 * 24 * 60,
    ];

    public static function nextDueAt(int $stage, ?CarbonImmutable $now = null): CarbonImmutable
    {
        $now = $now ?: CarbonImmutable::now();
        $minutes = self::STAGE_MINUTES[$stage] ?? self::STAGE_MINUTES[6];
        return $now->addMinutes($minutes);
    }

    public static function clampStage(int $stage): int
    {
        if ($stage < 0) {
            return 0;
        }

        if ($stage > 6) {
            return 6;
        }

        return $stage;
    }
}

