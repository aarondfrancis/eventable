<?php

namespace AaronFrancis\Eventable\Models;

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
    public function scopeOfType($query, $type): void
    {
        if (is_object($type) && enum_exists($type::class)) {
            $type = $type->value;
        }

        is_array($type)
            ? $query->whereIn('type', $type)
            : $query->where('type', $type);
    }

    public function scopeWhereData($query, $data = null): void
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

    public function scopeHappenedAfter($query, Carbon $time): void
    {
        $this->happened($query, $time, before: false);
    }

    public function scopeHappenedBefore($query, Carbon $time): void
    {
        $this->happened($query, $time, before: true);
    }

    public function scopeHappenedBetween($query, Carbon $start, Carbon $end): void
    {
        $query->where('created_at', '>', $start->copy()->setTimezone('UTC'))
            ->where('created_at', '<', $end->copy()->setTimezone('UTC'));
    }

    public function scopeHappenedToday($query): void
    {
        $query->whereDate('created_at', Carbon::today());
    }

    public function scopeHappenedThisWeek($query): void
    {
        $query->where('created_at', '>=', Carbon::now()->startOfWeek());
    }

    public function scopeHappenedThisMonth($query): void
    {
        $query->where('created_at', '>=', Carbon::now()->startOfMonth());
    }

    protected function happened($query, Carbon $time, bool $before = true): void
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
