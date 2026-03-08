<?php

namespace AaronFrancis\Eventable\Tests\Fixtures;

use AaronFrancis\Eventable\Models\Event;
use Illuminate\Database\Eloquent\Builder;

class ScopedCustomEvent extends Event
{
    protected static function booted(): void
    {
        static::addGlobalScope('without-updated-events', function (Builder $query) {
            $query->where('type', '!=', TestEvent::Updated->value);
        });
    }
}
