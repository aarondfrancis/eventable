<?php

namespace AaronFrancis\Eventable\Tests\Fixtures;

use AaronFrancis\Eventable\Contracts\PruneableEvent;
use AaronFrancis\Eventable\Prune;
use AaronFrancis\Eventable\PruneConfig;
use Illuminate\Support\Carbon;

enum CombinedPruneEvent: int implements PruneableEvent
{
    case KeepLast3OlderThan7Days = 1;
    case KeepLast5NoVaryOnData = 2;

    public function prune(): PruneConfig|Prune|null
    {
        return match ($this) {
            self::KeepLast3OlderThan7Days => Prune::before(Carbon::now()->subDays(7))
                ->keep(3)
                ->varyOnData(),
            self::KeepLast5NoVaryOnData => Prune::keep(5)->dontVaryOnData(),
        };
    }
}
