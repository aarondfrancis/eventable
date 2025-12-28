# Configuration

After publishing the config file with `php artisan vendor:publish --tag=eventable-config`, you can customize the package behavior in `config/eventable.php`.

## Configuration Options

### table

The database table name for storing events.

```php
'table' => 'events',
```

If you need a different table name (e.g., to avoid conflicts), change this before running migrations.

### model

The Eloquent model class used for events.

```php
'model' => AaronFrancis\Eventable\Models\Event::class,
```

You can extend the default Event model to add custom functionality:

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

### register_morph_map

Whether to register the Event model in Laravel's morph map.

```php
'register_morph_map' => true,
```

When enabled, polymorphic relationships store short type names (like `event`) instead of full class names. This keeps your database cleaner and allows you to refactor class names without breaking existing data.

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

    'register_morph_map' => true,

    'morph_alias' => 'event',
];
```

## Environment-Specific Configuration

You can use environment variables for configuration values:

```php
'table' => env('EVENTABLE_TABLE', 'events'),
```

However, avoid using `env()` directly in published config files for packages — Laravel's config caching won't work properly. Use config values with defaults instead.
