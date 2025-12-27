<?php

namespace AaronFrancis\Eventable\Concerns;

use AaronFrancis\Eventable\Models\Event;
use BackedEnum;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * @method MorphMany morphMany(string $related, string $name, ?string $type = null, ?string $id = null, ?string $localKey = null)
 */
trait HasEvents
{
    public function addEvent(BackedEnum $event, mixed $data = null): Event
    {
        return $this->events()->create([
            'type' => $event->value,
            'data' => $data,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */
    public function scopeWhereEventHasHappened($query, BackedEnum $event, array $data = []): void
    {
        $this->queryEventExistence($query, $event, $data, hasHappened: true);
    }

    public function scopeWhereEventHasntHappened($query, BackedEnum $event, array $data = []): void
    {
        $this->queryEventExistence($query, $event, $data, hasHappened: false);
    }

    protected function queryEventExistence($query, BackedEnum $event, array $data, bool $hasHappened = true): void
    {
        $method = $hasHappened ? 'whereHas' : 'whereDoesntHave';

        $query->$method('events', function ($events) use ($event, $data) {
            return $events->ofType($event)->whereData($data);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */
    public function events(): MorphMany
    {
        $eventModel = config('eventable.model', Event::class);

        return $this->morphMany($eventModel, 'eventable');
    }
}
