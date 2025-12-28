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

**1. Add the trait to your models:**

```php
use AaronFrancis\Eventable\Concerns\Eventable;

class User extends Model
{
    use Eventable;
}
```

**2. Create a backed enum for your event types:**

```php
enum UserEvent: int
{
    case LoggedIn = 1;
    case EmailVerified = 2;
    case SubscriptionStarted = 3;
    case Churned = 4;
    case Purchase = 5;
    case PageViewed = 6;
}
```

**3. Register your enum in `config/eventable.php`:**

```php
'event_types' => [
    'user' => App\Enums\UserEvent::class,
],
```

This registration is required and enables:
- Multiple enums without value collisions (e.g., both `UserEvent::Created = 1` and `OrderEvent::Created = 1`)
- Refactoring class names without breaking existing data

**4. (Recommended) Enforce a morph map:**

Since Eventable uses polymorphic relationships, we highly recommend using Laravel's [Enforced Morph Map](https://laravel.com/docs/eloquent-relationships#custom-polymorphic-types) to avoid storing full class names in the database:

```php
// In AppServiceProvider::boot()
use Illuminate\Database\Eloquent\Relations\Relation;

Relation::enforceMorphMap([
    'user' => \App\Models\User::class,
    'order' => \App\Models\Order::class,
]);
```

That's it! You're ready to start tracking events.

## Usage

### Recording Events

```php
$user->addEvent(UserEvent::LoggedIn);

// With additional data
$user->addEvent(UserEvent::Purchase, [
    'order_id' => 123,
    'total' => 99.99,
]);
```

### Helper Methods

```php
// Check if an event exists
$user->hasEvent(UserEvent::EmailVerified); // true or false

// Get the most recent event
$user->latestEvent(); // or filter by type
$user->latestEvent(UserEvent::Purchase);

// Get the first event
$user->firstEvent(UserEvent::LoggedIn);

// Count events
$user->eventCount(); // all events
$user->eventCount(UserEvent::LoggedIn); // by type
```

### Querying a Model's Events

```php
// Get all events for a model
$user->events;

// Filter by type
$user->events()->ofType(UserEvent::LoggedIn)->get();

// Filter by data
$user->events()->whereData(['order_id' => 123])->get();

// Time-based queries
$user->events()->happenedAfter(now()->subDays(7))->get();
$user->events()->happenedBefore(now()->subMonth())->get();
$user->events()->happenedInTheLast(7, Unit::Day)->get();
$user->events()->happenedInTheLast(3, Unit::Hour)->get();
$user->events()->happenedToday()->get();
$user->events()->happenedThisWeek()->get();
$user->events()->happenedThisMonth()->get();

// With explicit timezone (defaults to app timezone)
$user->events()->happenedToday('America/Chicago')->get();
```

### Querying Models by Event Criteria

Combine scopes for complex queries using `whereHas`:

```php
// Users who made a purchase over $100 in the last 7 days
User::whereHas('events', function ($query) {
    $query->ofType(UserEvent::Purchase)
        ->where('data->total', '>', 100)
        ->happenedAfter(now()->subDays(7));
})->get();

// Users who logged in today
User::whereHas('events', function ($query) {
    $query->ofType(UserEvent::LoggedIn)->happenedToday();
})->get();

// Users who viewed a specific page
User::whereHas('events', function ($query) {
    $query->ofType(UserEvent::PageViewed)
        ->whereData(['page' => '/pricing']);
})->get();
```

### Querying Models by Events

```php
// Find users who have logged in
User::whereEventHasHappened(UserEvent::LoggedIn)->get();

// Find users who haven't verified their email
User::whereEventHasntHappened(UserEvent::EmailVerified)->get();

// With specific data
User::whereEventHasHappened(UserEvent::Purchase, ['total' => 99.99])->get();

// Count-based queries
User::whereEventHasHappenedTimes(UserEvent::LoggedIn, 3)->get(); // exactly 3 times
User::whereEventHasHappenedAtLeast(UserEvent::Purchase, 5)->get(); // at least 5 times

// Find by latest event
User::whereLatestEventIs(UserEvent::SubscriptionStarted)->get();
```

## Pruning Old Events

Implement `PruneableEvent` on your registered enums to configure retention policies:

```php
use AaronFrancis\Eventable\Contracts\PruneableEvent;
use AaronFrancis\Eventable\PruneConfig;

enum UserEvent: int implements PruneableEvent
{
    case LoggedIn = 1;
    case EmailVerified = 2;
    // ... other cases ...
    case PageViewed = 6;

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
