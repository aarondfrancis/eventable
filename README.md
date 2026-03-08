# Eventable

[![Latest Version on Packagist](https://img.shields.io/packagist/v/aaronfrancis/eventable.svg?style=flat-square)](https://packagist.org/packages/aaronfrancis/eventable)
[![Tests](https://github.com/aarondfrancis/eventable/actions/workflows/tests.yaml/badge.svg)](https://github.com/aarondfrancis/eventable/actions/workflows/tests.yaml)
[![Total Downloads](https://img.shields.io/packagist/dt/aaronfrancis/eventable.svg?style=flat-square)](https://packagist.org/packages/aaronfrancis/eventable)
[![PHP Version](https://img.shields.io/packagist/php-v/aaronfrancis/eventable.svg?style=flat-square)](https://packagist.org/packages/aaronfrancis/eventable)
[![License](https://img.shields.io/packagist/l/aaronfrancis/eventable.svg?style=flat-square)](https://packagist.org/packages/aaronfrancis/eventable)

A Laravel package for tracking events on Eloquent models with polymorphic relationships, backed enums, and query helpers for both individual event records and parent models.

Eventable stores both a registered enum alias in `type_class` and the enum backing value in `type`. That keeps overlapping enum values safe across multiple event enums and lets you rename enum classes without breaking historical data.

Highlights:
- Works with int-backed and string-backed enums
- Stores array payloads and exact scalar JSON values
- Lets you query models by event history
- Supports pruning by age, count, or both
- Tested in CI on SQLite, MySQL 8, PostgreSQL 17, and PostgreSQL 18

```php
// Add the trait to your User model
class User extends Model
{
    use HasEvents;
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

## Quick Start

### 1. Add the trait to your models

```php
use AaronFrancis\Eventable\Concerns\HasEvents;

class User extends Model
{
    use HasEvents;
}
```

### 2. Create a backed enum for your event types

```php
enum UserEvent: int
{
    case LoggedIn = 1;
    case EmailVerified = 2;
    case SubscriptionStarted = 3;
    case Churned = 4;
    case Purchase = 5;
    case PageViewed = 6;
    case PasswordResetRequested = 7;
}
```

The published migration uses a `string` column for `type`, so int-backed and string-backed enums both work out of the box. If you customize the migration to use an integer column, string-backed enums will no longer fit.

### 3. Register your enum in `config/eventable.php`

```php
'event_types' => [
    'user' => App\Enums\UserEvent::class,
],
```

This registration is required. It enables:
- Multiple enums without value collisions, such as `UserEvent::Created = 1` and `OrderEvent::Created = 1`
- Alias-aware queries when you pass a `BackedEnum` to `ofType()`
- Refactoring enum class names without breaking stored records

### 4. Review morph key and morph map setup

The published migration uses `morphs('eventable')`, so it follows Laravel's default morph key type. If your app uses UUIDs or ULIDs for polymorphic keys, call `Schema::morphUsingUuids()` or `Schema::morphUsingUlids()` before running the migration.

Since Eventable uses polymorphic relationships, it is also a good idea to use Laravel's [enforced morph map](https://laravel.com/docs/eloquent-relationships#custom-polymorphic-types) for your own models:

```php
// In AppServiceProvider::boot()
use Illuminate\Database\Eloquent\Relations\Relation;

Relation::enforceMorphMap([
    'user' => \App\Models\User::class,
    'order' => \App\Models\Order::class,
]);
```

Eventable separately registers its own Event model in Laravel's morph map when `eventable.register_morph_map` is enabled.

## Recording Events

```php
$user->addEvent(UserEvent::LoggedIn);

$user->addEvent(UserEvent::Purchase, [
    'order_id' => 123,
    'total' => 99.99,
]);

$user->addEvent(UserEvent::PasswordResetRequested, false);
```

The second argument can be an array or any JSON-serializable scalar value. Exact scalar matching works for values like `false`, `0`, `'0'`, and `''`.

## Helper Methods

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

`latestEvent()` and `whereLatestEventIs()` use the same definition of "latest": newest `created_at`, with `id` as the tie-breaker.

## Querying a Model's Events

```php
// Get all events for a model
$user->events;

// Filter by type
$user->events()->ofType(UserEvent::LoggedIn)->get();

// Filter by raw values when you also know the alias
$user->events()->ofTypeClass('user')->ofType([1, 5])->get();

// Filter by data
$user->events()->whereData(['order_id' => 123])->get();
$user->events()->whereData(false)->get();

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

Raw values only filter the `type` column. If multiple enums can share the same backing values, pair raw values with `ofTypeClass()` or use an enum case directly.

## Querying Models by Event Criteria

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

## Querying Models by Events

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

When pruning by `keep`, Eventable keeps the newest rows by `created_at desc, id desc`. If `varyOnData` is enabled, rows are partitioned by model and stored JSON payload before the keep limit is applied.

## Custom Event Models

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

Relationships, direct `Event` queries, and the prune command all resolve through the configured model class.

## Docs

- [Introduction](docs/index.md)
- [Installation](docs/installation.md)
- [Configuration](docs/configuration.md)
- [Usage](docs/usage.md)
- [Querying Events](docs/querying.md)
- [Pruning Events](docs/pruning.md)
- [API Reference](docs/api-reference.md)
- [Troubleshooting](docs/troubleshooting.md)

## License

MIT
