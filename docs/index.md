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

// Query events
$user->events()->ofType(EventType::PurchasedItem)->get();

// Find models by event history
User::whereEventHasHappened(EventType::PurchasedItem)->get();
User::whereEventHasntHappened(EventType::VerifiedEmail)->get();
```

## Requirements

- PHP 8.2+
- Laravel 10, 11, or 12

## License

MIT
