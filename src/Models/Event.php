<?php

namespace AaronFrancis\Eventable\Models;

use Carbon\Unit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

class Event extends Model
{
    protected $casts = [
        'data' => 'json',
    ];

    protected $guarded = [];

    public function getTable(): string
    {
        return config('eventable.table', 'events');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */
    public function scopeOfType(Builder $query, $type): void
    {
        if (is_object($type) && enum_exists($type::class)) {
            $type = $type->value;
        }

        is_array($type)
            ? $query->whereIn('type', $type)
            : $query->where('type', $type);
    }

    public function scopeOfTypeClass(Builder $query, string $typeClass): void
    {
        $query->where('type_class', $typeClass);
    }

    public function scopeWhereData(Builder $query, $data = null): void
    {
        if (empty($data)) {
            return;
        }

        if (! is_array($data)) {
            $query->where('data', json_encode($data));

            return;
        }

        // If it's an array, then we need to convert a potentially
        // nested array into the form that Laravel supports
        // for json columns, which is `column->key->key`
        $data = Arr::dot($data, 'data.');

        foreach ($data as $key => $value) {
            $query->where(str_replace('.', '->', $key), $value);
        }
    }

    public function scopeHappenedAfter(Builder $query, Carbon $time): void
    {
        $this->happened($query, $time, before: false);
    }

    public function scopeHappenedBefore(Builder $query, Carbon $time): void
    {
        $this->happened($query, $time, before: true);
    }

    public function scopeHappenedBetween(Builder $query, Carbon $start, Carbon $end): void
    {
        $query->where('created_at', '>', $start->copy()->setTimezone('UTC'))
            ->where('created_at', '<', $end->copy()->setTimezone('UTC'));
    }

    public function scopeHappenedToday(Builder $query, ?string $timezone = null): void
    {
        $tz = $timezone ?? config('app.timezone', 'UTC');
        $start = Carbon::now($tz)->startOfDay()->setTimezone('UTC');
        $end = Carbon::now($tz)->endOfDay()->setTimezone('UTC');

        $query->where('created_at', '>=', $start)
            ->where('created_at', '<=', $end);
    }

    public function scopeHappenedThisWeek(Builder $query, ?string $timezone = null): void
    {
        $tz = $timezone ?? config('app.timezone', 'UTC');
        $start = Carbon::now($tz)->startOfWeek()->setTimezone('UTC');

        $query->where('created_at', '>=', $start);
    }

    public function scopeHappenedThisMonth(Builder $query, ?string $timezone = null): void
    {
        $tz = $timezone ?? config('app.timezone', 'UTC');
        $start = Carbon::now($tz)->startOfMonth()->setTimezone('UTC');

        $query->where('created_at', '>=', $start);
    }

    public function scopeHappenedInTheLast(Builder $query, int $value, Unit|string $unit): void
    {
        $cutoff = Carbon::now()->sub($value, Unit::toName($unit))->setTimezone('UTC');

        $query->where('created_at', '>=', $cutoff);
    }

    public function scopeHasntHappenedInTheLast(Builder $query, int $value, Unit|string $unit): void
    {
        $cutoff = Carbon::now()->sub($value, Unit::toName($unit))->setTimezone('UTC');

        $query->where('created_at', '<', $cutoff);
    }

    protected function happened(Builder $query, Carbon $time, bool $before = true): void
    {
        $query->where('created_at', $before ? '<' : '>', $time->copy()->setTimezone('UTC'));
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */
    public function eventable(): MorphTo
    {
        return $this->morphTo();
    }
}
