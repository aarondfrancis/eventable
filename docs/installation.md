# Installation

## Requirements

- PHP 8.2 or higher
- Laravel 11.x or 12.24+

CI coverage currently runs against:
- SQLite
- MySQL 8
- PostgreSQL 17
- PostgreSQL 18

## Install via Composer

```bash
composer require aaronfrancis/eventable
```

## Publish Assets

Publish the configuration file and migration:

```bash
php artisan vendor:publish --tag=eventable-config
php artisan vendor:publish --tag=eventable-migrations
```

This will create:
- `config/eventable.php` — Package configuration
- A migration file for the `events` table

The published migration:
- Uses a `string` column for `type`, so int-backed and string-backed enums work out of the box
- Uses `morphs('eventable')`, so it follows Laravel's configured morph key type

If your application uses UUIDs or ULIDs for polymorphic keys, configure that before you run the migration:

```php
use Illuminate\Support\Facades\Schema;

Schema::morphUsingUuids();
// or Schema::morphUsingUlids();
```

## Run the Migration

```bash
php artisan migrate
```

## Add the Trait to Your Models

Add the `HasEvents` trait to any model you want to track events on:

```php
<?php

namespace App\Models;

use AaronFrancis\Eventable\Concerns\HasEvents;
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    use HasEvents;
}
```

## Create and Register Your Event Enum

Create a backed enum for your event types:

```php
<?php

namespace App\Enums;

enum UserEvent: int
{
    case LoggedIn = 1;
    case EmailVerified = 2;
    case PasswordChanged = 3;
}
```

Register it in `config/eventable.php`:

```php
'event_types' => [
    'user' => App\Enums\UserEvent::class,
],
```

This registration is required before you call `addEvent()`. Eventable stores the alias in `type_class` and the enum backing value in `type`, which lets multiple enums safely reuse the same values.

If you later customize the migration to store `type` as an integer, remember that string-backed enums will no longer fit.

## (Recommended) Enforce a Morph Map

Since Eventable uses polymorphic relationships, we highly recommend using Laravel's [Enforced Morph Map](https://laravel.com/docs/eloquent-relationships#custom-polymorphic-types) to avoid storing full class names in the database:

```php
// In AppServiceProvider::boot()
use Illuminate\Database\Eloquent\Relations\Relation;

Relation::enforceMorphMap([
    'user' => \App\Models\User::class,
    'order' => \App\Models\Order::class,
]);
```

Eventable separately registers its own Event model in Laravel's morph map when `eventable.register_morph_map` is enabled.

You're now ready to start tracking events!
