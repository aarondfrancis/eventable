# API Reference

## HasEvents Trait

Add `HasEvents` to any Eloquent model to enable event tracking.

```php
use AaronFrancis\Eventable\Concerns\HasEvents;

class User extends Model
{
    use HasEvents;
}
```

### Instance Methods

#### addEvent()

```php
public function addEvent(BackedEnum $event, mixed $data = null): Event
```

Record an event on the model.

Parameters:
- `$event`: the enum case to store
- `$data`: optional JSON-serializable array or scalar payload

#### events()

```php
public function events(): MorphMany
```

Get the model's `MorphMany` relationship to the configured Event model.

#### hasEvent()

```php
public function hasEvent(BackedEnum $event, array $data = []): bool
```

Check whether the model has a matching event.

Parameters:
- `$event`: the enum case to look for
- `$data`: optional JSON fragment to match

Note: `hasEvent()` accepts array fragments. For scalar payload matching, use `$model->events()->whereData(...)`.

#### latestEvent()

```php
public function latestEvent(?BackedEnum $type = null): ?Event
```

Get the latest event overall or the latest event of a specific type.

Ordering:
- `created_at desc`
- `id desc`

#### firstEvent()

```php
public function firstEvent(?BackedEnum $type = null): ?Event
```

Get the oldest event overall or the oldest event of a specific type.

Ordering:
- `created_at asc`
- `id asc`

#### eventCount()

```php
public function eventCount(?BackedEnum $type = null): int
```

Count all events or just events of a specific type.

### Model Scopes

#### scopeWhereEventHasHappened()

```php
public function scopeWhereEventHasHappened(Builder $query, BackedEnum $event, array $data = []): void
```

Filter models that have at least one matching event.

#### scopeWhereEventHasntHappened()

```php
public function scopeWhereEventHasntHappened(Builder $query, BackedEnum $event, array $data = []): void
```

Filter models that do not have a matching event.

#### scopeWhereEventHasHappenedTimes()

```php
public function scopeWhereEventHasHappenedTimes(Builder $query, BackedEnum $event, int $count, array $data = []): void
```

Filter models with exactly `$count` matching events.

#### scopeWhereEventHasHappenedAtLeast()

```php
public function scopeWhereEventHasHappenedAtLeast(Builder $query, BackedEnum $event, int $count, array $data = []): void
```

Filter models with at least `$count` matching events.

#### scopeWhereLatestEventIs()

```php
public function scopeWhereLatestEventIs(Builder $query, BackedEnum $event): void
```

Filter models whose latest event matches the given enum case.

Ordering:
- `created_at desc`
- `id desc`

## Event Model

`AaronFrancis\Eventable\Models\Event` stores individual events.

### Core Properties

| Property | Type | Description |
|----------|------|-------------|
| `id` | `int` | Primary key |
| `type_class` | `string` | Registered enum alias |
| `type` | `int\|string` | Enum backing value |
| `eventable_id` | `int\|string` | Parent model key |
| `eventable_type` | `string` | Parent model morph type |
| `data` | `mixed` | JSON array or scalar payload |
| `created_at` | `Carbon` | Event timestamp |
| `updated_at` | `Carbon` | Updated timestamp |

### Relationships

#### eventable()

```php
public function eventable(): MorphTo
```

Resolve the parent model.

### Query Scopes

#### scopeOfType()

```php
public function scopeOfType(Builder $query, mixed $type): void
```

Accepted inputs:
- a `BackedEnum` case
- a raw backing value
- an array of enum cases from the same enum class
- an array of raw values

Behavior:
- `BackedEnum` cases filter by both `type_class` and `type`
- raw values filter only `type`
- arrays cannot mix enum cases and raw values
- arrays of enum cases cannot mix enum classes

#### scopeOfTypeClass()

```php
public function scopeOfTypeClass(Builder $query, string $typeClass): void
```

Filter by registered enum alias.

#### scopeWhereData()

```php
public function scopeWhereData(Builder $query, mixed $data = null): void
```

Accepted inputs:
- `null` or `[]` for no additional filtering
- array fragments for JSON matching
- scalar JSON values for exact matching

Examples:

```php
Event::whereData(['order_id' => 123])->get();
Event::whereData(['payment' => ['method' => 'card']])->get();
Event::whereData('admin_reset')->get();
Event::whereData(false)->get();
Event::whereData(0)->get();
```

#### scopeHappenedAfter()

```php
public function scopeHappenedAfter(Builder $query, Carbon $time): void
```

Strictly greater than the given timestamp.

#### scopeHappenedBefore()

```php
public function scopeHappenedBefore(Builder $query, Carbon $time): void
```

Strictly less than the given timestamp.

#### scopeHappenedBetween()

```php
public function scopeHappenedBetween(Builder $query, Carbon $start, Carbon $end): void
```

Exclusive on both ends.

#### scopeHappenedToday()

```php
public function scopeHappenedToday(Builder $query, ?string $timezone = null): void
```

Filter events from the current day in the given timezone or the app timezone.

#### scopeHappenedThisWeek()

```php
public function scopeHappenedThisWeek(Builder $query, ?string $timezone = null): void
```

Filter events since the start of the current week.

#### scopeHappenedThisMonth()

```php
public function scopeHappenedThisMonth(Builder $query, ?string $timezone = null): void
```

Filter events since the start of the current month.

#### scopeHappenedInTheLast()

```php
public function scopeHappenedInTheLast(Builder $query, int $value, Unit|string $unit): void
```

Filter events in the last N units.

#### scopeHasntHappenedInTheLast()

```php
public function scopeHasntHappenedInTheLast(Builder $query, int $value, Unit|string $unit): void
```

Filter events older than N units.

## EventTypeRegistry

`EventTypeRegistry` maps aliases to enum classes.

#### register()

```php
public static function register(string $alias, string $enumClass): void
```

Register an alias manually.

#### clear()

```php
public static function clear(): void
```

Clear manual registrations.

#### all()

```php
public static function all(): array
```

Return config-defined aliases merged with manual registrations.

#### getAlias()

```php
public static function getAlias(string|BackedEnum $enum): string
```

Resolve the alias for an enum class or enum case.

#### getClass()

```php
public static function getClass(string $alias): string
```

Resolve the enum class for an alias.

#### isRegistered()

```php
public static function isRegistered(string|BackedEnum $enum): bool
```

Check whether an enum class or case is registered.

#### hasAlias()

```php
public static function hasAlias(string $alias): bool
```

Check whether an alias exists.

## Pruning Contracts

### PruneableEvent

Implement on an enum to opt into pruning:

```php
public function prune(): PruneConfig|Prune|null;
```

Return `null` to skip pruning for that event case.

### Prune

Fluent builder for `PruneConfig`:

```php
Prune::before(now()->subDays(30))->keep(5)->dontVaryOnData()
```

Methods:
- `before(DateTimeInterface $before)`: set an age cutoff
- `keep(int $keep)`: keep the newest N rows per model
- `varyOnData(bool $varyOnData = true)`: toggle payload partitioning
- `dontVaryOnData()`: convenience alias for `varyOnData(false)`
- `toPruneConfig()`: resolve the builder into a concrete `PruneConfig`

### PruneConfig

```php
public function __construct(
    public ?Carbon $before = null,
    public ?int $keep = null,
    public bool $varyOnData = true,
)
```

Properties:
- `before`: delete rows older than this timestamp
- `keep`: keep the newest N rows per model; must be at least `1` when provided
- `varyOnData`: when `keep` is used, partition by canonicalized JSON payload as well

At least one of `before` or `keep` must be provided.

```php
public static function from(PruneConfig|Prune $prune): PruneConfig;
```
