# API Reference

## Eventable Trait

Add to any Eloquent model to enable event tracking.

```php
use AaronFrancis\Eventable\Concerns\Eventable;

class User extends Model
{
    use Eventable;
}
```

### Methods

#### addEvent()

Record an event on the model.

```php
public function addEvent(BackedEnum $event, mixed $data = null): Event
```

**Parameters:**
- `$event` — A backed enum case representing the event type
- `$data` — Optional data to attach (array, scalar, or any JSON-serializable value)

**Returns:** The created `Event` model instance

**Example:**
```php
$event = $user->addEvent(EventType::OrderPlaced, ['order_id' => 123]);
```

#### events()

Get the events relationship.

```php
public function events(): MorphMany
```

**Returns:** A `MorphMany` relationship to the Event model

**Example:**
```php
$user->events;           // Collection of events
$user->events()->get();  // Same, via query builder
```

#### hasEvent()

Check if the model has a specific event.

```php
public function hasEvent(BackedEnum $event, array $data = []): bool
```

**Parameters:**
- `$event` — The event type to check for
- `$data` — Optional data constraints

**Example:**
```php
$user->hasEvent(EventType::EmailVerified); // true or false
$user->hasEvent(EventType::OrderPlaced, ['currency' => 'USD']);
```

#### latestEvent()

Get the most recent event.

```php
public function latestEvent(?BackedEnum $type = null): ?Event
```

**Parameters:**
- `$type` — Optional event type filter

**Example:**
```php
$user->latestEvent();                       // Latest event of any type
$user->latestEvent(EventType::OrderPlaced); // Latest order event
```

#### firstEvent()

Get the oldest event.

```php
public function firstEvent(?BackedEnum $type = null): ?Event
```

**Parameters:**
- `$type` — Optional event type filter

**Example:**
```php
$user->firstEvent();                       // First event of any type
$user->firstEvent(EventType::UserLoggedIn); // First login event
```

#### eventCount()

Count events for the model.

```php
public function eventCount(?BackedEnum $type = null): int
```

**Parameters:**
- `$type` — Optional event type filter

**Example:**
```php
$user->eventCount();                      // Total event count
$user->eventCount(EventType::PageViewed); // Count of page views
```

### Model Scopes

#### scopeWhereEventHasHappened()

Find models that have a specific event.

```php
public function scopeWhereEventHasHappened(Builder $query, BackedEnum $event, array $data = []): void
```

**Parameters:**
- `$event` — The event type to check for
- `$data` — Optional data constraints

**Example:**
```php
User::whereEventHasHappened(EventType::OrderPlaced)->get();
User::whereEventHasHappened(EventType::OrderPlaced, ['total' => 100])->get();
```

#### scopeWhereEventHasntHappened()

Find models that don't have a specific event.

```php
public function scopeWhereEventHasntHappened(Builder $query, BackedEnum $event, array $data = []): void
```

**Parameters:**
- `$event` — The event type to check for absence
- `$data` — Optional data constraints

**Example:**
```php
User::whereEventHasntHappened(EventType::EmailVerified)->get();
```

#### scopeWhereEventHasHappenedTimes()

Find models with exactly N occurrences of an event.

```php
public function scopeWhereEventHasHappenedTimes(Builder $query, BackedEnum $event, int $count, array $data = []): void
```

**Parameters:**
- `$event` — The event type to count
- `$count` — Exact number of occurrences
- `$data` — Optional data constraints

**Example:**
```php
User::whereEventHasHappenedTimes(EventType::OrderPlaced, 3)->get();
User::whereEventHasHappenedTimes(EventType::OrderPlaced, 2, ['currency' => 'USD'])->get();
```

#### scopeWhereEventHasHappenedAtLeast()

Find models with at least N occurrences of an event.

```php
public function scopeWhereEventHasHappenedAtLeast(Builder $query, BackedEnum $event, int $count, array $data = []): void
```

**Parameters:**
- `$event` — The event type to count
- `$count` — Minimum number of occurrences
- `$data` — Optional data constraints

**Example:**
```php
User::whereEventHasHappenedAtLeast(EventType::UserLoggedIn, 5)->get();
User::whereEventHasHappenedAtLeast(EventType::OrderPlaced, 3, ['currency' => 'USD'])->get();
```

#### scopeWhereLatestEventIs()

Find models whose most recent event is a specific type.

```php
public function scopeWhereLatestEventIs(Builder $query, BackedEnum $event): void
```

**Parameters:**
- `$event` — The event type to match

**Example:**
```php
User::whereLatestEventIs(EventType::Subscribed)->get();
User::whereLatestEventIs(EventType::Churned)->get();
```

---

## Event Model

The `AaronFrancis\Eventable\Models\Event` model stores individual events.

### Properties

| Property | Type | Description |
|----------|------|-------------|
| `id` | `int` | Primary key |
| `type_class` | `string` | The registered enum alias |
| `type` | `int\|string` | The enum value |
| `eventable_id` | `int` | Parent model ID |
| `eventable_type` | `string` | Parent model class |
| `data` | `array\|null` | JSON data (auto-cast) |
| `created_at` | `Carbon` | When the event occurred |
| `updated_at` | `Carbon` | Last update time |

### Relationships

#### eventable()

Get the parent model.

```php
public function eventable(): MorphTo
```

**Example:**
```php
$event->eventable; // Returns the User, Order, etc.
```

### Query Scopes

#### scopeOfType()

Filter events by type.

```php
public function scopeOfType(Builder $query, $type): void
```

**Parameters:**
- `$type` — An enum case, enum value, or array of values

**Examples:**
```php
Event::ofType(EventType::OrderPlaced)->get();
Event::ofType(1)->get();
Event::ofType([1, 2, 3])->get();
```

#### scopeOfTypeClass()

Filter events by their registered enum alias.

```php
public function scopeOfTypeClass(Builder $query, string $typeClass): void
```

**Parameters:**
- `$typeClass` — The enum alias as registered in `config/eventable.php`

**Example:**
```php
Event::ofTypeClass('user')->get(); // All events from the 'user' enum
```

#### scopeWhereData()

Filter events by data content.

```php
public function scopeWhereData(Builder $query, $data = null): void
```

**Parameters:**
- `$data` — Array of key-value pairs, or a scalar value

**Examples:**
```php
Event::whereData(['order_id' => 123])->get();
Event::whereData(['payment' => ['method' => 'card']])->get();
Event::whereData('admin_reset')->get();
```

#### scopeHappenedAfter()

Filter events after a specific time.

```php
public function scopeHappenedAfter(Builder $query, Carbon $time): void
```

**Example:**
```php
Event::happenedAfter(now()->subDays(7))->get();
```

#### scopeHappenedBefore()

Filter events before a specific time.

```php
public function scopeHappenedBefore(Builder $query, Carbon $time): void
```

**Example:**
```php
Event::happenedBefore(now()->subMonth())->get();
```

#### scopeHappenedBetween()

Filter events within a date range.

```php
public function scopeHappenedBetween(Builder $query, Carbon $start, Carbon $end): void
```

**Parameters:**
- `$start` — Start of the date range (exclusive)
- `$end` — End of the date range (exclusive)

**Example:**
```php
Event::happenedBetween(
    Carbon::parse('2024-01-01'),
    Carbon::parse('2024-01-31')
)->get();
```

#### scopeHappenedToday()

Filter events from today.

```php
public function scopeHappenedToday(Builder $query, ?string $timezone = null): void
```

**Parameters:**
- `$timezone` — Optional timezone (defaults to `config('app.timezone')`)

**Example:**
```php
Event::happenedToday()->get();
Event::happenedToday('America/Chicago')->get();
Event::ofType(UserEvent::PageViewed)->happenedToday()->count();
```

#### scopeHappenedThisWeek()

Filter events from the current week (starts Monday).

```php
public function scopeHappenedThisWeek(Builder $query, ?string $timezone = null): void
```

**Parameters:**
- `$timezone` — Optional timezone (defaults to `config('app.timezone')`)

**Example:**
```php
Event::happenedThisWeek()->get();
Event::happenedThisWeek('Europe/London')->get();
Event::ofType(UserEvent::Purchase)->happenedThisWeek()->count();
```

#### scopeHappenedThisMonth()

Filter events from the current month.

```php
public function scopeHappenedThisMonth(Builder $query, ?string $timezone = null): void
```

**Parameters:**
- `$timezone` — Optional timezone (defaults to `config('app.timezone')`)

**Example:**
```php
Event::happenedThisMonth()->get();
Event::happenedThisMonth('Asia/Tokyo')->get();
Event::ofType(UserEvent::LoggedIn)->happenedThisMonth()->count();
```

#### scopeHappenedInTheLast()

Filter events that happened within the last N units of time.

```php
public function scopeHappenedInTheLast(Builder $query, int $value, Unit|string $unit): void
```

**Parameters:**
- `$value` — Number of time units
- `$unit` — Carbon `Unit` enum or string: `Unit::Day`, `Unit::Hour`, `Unit::Week`, `Unit::Month`, `Unit::Year`, etc.

**Examples:**
```php
use Carbon\Unit;

Event::happenedInTheLast(7, Unit::Day)->get();
Event::happenedInTheLast(24, Unit::Hour)->get();
Event::happenedInTheLast(3, Unit::Month)->get();

// Strings also work
Event::happenedInTheLast(7, 'days')->get();
```

#### scopeHasntHappenedInTheLast()

Filter events that happened BEFORE the last N units of time (older than).

```php
public function scopeHasntHappenedInTheLast(Builder $query, int $value, Unit|string $unit): void
```

**Parameters:**
- `$value` — Number of time units
- `$unit` — Carbon `Unit` enum or string: `Unit::Day`, `Unit::Hour`, `Unit::Week`, `Unit::Month`, `Unit::Year`, etc.

**Examples:**
```php
use Carbon\Unit;

Event::hasntHappenedInTheLast(30, Unit::Day)->get();  // Events older than 30 days
Event::hasntHappenedInTheLast(6, Unit::Month)->get(); // Events older than 6 months
```

---

## PruneConfig

Configuration for event pruning.

```php
use AaronFrancis\Eventable\PruneConfig;
```

### Constructor

```php
public function __construct(
    public ?Carbon $before = null,
    public int $keep = 0,
    public bool $varyOnData = true
)
```

**Parameters:**
- `$before` — Delete events older than this date
- `$keep` — Number of recent events to keep per model
- `$varyOnData` — Whether to count events with different data separately

**Examples:**
```php
new PruneConfig(before: now()->subDays(30));
new PruneConfig(keep: 5);
new PruneConfig(keep: 10, varyOnData: false);
new PruneConfig(before: now()->subDays(90), keep: 20);
```

---

## PruneableEvent Interface

Implement on your event enum to enable pruning.

```php
use AaronFrancis\Eventable\Contracts\PruneableEvent;
```

### Methods

#### prune()

Return the prune configuration for this event type.

```php
public function prune(): ?PruneConfig;
```

**Returns:** A `PruneConfig` instance, or `null` to skip pruning

**Example:**
```php
enum EventType: int implements PruneableEvent
{
    case PageViewed = 1;
    case OrderPlaced = 2;

    public function prune(): ?PruneConfig
    {
        return match ($this) {
            self::PageViewed => new PruneConfig(keep: 10),
            self::OrderPlaced => null,
        };
    }
}
```

---

## Artisan Commands

### eventable:prune

Prune old events based on retention policies.

```bash
php artisan eventable:prune [--dry-run]
```

**Options:**
- `--dry-run` — Preview what would be deleted without actually deleting

**Examples:**
```bash
php artisan eventable:prune --dry-run
php artisan eventable:prune
```

---

## Configuration

Located at `config/eventable.php`:

| Key | Type | Default | Description |
|-----|------|---------|-------------|
| `table` | `string` | `'events'` | Database table name |
| `model` | `string` | `Event::class` | Event model class |
| `event_types` | `array` | `[]` | Event enum aliases (required) |
| `register_morph_map` | `bool` | `true` | Register in morph map |
| `morph_alias` | `string` | `'event'` | Morph map alias |
