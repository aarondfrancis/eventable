# Installation

## Requirements

- PHP 8.2 or higher
- Laravel 10.x, 11.x, or 12.x

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

## Create Your Event Enum

Create a backed enum to define your event types:

```php
<?php

namespace App\Enums;

enum EventType: int
{
    case UserRegistered = 1;
    case UserLoggedIn = 2;
    case PasswordChanged = 3;
    case OrderPlaced = 4;
    case OrderShipped = 5;
}
```

You can use either `int` or `string` backed enums.

## Configure the Package

Update `config/eventable.php` to specify your enum:

```php
return [
    'event_enum' => App\Enums\EventType::class,
    // ...
];
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

You're now ready to start tracking events!
