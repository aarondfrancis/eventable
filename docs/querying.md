# Querying Events

Eventable provides powerful query scopes for filtering events.

## Querying a Model's Events

### Filter by Type

Use `ofType()` to filter by event type:

```php
// Single type
$user->events()->ofType(EventType::OrderPlaced)->get();

// Multiple types (pass an array of values)
$user->events()->ofType([
    EventType::OrderPlaced->value,
    EventType::OrderShipped->value,
])->get();
```

### Filter by Data

Use `whereData()` to filter by event data:

```php
// Match specific key-value pairs
$user->events()->whereData(['order_id' => 123])->get();

// Match nested data
$user->events()->whereData([
    'payment' => ['method' => 'credit_card'],
])->get();

// Match scalar data
$user->events()->whereData('admin_reset')->get();
```

The `whereData()` scope uses JSON column queries, so you can match any part of the stored data.

### Filter by Time

Use time-based scopes to filter by when events occurred:

```php
// Events after a specific time
$user->events()->happenedAfter(now()->subDays(7))->get();

// Events before a specific time
$user->events()->happenedBefore(now()->subMonth())->get();

// Combine for a date range
$user->events()
    ->happenedAfter(now()->subDays(30))
    ->happenedBefore(now()->subDays(7))
    ->get();
```

These scopes handle timezone conversion automatically.

### Chaining Scopes

Combine multiple scopes for complex queries:

```php
$recentLargeOrders = $user->events()
    ->ofType(EventType::OrderPlaced)
    ->whereData(['currency' => 'USD'])
    ->happenedAfter(now()->subDays(30))
    ->where('data->total', '>', 100)
    ->latest()
    ->get();
```

## Querying Models by Events

Find models based on their event history using model-level scopes.

### Find Models With an Event

```php
// Users who have logged in
User::whereEventHasHappened(EventType::UserLoggedIn)->get();

// Users who have placed orders
User::whereEventHasHappened(EventType::OrderPlaced)->get();
```

### Find Models Without an Event

```php
// Users who haven't verified their email
User::whereEventHasntHappened(EventType::EmailVerified)->get();

// Users who haven't logged in
User::whereEventHasntHappened(EventType::UserLoggedIn)->get();
```

### With Data Matching

Pass a second argument to match specific data:

```php
// Users who placed orders over $100
User::whereEventHasHappened(EventType::OrderPlaced, [
    'total' => 100,
])->get();

// Users who haven't used a specific coupon
User::whereEventHasntHappened(EventType::OrderPlaced, [
    'coupon_code' => 'SUMMER20',
])->get();
```

### Combining with Other Query Conditions

These scopes work with any other Eloquent query methods:

```php
// Active premium users who haven't verified email
User::where('status', 'active')
    ->where('plan', 'premium')
    ->whereEventHasntHappened(EventType::EmailVerified)
    ->get();

// Recently registered users who have made a purchase
User::where('created_at', '>', now()->subDays(7))
    ->whereEventHasHappened(EventType::OrderPlaced)
    ->get();
```

## Accessing the Parent Model

From an Event, access the parent model via the `eventable` relationship:

```php
$event = Event::find(1);

// Get the parent model
$user = $event->eventable;

// Works with any eventable model
echo $event->eventable_type; // "App\Models\User"
echo $event->eventable_id;   // 123
```

## Raw Queries on Events

You can query the Event model directly:

```php
use AaronFrancis\Eventable\Models\Event;

// All order events across all users
Event::ofType(EventType::OrderPlaced)
    ->happenedAfter(now()->subDays(7))
    ->count();

// Group by type
Event::selectRaw('type, count(*) as count')
    ->groupBy('type')
    ->get();
```
