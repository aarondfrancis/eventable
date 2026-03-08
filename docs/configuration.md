# Configuration

After publishing the config file with `php artisan vendor:publish --tag=eventable-config`, you can customize the package behavior in `config/eventable.php`.

## Configuration Options

### table

The database table name for storing events.

```php
'table' => 'events',
```

If you need a different table name, change this before running migrations.

### model

The Eloquent model class used for events.

```php
'model' => AaronFrancis\Eventable\Models\Event::class,
```

You can extend the default Event model to add custom functionality or use a different database connection:

```php
<?php

namespace App\Models;

use AaronFrancis\Eventable\Models\Event as BaseEvent;

class Event extends BaseEvent
{
    // Add custom methods, accessors, etc.

    public function getFormattedTypeAttribute(): string
    {
        return str_replace('_', ' ', $this->type);
    }
}
```

Then update the config:

```php
'model' => App\Models\Event::class,
```

Relationships, direct `Event` queries, and the `eventable:prune` command all resolve through this configured model class.

### event_types

**Required.** Register all your event enums with short aliases:

```php
'event_types' => [
    'user' => App\Enums\UserEvent::class,
    'order' => App\Enums\OrderEvent::class,
    'subscription' => App\Enums\SubscriptionEvent::class,
],
```

This registration serves two purposes:

1. **Prevents value collisions** — Two enums can have the same integer value (e.g., `UserEvent::Created = 1` and `OrderEvent::Created = 1`) without conflicts.

2. **Enables refactoring** — The alias is stored in the database instead of the full class name, so you can rename enum classes without breaking existing data.

The alias is stored in the `type_class` column, and the enum value is stored in the `type` column. Together, they uniquely identify each event.

Backed-enum queries such as `ofType(UserEvent::Created)` and `whereLatestEventIs(UserEvent::Created)` use both the alias and the enum value. Raw values only filter the `type` column.

**Important:** You must register an enum before using it with `addEvent()`. Attempting to use an unregistered enum throws an `InvalidArgumentException`.

### Manual Registry Access

For tests or dynamic bootstrapping, you can also interact with the registry directly:

```php
use AaronFrancis\Eventable\EventTypeRegistry;

EventTypeRegistry::register('user', App\Enums\UserEvent::class);
EventTypeRegistry::getAlias(App\Enums\UserEvent::class); // 'user'
EventTypeRegistry::getClass('user'); // App\Enums\UserEvent::class
```

Configuration is still the normal and preferred place to define aliases.

### register_morph_map

Whether to register the Event model in Laravel's morph map.

```php
'register_morph_map' => true,
```

When enabled, Eventable registers the configured Event model under the alias in `morph_alias`. This affects the Event model itself, not your application's own eventable models.

### morph_alias

The alias used in the morph map for the Event model.

```php
'morph_alias' => 'event',
```

Change this if you need a different alias (e.g., `activity` or `audit_event`).

## Full Configuration Example

```php
<?php

return [
    'table' => 'events',

    'model' => App\Models\Event::class,

    'event_types' => [
        'user' => App\Enums\UserEvent::class,
        'order' => App\Enums\OrderEvent::class,
    ],

    'register_morph_map' => true,

    'morph_alias' => 'event',
];
```

## Environment-Specific Configuration

You can use environment variables for configuration values:

```php
'table' => env('EVENTABLE_TABLE', 'events'),
```

Avoid calling `env()` from application or package code; use `config()` instead. If you do use `env()` in your app config, remember to refresh the config cache after changes.
