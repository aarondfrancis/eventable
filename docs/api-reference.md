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

### Model Scopes

#### scopeWhereEventHasHappened()

Find models that have a specific event.

```php
public function scopeWhereEventHasHappened($query, BackedEnum $event, array $data = []): void
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
public function scopeWhereEventHasntHappened($query, BackedEnum $event, array $data = []): void
```

**Parameters:**
- `$event` — The event type to check for absence
- `$data` — Optional data constraints

**Example:**
```php
User::whereEventHasntHappened(EventType::EmailVerified)->get();
```

---

## Event Model

The `AaronFrancis\Eventable\Models\Event` model stores individual events.

### Properties

| Property | Type | Description |
|----------|------|-------------|
| `id` | `int` | Primary key |
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
public function scopeOfType($query, $type): void
```

**Parameters:**
- `$type` — An enum case, enum value, or array of values

**Examples:**
```php
Event::ofType(EventType::OrderPlaced)->get();
Event::ofType(1)->get();
Event::ofType([1, 2, 3])->get();
```

#### scopeWhereData()

Filter events by data content.

```php
public function scopeWhereData($query, $data = null): void
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
public function scopeHappenedAfter($query, Carbon $time): void
```

**Example:**
```php
Event::happenedAfter(now()->subDays(7))->get();
```

#### scopeHappenedBefore()

Filter events before a specific time.

```php
public function scopeHappenedBefore($query, Carbon $time): void
```

**Example:**
```php
Event::happenedBefore(now()->subMonth())->get();
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
| `event_enum` | `string\|null` | `null` | Your event enum class |
| `register_morph_map` | `bool` | `true` | Register in morph map |
| `morph_alias` | `string` | `'event'` | Morph map alias |
