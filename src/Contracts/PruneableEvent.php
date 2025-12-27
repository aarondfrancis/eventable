<?php

namespace AaronFrancis\Eventable\Contracts;

use AaronFrancis\Eventable\PruneConfig;

interface PruneableEvent
{
    /**
     * Get the prune configuration for this event type.
     * Return null to skip pruning for this event type.
     */
    public function prune(): ?PruneConfig;
}
