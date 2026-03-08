# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- `Prune` fluent builder for pending prune policies, with chains like `Prune::before(...)->keep(...)->dontVaryOnData()`

### Changed
- `PruneableEvent::prune()` may now return `PruneConfig`, `Prune`, or `null`
- Pruning docs now prefer the fluent `Prune` builder while still supporting direct `PruneConfig` construction

## [0.2.1] - 2026-03-08

### Fixed
- `whereLatestEventIs()` now uses the same `created_at desc, id desc` ordering as `latestEvent()`
- `whereLatestEventIs()` now resolves through the configured Event model so custom global scopes are respected
- `eventable:prune` now uses deterministic `created_at` and `id` ordering when applying `keep`
- `varyOnData` pruning now groups canonicalized JSON payloads consistently across SQLite, PostgreSQL, and MySQL

### Changed
- `PruneConfig` now requires at least one retention rule and rejects `keep` values below `1`


## [0.2.0] - 2025-12-29

### Changed
- **BREAKING:** Renamed `Eventable` trait to `HasEvents` for Laravel naming consistency
- Require Carbon 3 explicitly for `Unit` enum support
- Support PHP 8.5 with Laravel 12 and Pest 4


## [0.1.0] - 2025-12-28

### Added
- Event tracking for Eloquent models using polymorphic relationships
- `Eventable` trait for models to record and query events
- Configurable Event model with JSON data storage
- Event enum support for type-safe event definitions
- Query scopes for filtering events by type, data, and time range
- `PruneEventsCommand` for automated event cleanup
- `PruneableEvent` contract for per-event-type retention policies
- Configurable table names and model classes
- Morph map registration for cleaner polymorphic types
- Helper methods on models: `hasEvent()`, `latestEvent()`, `firstEvent()`, `eventCount()`
- Count-based scopes: `whereEventHasHappenedTimes()`, `whereEventHasHappenedAtLeast()`, `whereLatestEventIs()`
- Date convenience scopes: `happenedBetween()`, `happenedToday()`, `happenedThisWeek()`, `happenedThisMonth()`


[Unreleased]: https://github.com/aarondfrancis/eventable/compare/v0.2.1...HEAD
[0.1.0]: https://github.com/aarondfrancis/eventable/releases/tag/v0.1.0
[0.2.0]: https://github.com/aarondfrancis/eventable/releases/tag/v0.2.0
[0.2.1]: https://github.com/aarondfrancis/eventable/releases/tag/v0.2.1
