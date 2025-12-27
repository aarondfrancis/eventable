<?php

namespace AaronFrancis\Eventable\Tests\Fixtures;

use AaronFrancis\Eventable\Contracts\PruneableEvent;
use AaronFrancis\Eventable\PruneConfig;
use Illuminate\Support\Carbon;

enum CombinedPruneEvent: int implements PruneableEvent
{
    case KeepLast3OlderThan7Days = 1;
    case KeepLast5NoVaryOnData = 2;

    public function prune(): ?PruneConfig
    {
        return match ($this) {
            self::KeepLast3OlderThan7Days => new PruneConfig(
                before: Carbon::now()->subDays(7),
                keep: 3,
                varyOnData: true
            ),
            self::KeepLast5NoVaryOnData => new PruneConfig(
                keep: 5,
                varyOnData: false
            ),
        };
    }
}
