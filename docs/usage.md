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
