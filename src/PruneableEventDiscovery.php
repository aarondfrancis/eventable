<?php

namespace AaronFrancis\Eventable;

use AaronFrancis\Eventable\Contracts\PruneableEvent;

class PruneableEventDiscovery
{
    /**
     * Discover all enums implementing PruneableEvent from the event_types config.
     *
     * @return array<class-string<PruneableEvent>>
     */
    public static function discover(): array
    {
        $eventTypes = EventTypeRegistry::all();
        $pruneableEnums = [];

        foreach ($eventTypes as $alias => $enumClass) {
            if (! enum_exists($enumClass)) {
                continue;
            }

            if (! is_subclass_of($enumClass, PruneableEvent::class)) {
                continue;
            }

            $pruneableEnums[] = $enumClass;
        }

        return $pruneableEnums;
    }
}
