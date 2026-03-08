<?php

namespace AaronFrancis\Eventable\Tests\Fixtures;

use AaronFrancis\Eventable\Contracts\PruneableEvent;
use AaronFrancis\Eventable\Prune;
use AaronFrancis\Eventable\PruneConfig;
use Illuminate\Support\Carbon;

enum PruneableTestEvent: int implements PruneableEvent
{
    case KeepForever = 1;
    case PruneOlderThan30Days = 2;
    case KeepLast5 = 3;
    case KeepLast3VaryOnData = 4;
    case NoPruneConfig = 5;

    public function prune(): PruneConfig|Prune|null
    {
        return match ($this) {
            self::KeepForever => null,
            self::PruneOlderThan30Days => Prune::before(Carbon::now()->subDays(30)),
            self::KeepLast5 => Prune::keep(5)->dontVaryOnData(),
            self::KeepLast3VaryOnData => Prune::keep(3)->varyOnData(),
            self::NoPruneConfig => null,
        };
    }
}
