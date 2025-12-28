<?php

namespace AaronFrancis\Eventable;

use AaronFrancis\Eventable\Contracts\PruneableEvent;
use Illuminate\Support\Facades\File;
use Symfony\Component\Finder\Finder;

class PruneableEventDiscovery
{
    /**
     * Manually registered enums (primarily for testing).
     *
     * @var array<class-string<PruneableEvent>>
     */
    protected static array $registered = [];

    /**
     * Register an enum class for pruning.
     *
     * @param  class-string<PruneableEvent>  $enumClass
     */
    public static function register(string $enumClass): void
    {
        if (! in_array($enumClass, static::$registered)) {
            static::$registered[] = $enumClass;
        }
    }

    /**
     * Clear all registered enums.
     */
    public static function clear(): void
    {
        static::$registered = [];
    }

    /**
     * Discover all enums implementing PruneableEvent.
     *
     * @return array<class-string<PruneableEvent>>
     */
    public static function discover(): array
    {
        // Return registered enums if any (for testing)
        if (! empty(static::$registered)) {
            return static::$registered;
        }

        $enums = [];

        // Scan app directory for enums implementing PruneableEvent
        $appPath = app_path();

        if (! is_dir($appPath)) {
            return [];
        }

        $finder = Finder::create()
            ->files()
            ->name('*.php')
            ->in($appPath);

        foreach ($finder as $file) {
            $class = static::classFromFile($file->getRealPath());

            if (! $class) {
                continue;
            }

            if (! enum_exists($class)) {
                continue;
            }

            if (! is_subclass_of($class, PruneableEvent::class)) {
                continue;
            }

            $enums[] = $class;
        }

        return $enums;
    }

    /**
     * Extract the fully qualified class name from a file.
     */
    protected static function classFromFile(string $path): ?string
    {
        $contents = File::get($path);

        // Extract namespace
        $namespace = null;
        if (preg_match('/namespace\s+([^;]+);/', $contents, $matches)) {
            $namespace = $matches[1];
        }

        // Extract enum name
        if (preg_match('/enum\s+(\w+)/', $contents, $matches)) {
            $enumName = $matches[1];

            return $namespace ? "{$namespace}\\{$enumName}" : $enumName;
        }

        return null;
    }
}
