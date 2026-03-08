# Eventable

A Laravel package for tracking events on Eloquent models with backed enums, polymorphic relationships, and query helpers for both event records and parent models.

## Features

- **Simple Event Tracking** — Add events to any Eloquent model with a single method call
- **Alias-Safe Enum Storage** — Store a registered alias in `type_class` and the enum value in `type`
- **Rich Data Storage** — Attach arbitrary JSON data or exact scalar JSON values to any event
- **Powerful Querying** — Filter events by type, data, or time range
- **Model-Level Queries** — Find models based on their event history, counts, or latest event
- **Automatic Pruning** — Configure retention policies per event type
- **Fully Customizable** — Extend the Event model, customize table names, and more
- **Cross-Database Coverage** — Tested in CI on SQLite, MySQL 8, PostgreSQL 17, and PostgreSQL 18

## Quick Example

```php
use AaronFrancis\Eventable\Concerns\HasEvents;

class User extends Model
{
    use HasEvents;
}

// Record events
$user->addEvent(EventType::LoggedIn);
$user->addEvent(EventType::PurchasedItem, ['item_id' => 123, 'price' => 29.99]);

// Helper methods
$user->hasEvent(EventType::EmailVerified);        // true/false
$user->latestEvent(EventType::LoggedIn);          // Most recent login
$user->eventCount(EventType::PurchasedItem);      // Number of purchases

// Query events
$user->events()->ofType(EventType::PurchasedItem)->get();
$user->events()->happenedToday()->get();
$user->events()->happenedThisMonth()->count();

// Find models by event history
User::whereEventHasHappened(EventType::PurchasedItem)->get();
User::whereEventHasntHappened(EventType::EmailVerified)->get();
User::whereEventHasHappenedAtLeast(EventType::PurchasedItem, 5)->get();  // VIP customers
User::whereLatestEventIs(EventType::Churned)->get();                     // Churned users
```

`ofType(EventType::...)` uses the enum's registered alias and value together, so different enums can safely reuse the same backing values.

## Requirements

- PHP 8.2+
- Laravel 11 or 12.24+

## Setup

1. Add the `HasEvents` trait to any model you want to track
2. Create backed enums for your event types
3. Register each enum in `config/eventable.php` under `event_types`
4. Publish the migration and run it
5. If your app uses UUIDs or ULIDs for morph keys, call `Schema::morphUsingUuids()` or `Schema::morphUsingUlids()` before migrating

See the [Installation](installation.md) guide for details.

## Next Steps

- [Configuration](configuration.md) for aliases, morph map settings, and custom models
- [Usage](usage.md) for patterns and examples
- [Querying Events](querying.md) for scopes and helper methods
- [Pruning Events](pruning.md) for retention policies

## License

MIT
