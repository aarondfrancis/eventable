# Usage

## Recording Events

Use `addEvent()` on any model with the `HasEvents` trait:

```php
$user->addEvent(UserEvent::LoggedIn);
```

### With Additional Data

Pass an array as the second argument to store structured JSON data:

```php
$user->addEvent(UserEvent::OrderPlaced, [
    'order_id' => 123,
    'total' => 99.99,
    'items' => ['SKU-001', 'SKU-002'],
]);
```

### Scalar Data

You can also store scalar JSON values:

```php
$user->addEvent(UserEvent::PasswordChanged, 'admin_reset');
$user->addEvent(UserEvent::PasswordResetRequested, false);
$user->addEvent(UserEvent::RetryCountUpdated, 0);
```

Eventable can later match those scalar values exactly through `whereData()`, including `false`, `0`, `'0'`, and `''`.

### Return Value

`addEvent()` returns the created Event model instance:

```php
$event = $user->addEvent(UserEvent::LoggedIn);

echo $event->id;         // The event ID
echo $event->type_class; // The registered enum alias
echo $event->type;       // The enum value
echo $event->created_at; // When it happened
```

## Accessing Events

### The Events Relationship

The `events` relationship returns all events for a model:

```php
// Get all events
$user->events;

// As a query builder
$user->events()->count();
$user->events()->latest('created_at')->first();
```

### Eager Loading

Eager load events to avoid N+1 queries:

```php
$users = User::with('events')->get();

$users = User::with(['events' => function ($query) {
    $query->ofType(UserEvent::OrderPlaced)->latest('created_at');
}])->get();
```

## Working with Event Types

### Defining Event Types

Create a backed enum for type-safe event definitions:

```php
<?php

namespace App\Enums;

enum UserEvent: int
{
    case Registered = 1;
    case LoggedIn = 2;
    case PasswordChanged = 3;
    case PasswordResetRequested = 4;
    case RetryCountUpdated = 5;
    case EmailVerified = 6;
    case OrderPlaced = 7;
    case AddressChanged = 8;
    case Created = 9;
}
```

### Registering Event Types

Register all enums in `config/eventable.php`:

```php
'event_types' => [
    'user' => App\Enums\UserEvent::class,
    'order' => App\Enums\OrderEvent::class,
],
```

This is required. Eventable stores the alias in `type_class` and the backing value in `type`, which enables:
- Overlapping enum values without collisions
- Alias-aware `ofType(BackedEnum)` queries
- Safe enum refactors without rewriting historical rows

### Using String-Backed Enums

String enums work too:

```php
enum UserEvent: string
{
    case Registered = 'user.registered';
    case LoggedIn = 'user.logged_in';
    case OrderPlaced = 'order.placed';
}
```

The published migration already uses a string column for `type`, so string-backed enums work out of the box.

### Overlapping Enum Values Stay Isolated

Different enums can safely reuse the same backing values:

```php
enum UserEvent: int
{
    case Created = 1;
}

enum OrderEvent: int
{
    case Created = 1;
}

$user->addEvent(UserEvent::Created);
$order->addEvent(OrderEvent::Created);

$user->events()->ofType(UserEvent::Created)->count();   // 1
$order->events()->ofType(OrderEvent::Created)->count(); // 1
```

## Event Data Best Practices

### Structure Your Data

Use a consistent payload shape for a given event type:

```php
$user->addEvent(UserEvent::AddressChanged, [
    'field' => 'shipping_address',
    'old' => $oldAddress,
    'new' => $newAddress,
]);
```

Avoid swapping between unrelated payload formats for the same event type.

### Include Context

Store enough context to understand the event later without loading the full source model:

```php
$user->addEvent(UserEvent::OrderPlaced, [
    'order_id' => $order->id,
    'total' => $order->total,
    'currency' => $order->currency,
    'items_count' => $order->items->count(),
    'coupon_code' => $order->coupon?->code,
]);
```

### Avoid Large Payloads

Prefer references and key facts over entire serialized models:

```php
// Good
$user->addEvent(UserEvent::OrderPlaced, [
    'order_id' => $order->id,
    'total' => $order->total,
]);

// Avoid
$user->addEvent(UserEvent::OrderPlaced, $order->toArray());
```

## Polymorphic Relationships

Eventable stores events through Laravel's polymorphic relationships.

### Events Are Isolated by Model Type

Each model type has its own event history:

```php
$user = User::find(1);
$order = Order::find(1); // Same ID, different model

$user->addEvent(UserEvent::Created);
$order->addEvent(OrderEvent::Created);

$user->events;  // User events only
$order->events; // Order events only
```

### Querying Across Model Types

Model scopes are automatically limited to the current model type:

```php
User::whereEventHasHappened(UserEvent::Created)->get();
Order::whereEventHasHappened(OrderEvent::Created)->get();
```

### Accessing the Parent Model

From an Event, you can access the parent model:

```php
$event = Event::first();

$event->eventable;      // Returns the User, Order, etc.
$event->eventable_type; // Morph alias or class name
$event->eventable_id;   // Parent key
```

### Multiple Models, Same Enum

You can use the same enum across multiple models:

```php
enum ActivityType: int
{
    case Created = 1;
    case Updated = 2;
    case Archived = 3;
}

$user->addEvent(ActivityType::Created);
$order->addEvent(ActivityType::Created);
$product->addEvent(ActivityType::Archived);
```

## Real-World Examples

### User Activity Tracking

```php
enum UserActivity: int implements PruneableEvent
{
    case LoggedIn = 1;
    case LoggedOut = 2;
    case PasswordChanged = 3;
    case ProfileUpdated = 4;

    public function prune(): ?PruneConfig
    {
        return match ($this) {
            self::LoggedIn => new PruneConfig(keep: 10),
            self::LoggedOut => new PruneConfig(keep: 5),
            default => null,
        };
    }
}

$user->addEvent(UserActivity::LoggedIn, [
    'ip' => request()->ip(),
    'user_agent' => request()->userAgent(),
]);

User::whereEventHasHappened(UserActivity::LoggedIn)
    ->whereHas('events', fn ($q) => $q->happenedToday())
    ->get();
```

### E-Commerce Order Tracking

```php
enum OrderEvent: int
{
    case Placed = 1;
    case Paid = 2;
    case Shipped = 3;
    case Delivered = 4;
    case Refunded = 5;
}

$order->addEvent(OrderEvent::Placed, ['total' => 99.99]);
$order->addEvent(OrderEvent::Paid, ['method' => 'credit_card']);
$order->addEvent(OrderEvent::Shipped, ['tracking' => 'ABC123']);

if ($order->hasEvent(OrderEvent::Shipped) && ! $order->hasEvent(OrderEvent::Delivered)) {
    // Order is in transit
}

// "Latest" means newest created_at, then highest id.
Order::whereLatestEventIs(OrderEvent::Shipped)->get();
```

### Subscription Management

```php
User::whereLatestEventIs(SubscriptionEvent::Subscribed)->get();
User::whereLatestEventIs(SubscriptionEvent::Cancelled)->get();

User::whereEventHasHappenedAtLeast(UserActivity::LoggedIn, 10)
    ->whereHas('events', fn ($q) => $q
        ->ofType(UserActivity::LoggedIn)
        ->happenedThisMonth()
    )
    ->get();
```
