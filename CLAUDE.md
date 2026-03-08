# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

@.claude/rules/laravel-package.md

## Commands

```bash
composer test          # Run all tests with Pest
vendor/bin/pest        # Run tests directly
vendor/bin/pest --filter=HelperMethodsTest  # Run single test file
vendor/bin/pest --filter="it processes items"  # Run test by name

composer lint          # Fix code style with Pint
vendor/bin/pint        # Run Pint directly
vendor/bin/pint --test # Check style without fixing
```

## Architecture

Eventable is a Laravel package for tracking events on Eloquent models using polymorphic relationships. Models use the `HasEvents` trait to gain event tracking capabilities.

### Core Components

- **`HasEvents` trait** (`src/Concerns/HasEvents.php`): Added to Eloquent models. Provides `addEvent()`, helper methods (`hasEvent`, `latestEvent`, `firstEvent`, `eventCount`), query scopes (`whereEventHasHappened`, `whereLatestEventIs`, etc.), and the `events()` relationship.

- **`Event` model** (`src/Models/Event.php`): Polymorphic model storing events. Has scopes for filtering: `ofType()`, `whereData()`, time-based scopes (`happenedToday`, `happenedInTheLast`, etc.). Uses configurable table name from `config('eventable.table')`.

- **`EventTypeRegistry`** (`src/EventTypeRegistry.php`): Maps enum classes to short aliases. Required so multiple enums can have overlapping values (e.g., `UserEvent::Created = 1` and `OrderEvent::Created = 1`). The alias is stored in `type_class` column.

### Database Schema

Events table has two type columns:
- `type_class`: The registered alias from config (e.g., 'user', 'order')
- `type`: The enum's backing value (int or string)

This allows refactoring enum class names without breaking existing data.

### Pruning System

Enums implement `PruneableEvent` interface with `prune(): ?PruneConfig` method. PruneConfig supports:
- `before`: Delete events older than a date
- `keep`: Keep only the N most recent events per model
- `varyOnData`: Whether to partition by data when using `keep`

The `eventable:prune` command uses CTEs (via staudenmeir/laravel-cte) for efficient row-limited deletions.

### Test Setup

Tests use Orchestra Testbench with package migrations and an environment-selectable database connection. The `TestCase` base class:
- Runs the package test migrations from `tests/database/migrations`
- Supports SQLite, MySQL, and PostgreSQL test connections
- Registers fixture enums via `EventTypeRegistry::register()`
- Clears the registry in `tearDown()` to prevent test pollution

Test fixtures are in `tests/Fixtures/`: `TestModel`, `TestEvent`, `StringEvent`, `PruneableTestEvent`, etc.
