# Eventable

A Laravel package for tracking events on Eloquent models using polymorphic relationships.

```php
// Add the trait to your User model
class User extends Model
{
    use Eventable;
}

// Record events
$user->addEvent(UserEvent::LoggedIn);
$user->addEvent(UserEvent::SubscriptionStarted, ['plan' => 'pro']);

// Query by events
User::whereEventHasntHappened(UserEvent::EmailVerified)->get(); // Unverified users
User::whereLatestEventIs(UserEvent::Churned)->get();            // Churned users
User::whereEventHasHappenedAtLeast(UserEvent::Purchase, 5)->get(); // VIP customers
```

## Installation

```bash
composer require aaronfrancis/eventable
```

Publish the config and migration:

```bash
php artisan vendor:publish --tag=eventable-config
php artisan vendor:publish --tag=eventable-migrations
php artisan migrate
```

## Setup

Add the `Eventable` trait to any model you want to track events on:

```php
use AaronFrancis\Eventable\Concerns\Eventable;

class User extends Model
{
    use Eventable;
}
```

Then create a backed enum for your event types:

```php
enum UserEvent: int
{
    case LoggedIn = 1;
    case EmailVerified = 2;
    case SubscriptionStarted = 3;
}
```

That's it! You're ready to start tracking events.

## Usage

### Recording Events

```php
$user->addEvent(EventType::UserLoggedIn);

// With additional data
$user->addEvent(EventType::OrderPlaced, [
    'order_id' => 123,
    'total' => 99.99,
]);
```

### Helper Methods

```php
// Check if an event exists
$user->hasEvent(EventType::EmailVerified); // true or false

// Get the most recent event
$user->latestEvent(); // or filter by type
$user->latestEvent(EventType::OrderPlaced);

// Get the first event
$user->firstEvent(EventType::UserLoggedIn);

// Count events
$user->eventCount(); // all events
$user->eventCount(EventType::PageViewed); // by type
```

### Querying Events

```php
// Get all events for a model
$user->events;

// Filter by type
$user->events()->ofType(EventType::UserLoggedIn)->get();

// Filter by data
$user->events()->whereData(['order_id' => 123])->get();

// Time-based queries
$user->events()->happenedAfter(now()->subDays(7))->get();
$user->events()->happenedBefore(now()->subMonth())->get();
$user->events()->happenedToday()->get();
$user->events()->happenedThisWeek()->get();
$user->events()->happenedThisMonth()->get();
```

### Querying Models by Events

```php
// Find users who have logged in
User::whereEventHasHappened(EventType::UserLoggedIn)->get();

// Find users who haven't placed an order
User::whereEventHasntHappened(EventType::OrderPlaced)->get();

// With specific data
User::whereEventHasHappened(EventType::OrderPlaced, ['total' => 99.99])->get();

// Count-based queries
User::whereEventHasHappenedTimes(EventType::UserLoggedIn, 3)->get(); // exactly 3 times
User::whereEventHasHappenedAtLeast(EventType::OrderPlaced, 5)->get(); // at least 5 times

// Find by latest event
User::whereLatestEventIs(EventType::Subscribed)->get();
```

## Pruning Old Events

Implement `PruneableEvent` on your enum to configure retention policies. Eventable will automatically discover all enums implementing this interface:

```php
use AaronFrancis\Eventable\Contracts\PruneableEvent;
use AaronFrancis\Eventable\PruneConfig;

enum UserEvent: int implements PruneableEvent
{
    case LoggedIn = 1;
    case EmailVerified = 2;
    case PageViewed = 3;

    public function prune(): ?PruneConfig
    {
        return match ($this) {
            // Keep only the last 5 login events per user
            self::LoggedIn => new PruneConfig(keep: 5),

            // Delete page views older than 30 days
            self::PageViewed => new PruneConfig(before: now()->subDays(30)),

            default => null, // Don't prune
        };
    }
}
```

Run the prune command:

```bash
# Preview what will be deleted
php artisan eventable:prune --dry-run

# Actually prune
php artisan eventable:prune
```

Schedule it in your `routes/console.php` or kernel:

```php
Schedule::command('eventable:prune')->daily();
```

## Extending the Event Model

You can extend the default Event model:

```php
<?php

namespace App\Models;

use AaronFrancis\Eventable\Models\Event as BaseEvent;

class Event extends BaseEvent
{
    // Your customizations
}
```

Then update the config:

```php
'model' => App\Models\Event::class,
```

## License

MIT
