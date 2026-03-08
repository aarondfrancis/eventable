# Querying Events

Eventable gives you query helpers on both the parent model and the Event model.

## Helper Methods

Quick access to common event information on a model instance:

```php
$user->hasEvent(UserEvent::EmailVerified);
$user->latestEvent();
$user->latestEvent(UserEvent::OrderPlaced);
$user->firstEvent(UserEvent::Registered);
$user->eventCount();
$user->eventCount(UserEvent::PageViewed);
```

`latestEvent()` and `whereLatestEventIs()` use the same ordering: newest `created_at`, then highest `id`. They also resolve through the configured Event model, so custom global scopes and soft-delete constraints still apply.

## Querying a Model's Events

### Filter by Type

Use `ofType()` to filter a model's events:

```php
// Single enum case
$user->events()->ofType(UserEvent::OrderPlaced)->get();

// Multiple cases from the same enum
$user->events()->ofType([
    UserEvent::OrderPlaced,
    UserEvent::OrderShipped,
])->get();

// Raw values when you also know the alias
$user->events()->ofTypeClass('user')->ofType([5, 6])->get();
```

Rules for `ofType()`:
- Passing a `BackedEnum` filters by both `type_class` and `type`
- Arrays of enum cases must all come from the same enum class
- Raw values only filter `type`
- If multiple enums reuse the same backing values, pair raw values with `ofTypeClass()`

### Filter by Data

Use `whereData()` to filter by stored JSON:

```php
// Match specific key-value pairs
$user->events()->whereData(['order_id' => 123])->get();

// Match nested data
$user->events()->whereData([
    'payment' => ['method' => 'credit_card'],
])->get();

// Match scalar values exactly
$user->events()->whereData('admin_reset')->get();
$user->events()->whereData(false)->get();
$user->events()->whereData(0)->get();
$user->events()->whereData('0')->get();
```

Arrays are matched as JSON fragments. Scalars are matched as exact JSON values.

### Filter by Time

Use the built-in time scopes:

```php
use Carbon\Unit;

$user->events()->happenedAfter(now()->subDays(7))->get();
$user->events()->happenedBefore(now()->subMonth())->get();
$user->events()->happenedInTheLast(7, Unit::Day)->get();
$user->events()->happenedInTheLast(24, Unit::Hour)->get();
$user->events()->hasntHappenedInTheLast(30, Unit::Day)->get();
```

These scopes convert timestamps to UTC before querying.

### Date Ranges

Use `happenedBetween()` for an explicit range:

```php
use Illuminate\Support\Carbon;

$user->events()
    ->happenedBetween(
        Carbon::parse('2024-01-01'),
        Carbon::parse('2024-01-31')
    )
    ->get();
```

`happenedBetween()` is exclusive on both ends.

### Convenience Date Scopes

```php
Event::happenedToday()->get();
Event::happenedThisWeek()->get();
Event::happenedThisMonth()->get();

$user->events()
    ->ofType(UserEvent::PageViewed)
    ->happenedToday()
    ->get();
```

You can also pass a timezone:

```php
Event::happenedToday('America/Chicago')->get();
Event::happenedThisWeek('Europe/London')->get();
Event::happenedThisMonth('Asia/Tokyo')->get();
```

### Chaining Scopes

Combine scopes for more targeted queries:

```php
$recentLargeOrders = $user->events()
    ->ofType(UserEvent::OrderPlaced)
    ->whereData(['currency' => 'USD'])
    ->happenedAfter(now()->subDays(30))
    ->where('data->total', '>', 100)
    ->latest('created_at')
    ->get();
```

## Querying Models by Events

Find parent models from their event history.

### Models With or Without an Event

```php
User::whereEventHasHappened(UserEvent::LoggedIn)->get();
User::whereEventHasntHappened(UserEvent::EmailVerified)->get();
```

### Match Event Data

Model scopes accept either array fragments or a closure for additional Laravel query constraints:

```php
User::whereEventHasHappened(UserEvent::OrderPlaced, [
    'currency' => 'USD',
])->get();

User::whereEventHasntHappened(UserEvent::OrderPlaced, [
    'coupon_code' => 'SUMMER20',
])->get();

User::whereEventHasHappened(UserEvent::OrderPlaced, function ($events) {
    $events->where('data->total', '>', 99);
})->get();
```

When you pass a closure, `ofType($event)` has already been applied to the event query. Use the closure only for extra constraints.

### Event Counts

```php
User::whereEventHasHappenedTimes(UserEvent::LoggedIn, 3)->get();
User::whereEventHasHappenedAtLeast(UserEvent::OrderPlaced, 5)->get();
```

### Latest Event

```php
User::whereLatestEventIs(UserEvent::Subscribed)->get();
User::whereLatestEventIs(UserEvent::Churned)->get();
```

That latest-event check uses the same `created_at desc, id desc` ordering as `latestEvent()`, and it honors the configured Event model's scopes.

### Combine With Normal Eloquent Conditions

```php
User::where('status', 'active')
    ->where('plan', 'premium')
    ->whereEventHasntHappened(UserEvent::EmailVerified)
    ->get();

User::where('created_at', '>', now()->subDays(7))
    ->whereEventHasHappened(UserEvent::OrderPlaced)
    ->get();
```

## Accessing the Parent Model

From an Event, access the related model through `eventable`:

```php
$event = Event::find(1);

$parent = $event->eventable;
echo $event->eventable_type; // Morph alias or class name
echo $event->eventable_id;
```

## Raw Queries on Events

You can query the Event model directly:

```php
use AaronFrancis\Eventable\Models\Event;

Event::ofType(UserEvent::OrderPlaced)
    ->happenedAfter(now()->subDays(7))
    ->count();

Event::selectRaw('type_class, type, count(*) as count')
    ->groupBy('type_class', 'type')
    ->get();
```
