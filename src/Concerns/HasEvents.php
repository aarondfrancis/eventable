<?php

namespace AaronFrancis\Eventable\Concerns;

use AaronFrancis\Eventable\EventTypeRegistry;
use AaronFrancis\Eventable\Models\Event;
use BackedEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * @method MorphMany morphMany(string $related, string $name, ?string $type = null, ?string $id = null, ?string $localKey = null)
 */
trait HasEvents
{
    public function addEvent(BackedEnum $event, mixed $data = null): Event
    {
        return $this->events()->create([
            'type_class' => EventTypeRegistry::getAlias($event),
            'type' => $event->value,
            'data' => $data,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */
    public function hasEvent(BackedEnum $event, array $data = []): bool
    {
        return $this->events()
            ->ofType($event)
            ->whereData($data)
            ->exists();
    }

    public function latestEvent(?BackedEnum $type = null): ?Event
    {
        $query = $this->events()
            ->reorder()
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if ($type !== null) {
            $query->ofType($type);
        }

        return $query->first();
    }

    public function firstEvent(?BackedEnum $type = null): ?Event
    {
        $query = $this->events()
            ->reorder()
            ->orderBy('created_at')
            ->orderBy('id');

        if ($type !== null) {
            $query->ofType($type);
        }

        return $query->first();
    }

    public function eventCount(?BackedEnum $type = null): int
    {
        $query = $this->events();

        if ($type !== null) {
            $query->ofType($type);
        }

        return $query->count();
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */
    public function scopeWhereEventHasHappened(Builder $query, BackedEnum $event, array $data = []): void
    {
        $this->queryEventExistence($query, $event, $data, hasHappened: true);
    }

    public function scopeWhereEventHasntHappened(Builder $query, BackedEnum $event, array $data = []): void
    {
        $this->queryEventExistence($query, $event, $data, hasHappened: false);
    }

    public function scopeWhereEventHasHappenedTimes(Builder $query, BackedEnum $event, int $count, array $data = []): void
    {
        $query->whereHas('events', function ($events) use ($event, $data) {
            $events->ofType($event)->whereData($data);
        }, '=', $count);
    }

    public function scopeWhereEventHasHappenedAtLeast(Builder $query, BackedEnum $event, int $count, array $data = []): void
    {
        $query->whereHas('events', function ($events) use ($event, $data) {
            $events->ofType($event)->whereData($data);
        }, '>=', $count);
    }

    public function scopeWhereLatestEventIs(Builder $query, BackedEnum $event): void
    {
        $typeClass = EventTypeRegistry::getAlias($event);

        $query->whereHas('events', function ($events) use ($event, $typeClass) {
            $table = $events->getModel()->getTable();

            $events->where('id', function ($sub) use ($table) {
                $sub->from($table.' as e2')
                    ->select('id')
                    ->whereColumn('e2.eventable_id', $table.'.eventable_id')
                    ->whereColumn('e2.eventable_type', $table.'.eventable_type')
                    ->orderByDesc('e2.created_at')
                    ->orderByDesc('e2.id')
                    ->limit(1);
            })
                ->where('type_class', $typeClass)
                ->where('type', $event->value);
        });
    }

    protected function queryEventExistence(Builder $query, BackedEnum $event, array $data, bool $hasHappened = true): void
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
