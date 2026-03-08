# Troubleshooting

Common issues and their solutions.

## Installation Issues

### Migration fails with "table already exists"

This usually means the migration was published more than once. Check your `database/migrations` directory and keep a single `create_events_table` migration.

### Class not found errors

Laravel should auto-discover the service provider. If auto-discovery is disabled, register it manually:

```php
// config/app.php
'providers' => [
    AaronFrancis\Eventable\EventableServiceProvider::class,
],
```

## Configuration Issues

### "Event enum [X] is not registered"

Register every enum in `config/eventable.php`:

```php
'event_types' => [
    'user' => App\Enums\UserEvent::class,
    'order' => App\Enums\OrderEvent::class,
],
```

The alias is stored in `type_class`, so registration is required before `addEvent()` can resolve the enum.

### Pruning command fails with "No PruneableEvent enums found"

Make sure the enum is both:
- registered in `config/eventable.php`
- implementing `PruneableEvent`

### Pruning doesn't delete anything

Your enum case must return a non-null `PruneConfig` with at least one retention rule:

```php
enum UserEvent: int implements PruneableEvent
{
    case LoggedIn = 1;

    public function prune(): ?PruneConfig
    {
        return match ($this) {
            self::LoggedIn => new PruneConfig(keep: 5),
            default => null,
        };
    }
}
```

## Query Issues

### Events not found when querying

Use enum cases whenever possible:

```php
$user->events()->ofType(UserEvent::LoggedIn)->get();
```

Raw values only filter the `type` column:

```php
$user->events()->ofTypeClass('user')->ofType(1)->get();
```

If multiple enums share the same backing values, using raw values without `ofTypeClass()` can match the wrong rows.

### Event query hits the wrong model type

Model scopes are always limited to the current model type:

```php
User::whereEventHasHappened(UserEvent::Created)->get();
Order::whereEventHasHappened(OrderEvent::Created)->get();
```

If your results look wrong, verify you are querying the expected parent model class.

### whereData() not matching

Array payloads must match the stored JSON structure:

```php
$user->addEvent(UserEvent::Updated, ['field' => 'name', 'value' => 'John']);

$user->events()->whereData(['field' => 'name'])->get(); // Matches
$user->events()->whereData(['name' => 'John'])->get();  // Does not match
```

Nested data must keep the same nesting:

```php
$user->addEvent(UserEvent::OrderPlaced, ['payment' => ['method' => 'card']]);

$user->events()->whereData(['payment' => ['method' => 'card']])->get();
```

Scalar values are matched exactly:

```php
$user->addEvent(UserEvent::RetryCountUpdated, 0);

$user->events()->whereData(0)->get();   // Matches
$user->events()->whereData('0')->get(); // Different JSON value
```

### Time-based queries return unexpected results

Time scopes convert timestamps to UTC before querying and use your app timezone by default:

```php
Event::happenedToday()->get();
Event::happenedThisWeek()->get();

Event::happenedToday('America/New_York')->get();
Event::happenedThisWeek('Europe/Paris')->get();
```

### Latest-event queries seem off

`latestEvent()` and `whereLatestEventIs()` both order by:
- newest `created_at`
- highest `id` as the tie-breaker

If you backfill historical events, make sure `created_at` reflects the event time you want to query against.
Those queries also resolve through your configured Event model, so custom global scopes still affect what counts as the latest visible event.

## String Enums and Morph Keys

### "Data too long for column 'type'"

The published migration already uses a string column for `type`. This error usually means the migration was customized to use an integer column.

If you want string-backed enums, `type` must remain a string column.

### UUID/ULID models fail to relate events

The migration uses Laravel's default morph key type. Configure UUIDs or ULIDs before you run the migration:

```php
use Illuminate\Support\Facades\Schema;

Schema::morphUsingUuids();
// or Schema::morphUsingUlids();
```

## Performance Issues

### Slow queries on large tables

Start with the built-in indexes from the published migration, then add database-specific indexes for your common patterns:

```php
Schema::table('events', function (Blueprint $table) {
    $table->index(['type', 'eventable_type', 'created_at']);
});
```

JSON indexing is database-specific:
- MySQL usually needs generated columns or functional indexes
- PostgreSQL often benefits from `jsonb` expression or GIN indexes
- SQLite JSON indexing options are more limited

### N+1 queries when loading events

Eager load the relationship:

```php
$users = User::with('events')->get();

$users = User::with(['events' => function ($query) {
    $query->ofType(UserEvent::LoggedIn)->latest('created_at')->limit(5);
}])->get();
```

### Too many events slowing down queries

Use pruning to limit table growth:

```php
public function prune(): ?PruneConfig
{
    return match ($this) {
        self::PageViewed => new PruneConfig(keep: 10),
        self::LoggedIn => new PruneConfig(before: now()->subMonths(3)),
        self::ApiCalled => new PruneConfig(
            before: now()->subDays(30),
            keep: 100,
        ),
        default => null,
    };
}
```

Schedule pruning to run regularly:

```php
Schedule::command('eventable:prune')->daily();
```

If you use `varyOnData`, Eventable groups canonicalized JSON objects together across supported drivers. Object key ordering does not matter, but array ordering still does.

## Custom Event Model Issues

### Custom model not being used

Set the custom class in `config/eventable.php` and extend the base model:

```php
return [
    'model' => App\Models\Event::class,
];
```

```php
namespace App\Models;

use AaronFrancis\Eventable\Models\Event as BaseEvent;

class Event extends BaseEvent
{
    // Your customizations
}
```

The configured model is also used by `eventable:prune`.

### Relationship returns wrong model type

Check your morph maps:

```php
return [
    'register_morph_map' => true,
    'morph_alias' => 'event',
];
```

If your own eventable models use aliases, make sure those aliases are registered in your application too.

## Still Having Issues?

1. Check the tests for working examples.
2. Enable query logging with `DB::enableQueryLog()`.
3. Confirm your Laravel version is 11.x or 12.24+.
4. Open an issue at [GitHub Issues](https://github.com/aarondfrancis/eventable/issues).
