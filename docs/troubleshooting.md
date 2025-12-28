# Troubleshooting

Common issues and their solutions.

## Installation Issues

### Migration fails with "table already exists"

If you've published the migration multiple times:

```bash
# Check for duplicate migrations
ls -la database/migrations/*events*

# Remove duplicates, keeping only one
rm database/migrations/2024_01_01_000001_create_events_table.php
```

### Class not found errors

Ensure the service provider is registered. Laravel should auto-discover it, but you can manually add it:

```php
// config/app.php
'providers' => [
    // ...
    AaronFrancis\Eventable\EventableServiceProvider::class,
],
```

## Configuration Issues

### Pruning command fails with "No PruneableEvent enums found"

Create an enum that implements `PruneableEvent` in your `app/` directory. Eventable automatically discovers these enums.

### Pruning doesn't delete anything

Your enum must implement `PruneableEvent`:

```php
use AaronFrancis\Eventable\Contracts\PruneableEvent;
use AaronFrancis\Eventable\PruneConfig;

enum EventType: int implements PruneableEvent
{
    case LoggedIn = 1;

    public function prune(): ?PruneConfig
    {
        return match ($this) {
            self::LoggedIn => new PruneConfig(keep: 5),
            default => null,  // Return null to skip pruning
        };
    }
}
```

## Query Issues

### Events not found when querying

**Check the event type value:**

```php
// Make sure you're using the enum, not a string
$user->events()->ofType(EventType::LoggedIn)->get();  // Correct
$user->events()->ofType('LoggedIn')->get();           // Wrong
$user->events()->ofType(1)->get();                    // Works (raw value)
```

**Check the model scope:**

Events are scoped to the model type. A User's events won't appear in Order queries:

```php
// This only searches User events
User::whereEventHasHappened(EventType::Created)->get();
```

### whereData() not matching

The `whereData()` scope uses JSON column queries. Ensure your data structure matches exactly:

```php
// If you stored this:
$user->addEvent(EventType::Updated, ['field' => 'name', 'value' => 'John']);

// This will match:
$user->events()->whereData(['field' => 'name'])->get();

// This won't match (different structure):
$user->events()->whereData(['name' => 'John'])->get();
```

For nested data, use the same nesting:

```php
// Stored:
$user->addEvent(EventType::Order, ['payment' => ['method' => 'card']]);

// Query:
$user->events()->whereData(['payment' => ['method' => 'card']])->get();
```

### Time-based queries return unexpected results

Time scopes convert to UTC. If your app uses a different timezone:

```php
// These use the current timezone
Event::happenedToday()->get();
Event::happenedThisWeek()->get();

// For explicit control, use happenedAfter/happenedBefore
Event::happenedAfter(now()->startOfDay())->get();
```

## String Enums

### "Data too long for column 'type'"

If using string-backed enums, your migration needs a string column:

```php
// In your migration
Schema::create('events', function (Blueprint $table) {
    $table->string('type');  // Not integer!
    // ...
});
```

Or modify the published migration before running it.

## Performance Issues

### Slow queries on large tables

Add indexes for your common query patterns:

```php
// Additional indexes beyond the defaults
Schema::table('events', function (Blueprint $table) {
    // If you frequently query by data fields
    $table->index([DB::raw('(JSON_EXTRACT(data, "$.order_id"))')]);

    // If you query specific event types often
    $table->index(['type', 'eventable_type', 'created_at']);
});
```

### N+1 queries when loading events

Eager load events to avoid N+1:

```php
// Instead of this (N+1):
$users = User::all();
foreach ($users as $user) {
    $user->events;  // Triggers a query each time
}

// Do this:
$users = User::with('events')->get();

// Or with constraints:
$users = User::with(['events' => function ($query) {
    $query->ofType(EventType::LoggedIn)->latest()->limit(5);
}])->get();
```

### Too many events slowing down queries

Use pruning to manage event volume:

```php
public function prune(): ?PruneConfig
{
    return match ($this) {
        // Keep only recent events
        self::PageViewed => new PruneConfig(keep: 10),

        // Delete old events
        self::LoggedIn => new PruneConfig(before: now()->subMonths(3)),

        // Combine both strategies
        self::ApiCalled => new PruneConfig(
            before: now()->subDays(30),
            keep: 100
        ),

        default => null,
    };
}
```

Schedule pruning to run regularly:

```php
// routes/console.php or app/Console/Kernel.php
Schedule::command('eventable:prune')->daily();
```

## Custom Event Model Issues

### Custom model not being used

Ensure your config is set correctly:

```php
// config/eventable.php
return [
    'model' => App\Models\Event::class,
    // ...
];
```

And your custom model extends the base:

```php
namespace App\Models;

use AaronFrancis\Eventable\Models\Event as BaseEvent;

class Event extends BaseEvent
{
    // Your customizations
}
```

### Relationship returns wrong model type

If `$event->eventable` returns the wrong type, check your morph map:

```php
// config/eventable.php
return [
    'register_morph_map' => true,
    'morph_alias' => 'event',
    // ...
];
```

Or register your own morph map in a service provider:

```php
use Illuminate\Database\Eloquent\Relations\Relation;

Relation::morphMap([
    'user' => App\Models\User::class,
    'order' => App\Models\Order::class,
]);
```

## Still Having Issues?

1. **Check the tests** — The package has comprehensive tests showing expected behavior
2. **Enable query logging** — Use `DB::enableQueryLog()` to see what queries are running
3. **Check your Laravel version** — Eventable requires Laravel 10, 11, or 12
4. **Open an issue** — [GitHub Issues](https://github.com/aarondfrancis/eventable/issues)
