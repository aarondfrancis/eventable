# Pruning Events

Over time, your events table can grow large. Eventable provides a pruning system to automatically clean up old events based on configurable retention policies.

## Setting Up Pruning

Implement `PruneableEvent` on your event enum. Eventable will automatically discover all enums in your `app/` directory that implement this interface:

```php
<?php

namespace App\Enums;

use AaronFrancis\Eventable\Contracts\PruneableEvent;
use AaronFrancis\Eventable\PruneConfig;

enum EventType: int implements PruneableEvent
{
    case UserLoggedIn = 1;
    case PasswordChanged = 2;
    case OrderPlaced = 3;
    case PageViewed = 4;

    public function prune(): ?PruneConfig
    {
        return match ($this) {
            // Keep only the last 5 login events per user
            self::UserLoggedIn => new PruneConfig(keep: 5),

            // Delete page views older than 30 days
            self::PageViewed => new PruneConfig(before: now()->subDays(30)),

            // Don't prune these events
            self::PasswordChanged, self::OrderPlaced => null,
        };
    }
}
```

## PruneConfig Options

The `PruneConfig` class accepts three parameters:

### before

Delete events older than this date:

```php
// Delete events older than 30 days
new PruneConfig(before: now()->subDays(30))

// Delete events older than 1 year
new PruneConfig(before: now()->subYear())
```

### keep

Keep the N most recent events per model:

```php
// Keep the last 5 events of this type per model
new PruneConfig(keep: 5)

// Keep the last 100 events
new PruneConfig(keep: 100)
```

### varyOnData

When using `keep`, whether to count events with different data separately. Defaults to `true`.

```php
// Keep last 3 events, treating different data as separate groups
new PruneConfig(keep: 3, varyOnData: true)

// Keep last 3 events total, regardless of data
new PruneConfig(keep: 3, varyOnData: false)
```

**Example with varyOnData:**

If a user has these events:
- `PageViewed` with `{page: 'home'}` (5 events)
- `PageViewed` with `{page: 'about'}` (3 events)

With `varyOnData: true` and `keep: 3`:
- Keeps 3 most recent `{page: 'home'}` events
- Keeps all 3 `{page: 'about'}` events

With `varyOnData: false` and `keep: 3`:
- Keeps only the 3 most recent events total

### Combining Options

You can combine `before` and `keep`:

```php
// Keep the last 10 events, but also delete anything older than 90 days
new PruneConfig(
    before: now()->subDays(90),
    keep: 10
)
```

## Running the Prune Command

### Preview (Dry Run)

See what would be deleted without actually deleting:

```bash
php artisan eventable:prune --dry-run
```

Output:
```
Event UserLoggedIn: 1,234 records to prune.
Event PageViewed: 45,678 records to prune.
Total: 46,912 records would be pruned.
```

### Execute

Actually delete the events:

```bash
php artisan eventable:prune
```

Output:
```
Event UserLoggedIn: 1,234 records pruned.
Event PageViewed: 45,678 records pruned.
Total: 46,912 records pruned.
```

## Scheduling Pruning

Add the prune command to your scheduler in `routes/console.php`:

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('eventable:prune')->daily();
```

Or in Laravel 10 and earlier, use the `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    $schedule->command('eventable:prune')->daily();
}
```

### Recommended Schedule

- **Daily** — Good for most applications
- **Hourly** — If you have high event volume
- **Weekly** — If events are rarely pruned

## Retention Strategies

### Keep Forever

Return `null` to never prune an event type:

```php
self::OrderPlaced => null,
self::PasswordChanged => null,
```

### Time-Based Retention

Delete events older than a specific age:

```php
// Low-value events: 7 days
self::PageViewed => new PruneConfig(before: now()->subDays(7)),

// Medium-value events: 90 days
self::UserLoggedIn => new PruneConfig(before: now()->subDays(90)),

// High-value events: 1 year
self::OrderShipped => new PruneConfig(before: now()->subYear()),
```

### Count-Based Retention

Keep only the N most recent events:

```php
// Keep last 10 login events per user
self::UserLoggedIn => new PruneConfig(keep: 10),

// Keep last 50 page views per user
self::PageViewed => new PruneConfig(keep: 50),
```

### Hybrid Retention

Combine time and count limits:

```php
// Keep last 20 events, but nothing older than 30 days
self::UserLoggedIn => new PruneConfig(
    before: now()->subDays(30),
    keep: 20
),
```
