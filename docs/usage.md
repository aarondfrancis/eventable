# Usage

## Recording Events

Use the `addEvent()` method to record an event on any model with the `Eventable` trait:

```php
$user->addEvent(EventType::LoggedIn);
```

### With Additional Data

Pass an array or any JSON-serializable value as the second argument:

```php
$user->addEvent(EventType::OrderPlaced, [
    'order_id' => 123,
    'total' => 99.99,
    'items' => ['SKU-001', 'SKU-002'],
]);
```

The data is stored as JSON and can be queried later.

### Scalar Data

You can also store simple scalar values:

```php
$user->addEvent(EventType::PasswordChanged, 'admin_reset');
```

### Return Value

`addEvent()` returns the created Event model instance:

```php
$event = $user->addEvent(EventType::LoggedIn);

echo $event->id;         // The event ID
echo $event->type;       // The enum value (e.g., 1)
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
$user->events()->latest()->first();
```

### Eager Loading

Eager load events to avoid N+1 queries:

```php
$users = User::with('events')->get();

// With constraints
$users = User::with(['events' => function ($query) {
    $query->ofType(EventType::OrderPlaced)->latest();
}])->get();
```

## Working with Event Types

### Defining Event Types

Create a backed enum for type-safe event definitions:

```php
<?php

namespace App\Enums;

enum EventType: int
{
    case UserRegistered = 1;
    case UserLoggedIn = 2;
    case PasswordChanged = 3;
    case EmailVerified = 4;
    case OrderPlaced = 5;
    case OrderShipped = 6;
    case OrderDelivered = 7;
}
```

### Using String-Backed Enums

String enums work too:

```php
enum EventType: string
{
    case UserRegistered = 'user.registered';
    case UserLoggedIn = 'user.logged_in';
    case OrderPlaced = 'order.placed';
}
```

Note: If you use string enums, make sure your `events` table has a `VARCHAR` column for `type` instead of an integer.

## Event Data Best Practices

### Structure Your Data

Use consistent data structures for each event type:

```php
// Good: Consistent structure
$user->addEvent(EventType::AddressChanged, [
    'field' => 'shipping_address',
    'old' => $oldAddress,
    'new' => $newAddress,
]);

// Avoid: Inconsistent structures make querying harder
$user->addEvent(EventType::AddressChanged, $newAddress);
```

### Include Context

Store enough context to understand the event later:

```php
$user->addEvent(EventType::OrderPlaced, [
    'order_id' => $order->id,
    'total' => $order->total,
    'currency' => $order->currency,
    'items_count' => $order->items->count(),
    'coupon_code' => $order->coupon?->code,
]);
```

### Avoid Large Payloads

Don't store entire models — store references and key data:

```php
// Good: Store references
$user->addEvent(EventType::OrderPlaced, [
    'order_id' => $order->id,
    'total' => $order->total,
]);

// Avoid: Don't store entire models
$user->addEvent(EventType::OrderPlaced, $order->toArray());
```

## Polymorphic Relationships

Eventable uses Laravel's polymorphic relationships to store events. This means:

### Events Are Isolated by Model Type

Each model type has its own separate event history:

```php
$user = User::find(1);
$order = Order::find(1);  // Same ID, different model

$user->addEvent(EventType::Created);
$order->addEvent(EventType::Created);

// Each model only sees its own events
$user->events;  // 1 event (User's)
$order->events; // 1 event (Order's)
```

### Querying Across Model Types

Query scopes are automatically scoped to the model type:

```php
// Only finds Users with this event, not Orders
User::whereEventHasHappened(EventType::Created)->get();

// Only finds Orders with this event, not Users
Order::whereEventHasHappened(EventType::Created)->get();
```

### Accessing the Parent Model

From an Event, you can access the parent model:

```php
$event = Event::first();

$event->eventable;      // Returns the User, Order, etc.
$event->eventable_type; // "App\Models\User"
$event->eventable_id;   // 123
```

### Multiple Models, Same Enum

You can use the same event enum across different models:

```php
enum ActivityType: int
{
    case Created = 1;
    case Updated = 2;
    case Archived = 3;
}

// Works on any model with the Eventable trait
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

// Record login with context
$user->addEvent(UserActivity::LoggedIn, [
    'ip' => request()->ip(),
    'user_agent' => request()->userAgent(),
    'location' => $geoip->getLocation(),
]);

// Find users who logged in today
User::whereEventHasHappened(UserActivity::LoggedIn)
    ->whereHas('events', fn($q) => $q->happenedToday())
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

// Track order lifecycle
$order->addEvent(OrderEvent::Placed, ['total' => 99.99]);
$order->addEvent(OrderEvent::Paid, ['method' => 'credit_card']);
$order->addEvent(OrderEvent::Shipped, ['tracking' => 'ABC123']);

// Check order status
if ($order->hasEvent(OrderEvent::Shipped) && !$order->hasEvent(OrderEvent::Delivered)) {
    // Order is in transit
}

// Find orders by their latest status
Order::whereLatestEventIs(OrderEvent::Shipped)->get();  // In transit
Order::whereLatestEventIs(OrderEvent::Refunded)->get(); // Refunded
```

### Subscription Management

```php
// Find active subscribers (latest event is "Subscribed")
User::whereLatestEventIs(SubscriptionEvent::Subscribed)->get();

// Find churned users
User::whereLatestEventIs(SubscriptionEvent::Cancelled)->get();

// Find power users with 10+ logins this month
User::whereEventHasHappenedAtLeast(UserActivity::LoggedIn, 10)
    ->whereHas('events', fn($q) => $q
        ->ofType(UserActivity::LoggedIn)
        ->happenedThisMonth()
    )
    ->get();
```
