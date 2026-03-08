<?php

namespace AaronFrancis\Eventable\Models;

use AaronFrancis\Eventable\EventTypeRegistry;
use BackedEnum;
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
    public function scopeOfType(Builder $query, mixed $type): void
    {
        if ($type instanceof BackedEnum) {
            $query->where('type_class', EventTypeRegistry::getAlias($type))
                ->where('type', $type->value);

            return;
        }

        if (is_array($type)) {
            $this->applyTypeArrayFilter($query, $type);

            return;
        }

        $query->where('type', $type);
    }

    public function scopeOfTypeClass(Builder $query, string $typeClass): void
    {
        $query->where('type_class', $typeClass);
    }

    public function scopeWhereData(Builder $query, mixed $data = null): void
    {
        if ($data === null || $data === []) {
            return;
        }

        if (! is_array($data)) {
            $this->whereEntireJsonValue($query, $data);

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

    protected function applyTypeArrayFilter(Builder $query, array $types): void
    {
        $enumTypes = array_filter($types, fn (mixed $type) => $type instanceof BackedEnum);

        if ($enumTypes === []) {
            $query->whereIn('type', $types);

            return;
        }

        if (count($enumTypes) !== count($types)) {
            throw new \InvalidArgumentException('ofType() expects either raw values or BackedEnum cases from the same enum.');
        }

        $firstEnum = reset($enumTypes);
        $enumClass = $firstEnum::class;

        foreach ($enumTypes as $enumType) {
            if ($enumType::class !== $enumClass) {
                throw new \InvalidArgumentException('ofType() cannot mix enum classes in the same array.');
            }
        }

        $query->where('type_class', EventTypeRegistry::getAlias($enumClass))
            ->whereIn('type', array_map(fn (BackedEnum $type) => $type->value, $enumTypes));
    }

    protected function whereEntireJsonValue(Builder $query, mixed $data): void
    {
        $encoded = json_encode($data);
        $column = $query->getQuery()->getGrammar()->wrap($this->qualifyColumn('data'));

        match ($query->getConnection()->getDriverName()) {
            'mysql' => $query->whereRaw("{$column} = CAST(? AS JSON)", [$encoded]),
            'pgsql' => $query->whereRaw("({$column})::jsonb = ?::jsonb", [$encoded]),
            'sqlite' => $query->whereRaw("json({$column}) = json(?)", [$encoded]),
            default => $query->where('data', $encoded),
        };
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
