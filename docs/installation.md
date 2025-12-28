# Installation

## Requirements

- PHP 8.2 or higher
- Laravel 11.x or 12.x

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

## Run Migrations

```bash
php artisan migrate
```

## Add the Trait to Your Models

Add the `Eventable` trait to any model you want to track events on:

```php
<?php

namespace App\Models;

use AaronFrancis\Eventable\Concerns\Eventable;
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    use Eventable;
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

> **Note:** Both int-backed and string-backed enums are supported. The default migration uses a `string` column for the `type` field. If you prefer an integer column for int-backed enums, customize the migration before running it.

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

You're now ready to start tracking events!
