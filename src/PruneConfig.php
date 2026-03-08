<?php

namespace AaronFrancis\Eventable;

use Illuminate\Support\Carbon;

readonly class PruneConfig
{
    public static function from(self|Prune $prune): self
    {
        if ($prune instanceof self) {
            return $prune;
        }

        return $prune->toPruneConfig();
    }

    public function __construct(
        public ?Carbon $before = null,
        public ?int $keep = null,
        public bool $varyOnData = true
    ) {
        if ($this->keep !== null && $this->keep < 1) {
            throw new \InvalidArgumentException('PruneConfig keep must be at least 1.');
        }

        if ($this->before === null && $this->keep === null) {
            throw new \InvalidArgumentException('PruneConfig must define before and/or keep.');
        }
    }
}
