# Eventable

A Laravel package for tracking events on Eloquent models using polymorphic relationships.

## Features

- **Simple Event Tracking** — Add events to any Eloquent model with a single method call
- **Type-Safe Events** — Use PHP enums to define your event types
- **Rich Data Storage** — Attach arbitrary JSON data to any event
- **Powerful Querying** — Filter events by type, data, or time range
- **Model-Level Queries** — Find models based on their event history
- **Automatic Pruning** — Configure retention policies per event type
- **Fully Customizable** — Extend the Event model, customize table names, and more

## Quick Example

```php
use AaronFrancis\Eventable\Concerns\Eventable;

class User extends Model
{
    use Eventable;
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

## Requirements

- PHP 8.2+
- Laravel 10, 11, or 12

## Setup

1. Add the `Eventable` trait to your models
2. Create backed enums for your event types
3. Register enums in `config/eventable.php` under `event_types`

See the [Installation](installation.md) guide for details.

## License

MIT
