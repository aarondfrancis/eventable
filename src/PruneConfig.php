<?php

namespace AaronFrancis\Eventable;

use Illuminate\Support\Carbon;

readonly class PruneConfig
{
    public function __construct(
        public ?Carbon $before = null,
        public int $keep = 0,
        public bool $varyOnData = true
    ) {
        //
    }
}
