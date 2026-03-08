# Pruning Events

Eventable can automatically delete old events based on per-event retention rules.

## Setting Up Pruning

Implement `PruneableEvent` on a registered event enum:

```php
<?php

namespace App\Enums;

use AaronFrancis\Eventable\Contracts\PruneableEvent;
use AaronFrancis\Eventable\PruneConfig;

enum UserEvent: int implements PruneableEvent
{
    case LoggedIn = 1;
    case PasswordChanged = 2;
    case OrderPlaced = 3;
    case PageViewed = 4;

    public function prune(): ?PruneConfig
    {
        return match ($this) {
            self::LoggedIn => new PruneConfig(keep: 5),
            self::PageViewed => new PruneConfig(before: now()->subDays(30)),
            self::PasswordChanged, self::OrderPlaced => null,
        };
    }
}
```

Return `null` to skip pruning for a case.

## PruneConfig Options

`PruneConfig` accepts three options.

### before

Delete events older than a given date:

```php
new PruneConfig(before: now()->subDays(30))
new PruneConfig(before: now()->subYear())
```

### keep

Keep the newest N events per model:

```php
new PruneConfig(keep: 5)
new PruneConfig(keep: 100)
```

When `keep` is used, Eventable defines "newest" as `created_at desc`, then `id desc`.

### varyOnData

When `keep` is used, `varyOnData` controls whether different payloads are counted separately. It defaults to `true`.

```php
new PruneConfig(keep: 3, varyOnData: true)
new PruneConfig(keep: 3, varyOnData: false)
```

Example:
- `PageViewed` with `{page: 'home'}` recorded 5 times
- `PageViewed` with `{page: 'about'}` recorded 3 times

With `varyOnData: true` and `keep: 3`:
- Keep the 3 newest `home` events
- Keep the 3 `about` events

With `varyOnData: false` and `keep: 3`:
- Keep only the 3 newest `PageViewed` events total

Internally, Eventable partitions by model and stored JSON payload before applying the keep limit.

### Combining Options

You can combine age-based and count-based retention:

```php
new PruneConfig(
    before: now()->subDays(90),
    keep: 10,
)
```

## Running the Prune Command

### Preview With `--dry-run`

```bash
php artisan eventable:prune --dry-run
```

Example output:

```text
Event LoggedIn: 1,234 records to prune.
Event PageViewed: 45,678 records to prune.
Total: 46,912 records would be pruned.
```

### Execute the Prune

```bash
php artisan eventable:prune
```

Example output:

```text
Event LoggedIn: 1,234 records pruned.
Event PageViewed: 45,678 records pruned.
Total: 46,912 records pruned.
```

The command resolves through your configured Event model and uses that model's connection.

## Scheduling Pruning

Add the command to your scheduler:

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('eventable:prune')->daily();
```

Typical schedules:
- Daily for most applications
- Hourly for high-volume event streams
- Weekly if retention windows are long and churn is low

## Retention Strategies

### Keep Forever

```php
self::OrderPlaced => null,
self::PasswordChanged => null,
```

### Time-Based Retention

```php
self::PageViewed => new PruneConfig(before: now()->subDays(7)),
self::LoggedIn => new PruneConfig(before: now()->subDays(90)),
self::OrderShipped => new PruneConfig(before: now()->subYear()),
```

### Count-Based Retention

```php
self::LoggedIn => new PruneConfig(keep: 10),
self::PageViewed => new PruneConfig(keep: 50),
```

### Hybrid Retention

```php
self::LoggedIn => new PruneConfig(
    before: now()->subDays(30),
    keep: 20,
),
```
