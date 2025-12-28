<?php

namespace AaronFrancis\Eventable\Concerns;

use AaronFrancis\Eventable\Models\Event;
use BackedEnum;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * @method MorphMany morphMany(string $related, string $name, ?string $type = null, ?string $id = null, ?string $localKey = null)
 */
trait Eventable
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
    | Helper Methods
    |--------------------------------------------------------------------------
    */
    public function hasEvent(BackedEnum $event, array $data = []): bool
    {
        return $this->events()->ofType($event)->whereData($data)->exists();
    }

    public function latestEvent(?BackedEnum $type = null): ?Event
    {
        $query = $this->events()->latest();

        if ($type !== null) {
            $query->ofType($type);
        }

        return $query->first();
    }

    public function firstEvent(?BackedEnum $type = null): ?Event
    {
        $query = $this->events()->oldest();

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
    public function scopeWhereEventHasHappened($query, BackedEnum $event, array $data = []): void
    {
        $this->queryEventExistence($query, $event, $data, hasHappened: true);
    }

    public function scopeWhereEventHasntHappened($query, BackedEnum $event, array $data = []): void
    {
        $this->queryEventExistence($query, $event, $data, hasHappened: false);
    }

    public function scopeWhereEventHasHappenedTimes($query, BackedEnum $event, int $count, array $data = []): void
    {
        $query->whereHas('events', function ($events) use ($event, $data) {
            $events->ofType($event)->whereData($data);
        }, '=', $count);
    }

    public function scopeWhereEventHasHappenedAtLeast($query, BackedEnum $event, int $count, array $data = []): void
    {
        $query->whereHas('events', function ($events) use ($event, $data) {
            $events->ofType($event)->whereData($data);
        }, '>=', $count);
    }

    public function scopeWhereLatestEventIs($query, BackedEnum $event): void
    {
        $eventModel = config('eventable.model', Event::class);
        $table = (new $eventModel)->getTable();

        $query->whereHas('events', function ($events) use ($event, $table) {
            $events->where('id', function ($sub) use ($table) {
                $sub->selectRaw('MAX(id)')
                    ->from($table.' as e2')
                    ->whereColumn('e2.eventable_id', $table.'.eventable_id')
                    ->whereColumn('e2.eventable_type', $table.'.eventable_type');
            })->where('type', $event->value);
        });
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
