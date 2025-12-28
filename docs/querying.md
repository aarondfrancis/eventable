# Querying Events

Eventable provides powerful query scopes for filtering events.

## Helper Methods

Quick access to common event information on a model instance.

### Check if an Event Exists

```php
// Check if the model has a specific event
$user->hasEvent(EventType::EmailVerified); // true or false

// Check with data matching
$user->hasEvent(EventType::OrderPlaced, ['currency' => 'USD']);
```

### Get Latest Event

```php
// Get the most recent event of any type
$user->latestEvent();

// Get the most recent event of a specific type
$user->latestEvent(EventType::OrderPlaced);
```

### Get First Event

```php
// Get the oldest event of any type
$user->firstEvent();

// Get the oldest event of a specific type
$user->firstEvent(EventType::UserLoggedIn);
```

### Count Events

```php
// Count all events
$user->eventCount(); // 42

// Count events of a specific type
$user->eventCount(EventType::PageViewed); // 15
```

---

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

// Events in the last N units (use Carbon\Unit enum)
use Carbon\Unit;

$user->events()->happenedInTheLast(7, Unit::Day)->get();
$user->events()->happenedInTheLast(24, Unit::Hour)->get();
$user->events()->happenedInTheLast(3, Unit::Month)->get();

// Events older than N units
$user->events()->hasntHappenedInTheLast(30, Unit::Day)->get();

// Combine for a date range
$user->events()
    ->happenedAfter(now()->subDays(30))
    ->happenedBefore(now()->subDays(7))
    ->get();
```

These scopes handle timezone conversion automatically.

### Date Range Queries

Use `happenedBetween()` for date range queries:

```php
use Illuminate\Support\Carbon;

// Events in a specific range
$user->events()
    ->happenedBetween(
        Carbon::parse('2024-01-01'),
        Carbon::parse('2024-01-31')
    )
    ->get();
```

### Convenience Date Scopes

Quick filters for common time periods:

```php
// Events from today
Event::happenedToday()->get();

// Events from this week (starts Monday)
Event::happenedThisWeek()->get();

// Events from this month
Event::happenedThisMonth()->get();

// Chain with other scopes
$user->events()
    ->ofType(UserEvent::PageViewed)
    ->happenedToday()
    ->get();
```

### Timezone Support

Date scopes accept an optional timezone parameter. All times are converted to UTC for querying:

```php
// Use app timezone (default)
Event::happenedToday()->get();

// Override with specific timezone
Event::happenedToday('America/Chicago')->get();
Event::happenedThisWeek('Europe/London')->get();
Event::happenedThisMonth('Asia/Tokyo')->get();
```

This is useful when your users are in different timezones and you need "today" to mean their local day, not the server's.

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

### Find Models by Event Count

```php
// Users who have exactly 3 logins
User::whereEventHasHappenedTimes(EventType::UserLoggedIn, 3)->get();

// Users who have at least 5 orders
User::whereEventHasHappenedAtLeast(EventType::OrderPlaced, 5)->get();

// With data matching
User::whereEventHasHappenedTimes(EventType::OrderPlaced, 2, ['currency' => 'USD'])->get();
User::whereEventHasHappenedAtLeast(EventType::OrderPlaced, 3, ['currency' => 'USD'])->get();
```

### Find Models by Latest Event

```php
// Users whose most recent event is "Subscribed"
User::whereLatestEventIs(EventType::Subscribed)->get();

// Users whose most recent event is "Churned"
User::whereLatestEventIs(EventType::Churned)->get();
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

// Power users with at least 10 logins this month
User::where('plan', 'pro')
    ->whereEventHasHappenedAtLeast(EventType::UserLoggedIn, 10)
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
