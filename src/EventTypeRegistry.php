<?php

namespace AaronFrancis\Eventable;

use BackedEnum;
use InvalidArgumentException;

class EventTypeRegistry
{
    /**
     * Manually registered types (primarily for testing).
     *
     * @var array<string, class-string<BackedEnum>>
     */
    protected static array $registered = [];

    /**
     * Register an event type with an alias.
     *
     * @param  class-string<BackedEnum>  $enumClass
     */
    public static function register(string $alias, string $enumClass): void
    {
        static::$registered[$alias] = $enumClass;
    }

    /**
     * Clear all registered types.
     */
    public static function clear(): void
    {
        static::$registered = [];
    }

    /**
     * Get all registered event types (config merged with manual registrations).
     *
     * @return array<string, class-string<BackedEnum>>
     */
    public static function all(): array
    {
        $configTypes = config('eventable.event_types', []);

        return array_merge($configTypes, static::$registered);
    }

    /**
     * Get the alias for an enum class.
     *
     * @param  class-string<BackedEnum>|BackedEnum  $enum
     *
     * @throws InvalidArgumentException
     */
    public static function getAlias(string|BackedEnum $enum): string
    {
        $class = is_object($enum) ? $enum::class : $enum;

        $alias = array_search($class, static::all(), true);

        if ($alias === false) {
            throw new InvalidArgumentException(
                "Event enum [{$class}] is not registered in the event_types config. ".
                "Add it to config/eventable.php: 'event_types' => ['alias' => {$class}::class]"
            );
        }

        return $alias;
    }

    /**
     * Get the enum class for an alias.
     *
     * @return class-string<BackedEnum>
     *
     * @throws InvalidArgumentException
     */
    public static function getClass(string $alias): string
    {
        $types = static::all();

        if (! isset($types[$alias])) {
            throw new InvalidArgumentException(
                "Event type alias [{$alias}] is not registered in the event_types config."
            );
        }

        return $types[$alias];
    }

    /**
     * Check if an enum class is registered.
     *
     * @param  class-string<BackedEnum>|BackedEnum  $enum
     */
    public static function isRegistered(string|BackedEnum $enum): bool
    {
        $class = is_object($enum) ? $enum::class : $enum;

        return in_array($class, static::all(), true);
    }

    /**
     * Check if an alias is registered.
     */
    public static function hasAlias(string $alias): bool
    {
        return isset(static::all()[$alias]);
    }
}
