# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

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

[Unreleased]: https://github.com/aarondfrancis/eventable/compare/HEAD
