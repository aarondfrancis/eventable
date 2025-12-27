<?php

namespace AaronFrancis\Eventable\Tests\Fixtures;

use AaronFrancis\Eventable\Contracts\PruneableEvent;
use AaronFrancis\Eventable\PruneConfig;
use Illuminate\Support\Carbon;

enum PruneableTestEvent: int implements PruneableEvent
{
    case KeepForever = 1;
    case PruneOlderThan30Days = 2;
    case KeepLast5 = 3;
    case KeepLast3VaryOnData = 4;
    case NoPruneConfig = 5;

    public function prune(): ?PruneConfig
    {
        return match ($this) {
            self::KeepForever => null,
            self::PruneOlderThan30Days => new PruneConfig(before: Carbon::now()->subDays(30)),
            self::KeepLast5 => new PruneConfig(keep: 5, varyOnData: false),
            self::KeepLast3VaryOnData => new PruneConfig(keep: 3, varyOnData: true),
            self::NoPruneConfig => null,
        };
    }
}
